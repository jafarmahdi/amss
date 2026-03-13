<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('assets.archive_title', 'Archive Asset')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('assets.archive_desc', 'Use this when the asset cannot be repaired and must be retired permanently with an approval document.')) ?></p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="alert alert-warning">
    <?= e(__('assets.archive_help', 'Archiving will mark the asset as archived, close active assignments, and store the approval file in the asset history.')) ?>
</div>

<?php if (($asset['status'] ?? '') === 'broken'): ?>
    <div class="alert alert-danger">
        <strong><?= e(__('status.broken', 'Broken')) ?></strong>
        <?= e(__('assets.archive_broken_hint', 'This asset is currently marked as broken and can now be archived after approval.')) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="text-muted small"><?= e(__('assets.name', 'Asset Name')) ?></div>
                <div class="fw-semibold"><?= e($asset['name']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small"><?= e(__('assets.tag', 'Asset Tag')) ?></div>
                <div class="fw-semibold"><?= e($asset['tag']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small"><?= e(__('assets.status', 'Operational Status')) ?></div>
                <div class="fw-semibold">
                    <span class="badge text-bg-<?= ($asset['status'] ?? '') === 'broken' ? 'danger' : 'secondary' ?>">
                        <?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?>
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= e(route('assets.archive.store', ['id' => $asset['id']])) ?>" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12">
                    <label for="archive_reason" class="form-label"><?= e(__('assets.archive_reason', 'Archive Reason')) ?></label>
                    <textarea class="form-control" id="archive_reason" name="archive_reason" rows="4" required placeholder="<?= e(__('assets.archive_reason_placeholder', 'Example: device failed repair inspection and disposal was approved by management.')) ?>"></textarea>
                </div>
                <div class="col-12">
                    <label for="archive_documents" class="form-label"><?= e(__('assets.archive_documents', 'Archive Approval Documents')) ?></label>
                    <input type="file" class="form-control" id="archive_documents" name="archive_documents[]" multiple required>
                    <div class="form-text"><?= e(__('assets.archive_documents_help', 'Upload the approval letter, committee report, vendor statement, or any disposal authorization.')) ?></div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-danger"><?= e(__('assets.archive_submit', 'Archive Asset')) ?></button>
                <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
