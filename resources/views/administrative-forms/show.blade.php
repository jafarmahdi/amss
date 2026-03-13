<?php
$pdfFile = $form['files']['pdf'] ?? null;
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-file-earmark-medical"></i> <?= e($form['category']) ?></div>
            <h2 class="mb-2"><?= e($form['title']) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e($form['description']) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('administrative-forms.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
            <?php if (!empty($form['is_editable']) && can('forms.manage')): ?>
                <a href="<?= e(route('administrative-forms.edit', ['id' => $form['id']])) ?>" class="btn btn-outline-dark"><?= e(__('actions.edit', 'Edit')) ?></a>
            <?php endif; ?>
            <a href="<?= e($form['related_route']) ?>" class="btn btn-primary"><?= e($form['related_label']) ?></a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('administrative_forms.available_files', 'Available files')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $form['files_count']) ?></div>
            <div class="ops-kpi-meta"><?= e($form['category']) ?> · <?= e($form['kind_label']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('administrative_forms.latest_update', 'Latest update')) ?></div>
            <div class="ops-kpi-value" style="font-size:1.05rem;"><?= e($form['updated_at'] !== '' ? $form['updated_at'] : '-') ?></div>
            <div class="ops-kpi-meta"><?= e(__('administrative_forms.file_library', 'File library')) ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 ops-table-card">
            <div class="card-body">
                <div class="text-muted small mb-2"><?= e(__('administrative_forms.related_to', 'Related workflow')) ?></div>
                <div class="fs-5 fw-semibold mb-2"><?= e($form['related_label']) ?></div>
                <a href="<?= e($form['related_route']) ?>" class="text-decoration-none"><?= e(__('administrative_forms.open_related', 'Open related module')) ?></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('administrative_forms.files_title', 'Available versions')) ?></h5>
                    <span class="small text-muted"><?= e(__('administrative_forms.download_help', 'Open or download the approved form version')) ?></span>
                </div>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <?php foreach ($form['files'] as $file): ?>
                    <div class="border rounded-4 p-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-semibold"><?= e(strtoupper($file['extension'])) ?></div>
                            <div class="small text-muted"><?= e(human_size((int) $file['size'])) ?> · <?= e($file['updated_at']) ?></div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($file['extension'] === 'pdf'): ?>
                                <a href="<?= e($file['download_route'] . '&inline=1') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                            <?php endif; ?>
                            <a href="<?= e($file['download_route']) ?>" class="btn btn-sm btn-primary"><?= e(__('administrative_forms.download_primary', 'Download')) ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('administrative_forms.preview_title', 'Preview')) ?></h5>
                    <span class="small text-muted"><?= e($pdfFile !== null ? __('administrative_forms.preview_available', 'PDF preview is available for this form.') : __('administrative_forms.preview_unavailable', 'Preview is available when a PDF version exists.')) ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($pdfFile !== null): ?>
                    <iframe src="<?= e($pdfFile['download_route'] . '&inline=1') ?>" title="<?= e($form['title']) ?>" style="width:100%; min-height:720px; border:0; border-radius: 1rem;"></iframe>
                <?php else: ?>
                    <div class="d-flex flex-column justify-content-center align-items-center text-center rounded-4" style="min-height: 420px; background: rgba(148, 163, 184, 0.08); border: 1px dashed var(--app-border);">
                        <div class="fs-1 text-muted mb-3"><i class="bi bi-file-earmark-word"></i></div>
                        <h6 class="mb-2"><?= e(__('administrative_forms.preview_unavailable', 'Preview is available when a PDF version exists.')) ?></h6>
                        <p class="text-muted mb-3"><?= e(__('administrative_forms.download_docx_hint', 'This form is currently stored as Word only. Download the DOCX version to edit or print it.')) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
