<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

load_env(__DIR__ . '/.env');

$GLOBALS['translations'] = [
    'en' => require __DIR__ . '/resources/lang/en.php',
    'ar' => require __DIR__ . '/resources/lang/ar.php',
];

function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $default;
}

function base_path(string $path = ''): string
{
    return $path === '' ? __DIR__ : __DIR__ . '/' . ltrim($path, '/');
}

function base_url(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $directory = str_replace('\\', '/', dirname($scriptName));

    if ($directory === '/' || $directory === '.') {
        return '';
    }

    return rtrim($directory, '/');
}

function app_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_url() . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function request_method(): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'POST' && isset($_POST['_method'])) {
        return strtoupper((string) $_POST['_method']);
    }

    return $method;
}

function request_path(): string
{
    if (isset($_GET['route'])) {
        $route = (string) $_GET['route'];
        return rtrim($route, '/') ?: '/';
    }

    if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '') {
        return rtrim((string) $_SERVER['PATH_INFO'], '/') ?: '/';
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if ($path === false || $path === null || $path === '') {
        return '/';
    }

    $baseUrl = base_url();
    if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
        $path = substr($path, strlen($baseUrl)) ?: '/';
    }

    return rtrim($path, '/') ?: '/';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, string $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function consume_flash(string $key): ?string
{
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function set_old_input(array $input): void
{
    unset($input['password'], $input['password_confirmation']);
    $_SESSION['_old'] = $input;
}

function consume_old_input(): array
{
    $value = $_SESSION['_old'] ?? [];
    unset($_SESSION['_old']);
    return is_array($value) ? $value : [];
}

function set_validation_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function consume_validation_errors(): array
{
    $value = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return is_array($value) ? $value : [];
}

function set_current_route(?string $route): void
{
    $GLOBALS['current_route'] = $route;
}

function current_route(): ?string
{
    return $GLOBALS['current_route'] ?? null;
}

function route(string $name, array $params = []): string
{
    $path = App\Support\Router::instance()->url($name, $params);
    return base_url() . '/index.php?route=' . urlencode($path);
}

function redirect_to(string $routeName, array $params = []): array
{
    return ['redirect' => route($routeName, $params)];
}

function render(string $template, array $data = []): void
{
    $template = str_replace('.', '/', $template);
    $viewFile = base_path('resources/views/' . $template . '.blade.php');

    if (!is_file($viewFile)) {
        throw new RuntimeException('View not found: ' . $template);
    }

    extract($data, EXTR_SKIP);
    $pageTitle = $data['pageTitle'] ?? 'Asset Management';
    $currentRoute = current_route();
    $flashStatus = consume_flash('status');
    $flashError = consume_flash('error');
    $validationErrors = consume_validation_errors();
    $oldInput = consume_old_input();
    $GLOBALS['validation_errors'] = $validationErrors;
    $GLOBALS['old_input'] = $oldInput;
    $currentUser = auth_user();
    if ($currentUser !== null) {
        App\Support\DataRepository::refreshSystemNotifications();
    }
    $notifications = $currentUser !== null ? App\Support\DataRepository::notificationsForCurrentUser() : [];
    $unreadNotifications = $currentUser !== null ? App\Support\DataRepository::unreadNotificationsCount() : 0;
    $currentLocale = current_locale();
    $currentTheme = current_theme();
    $isRtl = is_rtl();

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    require base_path('resources/views/layouts/app.blade.php');
}

function old(string $key, mixed $default = null): mixed
{
    return $GLOBALS['old_input'][$key] ?? $default;
}

function has_error(string $key): bool
{
    return isset($GLOBALS['validation_errors'][$key]);
}

function field_error(string $key): ?string
{
    $value = $GLOBALS['validation_errors'][$key] ?? null;
    return is_string($value) ? $value : null;
}

function translations(): array
{
    return $GLOBALS['translations'] ?? [];
}

function current_locale(): string
{
    $locale = $_SESSION['locale'] ?? env('APP_LOCALE', 'en');
    return in_array($locale, ['en', 'ar'], true) ? $locale : 'en';
}

function set_locale(string $locale): void
{
    if (in_array($locale, ['en', 'ar'], true)) {
        $_SESSION['locale'] = $locale;
    }
}

function is_rtl(): bool
{
    return current_locale() === 'ar';
}

function human_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = max(0, $bytes);
    $index = 0;
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }

    return number_format($size, $index === 0 ? 0 : 1) . ' ' . $units[$index];
}

