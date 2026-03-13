<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\BackupService;
use App\Support\Database;
use App\Support\DataRepository;

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
        $roleMatrix = DataRepository::rolePermissions();
        $roles = ['admin', 'it_manager', 'technician', 'finance', 'viewer'];
        $backups = BackupService::listBackups();
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
                'backups' => count($backups),
                'enabled_auth' => $enabledAuthProviders,
            ],
            'settingsValues' => $settings,
            'roles' => $roles,
            'permissions' => $permissionDefinitions,
            'permissionMatrix' => $roleMatrix,
            'backups' => $backups,
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
        return $this->redirect('settings');
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
        return $this->redirect('settings');
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
        return $this->redirect('settings');
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
                $matrix[$role][$permission] = isset($_POST['permissions'][$role][$permission]);
            }
        }

        DataRepository::saveRolePermissions($matrix);
        DataRepository::logAudit('update_permissions', 'role_permissions', null, null, $matrix);
        flash('status', __('settings.permissions_saved', 'Permissions updated successfully.'));

        return $this->redirect('settings');
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
        return $this->redirect('settings');
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
}
