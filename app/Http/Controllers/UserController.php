<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class UserController extends Controller
{
    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $users = array_values(array_filter(DataRepository::users(), function (array $user) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($user['name'] ?? ''),
                    (string) ($user['email'] ?? ''),
                    (string) ($user['role'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['role'] !== '' && (string) ($user['role'] ?? '') !== $filters['role']) {
                return false;
            }

            if ($filters['status'] !== '' && (string) ($user['status'] ?? '') !== $filters['status']) {
                return false;
            }

            return true;
        }));

        $this->render('users.index', [
            'pageTitle' => __('nav.users', 'Users'),
            'users' => $users,
            'filters' => $filters,
            'roles' => ['admin', 'it_manager', 'technician', 'finance', 'viewer'],
        ]);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];
    }

    public function create(): void
    {
        $this->render('users/form', [
            'pageTitle' => __('form.create_user', 'Create User'),
            'user' => null,
            'roles' => ['admin', 'it_manager', 'technician', 'finance', 'viewer'],
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,it_manager,technician,finance,viewer'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['required', 'min:6'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('users.create', $errors, $_POST);
        }
        $id = DataRepository::createUser($_POST);
        DataRepository::logAudit('create', 'users', $id, null, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.user_created', 'User created successfully.'));
        return $this->redirect('users.index');
    }

    public function edit(string $id): void
    {
        $user = DataRepository::findUser((int) $id);
        if ($user === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'User Not Found']);
            return;
        }

        $this->render('users/form', [
            'pageTitle' => __('form.edit_user', 'Edit User'),
            'user' => $user,
            'roles' => ['admin', 'it_manager', 'technician', 'finance', 'viewer'],
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,it_manager,technician,finance,viewer'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        if (trim((string) ($_POST['password'] ?? '')) !== '') {
            $errors = array_merge($errors, $this->validate($_POST, ['password' => ['min:6']]));
        }
        if ($errors !== []) {
            return $this->validationRedirect('users.edit', $errors, $_POST, ['id' => $recordId]);
        }
        $old = DataRepository::findUser($recordId);
        DataRepository::updateUser($recordId, $_POST);
        DataRepository::logAudit('update', 'users', $recordId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.user_updated', 'User updated successfully.'));
        return $this->redirect('users.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findUser($recordId);
        DataRepository::deleteUser($recordId);
        DataRepository::logAudit('delete', 'users', $recordId, $old, null);
        flash('status', __('flash.user_removed', 'User removed successfully.'));
        return $this->redirect('users.index');
    }

    public function show(string $id): void
    {
        $this->edit($id);
    }
}
