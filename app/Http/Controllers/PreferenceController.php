<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class PreferenceController extends Controller
{
    public function locale(string $locale): array
    {
        set_locale($locale);
        $target = auth_user() ? route('dashboard') : route('login');

        return ['redirect' => $target];
    }

    public function theme(): array
    {
        toggle_theme();

        return ['redirect' => auth_user() ? route('dashboard') : route('login')];
    }
}
