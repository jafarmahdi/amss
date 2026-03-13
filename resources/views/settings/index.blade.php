<?php
$tabLinks = [
    'overview' => __('settings.tab_overview', 'Overview'),
    'general' => __('settings.tab_general', 'General'),
    'translations' => __('settings.tab_translations', 'Custom translations'),
    'auth' => __('settings.tab_auth', 'Authentication'),
    'security' => __('settings.tab_security', 'Security & backup'),
    'workflow' => __('settings.tab_workflow', 'Request workflow'),
    'permissions' => __('settings.tab_permissions', 'Permissions'),
];
$activeSettingsTab = isset($tabLinks[$activeTab]) ? $activeTab : 'overview';
$badgeClassMap = [
    'success' => 'text-bg-success',
    'warning' => 'text-bg-warning',
    'danger' => 'text-bg-danger',
    'secondary' => 'text-bg-secondary',
];
$alertClassMap = [
    'success' => 'alert-success',
    'warning' => 'alert-warning',
    'danger' => 'alert-danger',
    'secondary' => 'alert-secondary',
];
$tabDescriptions = [
    'overview' => __('settings.setup_summary_desc', 'Each area shows whether it is ready, disabled, or missing required information.'),
    'general' => __('settings.general_desc', 'Core application identity and default user experience.'),
    'translations' => __('settings.translations_desc', 'Override selected words and labels without editing language files.'),
    'auth' => __('settings.permission_matrix_desc', 'Permissions are now organized by group so each role is easier to review.'),
    'security' => __('settings.security_center', 'Security & Backup'),
    'workflow' => __('settings.workflow_desc', 'Control the approval path, stock fulfillment lane, and default request values from one place.'),
    'permissions' => __('settings.permission_matrix_desc', 'Permissions are now organized by group so each role is easier to review.'),
];
$rolePermissionCounts = [];
$rolePermissionRatios = [];
$permissionTotal = max(1, count($permissions));
foreach ($roles as $role) {
    $allowedCount = count(array_filter((array) ($permissionMatrix[$role] ?? []), static fn (bool $allowed): bool => $allowed));
    $rolePermissionCounts[$role] = $allowedCount;
    $rolePermissionRatios[$role] = (int) round(($allowedCount / $permissionTotal) * 100);
}
$translationQuickEn = (array) old('quick_translation_en', $customTranslations['en'] ?? []);
$translationQuickAr = (array) old('quick_translation_ar', $customTranslations['ar'] ?? []);
$advancedTranslationEn = [];
$advancedTranslationAr = [];
foreach ((array) ($customTranslations['en'] ?? []) as $translationKey => $translationValue) {
    if (!isset($translationQuickKeys[$translationKey])) {
        $advancedTranslationEn[$translationKey] = $translationValue;
    }
}
foreach ((array) ($customTranslations['ar'] ?? []) as $translationKey => $translationValue) {
    if (!isset($translationQuickKeys[$translationKey])) {
        $advancedTranslationAr[$translationKey] = $translationValue;
    }
}
$advancedTranslationLinesEn = (string) old('custom_translation_lines_en', implode(PHP_EOL, array_map(
    static fn (string $key, string $value): string => $key . ' = ' . $value,
    array_keys($advancedTranslationEn),
    array_values($advancedTranslationEn)
)));
$advancedTranslationLinesAr = (string) old('custom_translation_lines_ar', implode(PHP_EOL, array_map(
    static fn (string $key, string $value): string => $key . ' = ' . $value,
    array_keys($advancedTranslationAr),
    array_values($advancedTranslationAr)
)));
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-sliders"></i> <?= e(__('settings.ops_ready', 'Operations ready')) ?></div>
            <h2 class="mb-2"><?= e(__('settings.title', 'Settings')) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e(__('settings.setup_summary_desc', 'Each area shows whether it is ready, disabled, or missing required information.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('api.docs')) ?>" class="btn btn-outline-secondary"><i class="bi bi-code-square"></i> <?= e(__('settings.open_docs', 'Open API Docs')) ?></a>
            <a href="<?= e(route('system.check')) ?>" class="btn btn-primary"><i class="bi bi-shield-check"></i> <?= e(__('settings.run_check', 'Run System Check')) ?></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4 position-relative" style="z-index:1;">
        <span class="surface-chip"><i class="bi bi-building"></i> <?= e($settingsValues['company_name'] ?? '') ?></span>
        <span class="surface-chip"><i class="bi bi-translate"></i> <?= e(strtoupper((string) $appSettings['locale'])) ?></span>
        <span class="surface-chip"><i class="bi bi-circle-half"></i> <?= e(ucfirst((string) $appSettings['theme'])) ?></span>
        <span class="surface-chip"><i class="bi bi-key"></i> <?= e((string) $settingsSummary['permission_groups']) ?> <?= e(__('settings.permission_groups', 'Permission groups')) ?></span>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body pb-3">
        <ul class="nav nav-pills ops-tab-nav">
            <?php foreach ($tabLinks as $tabKey => $tabLabel): ?>
                <li class="nav-item">
                    <a class="nav-link<?= $activeSettingsTab === $tabKey ? ' active' : '' ?>" href="<?= e(route('settings') . '&tab=' . rawurlencode($tabKey)) ?>"><?= e($tabLabel) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
            <div class="small text-muted"><?= e($tabDescriptions[$activeSettingsTab] ?? '') ?></div>
            <div class="d-flex flex-wrap gap-2">
                <span class="surface-chip"><i class="bi bi-collection"></i> <?= e((string) count($roles)) ?> <?= e(__('settings.roles_managed', 'roles managed')) ?></span>
                <span class="surface-chip"><i class="bi bi-key"></i> <?= e((string) count($permissions)) ?> <?= e(__('settings.permissions', 'Permissions')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($activeSettingsTab === 'overview'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.database_state', 'Database state')) ?></div>
                <div class="ops-kpi-value"><?= e($appSettings['connected'] ? __('settings.connected', 'Connected') : __('settings.not_connected', 'Not connected')) ?></div>
                <div class="ops-kpi-meta"><?= e((string) $appSettings['tables']) ?> <?= e(__('settings.visible_tables', 'Visible tables')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.auth_access', 'Authentication')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['enabled_auth']) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.enabled_providers', 'enabled providers')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.permission_groups', 'Permission groups')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['permission_groups']) ?></div>
                <div class="ops-kpi-meta"><?= e((string) $settingsSummary['permissions']) ?> <?= e(__('settings.permissions', 'Permissions')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.backup_center', 'Backup Center')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['backups']) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.available_backups', 'available backups')) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2"><?= e(__('settings.logo_preview', 'Logo preview')) ?></div>
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <div style="width: 116px; height: 116px; border-radius: 28px; overflow: hidden; border: 1px solid var(--app-border); background: var(--app-surface-muted);">
                            <img src="<?= e($logoUrl) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div>
                            <h4 class="mb-2"><?= e(__('settings.brand_panel', 'Brand Identity')) ?></h4>
                            <p class="text-muted mb-0" style="max-width: 24rem;"><?= e(__('settings.brand_note', 'The current logo is loaded directly from the project root and used as the main brand mark.')) ?></p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <span class="surface-chip"><i class="bi bi-building"></i> <?= e($settingsValues['company_name'] ?? '') ?></span>
                        <span class="surface-chip"><i class="bi bi-translate"></i> <?= e(strtoupper((string) $appSettings['locale'])) ?></span>
                        <span class="surface-chip"><i class="bi bi-circle-half"></i> <?= e(ucfirst((string) $appSettings['theme'])) ?></span>
                        <span class="surface-chip"><i class="bi bi-input-cursor-text"></i> <?= e((string) ($settingsSummary['translation_overrides'] ?? 0)) ?> <?= e(__('settings.translation_overrides', 'translation overrides')) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.setup_summary', 'Setup summary')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.setup_summary_desc', 'Each area shows whether it is ready, disabled, or missing required information.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-activity"></i> <?= e(__('settings.quick_stats', 'Quick stats')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ops-subtle-list">
                        <?php foreach ($setupStatus as $status): ?>
                            <?php $badgeClass = $badgeClassMap[$status['tone']] ?? 'text-bg-secondary'; ?>
                            <div class="ops-subtle-item align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= e($status['title']) ?></div>
                                    <div class="small text-muted mt-1"><?= e($status['reason']) ?></div>
                                    <?php if (($status['meta'] ?? '') !== ''): ?>
                                        <div class="small mt-2"><?= e((string) $status['meta']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge rounded-pill <?= e($badgeClass) ?>"><?= e($status['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="ops-panel-title">
                <h5><?= e(__('settings.system_profile', 'System profile')) ?></h5>
                <span class="small text-muted"><?= e(__('settings.current_profile', 'Current profile')) ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.app_name', 'Application name')) ?></span><strong><?= e($appSettings['name']) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.interface_language', 'Interface language')) ?></span><strong><?= e(strtoupper((string) $appSettings['locale'])) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.theme_mode', 'Theme mode')) ?></span><strong><?= e(ucfirst((string) $appSettings['theme'])) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.text_direction', 'Text direction')) ?></span><strong><?= e((string) $appSettings['direction']) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.database_name', 'Database name')) ?></span><strong><?= e((string) $appSettings['database']) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="ops-subtle-item"><span><?= e(__('settings.support_email', 'Support email')) ?></span><strong><?= e((string) ($settingsValues['support_email'] ?? '')) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeSettingsTab === 'general'): ?>
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2"><?= e(__('settings.logo_preview', 'Logo preview')) ?></div>
                    <div style="width: 100%; max-width: 180px; height: 180px; border-radius: 28px; overflow: hidden; border: 1px solid var(--app-border); background: var(--app-surface-muted);">
                        <img src="<?= e($logoUrl) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <p class="text-muted mt-3 mb-0"><?= e(__('settings.brand_note', 'The current logo is loaded directly from the project root and used as the main brand mark.')) ?></p>
                    <div class="ops-subtle-list mt-4">
                        <div class="ops-subtle-item"><span><?= e(__('settings.company_name', 'Company name')) ?></span><strong><?= e((string) ($settingsValues['company_name'] ?? '')) ?></strong></div>
                        <div class="ops-subtle-item"><span><?= e(__('settings.default_locale', 'Default language')) ?></span><strong><?= e(strtoupper((string) ($settingsValues['default_locale'] ?? 'en'))) ?></strong></div>
                        <div class="ops-subtle-item"><span><?= e(__('settings.default_theme', 'Default theme')) ?></span><strong><?= e(ucfirst((string) ($settingsValues['default_theme'] ?? 'light'))) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.general_section', 'General Settings')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.general_desc', 'Core application identity and default user experience.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-gear"></i> <?= e(__('settings.branding', 'Branding')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route('settings.general.save')) ?>" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.app_name', 'Application name')) ?></label>
                            <input type="text" name="app_name" class="form-control" value="<?= e($settingsValues['app_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.company_name', 'Company name')) ?></label>
                            <input type="text" name="company_name" class="form-control" value="<?= e($settingsValues['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.support_email', 'Support email')) ?></label>
                            <input type="email" name="support_email" class="form-control" value="<?= e($settingsValues['support_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= e(__('settings.default_locale', 'Default language')) ?></label>
                            <select name="default_locale" class="form-select">
                                <option value="en" <?= ($settingsValues['default_locale'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="ar" <?= ($settingsValues['default_locale'] ?? 'en') === 'ar' ? 'selected' : '' ?>>العربية</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= e(__('settings.default_theme', 'Default theme')) ?></label>
                            <select name="default_theme" class="form-select">
                                <option value="light" <?= ($settingsValues['default_theme'] ?? 'light') === 'light' ? 'selected' : '' ?>><?= e(__('settings.theme_light', 'Light')) ?></option>
                                <option value="dark" <?= ($settingsValues['default_theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>><?= e(__('settings.theme_dark', 'Dark')) ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="surface-chip"><i class="bi bi-layout-text-window-reverse"></i> <?= e(__('settings.general_live_note', 'These defaults affect new sessions and fresh logins.')) ?></div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= e(__('settings.save_general', 'Save General Settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeSettingsTab === 'auth'): ?>
    <div class="row g-3 mb-4">
        <?php foreach (['ldap', 'sso'] as $providerKey): ?>
            <?php $providerStatus = $setupStatus[$providerKey]; ?>
            <div class="col-md-6">
                <div class="ops-kpi-card h-100">
                    <div class="ops-kpi-label"><?= e($providerStatus['title']) ?></div>
                    <div class="ops-kpi-value" style="font-size:1.45rem;"><?= e($providerStatus['label']) ?></div>
                    <div class="ops-kpi-meta"><?= e($providerStatus['reason']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.ldap_section', 'LDAP Login')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.auth_status', 'Activation status')) ?></div>
                        </div>
                        <span class="badge rounded-pill <?= e($badgeClassMap[$setupStatus['ldap']['tone']] ?? 'text-bg-secondary') ?>"><?= e($setupStatus['ldap']['label']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert <?= e($alertClassMap[$setupStatus['ldap']['tone']] ?? 'alert-secondary') ?> mb-4">
                        <div class="fw-semibold mb-1"><?= e($setupStatus['ldap']['title']) ?></div>
                        <div><?= e($setupStatus['ldap']['reason']) ?></div>
                    </div>
                    <form method="POST" action="<?= e(route('settings.auth.save')) ?>" class="row g-3">
                        <input type="hidden" name="auth_section" value="ldap">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ldap_enabled" id="ldap_enabled" <?= ($settingsValues['ldap_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ldap_enabled"><?= e(__('settings.enable_ldap', 'Enable LDAP authentication')) ?></label>
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Host</label><input type="text" name="ldap_host" class="form-control" value="<?= e($settingsValues['ldap_host'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Port</label><input type="text" name="ldap_port" class="form-control" value="<?= e($settingsValues['ldap_port'] ?? '389') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Base DN</label><input type="text" name="ldap_base_dn" class="form-control" value="<?= e($settingsValues['ldap_base_dn'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Bind DN</label><input type="text" name="ldap_bind_dn" class="form-control" value="<?= e($settingsValues['ldap_bind_dn'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label"><?= e(__('settings.bind_password', 'Bind Password')) ?></label><input type="password" name="ldap_bind_password" class="form-control" value="<?= e($settingsValues['ldap_bind_password'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label"><?= e(__('settings.user_filter', 'User Filter')) ?></label><input type="text" name="ldap_user_filter" class="form-control" value="<?= e($settingsValues['ldap_user_filter'] ?? '') ?>"></div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= e(__('settings.save_auth', 'Save Auth Settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.sso_section', 'SSO Login')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.auth_status', 'Activation status')) ?></div>
                        </div>
                        <span class="badge rounded-pill <?= e($badgeClassMap[$setupStatus['sso']['tone']] ?? 'text-bg-secondary') ?>"><?= e($setupStatus['sso']['label']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert <?= e($alertClassMap[$setupStatus['sso']['tone']] ?? 'alert-secondary') ?> mb-4">
                        <div class="fw-semibold mb-1"><?= e($setupStatus['sso']['title']) ?></div>
                        <div><?= e($setupStatus['sso']['reason']) ?></div>
                    </div>
                    <form method="POST" action="<?= e(route('settings.auth.save')) ?>" class="row g-3">
                        <input type="hidden" name="auth_section" value="sso">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="sso_enabled" id="sso_enabled" <?= ($settingsValues['sso_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sso_enabled"><?= e(__('settings.enable_sso', 'Enable SSO authentication')) ?></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('settings.provider', 'Provider')) ?></label>
                            <select name="sso_provider" class="form-select">
                                <option value="microsoft" <?= ($settingsValues['sso_provider'] ?? 'microsoft') === 'microsoft' ? 'selected' : '' ?>>Microsoft</option>
                                <option value="google" <?= ($settingsValues['sso_provider'] ?? 'microsoft') === 'google' ? 'selected' : '' ?>>Google</option>
                                <option value="okta" <?= ($settingsValues['sso_provider'] ?? 'microsoft') === 'okta' ? 'selected' : '' ?>>Okta</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label"><?= e(__('settings.tenant_id', 'Tenant ID')) ?></label><input type="text" name="sso_tenant_id" class="form-control" value="<?= e($settingsValues['sso_tenant_id'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label"><?= e(__('settings.client_id', 'Client ID')) ?></label><input type="text" name="sso_client_id" class="form-control" value="<?= e($settingsValues['sso_client_id'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label"><?= e(__('settings.client_secret', 'Client Secret')) ?></label><input type="password" name="sso_client_secret" class="form-control" value="<?= e($settingsValues['sso_client_secret'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label"><?= e(__('settings.redirect_uri', 'Redirect URI')) ?></label><input type="text" name="sso_redirect_uri" class="form-control" value="<?= e($settingsValues['sso_redirect_uri'] ?? '') ?>"></div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= e(__('settings.save_auth', 'Save Auth Settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeSettingsTab === 'security'): ?>
    <div class="row g-3 mb-4">
        <?php foreach (['database', 'ssl', 'backup'] as $securityCard): ?>
            <?php $securityStatus = $setupStatus[$securityCard]; ?>
            <div class="col-md-4">
                <div class="ops-kpi-card h-100">
                    <div class="ops-kpi-label"><?= e($securityStatus['title']) ?></div>
                    <div class="ops-kpi-value" style="font-size:1.45rem;"><?= e($securityStatus['label']) ?></div>
                    <div class="ops-kpi-meta"><?= e($securityStatus['reason']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <h5><?= e($setupStatus['database']['title']) ?></h5>
                        <span class="badge rounded-pill <?= e($badgeClassMap[$setupStatus['database']['tone']] ?? 'text-bg-secondary') ?>"><?= e($setupStatus['database']['label']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert <?= e($alertClassMap[$setupStatus['database']['tone']] ?? 'alert-secondary') ?> mb-0">
                        <div><?= e($setupStatus['database']['reason']) ?></div>
                        <?php if (($setupStatus['database']['meta'] ?? '') !== ''): ?>
                            <div class="small mt-2"><?= e((string) $setupStatus['database']['meta']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <h5><?= e($setupStatus['ssl']['title']) ?></h5>
                        <span class="badge rounded-pill <?= e($badgeClassMap[$setupStatus['ssl']['tone']] ?? 'text-bg-secondary') ?>"><?= e($setupStatus['ssl']['label']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert <?= e($alertClassMap[$setupStatus['ssl']['tone']] ?? 'alert-secondary') ?> mb-4">
                        <div><?= e($setupStatus['ssl']['reason']) ?></div>
                    </div>
                    <form method="POST" action="<?= e(route('settings.security.save')) ?>" class="row g-3">
                        <input type="hidden" name="security_section" value="ssl">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ssl_enabled" id="ssl_enabled" <?= ($settingsValues['ssl_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ssl_enabled"><?= e(__('settings.enable_ssl', 'Enable SSL certificate configuration')) ?></label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ssl_force_https" id="ssl_force_https" <?= ($settingsValues['ssl_force_https'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ssl_force_https"><?= e(__('settings.force_https', 'Force HTTPS')) ?></label>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label"><?= e(__('settings.certificate_path', 'Certificate Path')) ?></label><input type="text" name="ssl_certificate_path" class="form-control" value="<?= e($settingsValues['ssl_certificate_path'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label"><?= e(__('settings.private_key_path', 'Private Key Path')) ?></label><input type="text" name="ssl_private_key_path" class="form-control" value="<?= e($settingsValues['ssl_private_key_path'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label"><?= e(__('settings.chain_path', 'Chain Path')) ?></label><input type="text" name="ssl_chain_path" class="form-control" value="<?= e($settingsValues['ssl_chain_path'] ?? '') ?>"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary"><?= e(__('settings.save_security', 'Save Security Settings')) ?></button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e($setupStatus['backup']['title']) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.last_backup', 'Latest backup')) ?></div>
                        </div>
                        <form method="POST" action="<?= e(route('settings.backups.create')) ?>">
                            <button type="submit" class="btn btn-primary btn-sm"><?= e(__('settings.create_backup', 'Create Backup')) ?></button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert <?= e($alertClassMap[$setupStatus['backup']['tone']] ?? 'alert-secondary') ?> mb-4">
                        <div><?= e($setupStatus['backup']['reason']) ?></div>
                        <?php if (($setupStatus['backup']['meta'] ?? '') !== ''): ?>
                            <div class="small mt-2"><?= e((string) $setupStatus['backup']['meta']) ?></div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?= e(route('settings.security.save')) ?>" class="row g-3 mb-4">
                        <input type="hidden" name="security_section" value="backup">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.retention_days', 'Retention Days')) ?></label>
                            <input type="number" min="1" name="backup_retention_days" class="form-control" value="<?= e($settingsValues['backup_retention_days'] ?? '14') ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="backup_include_uploads" id="backup_include_uploads" <?= ($settingsValues['backup_include_uploads'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="backup_include_uploads"><?= e(__('settings.include_uploads', 'Include uploads in backup')) ?></label>
                            </div>
                        </div>
                        <div class="col-12"><button type="submit" class="btn btn-outline-secondary"><?= e(__('settings.save_backup_policy', 'Save Backup Policy')) ?></button></div>
                    </form>
                    <div class="table-wrap">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= e(__('common.name', 'Name')) ?></th>
                                        <th><?= e(__('common.size', 'Size')) ?></th>
                                        <th><?= e(__('common.date', 'Date')) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($backups !== []): ?>
                                        <?php foreach ($backups as $backup): ?>
                                            <tr>
                                                <td><a href="<?= e(base_url() . '/' . ltrim($backup['path'], '/')) ?>" target="_blank"><?= e($backup['name']) ?></a></td>
                                                <td><?= e(number_format($backup['size'] / 1024, 1)) ?> KB</td>
                                                <td><?= e($backup['modified_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-muted"><?= e(__('settings.no_backups', 'No backups created yet.')) ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeSettingsTab === 'translations'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.translation_overrides', 'Translation overrides')) ?></div>
                <div class="ops-kpi-value"><?= e((string) ($settingsSummary['translation_overrides'] ?? 0)) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.translation_overrides_desc', 'Custom words currently replacing the default language files.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.translation_common_labels', 'Quick labels')) ?></div>
                <div class="ops-kpi-value"><?= e((string) count($translationQuickKeys)) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.translation_common_labels_desc', 'Most-used UI words that admins usually rename first.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.translation_languages', 'Languages')) ?></div>
                <div class="ops-kpi-value">2</div>
                <div class="ops-kpi-meta"><?= e(__('settings.translation_languages_desc', 'Manage Arabic and English overrides side by side.')) ?></div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= e(route('settings.translations.save')) ?>">
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="ops-panel-title">
                            <div>
                                <h5><?= e(__('settings.translation_english', 'English overrides')) ?></h5>
                                <div class="small text-muted"><?= e(__('settings.translation_english_desc', 'Change how the selected labels appear when the interface is in English.')) ?></div>
                            </div>
                            <span class="surface-chip"><i class="bi bi-translate"></i> EN</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($translationQuickKeys as $translationKey => $translationLabel): ?>
                                <div class="col-12">
                                    <label class="form-label"><?= e($translationLabel) ?></label>
                                    <input type="text" name="quick_translation_en[<?= e($translationKey) ?>]" class="form-control" value="<?= e((string) ($translationQuickEn[$translationKey] ?? '')) ?>" placeholder="<?= e(__($translationKey, $translationKey)) ?>">
                                    <div class="form-text"><?= e($translationKey) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-12">
                                <label class="form-label"><?= e(__('settings.translation_advanced', 'Advanced overrides')) ?></label>
                                <textarea name="custom_translation_lines_en" class="form-control<?= has_error('custom_translation_lines_en') ? ' is-invalid' : '' ?>" rows="8" placeholder="requests.status.pending_it = Pending Review"><?= e($advancedTranslationLinesEn) ?></textarea>
                                <?php if (field_error('custom_translation_lines_en') !== null): ?>
                                    <div class="invalid-feedback d-block"><?= e((string) field_error('custom_translation_lines_en')) ?></div>
                                <?php endif; ?>
                                <div class="form-text"><?= e(__('settings.translation_advanced_help', 'Add one override per line using this format: key = value')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="ops-panel-title">
                            <div>
                                <h5><?= e(__('settings.translation_arabic', 'Arabic overrides')) ?></h5>
                                <div class="small text-muted"><?= e(__('settings.translation_arabic_desc', 'Change how the selected labels appear when the interface is in Arabic.')) ?></div>
                            </div>
                            <span class="surface-chip"><i class="bi bi-translate"></i> AR</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($translationQuickKeys as $translationKey => $translationLabel): ?>
                                <div class="col-12">
                                    <label class="form-label"><?= e($translationLabel) ?></label>
                                    <input type="text" name="quick_translation_ar[<?= e($translationKey) ?>]" class="form-control" value="<?= e((string) ($translationQuickAr[$translationKey] ?? '')) ?>" placeholder="<?= e((translations()['ar'][$translationKey] ?? $translationKey)) ?>">
                                    <div class="form-text"><?= e($translationKey) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-12">
                                <label class="form-label"><?= e(__('settings.translation_advanced', 'Advanced overrides')) ?></label>
                                <textarea name="custom_translation_lines_ar" class="form-control<?= has_error('custom_translation_lines_ar') ? ' is-invalid' : '' ?>" rows="8" placeholder="requests.status.pending_it = بانتظار المراجعة"><?= e($advancedTranslationLinesAr) ?></textarea>
                                <?php if (field_error('custom_translation_lines_ar') !== null): ?>
                                    <div class="invalid-feedback d-block"><?= e((string) field_error('custom_translation_lines_ar')) ?></div>
                                <?php endif; ?>
                                <div class="form-text"><?= e(__('settings.translation_advanced_help', 'Add one override per line using this format: key = value')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="fw-semibold"><?= e(__('settings.translation_apply_title', 'Custom language layer')) ?></div>
                    <div class="small text-muted"><?= e(__('settings.translation_apply_desc', 'Empty fields keep the original file translation. Saved values override the built-in dictionaries immediately after refresh.')) ?></div>
                </div>
                <button type="submit" class="btn btn-primary"><?= e(__('settings.save_translations', 'Save custom translations')) ?></button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php if ($activeSettingsTab === 'workflow'): ?>
    <?php
    $financeMode = (string) ($workflowSettings['finance_mode'] ?? 'always');
    $workflowStageBadge = static function (string $tone) use ($badgeClassMap): string {
        return $badgeClassMap[$tone] ?? 'text-bg-secondary';
    };
    $workflowStages = [
        [
            'title' => __('settings.workflow_stage_submit', 'Requester submission'),
            'description' => __('settings.workflow_stage_submit_desc', 'Users save drafts, then submit them into the review queue.'),
            'label' => __('settings.ready', 'Ready'),
            'tone' => 'success',
        ],
        [
            'title' => __('settings.workflow_stage_it', 'IT review'),
            'description' => __('settings.workflow_stage_it_desc', 'Technical review is always the first operational checkpoint.'),
            'label' => __('settings.workflow_required', 'Required'),
            'tone' => 'success',
        ],
        [
            'title' => __('settings.workflow_stage_it_manager', 'IT manager review'),
            'description' => ($workflowSettings['it_manager_required'] ?? true)
                ? __('settings.workflow_stage_it_manager_on', 'Manager approval is active before procurement or stock fulfillment.')
                : __('settings.workflow_stage_it_manager_off', 'Manager approval is skipped and the flow moves directly after IT review.'),
            'label' => ($workflowSettings['it_manager_required'] ?? true) ? __('settings.workflow_required', 'Required') : __('settings.workflow_skipped', 'Skipped'),
            'tone' => ($workflowSettings['it_manager_required'] ?? true) ? 'success' : 'secondary',
        ],
        [
            'title' => __('settings.workflow_stage_finance', 'Finance review'),
            'description' => $financeMode === 'always'
                ? __('settings.workflow_stage_finance_always', 'Every request passes finance review before approval is completed.')
                : ($financeMode === 'threshold'
                    ? __('settings.workflow_stage_finance_threshold_desc', 'Finance review starts only when the estimated cost reaches the configured threshold.')
                    : __('settings.workflow_stage_finance_disabled', 'Formal finance approval is skipped, but finance still receives approved requests for execution.')),
            'label' => $financeMode === 'always'
                ? __('settings.workflow_required', 'Required')
                : ($financeMode === 'threshold'
                    ? __('settings.workflow_threshold_label', 'Threshold based')
                    : __('settings.workflow_skipped', 'Skipped')),
            'tone' => $financeMode === 'disabled' ? 'secondary' : 'warning',
        ],
        [
            'title' => __('settings.workflow_stage_storage', 'Storage fulfillment lane'),
            'description' => ($workflowSettings['allow_storage_fulfillment'] ?? true)
                ? sprintf(
                    __('settings.workflow_stage_storage_on', 'Stock-ready requests can be closed directly from storage during the %s step.'),
                    e(__(
                        ($workflowSettings['storage_fulfillment_role'] ?? 'it_manager') === 'it_manager'
                            ? 'settings.role_it_manager'
                            : 'settings.role_technician',
                        ($workflowSettings['storage_fulfillment_role'] ?? 'it_manager') === 'it_manager' ? 'IT Manager' : 'Technician'
                    ))
                )
                : __('settings.workflow_stage_storage_off', 'All requests continue through the purchase/receive path even if stock is available.'),
            'label' => ($workflowSettings['allow_storage_fulfillment'] ?? true) ? __('settings.enabled', 'Enabled') : __('settings.disabled', 'Disabled'),
            'tone' => ($workflowSettings['allow_storage_fulfillment'] ?? true) ? 'success' : 'secondary',
        ],
        [
            'title' => __('settings.workflow_stage_close', 'Closing rule'),
            'description' => ($workflowSettings['auto_close_on_receive'] ?? false)
                ? __('settings.workflow_stage_close_auto', 'Requests close automatically after the receive step when all required assets are linked.')
                : __('settings.workflow_stage_close_manual', 'Finance reviews the received request and closes it manually after final verification.'),
            'label' => ($workflowSettings['auto_close_on_receive'] ?? false) ? __('settings.workflow_auto_close_label', 'Auto close') : __('settings.workflow_manual_close_label', 'Manual close'),
            'tone' => ($workflowSettings['auto_close_on_receive'] ?? false) ? 'warning' : 'secondary',
        ],
    ];
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.workflow_approval_steps', 'Approval steps')) ?></div>
                <div class="ops-kpi-value"><?= e((string) ($settingsSummary['workflow_steps'] ?? 0)) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.workflow_approval_steps_desc', 'Active checkpoints before procurement execution.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.workflow_default_scenario', 'Default scenario')) ?></div>
                <div class="ops-kpi-value" style="font-size:1.4rem;"><?= e(\App\Support\RequestWorkflow::scenarioLabels()[$workflowSettings['default_scenario'] ?? 'general'] ?? ($workflowSettings['default_scenario'] ?? 'general')) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.workflow_request_defaults', 'Defaults shown on new requests.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.workflow_default_urgency', 'Default urgency')) ?></div>
                <div class="ops-kpi-value" style="font-size:1.4rem;"><?= e(\App\Support\RequestWorkflow::urgencyLabel((string) ($workflowSettings['default_urgency'] ?? 'normal'))) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.workflow_request_defaults', 'Defaults shown on new requests.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.workflow_finance_mode', 'Finance review')) ?></div>
                <div class="ops-kpi-value" style="font-size:1.4rem;">
                    <?= e($financeMode === 'always'
                        ? __('settings.workflow_finance_mode_always', 'Always')
                        : ($financeMode === 'threshold'
                            ? __('settings.workflow_finance_mode_threshold', 'Threshold')
                            : __('settings.workflow_finance_mode_disabled', 'Disabled'))) ?>
                </div>
                <div class="ops-kpi-meta">
                    <?= e($financeMode === 'threshold'
                        ? __('settings.workflow_threshold_meta', 'Threshold') . ': ' . number_format((float) ($workflowSettings['finance_threshold'] ?? 0), 2)
                        : __('settings.workflow_finance_mode_desc', 'Choose when finance approval becomes mandatory.')) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.workflow_map', 'Workflow map')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.workflow_map_desc', 'A clean view of the current request route from draft to close.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-diagram-3"></i> <?= e(__('settings.workflow_live_route', 'Live route')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <?php foreach ($workflowStages as $stage): ?>
                            <div class="ops-subtle-item align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= e($stage['title']) ?></div>
                                    <div class="small text-muted mt-1"><?= e($stage['description']) ?></div>
                                </div>
                                <span class="badge rounded-pill <?= e($workflowStageBadge($stage['tone'])) ?>"><?= e($stage['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.workflow_settings', 'Workflow settings')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.workflow_settings_desc', 'Adjust approvals, stock fulfillment behavior, and new-request defaults without touching code.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-sliders2"></i> <?= e(__('settings.workflow_precision_badge', 'Precise control')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route('settings.workflow.save')) ?>" class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100" style="border:1px solid var(--app-border); background: var(--app-surface-muted);">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="request_workflow_it_manager_required" id="request_workflow_it_manager_required" <?= old('request_workflow_it_manager_required', ($workflowSettings['it_manager_required'] ?? true) ? '1' : '') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="request_workflow_it_manager_required"><?= e(__('settings.workflow_it_manager_required', 'Require IT manager approval')) ?></label>
                                    </div>
                                    <div class="small text-muted"><?= e(__('settings.workflow_it_manager_required_help', 'When this is off, the flow moves straight from IT review to the next configured stage.')) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100" style="border:1px solid var(--app-border); background: var(--app-surface-muted);">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="request_workflow_allow_storage_fulfillment" id="request_workflow_allow_storage_fulfillment" <?= old('request_workflow_allow_storage_fulfillment', ($workflowSettings['allow_storage_fulfillment'] ?? true) ? '1' : '') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="request_workflow_allow_storage_fulfillment"><?= e(__('settings.workflow_allow_storage_fulfillment', 'Allow direct storage fulfillment')) ?></label>
                                    </div>
                                    <div class="small text-muted"><?= e(__('settings.workflow_allow_storage_fulfillment_help', 'Stock-ready requests can close from storage during the active operational approval stage.')) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label"><?= e(__('settings.workflow_finance_mode', 'Finance review')) ?></label>
                            <select name="request_workflow_finance_mode" class="form-select<?= has_error('request_workflow_finance_mode') ? ' is-invalid' : '' ?>">
                                <?php $selectedFinanceMode = (string) old('request_workflow_finance_mode', $financeMode); ?>
                                <option value="always" <?= $selectedFinanceMode === 'always' ? 'selected' : '' ?>><?= e(__('settings.workflow_finance_mode_always', 'Always')) ?></option>
                                <option value="threshold" <?= $selectedFinanceMode === 'threshold' ? 'selected' : '' ?>><?= e(__('settings.workflow_finance_mode_threshold', 'Only above threshold')) ?></option>
                                <option value="disabled" <?= $selectedFinanceMode === 'disabled' ? 'selected' : '' ?>><?= e(__('settings.workflow_finance_mode_disabled', 'Skip formal approval')) ?></option>
                            </select>
                            <?php if (field_error('request_workflow_finance_mode') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('request_workflow_finance_mode')) ?></div>
                            <?php endif; ?>
                            <div class="form-text"><?= e(__('settings.workflow_finance_mode_desc', 'Choose when finance approval becomes mandatory.')) ?></div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label"><?= e(__('settings.workflow_finance_threshold', 'Finance threshold amount')) ?></label>
                            <input type="number" min="0" step="0.01" name="request_workflow_finance_threshold" class="form-control<?= has_error('request_workflow_finance_threshold') ? ' is-invalid' : '' ?>" value="<?= e((string) old('request_workflow_finance_threshold', number_format((float) ($workflowSettings['finance_threshold'] ?? 0), 2, '.', ''))) ?>">
                            <?php if (field_error('request_workflow_finance_threshold') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('request_workflow_finance_threshold')) ?></div>
                            <?php endif; ?>
                            <div class="form-text"><?= e(__('settings.workflow_finance_threshold_help', 'Used only when finance review is set to threshold mode.')) ?></div>
                        </div>

                        <div class="col-12">
                            <div class="card" style="border:1px solid var(--app-border); background: var(--app-surface-muted);">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="request_workflow_auto_close_on_receive" id="request_workflow_auto_close_on_receive" <?= old('request_workflow_auto_close_on_receive', ($workflowSettings['auto_close_on_receive'] ?? false) ? '1' : '') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="request_workflow_auto_close_on_receive"><?= e(__('settings.workflow_auto_close_on_receive', 'Auto-close after receive')) ?></label>
                                    </div>
                                    <div class="small text-muted"><?= e(__('settings.workflow_auto_close_on_receive_help', 'After stock or purchased items are received and linked correctly, the request can close automatically.')) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.workflow_default_scenario', 'Default scenario')) ?></label>
                            <select name="request_default_scenario" class="form-select<?= has_error('request_default_scenario') ? ' is-invalid' : '' ?>">
                                <?php $selectedScenario = (string) old('request_default_scenario', $workflowSettings['default_scenario'] ?? 'general'); ?>
                                <?php foreach (\App\Support\RequestWorkflow::scenarioLabels() as $scenarioKey => $scenarioLabel): ?>
                                    <option value="<?= e($scenarioKey) ?>" <?= $selectedScenario === $scenarioKey ? 'selected' : '' ?>><?= e($scenarioLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (field_error('request_default_scenario') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('request_default_scenario')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('settings.workflow_default_urgency', 'Default urgency')) ?></label>
                            <select name="request_default_urgency" class="form-select<?= has_error('request_default_urgency') ? ' is-invalid' : '' ?>">
                                <?php $selectedUrgency = (string) old('request_default_urgency', $workflowSettings['default_urgency'] ?? 'normal'); ?>
                                <?php foreach (\App\Support\RequestWorkflow::urgencyLabels() as $urgencyKey => $urgencyLabel): ?>
                                    <option value="<?= e($urgencyKey) ?>" <?= $selectedUrgency === $urgencyKey ? 'selected' : '' ?>><?= e($urgencyLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (field_error('request_default_urgency') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('request_default_urgency')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="fw-semibold mb-1"><?= e(__('settings.workflow_route_hint', 'How the route adapts')) ?></div>
                                <div><?= e(__('settings.workflow_route_hint_desc', 'If manager approval is turned off, direct storage fulfillment automatically moves to the IT review stage. If finance approval is skipped, approved requests still stay with finance for purchase, receive, and close actions.')) ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= e(__('settings.workflow_save', 'Save workflow settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeSettingsTab === 'permissions'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.permissions', 'Permissions')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['permissions']) ?></div>
                <div class="ops-kpi-meta"><?= e((string) $settingsSummary['roles']) ?> <?= e(__('settings.roles_managed', 'roles managed')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.permission_groups', 'Permission groups')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['permission_groups']) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.permission_matrix_desc', 'Permissions are now organized by group so each role is easier to review.')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.custom_permission_groups', 'Custom groups')) ?></div>
                <div class="ops-kpi-value"><?= e((string) $settingsSummary['custom_permission_groups']) ?></div>
                <div class="ops-kpi-meta"><?= e(__('settings.permission_group_builder', 'Add permission group')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('settings.role_admin', 'Administrator')) ?></div>
                <div class="ops-kpi-value">100%</div>
                <div class="ops-kpi-meta"><?= e(__('settings.permission_locked_admin', 'Administrator access is always enabled and cannot be turned off here.')) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.permission_group_builder', 'Add permission group')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.permission_group_builder_desc', 'Create a new group and define its custom permission labels in one step.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-folder-plus"></i> <?= e(__('settings.permission_group_custom_badge', 'Custom group')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route('settings.permission-groups.save')) ?>" class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= e(__('settings.permission_group_label', 'Group name')) ?></label>
                            <input type="text" name="permission_group_label" class="form-control<?= has_error('permission_group_label') ? ' is-invalid' : '' ?>" value="<?= e((string) old('permission_group_label')) ?>">
                            <?php if (field_error('permission_group_label') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('permission_group_label')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('settings.permission_group_key', 'Group key')) ?></label>
                            <input type="text" name="permission_group_key" class="form-control<?= has_error('permission_group_key') ? ' is-invalid' : '' ?>" value="<?= e((string) old('permission_group_key')) ?>" placeholder="procurement">
                            <?php if (field_error('permission_group_key') !== null): ?>
                                <div class="invalid-feedback"><?= e((string) field_error('permission_group_key')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('settings.permission_group_description', 'Description')) ?></label>
                            <textarea name="permission_group_description" class="form-control" rows="3"><?= e((string) old('permission_group_description')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('settings.permission_group_permissions', 'Permission labels')) ?></label>
                            <textarea name="permission_labels" class="form-control<?= has_error('permission_labels') ? ' is-invalid' : '' ?>" rows="5" placeholder="View vendors&#10;Manage vendors"><?= e((string) old('permission_labels')) ?></textarea>
                            <div class="form-text"><?= e(__('settings.permission_group_permissions_help', 'Write one permission label per line, for example: View vendors')) ?></div>
                            <?php if (field_error('permission_labels') !== null): ?>
                                <div class="invalid-feedback d-block"><?= e((string) field_error('permission_labels')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= e(__('settings.permission_group_create', 'Create group')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <div class="ops-panel-title">
                        <div>
                            <h5><?= e(__('settings.permission_editor', 'Permission editor')) ?></h5>
                            <div class="small text-muted"><?= e(__('settings.permission_matrix_desc', 'Permissions are now organized by group so each role is easier to review.')) ?></div>
                        </div>
                        <span class="surface-chip"><i class="bi bi-shield-lock"></i> <?= e((string) count($roles)) ?> <?= e(__('settings.roles_managed', 'roles managed')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4"><?= e(__('settings.permission_locked_admin', 'Administrator access is always enabled and cannot be turned off here.')) ?></div>
                    <div class="row g-3">
                        <?php foreach ($roles as $role): ?>
                            <div class="col-md-6">
                                <div class="card h-100" style="border:1px solid var(--app-border); background: var(--app-surface-muted);">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                            <div class="fw-semibold"><?= e($roleLabels[$role] ?? $role) ?></div>
                                            <span class="surface-chip" style="padding:6px 10px;"><?= e((string) $rolePermissionCounts[$role]) ?>/<?= e((string) count($permissions)) ?></span>
                                        </div>
                                        <div class="progress" style="height:10px; border-radius:999px; background: rgba(148, 163, 184, 0.16);">
                                            <div class="progress-bar" role="progressbar" style="width: <?= e((string) $rolePermissionRatios[$role]) ?>%; background: linear-gradient(135deg, var(--app-primary), var(--app-primary-strong));" aria-valuenow="<?= e((string) $rolePermissionRatios[$role]) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small text-muted mt-2"><?= e((string) $rolePermissionRatios[$role]) ?>% <?= e(__('settings.permissions', 'Permissions')) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= e(route('settings.permissions.save')) ?>">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <label class="form-label fw-semibold" for="permission-role-select"><?= e(__('settings.role_switcher', 'Role switcher')) ?></label>
                        <select class="form-select" id="permission-role-select">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e($role) ?>"><?= e($roleLabels[$role] ?? $role) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= e(__('settings.role_switcher_desc', 'Choose a role tab to edit only its permissions without crowding the page.')) ?></div>
                    </div>
                    <div class="col-lg-7">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <?php foreach ($roles as $index => $role): ?>
                                <span class="surface-chip js-role-chip<?= $index === 0 ? '' : ' d-none' ?>" data-role-summary="<?= e($role) ?>"><i class="bi bi-person-badge"></i> <?= e($roleLabels[$role] ?? $role) ?>: <?= e((string) $rolePermissionCounts[$role]) ?>/<?= e((string) count($permissions)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ($roles as $index => $role): ?>
            <div class="js-role-pane<?= $index === 0 ? '' : ' d-none' ?>" data-role-pane="<?= e($role) ?>">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ops-kpi-card h-100">
                            <div class="ops-kpi-label"><?= e($roleLabels[$role] ?? $role) ?></div>
                            <div class="ops-kpi-value"><?= e((string) $rolePermissionCounts[$role]) ?></div>
                            <div class="ops-kpi-meta"><?= e(__('settings.permissions', 'Permissions')) ?> / <?= e((string) count($permissions)) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ops-kpi-card h-100">
                            <div class="ops-kpi-label"><?= e(__('settings.coverage', 'Coverage')) ?></div>
                            <div class="ops-kpi-value"><?= e((string) $rolePermissionRatios[$role]) ?>%</div>
                            <div class="ops-kpi-meta"><?= e(__('settings.role_coverage_desc', 'This ratio shows how many permissions are enabled for the selected role.')) ?></div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-3 mb-4">
                    <?php foreach ($permissionGroups as $groupIndex => $group): ?>
                        <?php
                        $groupPermissions = (array) ($group['permissions'] ?? []);
                        $groupPermissionCount = count($groupPermissions);
                        $enabledWithinGroup = 0;
                        foreach (array_keys($groupPermissions) as $permissionKey) {
                            if ((bool) ($permissionMatrix[$role][$permissionKey] ?? ($role === 'admin'))) {
                                $enabledWithinGroup++;
                            }
                        }
                        ?>
                        <details class="card"<?= $groupIndex === 0 ? ' open' : '' ?>>
                            <summary class="card-header" style="cursor:pointer; list-style:none;">
                                <div class="ops-panel-title mb-0">
                                    <div>
                                        <h5><?= e((string) ($group['label'] ?? '')) ?></h5>
                                        <div class="small text-muted"><?= e((string) ($group['description'] ?? '')) ?></div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="surface-chip"><i class="bi bi-list-check"></i> <?= e((string) $enabledWithinGroup) ?>/<?= e((string) $groupPermissionCount) ?></span>
                                        <span class="surface-chip"><?= e(!empty($group['custom']) ? __('settings.permission_group_custom_badge', 'Custom group') : __('settings.permission_group_builtin_badge', 'Built-in group')) ?></span>
                                    </div>
                                </div>
                            </summary>
                            <div class="card-body">
                                <?php if ($groupPermissions === []): ?>
                                    <div class="alert alert-secondary mb-0"><?= e(__('settings.no_permissions_in_group', 'This group does not have permissions yet.')) ?></div>
                                <?php else: ?>
                                    <div class="d-grid gap-2">
                                        <?php foreach ($groupPermissions as $permissionKey => $label): ?>
                                            <?php $checked = (bool) ($permissionMatrix[$role][$permissionKey] ?? ($role === 'admin')); ?>
                                            <label class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3" style="background: rgba(148, 163, 184, 0.08); border: 1px solid var(--app-border);">
                                                <span>
                                                    <span class="d-block fw-semibold"><?= e((string) $label) ?></span>
                                                    <span class="small text-muted"><?= e((string) $permissionKey) ?></span>
                                                </span>
                                                <span class="pt-1">
                                                    <?php if ($role === 'admin'): ?>
                                                        <input type="hidden" name="permissions[admin][<?= e((string) $permissionKey) ?>]" value="1">
                                                        <input class="form-check-input" type="checkbox" checked disabled>
                                                    <?php else: ?>
                                                        <input class="form-check-input" type="checkbox" name="permissions[<?= e($role) ?>][<?= e((string) $permissionKey) ?>]" id="<?= e($role . '_' . $permissionKey) ?>" <?= $checked ? 'checked' : '' ?>>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card mt-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="fw-semibold"><?= e(__('settings.permission_editor', 'Permission editor')) ?></div>
                    <div class="small text-muted"><?= e((string) count($permissionGroups)) ?> <?= e(__('settings.permission_groups', 'Permission groups')) ?> / <?= e((string) count($permissions)) ?> <?= e(__('settings.permissions', 'Permissions')) ?></div>
                </div>
                <button type="submit" class="btn btn-primary"><?= e(__('settings.save_permissions', 'Save permissions')) ?></button>
            </div>
        </div>
    </form>

    <script>
    (function () {
        const roleSelect = document.getElementById('permission-role-select');
        if (!roleSelect) {
            return;
        }

        const panes = Array.from(document.querySelectorAll('[data-role-pane]'));
        const chips = Array.from(document.querySelectorAll('[data-role-summary]'));

        const syncRole = function () {
            const role = roleSelect.value;
            panes.forEach(function (pane) {
                pane.classList.toggle('d-none', pane.getAttribute('data-role-pane') !== role);
            });
            chips.forEach(function (chip) {
                chip.classList.toggle('d-none', chip.getAttribute('data-role-summary') !== role);
            });
        };

        roleSelect.addEventListener('change', syncRole);
        syncRole();
    }());
    </script>
<?php endif; ?>
