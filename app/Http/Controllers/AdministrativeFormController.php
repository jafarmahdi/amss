<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class AdministrativeFormController extends Controller
{
    public function index(): void
    {
        $forms = DataRepository::administrativeForms();

        $this->render('administrative-forms.index', [
            'pageTitle' => __('nav.administrative_forms', 'Administrative Forms'),
            'forms' => $forms,
        ]);
    }

    public function create(): void
    {
        if (!can('forms.manage')) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return;
        }

        $this->render('administrative-forms.form', [
            'pageTitle' => __('administrative_forms.add_title', 'Add Administrative Book'),
            'routeOptions' => DataRepository::administrativeFormRouteOptions(),
        ]);
    }

    public function store(): array
    {
        if (!can('forms.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('administrative-forms.index');
        }

        $errors = $this->validate($_POST, [
            'title' => ['required'],
            'kind' => ['required', 'in:form,book'],
        ]);

        $files = $this->collectUploadedFiles();
        if ($files['name'] === []) {
            $errors['documents'] = __('administrative_forms.file_required', 'Upload at least one file.');
        }

        foreach ($files['name'] as $name) {
            $extension = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
                $errors['documents'] = __('administrative_forms.file_type_invalid', 'Only PDF, DOC, or DOCX files are allowed.');
                break;
            }
        }

        if ($errors !== []) {
            return $this->validationRedirect('administrative-forms.create', $errors, $_POST);
        }

        $formId = DataRepository::createAdministrativeForm($_POST, $files);
        DataRepository::logAudit('create', 'administrative_forms', null, null, [
            'form' => $formId,
            'kind' => $_POST['kind'] ?? 'book',
            'title' => $_POST['title'] ?? '',
        ]);
        flash('status', __('administrative_forms.created', 'Administrative document added successfully.'));

        return $this->redirect('administrative-forms.show', ['id' => $formId]);
    }

    public function show(string $id): void
    {
        $form = DataRepository::findAdministrativeForm($id);
        if ($form === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return;
        }

        DataRepository::logAudit('view', 'administrative_forms', null, null, ['form' => $form['id']]);

        $this->render('administrative-forms.show', [
            'pageTitle' => $form['title'],
            'form' => $form,
        ]);
    }

    public function edit(string $id): void
    {
        if (!can('forms.manage')) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return;
        }

        $form = $this->editableFormOr404($id);
        if ($form === null) {
            return;
        }

        $this->render('administrative-forms.form', [
            'pageTitle' => __('administrative_forms.edit_title', 'Edit Administrative Document'),
            'routeOptions' => DataRepository::administrativeFormRouteOptions(),
            'form' => $form,
        ]);
    }

    public function update(string $id): array
    {
        if (!can('forms.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('administrative-forms.index');
        }

        $form = DataRepository::findAdministrativeForm($id);
        if ($form === null || empty($form['is_editable'])) {
            flash('error', __('administrative_forms.edit_locked', 'This document is system-managed and cannot be edited or deleted from here.'));
            return $this->redirect('administrative-forms.index');
        }

        $errors = $this->validate($_POST, [
            'title' => ['required'],
            'kind' => ['required', 'in:form,book'],
        ]);

        $files = $this->collectUploadedFiles();
        foreach ($files['name'] as $name) {
            $extension = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
                $errors['documents'] = __('administrative_forms.file_type_invalid', 'Only PDF, DOC, or DOCX files are allowed.');
                break;
            }
        }

        $removeVariants = array_values(array_filter(array_map('strval', (array) ($_POST['remove_variants'] ?? [])), static fn (string $value): bool => $value !== ''));
        $remainingCount = count((array) ($form['files'] ?? [])) - count(array_intersect(array_keys((array) ($form['files'] ?? [])), $removeVariants)) + count($files['name']);
        if ($remainingCount <= 0) {
            $errors['documents'] = __('administrative_forms.keep_one_file', 'Keep at least one file for this document.');
        }

        if ($errors !== []) {
            return $this->validationRedirect('administrative-forms.edit', $errors, $_POST, ['id' => $id]);
        }

        DataRepository::updateAdministrativeForm($id, $_POST, $files, $removeVariants);
        DataRepository::logAudit('update', 'administrative_forms', null, ['form' => $id], [
            'form' => $id,
            'title' => $_POST['title'] ?? '',
            'removed_variants' => $removeVariants,
        ]);
        flash('status', __('administrative_forms.updated', 'Administrative document updated successfully.'));

        return $this->redirect('administrative-forms.show', ['id' => $id]);
    }

    public function destroy(string $id): array
    {
        if (!can('forms.manage')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('administrative-forms.index');
        }

        $form = DataRepository::findAdministrativeForm($id);
        if ($form === null || empty($form['is_editable'])) {
            flash('error', __('administrative_forms.edit_locked', 'This document is system-managed and cannot be edited or deleted from here.'));
            return $this->redirect('administrative-forms.index');
        }

        DataRepository::deleteAdministrativeForm($id);
        DataRepository::logAudit('delete', 'administrative_forms', null, $form, null);
        flash('status', !empty($form['is_builtin'])
            ? __('administrative_forms.restored', 'System document restored to default successfully.')
            : __('administrative_forms.deleted', 'Administrative document deleted successfully.'));

        return $this->redirect('administrative-forms.index');
    }

    public function download(string $id, string $variant): void
    {
        $file = DataRepository::findAdministrativeFormFile($id, $variant);
        if ($file === null || !is_file($file['path'])) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return;
        }

        $inline = isset($_GET['inline']) && (string) $_GET['inline'] === '1' && $file['extension'] === 'pdf';
        DataRepository::logAudit($inline ? 'preview' : 'download', 'administrative_forms', null, null, [
            'form' => $id,
            'variant' => $variant,
            'extension' => $file['extension'],
        ]);

        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . (string) filesize($file['path']));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($file['download_name']) . '"');
        readfile($file['path']);
        exit;
    }

    private function collectUploadedFiles(): array
    {
        $buckets = ['primary_file', 'secondary_file'];
        $names = [];
        $tmpNames = [];
        $errors = [];

        foreach ($buckets as $bucket) {
            $file = $_FILES[$bucket] ?? null;
            if (!is_array($file)) {
                continue;
            }

            $name = trim((string) ($file['name'] ?? ''));
            $tmpName = (string) ($file['tmp_name'] ?? '');
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($error !== UPLOAD_ERR_OK || $name === '' || $tmpName === '') {
                continue;
            }

            $names[] = $name;
            $tmpNames[] = $tmpName;
            $errors[] = $error;
        }

        return [
            'name' => $names,
            'tmp_name' => $tmpNames,
            'error' => $errors,
        ];
    }

    private function editableFormOr404(string $id): ?array
    {
        $form = DataRepository::findAdministrativeForm($id);
        if ($form === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return null;
        }

        if (empty($form['is_editable'])) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => __('nav.administrative_forms', 'Administrative Forms')]);
            return null;
        }

        return $form;
    }
}
