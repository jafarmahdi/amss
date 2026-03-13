<?php
$editing = isset($form) && is_array($form);
$formAction = $editing ? route('administrative-forms.update', ['id' => $form['id']]) : route('administrative-forms.store');
$selectedKind = (string) old('kind', $editing ? ($form['kind'] ?? 'book') : 'book');
$selectedRoute = (string) old('related_route_name', $editing ? ($form['related_route_name'] ?? 'dashboard') : 'dashboard');
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-journal-plus"></i> <?= e($editing ? __('administrative_forms.edit_title', 'Edit Administrative Document') : __('administrative_forms.add_title', 'Add Administrative Book')) ?></div>
            <h2 class="mb-2"><?= e($editing ? __('administrative_forms.edit_title', 'Edit Administrative Document') : __('administrative_forms.add_title', 'Add Administrative Book')) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e($editing ? __('administrative_forms.edit_desc', 'Update the document details or replace the stored files.') : __('administrative_forms.add_desc', 'Upload a new administrative form or book to the internal library.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('administrative-forms.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
        </div>
    </div>
</div>

<div class="card ops-table-card">
    <div class="card-body">
        <form method="POST" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="row g-3">
            <?php if ($editing): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="col-md-4">
                <label for="kind" class="form-label fw-semibold"><?= e(__('administrative_forms.kind_label', 'Document type')) ?></label>
                <select name="kind" id="kind" class="form-select<?= has_error('kind') ? ' is-invalid' : '' ?>">
                    <option value="book" <?= $selectedKind === 'book' ? 'selected' : '' ?>><?= e(__('administrative_forms.kind_book', 'Administrative Book')) ?></option>
                    <option value="form" <?= $selectedKind === 'form' ? 'selected' : '' ?>><?= e(__('administrative_forms.kind_form', 'Administrative Form')) ?></option>
                </select>
                <?php if (has_error('kind')): ?><div class="invalid-feedback"><?= e(field_error('kind')) ?></div><?php endif; ?>
            </div>
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold"><?= e(__('common.name', 'Name')) ?></label>
                <input type="text" name="title" id="title" value="<?= e((string) old('title', $editing ? ($form['title'] ?? '') : '')) ?>" class="form-control<?= has_error('title') ? ' is-invalid' : '' ?>" required>
                <?php if (has_error('title')): ?><div class="invalid-feedback"><?= e(field_error('title')) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="category" class="form-label fw-semibold"><?= e(__('assets.category', 'Category')) ?></label>
                <input type="text" name="category" id="category" value="<?= e((string) old('category', $editing ? ($form['category'] ?? __('administrative_forms.category_books', 'Administrative Books')) : __('administrative_forms.category_books', 'Administrative Books'))) ?>" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="related_route_name" class="form-label fw-semibold"><?= e(__('administrative_forms.related_to', 'Related workflow')) ?></label>
                <select name="related_route_name" id="related_route_name" class="form-select">
                    <?php foreach ($routeOptions as $routeName => $routeLabel): ?>
                        <option value="<?= e($routeName) ?>" <?= $selectedRoute === $routeName ? 'selected' : '' ?>><?= e($routeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label for="description" class="form-label fw-semibold"><?= e(__('assets.notes', 'Notes')) ?></label>
                <textarea name="description" id="description" rows="4" class="form-control"><?= e((string) old('description', $editing ? ($form['description'] ?? '') : '')) ?></textarea>
            </div>
            <?php if ($editing && !empty($form['files'])): ?>
                <div class="col-12">
                    <div class="border rounded-4 p-3">
                        <div class="fw-semibold mb-3"><?= e(__('administrative_forms.current_files', 'Current files')) ?></div>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($form['files'] as $file): ?>
                                <label class="d-flex justify-content-between align-items-center gap-3 border rounded-4 px-3 py-2 flex-wrap">
                                    <span>
                                        <span class="fw-semibold"><?= e(strtoupper($file['extension'])) ?></span>
                                        <span class="small text-muted ms-2"><?= e($file['download_name']) ?> · <?= e(human_size((int) $file['size'])) ?></span>
                                    </span>
                                    <span class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="remove_variants[]" value="<?= e($file['variant']) ?>" id="remove_<?= e($file['variant']) ?>">
                                        <label class="form-check-label small ms-1" for="remove_<?= e($file['variant']) ?>"><?= e(__('administrative_forms.remove_file', 'Remove file')) ?></label>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label for="primary_file" class="form-label fw-semibold"><?= e(__('administrative_forms.primary_file', 'Primary file')) ?></label>
                <input type="file" name="primary_file" id="primary_file" class="form-control<?= has_error('documents') ? ' is-invalid' : '' ?>" accept=".pdf,.doc,.docx" <?= $editing ? '' : 'required' ?>>
                <div class="form-text"><?= e($editing ? __('administrative_forms.replace_help', 'Upload a file with the same extension to replace it, or a new extension to add another version.') : __('administrative_forms.file_help', 'Allowed formats: PDF, DOC, DOCX.')) ?></div>
                <?php if (has_error('documents')): ?><div class="invalid-feedback d-block"><?= e(field_error('documents')) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="secondary_file" class="form-label fw-semibold"><?= e(__('administrative_forms.secondary_file', 'Secondary file')) ?></label>
                <input type="file" name="secondary_file" id="secondary_file" class="form-control" accept=".pdf,.doc,.docx">
                <div class="form-text"><?= e(__('administrative_forms.secondary_help', 'Optional: upload a PDF preview or a second version.')) ?></div>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary"><?= e($editing ? __('actions.save', 'Save Changes') : __('actions.create', 'Create')) ?></button>
                <a href="<?= e(route('administrative-forms.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
        <?php if ($editing): ?>
            <form method="POST" action="<?= e(route('administrative-forms.destroy', ['id' => $form['id']])) ?>" class="mt-3">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this document?')">
                    <?= e(!empty($form['is_builtin']) ? __('administrative_forms.restore_default', 'Restore Default') : __('actions.delete', 'Delete')) ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
