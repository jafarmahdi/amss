<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class CategoryController extends Controller
{
    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $categories = array_values(array_filter(DataRepository::categories(), function (array $category) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($category['name'] ?? ''),
                    (string) ($category['description'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['usage'] === 'used' && (int) ($category['count'] ?? 0) <= 0) {
                return false;
            }

            if ($filters['usage'] === 'empty' && (int) ($category['count'] ?? 0) > 0) {
                return false;
            }

            return true;
        }));

        $this->render('categories.index', [
            'pageTitle' => __('nav.categories', 'Categories'),
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'usage' => trim((string) ($_GET['usage'] ?? '')),
        ];
    }

    public function create(): void
    {
        $this->render('categories/form', [
            'pageTitle' => __('form.create_category', 'Create Category'),
            'category' => null,
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'description' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('categories.create', $errors, $_POST);
        }
        $id = DataRepository::createCategory($_POST);
        DataRepository::logAudit('create', 'asset_categories', $id, null, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.category_created', 'Category created successfully.'));
        return $this->redirect('categories.index');
    }

    public function edit(string $id): void
    {
        $category = DataRepository::findCategory((int) $id);
        if ($category === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Category Not Found']);
            return;
        }

        $this->render('categories/form', [
            'pageTitle' => __('form.edit_category', 'Edit Category'),
            'category' => $category,
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'description' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('categories.edit', $errors, $_POST, ['id' => $recordId]);
        }
        $old = DataRepository::findCategory($recordId);
        DataRepository::updateCategory($recordId, $_POST);
        DataRepository::logAudit('update', 'asset_categories', $recordId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.category_updated', 'Category updated successfully.'));
        return $this->redirect('categories.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findCategory($recordId);
        DataRepository::deleteCategory($recordId);
        DataRepository::logAudit('delete', 'asset_categories', $recordId, $old, null);
        flash('status', __('flash.category_removed', 'Category removed successfully.'));
        return $this->redirect('categories.index');
    }

    public function show(string $id): void
    {
        $category = DataRepository::findCategory((int) $id);
        if ($category === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Category Not Found']);
            return;
        }

        $this->render('categories.show', [
            'pageTitle' => $category['name'],
            'category' => $category,
            'assets' => DataRepository::categoryAssets((int) $id),
        ]);
    }
}
