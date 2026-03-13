<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class PreferenceController extends Controller
{
    public function locale(string $locale): array
    {
        set_locale($locale);
        $fallback = auth_user() ? route('dashboard') : route('login');
        $target = sanitize_redirect_target((string) ($_GET['redirect'] ?? ''), $fallback);

        return ['redirect' => $target];
    }

    public function theme(): array
    {
        toggle_theme();
        $fallback = auth_user() ? route('dashboard') : route('login');

        return ['redirect' => sanitize_redirect_target((string) ($_POST['redirect'] ?? ''), $fallback)];
    }
}
