<?php
$formsCount = count($forms);
$filesCount = array_sum(array_map(static fn (array $form): int => (int) ($form['files_count'] ?? 0), $forms));
$latestUpdate = $forms !== [] ? max(array_map(static fn (array $form): string => (string) ($form['updated_at'] ?? ''), $forms)) : '';
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-folder2-open"></i> <?= e(__('nav.administrative_forms', 'Administrative Forms')) ?></div>
            <h2 class="mb-2"><?= e(__('nav.administrative_forms', 'Administrative Forms')) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e(__('administrative_forms.desc', 'Central library for official forms used in requests, access control, and employee clearance.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <?php if (can('forms.manage')): ?>
                <a href="<?= e(route('administrative-forms.create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(__('administrative_forms.add_title', 'Add Administrative Book')) ?></a>
            <?php endif; ?>
            <a href="<?= e(route('requests.index')) ?>" class="btn btn-outline-secondary"><i class="bi bi-clipboard-check"></i> <?= e(__('nav.requests', 'Requests')) ?></a>
            <a href="<?= e(route('employees.index')) ?>" class="btn btn-outline-secondary"><i class="bi bi-people"></i> <?= e(__('nav.employees', 'Employees')) ?></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4 position-relative" style="z-index:1;">
        <span class="surface-chip"><i class="bi bi-collection"></i> <?= e((string) $formsCount) ?> <?= e(__('administrative_forms.total_forms', 'forms')) ?></span>
        <span class="surface-chip"><i class="bi bi-file-earmark-text"></i> <?= e((string) $filesCount) ?> <?= e(__('administrative_forms.available_files', 'available files')) ?></span>
        <?php if ($latestUpdate !== ''): ?>
            <span class="surface-chip"><i class="bi bi-clock-history"></i> <?= e(__('administrative_forms.latest_update', 'Latest update')) ?>: <?= e($latestUpdate) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($forms as $form): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 ops-table-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="small text-muted mb-1"><?= e($form['kind_label']) ?></div>
                            <div class="badge text-bg-light border mb-2"><?= e($form['category']) ?></div>
                            <h5 class="mb-1"><?= e($form['title']) ?></h5>
                            <div class="small text-muted"><?= e(strtr(__('administrative_forms.files_count', ':count file(s)'), [':count' => (string) $form['files_count']])) ?></div>
                        </div>
                        <div class="fs-4 text-primary"><i class="bi bi-file-earmark-richtext"></i></div>
                    </div>
                    <p class="text-muted mb-4"><?= e($form['description']) ?></p>
                    <div class="small text-muted mb-3">
                        <?= e(__('administrative_forms.related_to', 'Related workflow')) ?>:
                        <a href="<?= e($form['related_route']) ?>" class="text-decoration-none"><?= e($form['related_label']) ?></a>
                    </div>
                    <div class="mt-auto d-flex flex-wrap gap-2">
                        <a href="<?= e(route('administrative-forms.show', ['id' => $form['id']])) ?>" class="btn btn-primary"><?= e(__('actions.view', 'View')) ?></a>
                        <?php if (!empty($form['is_editable']) && can('forms.manage')): ?>
                            <a href="<?= e(route('administrative-forms.edit', ['id' => $form['id']])) ?>" class="btn btn-outline-dark"><?= e(__('actions.edit', 'Edit')) ?></a>
                        <?php endif; ?>
                        <?php $primaryFile = $form['files'][$form['primary_variant']] ?? null; ?>
                        <?php if (is_array($primaryFile)): ?>
                            <a href="<?= e($primaryFile['download_route']) ?>" class="btn btn-outline-secondary"><?= e(__('administrative_forms.download_primary', 'Download')) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
