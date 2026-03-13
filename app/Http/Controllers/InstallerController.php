<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\InstallerService;

class InstallerController extends Controller
{
    public function index(): void
    {
        if (!InstallerService::needsInstallation()) {
            header('Location: ' . route(auth_user() !== null ? 'dashboard' : 'login'));
            exit;
        }

        $this->render('install.index', [
            'pageTitle' => __('install.title', 'Initial Setup'),
            'installerState' => InstallerService::state(),
        ]);
    }

    public function store(): array
    {
        if (!InstallerService::needsInstallation()) {
            return $this->redirect(auth_user() !== null ? 'dashboard' : 'login');
        }

        $errors = $this->validate($_POST, [
            'app_name' => ['required', 'min:2'],
            'company_name' => ['required', 'min:2'],
            'support_email' => ['required', 'email'],
            'default_locale' => ['required', 'in:en,ar'],
            'default_theme' => ['required', 'in:light,dark'],
            'db_database' => ['required', 'min:1'],
            'db_username' => ['required', 'min:1'],
            'admin_name' => ['required', 'min:2'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'min:8'],
        ]);

        if (trim((string) ($_POST['accept_terms'] ?? '')) !== '1') {
            $errors['accept_terms'] = __('install.accept_terms_required', 'You must accept the terms and conditions to continue.');
        }

        if (trim((string) ($_POST['admin_password'] ?? '')) !== trim((string) ($_POST['admin_password_confirmation'] ?? ''))) {
            $errors['admin_password_confirmation'] = __('install.password_confirm_mismatch', 'Admin password confirmation does not match.');
        }

        $logoName = strtolower((string) ($_FILES['logo_file']['name'] ?? ''));
        if ($logoName !== '' && !str_ends_with($logoName, '.png')) {
            $errors['logo_file'] = __('install.logo_format_invalid', 'Logo upload currently accepts PNG only.');
        }

        $faviconName = strtolower((string) ($_FILES['favicon_file']['name'] ?? ''));
        if ($faviconName !== '' && !str_ends_with($faviconName, '.ico')) {
            $errors['favicon_file'] = __('install.favicon_format_invalid', 'Favicon upload currently accepts ICO only.');
        }

        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return ['redirect' => route('install')];
        }

        try {
            $user = InstallerService::install($_POST, $_FILES);
            login_user($user);
            set_locale((string) ($user['locale'] ?? 'en'));
            set_theme((string) ($user['theme'] ?? 'light'));
            flash('status', __('install.completed', 'Installation completed successfully.'));
            return $this->redirect('dashboard');
        } catch (\Throwable $exception) {
            set_old_input($_POST);
            flash('error', $exception->getMessage());
            return ['redirect' => route('install')];
        }
    }
}
