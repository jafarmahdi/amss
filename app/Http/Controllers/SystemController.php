<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\BackupService;
use App\Support\Database;
use App\Support\DataRepository;
use App\Support\RequestWorkflow;

class SystemController extends Controller
{
    public function settings(): void
    {
        if (!can('settings.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            header('Location: ' . route('dashboard'));
            exit;
        }

        $dbStatus = Database::status();
        $settings = DataRepository::systemSettings();
        $permissionDefinitions = DataRepository::permissionDefinitions();
        $permissionGroups = DataRepository::permissionGroups();
        $roleMatrix = DataRepository::rolePermissions();
        $customTranslationsEn = DataRepository::customTranslations('en');
        $customTranslationsAr = DataRepository::customTranslations('ar');
        $roles = ['admin', 'it_manager', 'technician', 'finance', 'viewer'];
        if ($roleMatrix === []) {
            $roleMatrix = DataRepository::defaultRolePermissions();
        }
        $backups = BackupService::listBackups();
        $workflowSettings = RequestWorkflow::configuration();
        $enabledAuthProviders = 0;
        if (($settings['ldap_enabled'] ?? '0') === '1') {
            $enabledAuthProviders++;
        }
        if (($settings['sso_enabled'] ?? '0') === '1') {
            $enabledAuthProviders++;
        }

        $this->render('settings.index', [
            'pageTitle' => __('settings.title', 'Settings'),
            'logoUrl' => base_url() . '/logo.png',
            'appSettings' => [
                'name' => $settings['app_name'] ?? __('app.name', 'Asset Management'),
                'locale' => current_locale(),
                'theme' => current_theme(),
                'direction' => is_rtl() ? 'RTL' : 'LTR',
                'database' => $dbStatus['database'],
                'connected' => $dbStatus['connected'],
                'tables' => count($dbStatus['tables']),
            ],
            'settingsSummary' => [
                'roles' => count($roles),
                'permissions' => count($permissionDefinitions),
                'permission_groups' => count($permissionGroups),
                'custom_permission_groups' => count(array_filter($permissionGroups, static fn (array $group): bool => !empty($group['custom']))),
                'backups' => count($backups),
                'enabled_auth' => $enabledAuthProviders,
                'workflow_steps' => 2 + (($workflowSettings['it_manager_required'] ?? true) ? 1 : 0) + ((($workflowSettings['finance_mode'] ?? 'always') !== 'disabled') ? 1 : 0),
                'translation_overrides' => count($customTranslationsEn) + count($customTranslationsAr),
            ],
            'activeTab' => $this->activeSettingsTab((string) ($_GET['tab'] ?? 'overview')),
            'settingsValues' => $settings,
            'workflowSettings' => $workflowSettings,
            'customTranslations' => [
                'en' => $customTranslationsEn,
                'ar' => $customTranslationsAr,
            ],
            'translationQuickKeys' => $this->translationQuickKeys(),
            'roles' => $roles,
            'roleLabels' => $this->roleLabels($roles),
            'permissions' => $permissionDefinitions,
            'permissionGroups' => $permissionGroups,
            'permissionMatrix' => $roleMatrix,
            'backups' => $backups,
            'setupStatus' => [
                'database' => $this->databaseSetupStatus($dbStatus),
                'ldap' => $this->ldapSetupStatus($settings),
                'sso' => $this->ssoSetupStatus($settings),
                'ssl' => $this->sslSetupStatus($settings),
                'backup' => $this->backupSetupStatus($backups, $settings),
            ],
        ]);
    }

    public function saveGeneral(): array
    {
        DataRepository::saveSystemSettings([
            'app_name' => trim((string) ($_POST['app_name'] ?? 'Alnahala AMS')),
            'company_name' => trim((string) ($_POST['company_name'] ?? 'Alnahala')),
            'support_email' => trim((string) ($_POST['support_email'] ?? 'it@alnahala.com')),
            'default_locale' => trim((string) ($_POST['default_locale'] ?? 'en')),
            'default_theme' => trim((string) ($_POST['default_theme'] ?? 'light')),
        ]);
        DataRepository::logAudit('update_settings', 'system_settings', null, null, ['section' => 'general']);
        flash('status', __('settings.saved', 'Settings updated successfully.'));
        return $this->settingsTabRedirect('general');
    }

    public function saveAuth(): array
    {
        $current = DataRepository::systemSettings();
        $payload = [];
        $section = trim((string) ($_POST['auth_section'] ?? ''));
        $keys = $section === 'sso'
            ? ['sso_enabled', 'sso_provider', 'sso_tenant_id', 'sso_client_id', 'sso_client_secret', 'sso_redirect_uri']
            : ['ldap_enabled', 'ldap_host', 'ldap_port', 'ldap_base_dn', 'ldap_bind_dn', 'ldap_bind_password', 'ldap_user_filter'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_POST)) {
                $payload[$key] = trim((string) $_POST[$key]);
            } elseif (in_array($key, ['ldap_enabled', 'sso_enabled'], true)) {
                $payload[$key] = '0';
            } else {
                $payload[$key] = (string) ($current[$key] ?? '');
            }
        }
        DataRepository::saveSystemSettings($payload);
        DataRepository::logAudit('update_settings', 'system_settings', null, null, ['section' => 'auth']);
        flash('status', __('settings.saved', 'Settings updated successfully.'));
        return $this->settingsTabRedirect('auth');
    }

    public function saveSecurity(): array
    {
        $current = DataRepository::systemSettings();
        $payload = [];
        $section = trim((string) ($_POST['security_section'] ?? ''));
        $keys = $section === 'backup'
            ? ['backup_retention_days', 'backup_include_uploads']
            : ['ssl_enabled', 'ssl_force_https', 'ssl_certificate_path', 'ssl_private_key_path', 'ssl_chain_path'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_POST)) {
                $payload[$key] = in_array($key, ['ssl_enabled', 'ssl_force_https', 'backup_include_uploads'], true) ? '1' : trim((string) $_POST[$key]);
            } elseif (in_array($key, ['ssl_enabled', 'ssl_force_https', 'backup_include_uploads'], true)) {
                $payload[$key] = '0';
            } else {
                $payload[$key] = (string) ($current[$key] ?? '');
            }
        }
        DataRepository::saveSystemSettings($payload);
        DataRepository::logAudit('update_settings', 'system_settings', null, null, ['section' => 'security_backup']);
        flash('status', __('settings.saved', 'Settings updated successfully.'));
        return $this->settingsTabRedirect('security');
    }

    public function saveWorkflow(): array
    {
        if (!can('settings.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->settingsTabRedirect('workflow');
        }

        $financeMode = trim((string) ($_POST['request_workflow_finance_mode'] ?? 'always'));
        $defaultScenario = trim((string) ($_POST['request_default_scenario'] ?? 'general'));
        $defaultUrgency = trim((string) ($_POST['request_default_urgency'] ?? 'normal'));
        $financeThreshold = trim((string) ($_POST['request_workflow_finance_threshold'] ?? '0'));
        $errors = [];

        if (!in_array($financeMode, ['always', 'threshold', 'disabled'], true)) {
            $errors['request_workflow_finance_mode'] = __('settings.workflow_finance_mode_invalid', 'Choose a valid finance review mode.');
        }

        if ($financeThreshold !== '' && !is_numeric($financeThreshold)) {
            $errors['request_workflow_finance_threshold'] = __('settings.workflow_finance_threshold_invalid', 'Enter a valid finance threshold amount.');
        }

        if (!in_array($defaultScenario, ['general', 'employee_onboarding', 'branch_deployment', 'replacement', 'stock_replenishment'], true)) {
            $errors['request_default_scenario'] = __('settings.workflow_default_scenario_invalid', 'Choose a valid default scenario.');
        }

        if (!in_array($defaultUrgency, ['low', 'normal', 'high', 'critical'], true)) {
            $errors['request_default_urgency'] = __('settings.workflow_default_urgency_invalid', 'Choose a valid default urgency.');
        }

        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->settingsTabRedirect('workflow');
        }

        DataRepository::saveSystemSettings([
            'request_workflow_it_manager_required' => isset($_POST['request_workflow_it_manager_required']) ? '1' : '0',
            'request_workflow_allow_storage_fulfillment' => isset($_POST['request_workflow_allow_storage_fulfillment']) ? '1' : '0',
            'request_workflow_finance_mode' => $financeMode,
            'request_workflow_finance_threshold' => number_format(max(0, (float) $financeThreshold), 2, '.', ''),
            'request_workflow_auto_close_on_receive' => isset($_POST['request_workflow_auto_close_on_receive']) ? '1' : '0',
            'request_default_scenario' => $defaultScenario,
            'request_default_urgency' => $defaultUrgency,
        ]);
        DataRepository::logAudit('update_settings', 'system_settings', null, null, ['section' => 'workflow']);
        flash('status', __('settings.workflow_saved', 'Workflow settings updated successfully.'));

        return $this->settingsTabRedirect('workflow');
    }

    public function saveTranslations(): array
    {
        if (!can('settings.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->settingsTabRedirect('translations');
        }

        $errors = [];
        $quickEn = is_array($_POST['quick_translation_en'] ?? null) ? $_POST['quick_translation_en'] : [];
        $quickAr = is_array($_POST['quick_translation_ar'] ?? null) ? $_POST['quick_translation_ar'] : [];
        $customEn = $this->mergeTranslationOverrides($quickEn, (string) ($_POST['custom_translation_lines_en'] ?? ''), 'custom_translation_lines_en', $errors);
        $customAr = $this->mergeTranslationOverrides($quickAr, (string) ($_POST['custom_translation_lines_ar'] ?? ''), 'custom_translation_lines_ar', $errors);

        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->settingsTabRedirect('translations');
        }

        DataRepository::saveSystemSettings([
            'custom_translations_en' => $customEn,
            'custom_translations_ar' => $customAr,
        ]);
        DataRepository::logAudit('update_settings', 'system_settings', null, null, [
            'section' => 'translations',
            'custom_translations_en' => $customEn,
            'custom_translations_ar' => $customAr,
        ]);
        flash('status', __('settings.translations_saved', 'Custom translations updated successfully.'));

        return $this->settingsTabRedirect('translations');
    }

    public function savePermissions(): array
    {
        if (!can('settings.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('settings');
        }

        $roles = ['admin', 'it_manager', 'technician', 'finance', 'viewer'];
        $permissions = array_keys(DataRepository::permissionDefinitions());
        $matrix = [];
        foreach ($roles as $role) {
            foreach ($permissions as $permission) {
                $matrix[$role][$permission] = $role === 'admin' || isset($_POST['permissions'][$role][$permission]);
            }
        }

        DataRepository::saveRolePermissions($matrix);
        DataRepository::logAudit('update_permissions', 'role_permissions', null, null, $matrix);
        flash('status', __('settings.permissions_saved', 'Permissions updated successfully.'));

        return $this->settingsTabRedirect('permissions');
    }

    public function savePermissionGroup(): array
    {
        if (!can('settings.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->settingsTabRedirect('permissions');
        }

        $label = trim((string) ($_POST['permission_group_label'] ?? ''));
        $description = trim((string) ($_POST['permission_group_description'] ?? ''));
        $requestedKey = trim((string) ($_POST['permission_group_key'] ?? ''));
        $groupKey = $this->slugify($requestedKey !== '' ? $requestedKey : $label);
        if ($groupKey === '' && $label !== '') {
            $groupKey = 'group_' . substr(md5($label), 0, 8);
        }
        $permissionLabels = $this->parsePermissionLabels((string) ($_POST['permission_labels'] ?? ''));
        $errors = [];

        if ($label === '' || mb_strlen($label) < 2) {
            $errors['permission_group_label'] = __('settings.permission_group_label_required', 'Enter a group name with at least 2 characters.');
        }

        if ($groupKey === '') {
            $errors['permission_group_key'] = __('settings.permission_group_key_invalid', 'Enter a valid group key using letters and numbers.');
        }

        if ($permissionLabels === []) {
            $errors['permission_labels'] = __('settings.permission_labels_required', 'Add at least one permission label for the new group.');
        }

        $existingGroups = DataRepository::permissionGroups();
        if ($groupKey !== '' && isset($existingGroups[$groupKey])) {
            $errors['permission_group_key'] = __('settings.permission_group_exists', 'This permission group already exists.');
        }

        $existingPermissions = DataRepository::permissionDefinitions();
        $permissions = [];
        foreach ($permissionLabels as $index => $permissionLabel) {
            $permissionSlug = $this->slugify($permissionLabel);
            if ($permissionSlug === '') {
                $permissionSlug = 'permission_' . ($index + 1);
            }

            $permissionKey = 'custom.' . $groupKey . '.' . $permissionSlug;
            if (isset($existingPermissions[$permissionKey])) {
                $errors['permission_labels'] = __('settings.permission_group_permissions_exist', 'One or more permission keys already exist. Adjust the labels and try again.');
                break;
            }

            $permissions[$permissionKey] = $permissionLabel;
        }

        if ($permissions === []) {
            $errors['permission_labels'] = __('settings.permission_labels_required', 'Add at least one permission label for the new group.');
        }

        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->settingsTabRedirect('permissions');
        }

        $customGroups = DataRepository::customPermissionGroups();
        $customGroups[$groupKey] = [
            'key' => $groupKey,
            'label' => $label,
            'description' => $description,
            'permissions' => $permissions,
            'custom' => true,
        ];

        DataRepository::saveSystemSettings([
            'custom_permission_groups' => array_values($customGroups),
        ]);
        DataRepository::logAudit('create_permission_group', 'system_settings', null, null, $customGroups[$groupKey]);
        flash('status', __('settings.permission_group_saved', 'Permission group added successfully.'));

        return $this->settingsTabRedirect('permissions');
    }

    public function createBackup(): array
    {
        try {
            $settings = DataRepository::systemSettings();
            $artifacts = BackupService::createDeploymentBackup(($settings['backup_include_uploads'] ?? '1') === '1');
            DataRepository::logAudit('backup', 'system', null, null, $artifacts);
            flash('status', __('settings.backup_created', 'Backup created successfully.'));
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        return $this->settingsTabRedirect('security');
    }

    public function check(): void
    {
        $dbStatus = Database::status();
        $sessionDirectory = base_path('storage/sessions');
        $uploadsDirectory = base_path('storage/uploads');
        $formsDirectory = base_path('storage/app/administrative-forms');
        $backupDirectory = base_path('storage/system-backups');

        $checks = [
            ['label' => __('system.php_version', 'PHP version'), 'status' => PHP_VERSION_ID >= 80000, 'detail' => PHP_VERSION],
            ['label' => __('system.pdo_mysql', 'PDO MySQL extension'), 'status' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? __('system.loaded', 'Loaded') : __('system.missing', 'Missing')],
            ['label' => __('system.router_file', 'Router file'), 'status' => is_file(base_path('routes/router.php')), 'detail' => 'routes/router.php'],
            ['label' => __('system.db_connection', 'Database connection'), 'status' => $dbStatus['connected'], 'detail' => $dbStatus['connected'] ? __('system.connected', 'Connected') . ' ' . $dbStatus['database'] : __('system.connection_failed', 'Connection failed')],
            ['label' => __('system.required_tables', 'Required tables'), 'status' => $dbStatus['connected'] && $dbStatus['missing_tables'] === [], 'detail' => $dbStatus['connected'] ? (string) count($dbStatus['tables']) . ' ' . __('system.tables_visible', 'tables visible') : __('system.no_tables', 'No tables inspected')],
            $this->pathCheck(__('system.storage_sessions', 'Session storage'), $sessionDirectory, true, true),
            $this->pathCheck(__('system.storage_uploads', 'Upload storage'), $uploadsDirectory, true, true),
            $this->pathCheck(__('system.forms_directory', 'Administrative forms storage'), $formsDirectory, true, true),
            $this->pathCheck(__('system.backup_directory', 'Backup storage'), $backupDirectory, false, true),
        ];

        $this->render('system/check', [
            'pageTitle' => __('system.title', 'System Check'),
            'checks' => $checks,
            'dbStatus' => $dbStatus,
        ]);
    }

    private function pathCheck(string $label, string $path, bool $requiresRead, bool $requiresWrite): array
    {
        $exists = is_dir($path);
        $readable = $exists && (!$requiresRead || is_readable($path));
        $writable = $exists && (!$requiresWrite || is_writable($path));
        $status = $exists && $readable && $writable;

        return [
            'label' => $label,
            'status' => $status,
            'detail' => $this->pathDetail($path, $exists, $readable, $writable, $requiresRead, $requiresWrite),
        ];
    }

    private function pathDetail(string $path, bool $exists, bool $readable, bool $writable, bool $requiresRead, bool $requiresWrite): string
    {
        $requirements = [];
        if ($requiresRead) {
            $requirements[] = __('system.readable', 'readable');
        }
        if ($requiresWrite) {
            $requirements[] = __('system.writable', 'writable');
        }

        $state = !$exists
            ? __('system.directory_missing', 'Directory missing')
            : ($readable && $writable
                ? __('system.directory_ready', 'Directory ready')
                : __('system.directory_not_ready', 'Directory permissions need attention'));

        $requirementLabel = $requirements === []
            ? ''
            : ' (' . implode(' + ', $requirements) . ')';

        return $path . ' - ' . $state . $requirementLabel;
    }

    private function activeSettingsTab(string $requestedTab): string
    {
        $allowed = ['overview', 'general', 'translations', 'auth', 'security', 'workflow', 'permissions'];
        return in_array($requestedTab, $allowed, true) ? $requestedTab : 'overview';
    }

    private function settingsTabRedirect(string $tab): array
    {
        return ['redirect' => route('settings') . '&tab=' . rawurlencode($this->activeSettingsTab($tab))];
    }

    private function roleLabels(array $roles): array
    {
        $labels = [
            'admin' => __('settings.role_admin', 'Administrator'),
            'it_manager' => __('settings.role_it_manager', 'IT Manager'),
            'technician' => __('settings.role_technician', 'Technician'),
            'finance' => __('settings.role_finance', 'Finance'),
            'viewer' => __('settings.role_viewer', 'Viewer'),
        ];

        foreach ($roles as $role) {
            $labels[$role] = $labels[$role] ?? ucwords(str_replace('_', ' ', $role));
        }

        return $labels;
    }

    private function translationQuickKeys(): array
    {
        return [
            'app.name' => __('settings.translation_app_name', 'Application name'),
            'nav.dashboard' => __('settings.translation_dashboard', 'Dashboard'),
            'nav.assets' => __('settings.translation_assets', 'Assets'),
            'nav.requests' => __('settings.translation_requests', 'Requests'),
            'nav.storage' => __('settings.translation_storage', 'Storage'),
            'nav.administrative_forms' => __('settings.translation_admin_forms', 'Administrative forms'),
            'nav.settings' => __('settings.translation_settings', 'Settings'),
            'auth.login' => __('settings.translation_login', 'Login'),
            'auth.logout' => __('settings.translation_logout', 'Logout'),
            'theme.light' => __('settings.translation_theme_light', 'Light'),
            'theme.dark' => __('settings.translation_theme_dark', 'Dark'),
        ];
    }

    private function mergeTranslationOverrides(array $quickTranslations, string $advancedLines, string $errorField, array &$errors): array
    {
        $merged = [];
        foreach ($quickTranslations as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);
            if ($key === '' || $value === '') {
                continue;
            }

            $merged[$key] = $value;
        }

        $parsedAdvanced = $this->parseTranslationOverrideLines($advancedLines, $errorField, $errors);
        if ($parsedAdvanced === null) {
            return $merged;
        }

        foreach ($parsedAdvanced as $key => $value) {
            $merged[$key] = $value;
        }

        ksort($merged);
        return $merged;
    }

    private function parseTranslationOverrideLines(string $lines, string $errorField, array &$errors): ?array
    {
        $result = [];
        $rows = preg_split('/\r\n|\r|\n/', $lines) ?: [];
        foreach ($rows as $index => $row) {
            $row = trim($row);
            if ($row === '' || str_starts_with($row, '#')) {
                continue;
            }

            if (!str_contains($row, '=')) {
                $errors[$errorField] = __('settings.translation_line_invalid', 'Each custom translation line must use the format key = value.');
                return null;
            }

            [$key, $value] = explode('=', $row, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '' || $value === '') {
                $errors[$errorField] = __('settings.translation_line_invalid', 'Each custom translation line must use the format key = value.');
                return null;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function databaseSetupStatus(array $dbStatus): array
    {
        if (!$dbStatus['connected']) {
            return [
                'title' => __('settings.database_state', 'Database state'),
                'label' => __('settings.not_connected', 'Not connected'),
                'tone' => 'danger',
                'reason' => __('settings.database_reason_disconnected', 'The application could not connect using the current database credentials.'),
                'meta' => $dbStatus['database'],
            ];
        }

        if ($dbStatus['missing_tables'] !== []) {
            $sample = array_slice($dbStatus['missing_tables'], 0, 3);
            $reason = __('settings.database_reason_missing_tables', 'Required tables are still missing.')
                . ' ' . implode(', ', $sample)
                . (count($dbStatus['missing_tables']) > 3 ? ' +' . (count($dbStatus['missing_tables']) - 3) : '');

            return [
                'title' => __('settings.database_state', 'Database state'),
                'label' => __('settings.needs_attention', 'Needs attention'),
                'tone' => 'warning',
                'reason' => $reason,
                'meta' => (string) count($dbStatus['tables']) . ' ' . __('settings.visible_tables', 'Visible tables'),
            ];
        }

        return [
            'title' => __('settings.database_state', 'Database state'),
            'label' => __('settings.connected', 'Connected'),
            'tone' => 'success',
            'reason' => __('settings.database_reason_ready', 'The database is connected and all required tables are available.'),
            'meta' => (string) count($dbStatus['tables']) . ' ' . __('settings.visible_tables', 'Visible tables'),
        ];
    }

    private function ldapSetupStatus(array $settings): array
    {
        if (($settings['ldap_enabled'] ?? '0') !== '1') {
            return [
                'title' => __('settings.ldap_section', 'LDAP Login'),
                'label' => __('settings.disabled', 'Disabled'),
                'tone' => 'secondary',
                'reason' => __('settings.provider_switch_off_reason', 'The provider toggle is currently turned off.'),
                'meta' => '',
            ];
        }

        $missing = [];
        foreach ([
            'ldap_host' => 'Host',
            'ldap_base_dn' => 'Base DN',
            'ldap_bind_dn' => 'Bind DN',
            'ldap_bind_password' => __('settings.bind_password', 'Bind Password'),
            'ldap_user_filter' => __('settings.user_filter', 'User Filter'),
        ] as $key => $label) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            return [
                'title' => __('settings.ldap_section', 'LDAP Login'),
                'label' => __('settings.needs_setup', 'Needs setup'),
                'tone' => 'warning',
                'reason' => __('settings.provider_missing_fields_reason', 'Missing required fields:')
                    . ' ' . implode(', ', $missing),
                'meta' => '',
            ];
        }

        return [
            'title' => __('settings.ldap_section', 'LDAP Login'),
            'label' => __('settings.ready', 'Ready'),
            'tone' => 'success',
            'reason' => __('settings.ldap_ready_reason', 'All required LDAP fields are filled in and ready for connection testing.'),
            'meta' => trim((string) ($settings['ldap_host'] ?? '')),
        ];
    }

    private function ssoSetupStatus(array $settings): array
    {
        if (($settings['sso_enabled'] ?? '0') !== '1') {
            return [
                'title' => __('settings.sso_section', 'SSO Login'),
                'label' => __('settings.disabled', 'Disabled'),
                'tone' => 'secondary',
                'reason' => __('settings.provider_switch_off_reason', 'The provider toggle is currently turned off.'),
                'meta' => '',
            ];
        }

        $missing = [];
        foreach ([
            'sso_provider' => __('settings.provider', 'Provider'),
            'sso_tenant_id' => __('settings.tenant_id', 'Tenant ID'),
            'sso_client_id' => __('settings.client_id', 'Client ID'),
            'sso_client_secret' => __('settings.client_secret', 'Client Secret'),
            'sso_redirect_uri' => __('settings.redirect_uri', 'Redirect URI'),
        ] as $key => $label) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            return [
                'title' => __('settings.sso_section', 'SSO Login'),
                'label' => __('settings.needs_setup', 'Needs setup'),
                'tone' => 'warning',
                'reason' => __('settings.provider_missing_fields_reason', 'Missing required fields:')
                    . ' ' . implode(', ', $missing),
                'meta' => '',
            ];
        }

        return [
            'title' => __('settings.sso_section', 'SSO Login'),
            'label' => __('settings.ready', 'Ready'),
            'tone' => 'success',
            'reason' => __('settings.sso_ready_reason', 'The provider, credentials, and redirect URI are all present.'),
            'meta' => ucfirst((string) ($settings['sso_provider'] ?? '')),
        ];
    }

    private function sslSetupStatus(array $settings): array
    {
        if (($settings['ssl_enabled'] ?? '0') !== '1') {
            return [
                'title' => __('settings.ssl_section', 'SSL Certificate'),
                'label' => __('settings.disabled', 'Disabled'),
                'tone' => 'secondary',
                'reason' => __('settings.provider_switch_off_reason', 'The provider toggle is currently turned off.'),
                'meta' => '',
            ];
        }

        $missing = [];
        $notFound = [];
        foreach ([
            'ssl_certificate_path' => __('settings.certificate_path', 'Certificate Path'),
            'ssl_private_key_path' => __('settings.private_key_path', 'Private Key Path'),
        ] as $key => $label) {
            $value = trim((string) ($settings[$key] ?? ''));
            if ($value === '') {
                $missing[] = $label;
                continue;
            }

            if (!$this->pathExists($value)) {
                $notFound[] = $label;
            }
        }

        if ($missing !== []) {
            return [
                'title' => __('settings.ssl_section', 'SSL Certificate'),
                'label' => __('settings.needs_setup', 'Needs setup'),
                'tone' => 'warning',
                'reason' => __('settings.provider_missing_fields_reason', 'Missing required fields:')
                    . ' ' . implode(', ', $missing),
                'meta' => '',
            ];
        }

        if ($notFound !== []) {
            return [
                'title' => __('settings.ssl_section', 'SSL Certificate'),
                'label' => __('settings.needs_attention', 'Needs attention'),
                'tone' => 'warning',
                'reason' => __('settings.provider_missing_files_reason', 'Configured files were not found:')
                    . ' ' . implode(', ', $notFound),
                'meta' => '',
            ];
        }

        return [
            'title' => __('settings.ssl_section', 'SSL Certificate'),
            'label' => __('settings.ready', 'Ready'),
            'tone' => 'success',
            'reason' => __('settings.ssl_ready_reason', 'Certificate and private key files are present.'),
            'meta' => ($settings['ssl_force_https'] ?? '0') === '1'
                ? __('settings.force_https', 'Force HTTPS')
                : '',
        ];
    }

    private function backupSetupStatus(array $backups, array $settings): array
    {
        if ($backups === []) {
            return [
                'title' => __('settings.backup_center', 'Backup Center'),
                'label' => __('settings.awaiting_backup', 'Awaiting first backup'),
                'tone' => 'warning',
                'reason' => __('settings.backup_reason_pending', 'No backup has been created yet. Run the backup task after finishing configuration.'),
                'meta' => __('settings.retention_days', 'Retention Days') . ': ' . (string) ($settings['backup_retention_days'] ?? '14'),
            ];
        }

        $latest = $backups[0];

        return [
            'title' => __('settings.backup_center', 'Backup Center'),
            'label' => __('settings.ready', 'Ready'),
            'tone' => 'success',
            'reason' => __('settings.backup_reason_ready', 'Backups are available and the retention policy is configured.'),
            'meta' => (string) ($latest['modified_at'] ?? ''),
        ];
    }

    private function pathExists(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (is_file($path)) {
            return true;
        }

        return is_file(base_path(ltrim($path, '/')));
    }

    private function parsePermissionLabels(string $input): array
    {
        $items = preg_split('/\r\n|\r|\n|,/', $input) ?: [];
        $labels = [];

        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '' || in_array($item, $labels, true)) {
                continue;
            }

            $labels[] = $item;
        }

        return $labels;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        return trim($value, '_');
    }
}
