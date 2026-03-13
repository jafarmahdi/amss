<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class BranchController extends Controller
{
    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $branches = array_values(array_filter(DataRepository::branches(), function (array $branch) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($branch['name'] ?? ''),
                    (string) ($branch['type'] ?? ''),
                    (string) ($branch['address'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['type'] !== '' && (string) ($branch['type'] ?? '') !== $filters['type']) {
                return false;
            }

            return true;
        }));

        $this->render('branches.index', [
            'pageTitle' => __('nav.branches', 'Branches'),
            'branches' => $branches,
            'filters' => $filters,
            'typeOptions' => ['HQ', 'Branch', 'Office', 'Storage'],
        ]);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
        ];
    }

    public function create(): void
    {
        $this->render('branches/form', [
            'pageTitle' => __('form.create_branch', 'Create Branch'),
            'branch' => null,
            'types' => ['HQ', 'Branch', 'Office', 'Storage'],
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'type' => ['required', 'in:HQ,Branch,Office,Storage'],
            'address' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('branches.create', $errors, $_POST);
        }
        $id = DataRepository::createBranch($_POST);
        DataRepository::logAudit('create', 'branches', $id, null, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.branch_created', 'Branch created successfully.'));
        return $this->redirect('branches.index');
    }

    public function edit(string $id): void
    {
        $branch = DataRepository::findBranch((int) $id);
        if ($branch === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Branch Not Found']);
            return;
        }

        $this->render('branches/form', [
            'pageTitle' => __('form.edit_branch', 'Edit Branch'),
            'branch' => $branch,
            'types' => ['HQ', 'Branch', 'Office', 'Storage'],
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'type' => ['required', 'in:HQ,Branch,Office,Storage'],
            'address' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('branches.edit', $errors, $_POST, ['id' => $recordId]);
        }
        $old = DataRepository::findBranch($recordId);
        DataRepository::updateBranch($recordId, $_POST);
        DataRepository::logAudit('update', 'branches', $recordId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.branch_updated', 'Branch updated successfully.'));
        return $this->redirect('branches.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findBranch($recordId);
        DataRepository::deleteBranch($recordId);
        DataRepository::logAudit('delete', 'branches', $recordId, $old, null);
        flash('status', __('flash.branch_removed', 'Branch removed successfully.'));
        return $this->redirect('branches.index');
    }

    public function show(string $id): void
    {
        $branch = DataRepository::branchDetail((int) $id);
        if ($branch === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Branch Not Found']);
            return;
        }

        $this->render('branches.show', [
            'pageTitle' => $branch['name'],
            'branch' => $branch,
            'employees' => DataRepository::branchEmployees((int) $id),
            'assets' => DataRepository::branchAssets((int) $id),
            'categories' => DataRepository::branchCategoryBreakdown((int) $id),
        ]);
    }
}
