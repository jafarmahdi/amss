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
    http_response_code(500);
    set_current_route(null);
    render('errors/500', ['pageTitle' => __('errors.500_page', 'System Error')]);
    exit;
}