function __(string $key, ?string $fallback = null): string
{
    $locale = current_locale();
    $dictionary = translations()[$locale] ?? [];
    return $dictionary[$key] ?? $fallback ?? $key;
}

function setting(string $key, ?string $default = null): ?string
{
    static $settings = null;
    if ($settings === null) {
        $settings = App\Support\DataRepository::systemSettings();
    }

    return $settings[$key] ?? $default;
}

function user_role(): ?string
{
    $user = auth_user();
    return is_array($user) ? (string) ($user['role'] ?? '') : null;
}

function can(string $permission): bool
{
    $role = user_role();
    if ($role === null || $role === '') {
        return false;
    }

    if ($role === 'admin') {
        return true;
    }

    return App\Support\DataRepository::roleHasPermission($role, $permission);
}

function route_permission(?string $routeName): ?string
{
    if ($routeName === null) {
        return null;
    }

    $map = [
        'dashboard' => 'dashboard.view',
        'api.docs' => 'api.docs',
        'api.spec' => 'api.docs',
        'api.dashboard' => 'api.docs',
        'api.assets' => 'api.docs',
        'api.assets.show' => 'api.docs',
        'api.branches' => 'api.docs',
        'api.branches.show' => 'api.docs',
        'api.categories' => 'api.docs',
        'api.employees' => 'api.docs',
        'api.employees.show' => 'api.docs',
        'api.licenses' => 'api.docs',
        'api.licenses.show' => 'api.docs',
        'api.spare_parts' => 'api.docs',
        'api.reports' => 'api.docs',
        'requests.index' => 'requests.view',
        'requests.show' => 'requests.view',
        'requests.create' => 'requests.manage',
        'requests.store' => 'requests.manage',
        'requests.edit' => 'requests.manage',
        'requests.update' => 'requests.manage',
        'requests.destroy' => 'requests.manage',
        'requests.submit' => 'requests.manage',
        'requests.decision' => 'requests.approve',
        'requests.fulfill_storage' => 'requests.approve',
        'requests.advance' => 'requests.approve',
        'assets.index' => 'assets.view',
        'assets.archived' => 'assets.view',
        'assets.export' => 'assets.view',
        'assets.bulk' => 'assets.manage',
        'assets.show' => 'assets.view',
        'assets.create' => 'assets.manage',
        'assets.store' => 'assets.manage',
        'assets.edit' => 'assets.manage',
        'assets.update' => 'assets.manage',
        'assets.destroy' => 'assets.manage',
        'assets.move' => 'assets.move',
        'assets.move.store' => 'assets.move',
        'assets.return' => 'assets.move',
        'assets.return.store' => 'assets.move',
        'assets.repair' => 'assets.move',
        'assets.repair.store' => 'assets.move',
        'assets.archive' => 'assets.manage',
        'assets.archive.store' => 'assets.manage',
        'assets.handover' => 'assets.move',
        'assets.handover.store' => 'assets.move',
        'assets.handover.print' => 'assets.view',
        'assets.maintenance' => 'assets.move',
        'assets.maintenance.store' => 'assets.move',
        'branches.index' => 'branches.view',
        'branches.show' => 'branches.view',
        'branches.create' => 'branches.manage',
        'branches.store' => 'branches.manage',
        'branches.edit' => 'branches.manage',
        'branches.update' => 'branches.manage',
        'branches.destroy' => 'branches.manage',
        'categories.index' => 'categories.view',
        'categories.show' => 'categories.view',
        'categories.create' => 'categories.manage',
        'categories.store' => 'categories.manage',
        'categories.edit' => 'categories.manage',
        'categories.update' => 'categories.manage',
        'categories.destroy' => 'categories.manage',
        'employees.index' => 'employees.view',
        'employees.show' => 'employees.view',
        'employees.create' => 'employees.manage',
        'employees.store' => 'employees.manage',
        'employees.edit' => 'employees.manage',
        'employees.update' => 'employees.manage',
        'employees.destroy' => 'employees.manage',
        'employees.offboarding' => 'employees.manage',
        'employees.offboarding.store' => 'employees.manage',
        'licenses.index' => 'licenses.view',
        'licenses.show' => 'licenses.view',
        'licenses.create' => 'licenses.manage',
        'licenses.store' => 'licenses.manage',
        'licenses.edit' => 'licenses.manage',
        'licenses.update' => 'licenses.manage',
        'licenses.destroy' => 'licenses.manage',
        'licenses.renew' => 'licenses.manage',
        'spare-parts.index' => 'spare_parts.view',
        'spare-parts.show' => 'spare_parts.view',
        'spare-parts.create' => 'spare_parts.manage',
        'spare-parts.store' => 'spare_parts.manage',
        'spare-parts.edit' => 'spare_parts.manage',
        'spare-parts.update' => 'spare_parts.manage',
        'spare-parts.destroy' => 'spare_parts.manage',
        'storage.index' => 'storage.view',
        'storage.export' => 'storage.view',
        'administrative-forms.index' => 'forms.view',
        'administrative-forms.show' => 'forms.view',
        'administrative-forms.download' => 'forms.view',
        'administrative-forms.create' => 'forms.manage',
        'administrative-forms.store' => 'forms.manage',
        'administrative-forms.edit' => 'forms.manage',
        'administrative-forms.update' => 'forms.manage',
        'administrative-forms.destroy' => 'forms.manage',
        'reports.index' => 'reports.view',
        'reports.export' => 'reports.export',
        'audit.index' => 'audit.view',
        'audit.export' => 'audit.view',
        'users.index' => 'users.view',
        'users.show' => 'users.view',
        'users.create' => 'users.manage',
        'users.store' => 'users.manage',
        'users.edit' => 'users.manage',
        'users.update' => 'users.manage',
        'users.destroy' => 'users.manage',
        'settings' => 'settings.manage',
        'settings.general.save' => 'settings.manage',
        'settings.auth.save' => 'settings.manage',
        'settings.security.save' => 'settings.manage',
        'settings.permissions.save' => 'settings.manage',
        'settings.backups.create' => 'settings.manage',
        'tools.index' => 'settings.manage',
        'tools.export' => 'settings.manage',
        'tools.template' => 'settings.manage',
        'tools.import' => 'settings.manage',
        'system.check' => 'system.check',
    ];

    return $map[$routeName] ?? null;
}

