<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class ApiDocsController extends Controller
{
    public function index(): void
    {
        $this->render('api.docs', [
            'pageTitle' => __('api.docs', 'API Documentation'),
            'specUrl' => route('api.spec'),
        ]);
    }

    public function spec(): void
    {
        $path = base_path('openapi.json');
        if (!is_file($path)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'OpenAPI spec not found'], JSON_PRETTY_PRINT);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        readfile($path);
    }
}
