<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$sessionPath = __DIR__ . '/storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
@chmod($sessionPath, 0777);
session_name('asset_session');
session_save_path($sessionPath);
session_start();

$logPath = app_log_path('php-error');
$logDirectory = dirname($logPath);
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0777, true);
}
@chmod($logDirectory, 0777);
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_log', $logPath);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    app_log('warning', 'PHP runtime warning', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'route' => request_path(),
        'method' => request_method(),
    ]);

    return false;
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!is_array($error)) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int) ($error['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    app_log('critical', 'Fatal shutdown error', [
        'type' => (int) ($error['type'] ?? 0),
        'message' => (string) ($error['message'] ?? ''),
        'file' => (string) ($error['file'] ?? ''),
        'line' => (int) ($error['line'] ?? 0),
        'route' => request_path(),
        'method' => request_method(),
    ]);
});

try {
    $router = new App\Support\Router();
    $registerRoutes = require __DIR__ . '/routes/router.php';
    $registerRoutes($router);

    $match = $router->match(request_method(), request_path());

    if ($match === null) {
        http_response_code(404);
        set_current_route(null);
        render('errors/404', ['pageTitle' => __('errors.404_page', 'Page Not Found')]);
        exit;
    }

    set_current_route($match['name']);

    $setupRequired = App\Support\InstallerService::needsInstallation();
    if ($setupRequired && !in_array($match['name'], ['install', 'install.run', 'locale.switch'], true)) {
        header('Location: ' . route('install'));
        exit;
    }

    if (!$setupRequired && in_array($match['name'], ['install', 'install.run'], true)) {
        header('Location: ' . (auth_user() !== null ? route('dashboard') : route('login')));
        exit;
    }

    if (!is_public_route($match['name']) && auth_user() === null) {
        header('Location: ' . route('login'));
        exit;
    }

    if (!is_public_route($match['name']) && !route_allowed($match['name'])) {
        http_response_code(403);
        flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
        render('errors/403', ['pageTitle' => __('errors.403_page', 'Access Denied')]);
        exit;
    }

    [$controllerClass, $action] = $match['handler'];
    $controller = new $controllerClass();
    $response = $controller->{$action}(...array_values($match['params']));

    if (is_array($response) && isset($response['redirect'])) {
        header('Location: ' . $response['redirect']);
        exit;
    }
} catch (\Throwable $exception) {
    app_log_exception($exception, [
        'route' => request_path(),
        'method' => request_method(),
        'current_route' => current_route(),
        'user_id' => auth_user()['id'] ?? null,
    ]);
    http_response_code(500);
    set_current_route(null);
    render('errors/500', ['pageTitle' => __('errors.500_page', 'System Error')]);
    exit;
}
