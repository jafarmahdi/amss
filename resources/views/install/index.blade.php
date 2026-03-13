<?php
$dbStatus = $installerState['database'] ?? ['connected' => false, 'database' => '', 'missing_tables' => []];
$requirements = $installerState['requirements'] ?? [];
$tabItems = [
    'intro' => __('install.tab_intro', 'System'),
    'terms' => __('install.tab_terms', 'Terms'),
    'language' => __('install.tab_language', 'Language'),
    'database' => __('install.tab_database', 'Database'),
    'admin' => __('install.tab_admin', 'Administrator'),
    'branding' => __('install.tab_branding', 'Branding'),
];
?>

<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="mb-2"><?= e(__('install.title', 'Initial Setup')) ?></h2>
            <p class="text-muted mb-0"><?= e(__('install.desc', 'Prepare the system for a new server with guided setup for language, database, branding, and the first administrator account.')) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'en']) . '&redirect=' . rawurlencode(current_request_uri())) ?>">English</a>
            <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'ar']) . '&redirect=' . rawurlencode(current_request_uri())) ?>">العربية</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('install.database_state', 'Database state')) ?></div>
                <div class="ops-kpi-value"><?= e($dbStatus['connected'] ? __('install.connected', 'Connected') : __('install.not_connected', 'Not connected')) ?></div>
                <div class="ops-kpi-meta"><?= e((string) ($dbStatus['database'] ?? '')) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('install.missing_tables', 'Missing tables')) ?></div>
                <div class="ops-kpi-value"><?= e((string) count((array) ($dbStatus['missing_tables'] ?? []))) ?></div>
                <div class="ops-kpi-meta"><?= e(__('install.schema_ready_hint', 'The installer will import the schema if needed.')) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ops-kpi-card h-100">
                <div class="ops-kpi-label"><?= e(__('install.requirements', 'Requirements')) ?></div>
                <div class="ops-kpi-value"><?= e((string) count(array_filter($requirements, static fn (array $item): bool => !empty($item['status'])))) ?>/<?= e((string) count($requirements)) ?></div>
                <div class="ops-kpi-meta"><?= e(__('install.requirements_hint', 'PHP, extension, and writable paths are checked live.')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($requirements as $requirement): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="ops-subtle-item">
                        <div>
                            <div class="fw-semibold"><?= e((string) ($requirement['label'] ?? '')) ?></div>
                            <div class="small text-muted mt-1"><?= e((string) ($requirement['detail'] ?? '')) ?></div>
                        </div>
                        <span class="badge rounded-pill <?= !empty($requirement['status']) ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e(!empty($requirement['status']) ? __('install.ready', 'Ready') : __('install.fix_required', 'Fix required')) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<form method="POST" action="<?= e(route('install.run')) ?>" enctype="multipart/form-data">
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills ops-tab-nav mb-4" id="install-tab-nav">
                <?php $tabIndex = 0; ?>
                <?php foreach ($tabItems as $tabKey => $tabLabel): ?>
                    <li class="nav-item">
                        <button type="button" class="nav-link<?= $tabIndex === 0 ? ' active' : '' ?>" data-install-tab="<?= e($tabKey) ?>"><?= e($tabLabel) ?></button>
                    </li>
                    <?php $tabIndex++; ?>
                <?php endforeach; ?>
            </ul>

            <div class="d-grid gap-4">
                <section data-install-pane="intro">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.app_name', 'Application name')) ?></label>
                            <input type="text" name="app_name" class="form-control<?= has_error('app_name') ? ' is-invalid' : '' ?>" value="<?= e((string) old('app_name', 'Asset Management System')) ?>">
                            <?php if (field_error('app_name') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('app_name')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.company_name', 'Company name')) ?></label>
                            <input type="text" name="company_name" class="form-control<?= has_error('company_name') ? ' is-invalid' : '' ?>" value="<?= e((string) old('company_name', 'Alnahala')) ?>">
                            <?php if (field_error('company_name') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('company_name')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.support_email', 'Support email')) ?></label>
                            <input type="email" name="support_email" class="form-control<?= has_error('support_email') ? ' is-invalid' : '' ?>" value="<?= e((string) old('support_email', 'it@example.com')) ?>">
                            <?php if (field_error('support_email') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('support_email')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.server_note', 'Deployment note')) ?></label>
                            <div class="form-control" style="height:auto; min-height:48px;"><?= e(__('install.server_note_desc', 'Use this installer on a fresh or incomplete deployment. It writes the environment file and prepares the database automatically.')) ?></div>
                        </div>
                    </div>
                </section>

                <section class="d-none" data-install-pane="terms">
                    <div class="card" style="border:1px solid var(--app-border); background: var(--app-surface-muted);">
                        <div class="card-body">
                            <h5 class="mb-3"><?= e(__('install.terms_title', 'Terms and conditions')) ?></h5>
                            <p class="text-muted"><?= e(__('install.terms_paragraph_one', 'This system is intended for authorized organizational use only. All uploaded files, employee records, asset movements, and audit events must be handled according to your internal governance, privacy, and retention policies.')) ?></p>
                            <p class="text-muted"><?= e(__('install.terms_paragraph_two', 'By continuing, the installer operator confirms that the target server is approved for business data, that backups and access controls will be maintained, and that the organization accepts responsibility for the correctness of the initial configuration entered during setup.')) ?></p>
                            <p class="text-muted mb-4"><?= e(__('install.terms_paragraph_three', 'The application records operational actions, login activity, approvals, and inventory movement history. Administrators should review local laws, company policy, and security requirements before activating the platform for production use.')) ?></p>
                            <div class="form-check">
                                <input class="form-check-input<?= has_error('accept_terms') ? ' is-invalid' : '' ?>" type="checkbox" value="1" id="accept_terms" name="accept_terms" <?= old('accept_terms') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="accept_terms"><?= e(__('install.accept_terms', 'I agree to the setup terms and conditions')) ?></label>
                                <?php if (field_error('accept_terms') !== null): ?><div class="invalid-feedback d-block"><?= e((string) field_error('accept_terms')) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="d-none" data-install-pane="language">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.default_language', 'Default language')) ?></label>
                            <select name="default_locale" class="form-select">
                                <option value="en" <?= old('default_locale', 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="ar" <?= old('default_locale') === 'ar' ? 'selected' : '' ?>>العربية</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.default_theme', 'Default theme')) ?></label>
                            <select name="default_theme" class="form-select">
                                <option value="light" <?= old('default_theme', 'light') === 'light' ? 'selected' : '' ?>><?= e(__('theme.light', 'Light')) ?></option>
                                <option value="dark" <?= old('default_theme') === 'dark' ? 'selected' : '' ?>><?= e(__('theme.dark', 'Dark')) ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0"><?= e(__('install.language_hint', 'These values become the default experience for new sessions and for the first administrator account.')) ?></div>
                        </div>
                    </div>
                </section>

                <section class="d-none" data-install-pane="database">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_host', 'DB host')) ?></label>
                            <input type="text" name="db_host" class="form-control" value="<?= e((string) old('db_host', '127.0.0.1')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_port', 'DB port')) ?></label>
                            <input type="text" name="db_port" class="form-control" value="<?= e((string) old('db_port', '3306')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_socket', 'DB socket')) ?></label>
                            <input type="text" name="db_socket" class="form-control" value="<?= e((string) old('db_socket', env('DB_SOCKET', ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_name', 'Database name')) ?></label>
                            <input type="text" name="db_database" class="form-control<?= has_error('db_database') ? ' is-invalid' : '' ?>" value="<?= e((string) old('db_database', 'ams')) ?>">
                            <?php if (field_error('db_database') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('db_database')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_user', 'Database user')) ?></label>
                            <input type="text" name="db_username" class="form-control<?= has_error('db_username') ? ' is-invalid' : '' ?>" value="<?= e((string) old('db_username', 'root')) ?>">
                            <?php if (field_error('db_username') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('db_username')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('install.db_password', 'Database password')) ?></label>
                            <input type="password" name="db_password" class="form-control" value="<?= e((string) old('db_password', '')) ?>">
                            <div class="form-text"><?= e(__('install.db_password_hint', 'Leave this empty if the database user does not have a password.')) ?></div>
                        </div>
                    </div>
                </section>

                <section class="d-none" data-install-pane="admin">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.admin_name', 'Administrator name')) ?></label>
                            <input type="text" name="admin_name" class="form-control<?= has_error('admin_name') ? ' is-invalid' : '' ?>" value="<?= e((string) old('admin_name', 'Admin User')) ?>">
                            <?php if (field_error('admin_name') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('admin_name')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.admin_email', 'Administrator email')) ?></label>
                            <input type="email" name="admin_email" class="form-control<?= has_error('admin_email') ? ' is-invalid' : '' ?>" value="<?= e((string) old('admin_email', 'admin@example.com')) ?>">
                            <?php if (field_error('admin_email') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('admin_email')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.admin_password', 'Administrator password')) ?></label>
                            <input type="password" name="admin_password" class="form-control<?= has_error('admin_password') ? ' is-invalid' : '' ?>">
                            <?php if (field_error('admin_password') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('admin_password')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.admin_password_confirm', 'Confirm administrator password')) ?></label>
                            <input type="password" name="admin_password_confirmation" class="form-control<?= has_error('admin_password_confirmation') ? ' is-invalid' : '' ?>">
                            <?php if (field_error('admin_password_confirmation') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('admin_password_confirmation')) ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="d-none" data-install-pane="branding">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.logo_file', 'Logo file')) ?></label>
                            <input type="file" name="logo_file" class="form-control<?= has_error('logo_file') ? ' is-invalid' : '' ?>" accept=".png,image/png">
                            <?php if (field_error('logo_file') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('logo_file')) ?></div><?php endif; ?>
                            <div class="form-text"><?= e(__('install.logo_hint', 'Optional. Upload a PNG file and it will replace the default logo.')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('install.favicon_file', 'Favicon file')) ?></label>
                            <input type="file" name="favicon_file" class="form-control<?= has_error('favicon_file') ? ' is-invalid' : '' ?>" accept=".ico,image/x-icon">
                            <?php if (field_error('favicon_file') !== null): ?><div class="invalid-feedback"><?= e((string) field_error('favicon_file')) ?></div><?php endif; ?>
                            <div class="form-text"><?= e(__('install.favicon_hint', 'Optional. Upload an ICO file for browser tabs and bookmarks.')) ?></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="small text-muted"><?= e(__('install.submit_hint', 'When you click install, the system writes the environment file, imports the schema, saves branding, and signs in the first administrator.')) ?></div>
            <button type="submit" class="btn btn-primary btn-lg"><?= e(__('install.install_now', 'Install system now')) ?></button>
        </div>
    </div>
</form>

<script>
(function () {
    var buttons = Array.from(document.querySelectorAll('[data-install-tab]'));
    var panes = Array.from(document.querySelectorAll('[data-install-pane]'));

    function activateTab(key) {
        buttons.forEach(function (button) {
            button.classList.toggle('active', button.getAttribute('data-install-tab') === key);
        });
        panes.forEach(function (pane) {
            pane.classList.toggle('d-none', pane.getAttribute('data-install-pane') !== key);
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab(button.getAttribute('data-install-tab'));
        });
    });

    var invalidField = document.querySelector('.is-invalid');
    if (invalidField) {
        var invalidPane = invalidField.closest('[data-install-pane]');
        if (invalidPane) {
            activateTab(invalidPane.getAttribute('data-install-pane'));
            return;
        }
    }

    activateTab('intro');
})();
</script>
