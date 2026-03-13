<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (auth_user() !== null) {
            header('Location: ' . route('dashboard'));
            exit;
        }

        $this->render('auth/login', [
            'pageTitle' => __('auth.login', 'Login'),
            'error' => consume_flash('error'),
        ]);
    }

    public function login(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = $this->validate($_POST, [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:3'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('login', $errors, ['email' => $email]);
        }
        $user = DataRepository::findUserByEmail($email);

        if (
            $user === null ||
            ($user['status'] ?? 'active') !== 'active' ||
            !password_verify($password, (string) ($user['password'] ?? ''))
        ) {
            $_SESSION['_flash']['error'] = __('auth.invalid', 'Invalid email or password.');
            return $this->redirect('login');
        }

        login_user($user);
        flash('status', __('auth.login', 'Login') . ' ' . __('users.active', 'Active') . '.');

        return $this->redirect('dashboard');
    }

    public function logout(): array
    {
        logout_user();
        flash('status', __('auth.logout', 'Logout') . ' OK.');

        return $this->redirect('login');
    }
}
