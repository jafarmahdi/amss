<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-sliders"></i> <?= e(__('settings.ops_ready', 'Operations ready')) ?></div>
            <h2 class="mb-2"><?= e(__('settings.title', 'Settings')) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e(__('settings.desc', 'Application-level configuration placeholders for the next implementation stage.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('api.docs')) ?>" class="btn btn-outline-secondary"><i class="bi bi-code-square"></i> <?= e(__('settings.open_docs', 'Open API Docs')) ?></a>
            <a href="<?= e(route('system.check')) ?>" class="btn btn-primary"><i class="bi bi-shield-check"></i> <?= e(__('settings.run_check', 'Run System Check')) ?></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4 position-relative" style="z-index:1;">
        <a href="#general-settings" class="surface-chip"><i class="bi bi-sliders2"></i> <?= e(__('settings.general_section', 'General Settings')) ?></a>
        <a href="#auth-settings" class="surface-chip"><i class="bi bi-person-lock"></i> <?= e(__('settings.auth_access', 'Authentication')) ?></a>
        <a href="#security-settings" class="surface-chip"><i class="bi bi-shield-lock"></i> <?= e(__('settings.security_center', 'Security & Backup')) ?></a>
        <a href="#permission-settings" class="surface-chip"><i class="bi bi-key"></i> <?= e(__('settings.permission_editor', 'Permission editor')) ?></a>
    </div>
</div>

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
            <div class="ops-kpi-label"><?= e(__('settings.backup_center', 'Backup Center')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $settingsSummary['backups']) ?></div>
            <div class="ops-kpi-meta"><?= e(__('settings.available_backups', 'available backups')) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('settings.permissions', 'Permissions')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $settingsSummary['permissions']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $settingsSummary['roles']) ?> <?= e(__('settings.roles_managed', 'roles managed')) ?></div>
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
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card h-100">
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
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <div class="surface-chip"><i class="bi bi-info-circle"></i> <?= e(__('settings.language_note', 'Language and theme switches are applied live from the top bar.')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="general-settings" class="card mb-4">
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
            <div class="col-md-4">
                <label class="form-label"><?= e(__('settings.app_name', 'Application name')) ?></label>
                <input type="text" name="app_name" class="form-control" value="<?= e($settingsValues['app_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label"><?= e(__('settings.company_name', 'Company name')) ?></label>
                <input type="text" name="company_name" class="form-control" value="<?= e($settingsValues['company_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
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
            <div class="col-md-6 d-flex align-items-end">
                <div class="surface-chip"><i class="bi bi-layout-text-window-reverse"></i> <?= e(__('settings.general_live_note', 'These defaults affect new sessions and fresh logins.')) ?></div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= e(__('settings.save_general', 'Save General Settings')) ?></button>
            </div>
        </form>
    </div>
</div>

<div id="auth-settings" class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('settings.ldap_section', 'LDAP Login')) ?></h5>
                    <span class="surface-chip"><i class="bi bi-diagram-3"></i> <?= ($settingsValues['ldap_enabled'] ?? '0') === '1' ? e(__('settings.connected', 'Connected')) : e(__('settings.disabled', 'Disabled')) ?></span>
                </div>
            </div>
            <div class="card-body">
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
                    <h5><?= e(__('settings.sso_section', 'SSO Login')) ?></h5>
                    <span class="surface-chip"><i class="bi bi-globe2"></i> <?= ($settingsValues['sso_enabled'] ?? '0') === '1' ? e(__('settings.connected', 'Connected')) : e(__('settings.disabled', 'Disabled')) ?></span>
                </div>
            </div>
            <div class="card-body">
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

<div id="security-settings" class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('settings.ssl_section', 'SSL Certificate')) ?></h5>
                    <span class="small text-muted"><?= e(__('settings.security_center', 'Security & Backup')) ?></span>
                </div>
            </div>
            <div class="card-body">
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
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('settings.backup_center', 'Backup Center')) ?></h5>
                    <form method="POST" action="<?= e(route('settings.backups.create')) ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><?= e(__('settings.create_backup', 'Create Backup')) ?></button>
                    </form>
                </div>
            </div>
            <div class="card-body">
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

<div id="permission-settings" class="card">
    <div class="card-header">
        <div class="ops-panel-title">
            <div>
                <h5><?= e(__('settings.permission_editor', 'Permission editor')) ?></h5>
                <div class="small text-muted"><?= e(__('settings.permission_desc', 'Control which roles can export reports, manage settings, and open audit logs.')) ?></div>
            </div>
            <span class="surface-chip"><i class="bi bi-shield-lock"></i> <?= e((string) count($roles)) ?> <?= e(__('settings.roles_managed', 'roles managed')) ?></span>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= e(route('settings.permissions.save')) ?>">
            <div class="table-wrap">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th><?= e(__('common.role', 'Role')) ?></th>
                                <?php foreach ($permissions as $permissionKey => $label): ?>
                                    <th><?= e($label) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($role) ?></td>
                                    <?php foreach (array_keys($permissions) as $permissionKey): ?>
                                        <?php $checked = (bool) ($permissionMatrix[$role][$permissionKey] ?? ($role === 'admin')); ?>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[<?= e($role) ?>][<?= e($permissionKey) ?>]" id="<?= e($role . '_' . $permissionKey) ?>" <?= $checked ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><?= e(__('settings.save_permissions', 'Save permissions')) ?></button>
            </div>
        </form>
    </div>
</div>