function route_allowed(?string $routeName): bool
{
    $permission = route_permission($routeName);

    if ($permission === null) {
        return true;
    }

    return can($permission);
}

function current_theme(): string
{
    $theme = $_SESSION['theme'] ?? 'light';
    return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
}

function set_theme(string $theme): void
{
    if (in_array($theme, ['light', 'dark'], true)) {
        $_SESSION['theme'] = $theme;
    }
}

function toggle_theme(): void
{
    set_theme(current_theme() === 'dark' ? 'light' : 'dark');
}

function auth_user(): ?array
{
    if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
        return $_SESSION['auth_user'];
    }

    if (!empty($_COOKIE['asset_auth_email'])) {
        $email = (string) $_COOKIE['asset_auth_email'];
        $user = App\Support\DataRepository::findUserByEmail($email);

        if ($user !== null && ($user['status'] ?? 'active') === 'active') {
            login_user($user);
            return $_SESSION['auth_user'] ?? null;
        }
    }

    return $_SESSION['auth_user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['auth_user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'] ?? 'active',
    ];
    setcookie('asset_auth_email', (string) $user['email'], [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    if (!empty($user['locale'])) {
        set_locale((string) $user['locale']);
    }
    if (!empty($user['theme'])) {
        set_theme((string) $user['theme']);
    }
}

function logout_user(): void
{
    unset($_SESSION['auth_user']);
    setcookie('asset_auth_email', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function is_public_route(?string $routeName): bool
{
    return in_array($routeName, ['login', 'login.attempt', 'locale.switch'], true);
}
