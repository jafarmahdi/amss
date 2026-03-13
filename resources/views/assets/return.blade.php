<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('assets.return_title', 'Return Asset')) ?></h2>
        <p class="text-muted mb-0"><?= e($asset['name']) ?> (<?= e($asset['tag']) ?>)</p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e(route('assets.return.store', ['id' => $asset['id']])) ?>" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="branch_id"><?= e(__('movements.branch', 'Move To Branch')) ?></label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value=""><?= e(__('movements.keep_branch', 'Keep current branch')) ?></option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e((string) $branch['id']) ?>" <?= ((string) old('branch_id', (string) ($asset['branch_id'] ?? '')) === (string) $branch['id']) ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status"><?= e(__('assets.return_status', 'Return Status')) ?></label>
                    <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                        <?php foreach ($returnStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= ((string) old('status', 'storage') === $status) ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('status')): ?><div class="invalid-feedback"><?= e((string) field_error('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="return_documents"><?= e(__('assets.return_documents', 'Return Documents')) ?></label>
                    <input class="form-control <?= has_error('return_documents') ? 'is-invalid' : '' ?>" type="file" id="return_documents" name="return_documents[]" multiple>
                    <?php if (has_error('return_documents')): ?><div class="invalid-feedback"><?= e((string) field_error('return_documents')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e(__('assets.return_documents_help', 'Upload the handover, return receipt, or inspection document.')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="return_notes"><?= e(__('assets.return_notes', 'Return Notes')) ?></label>
                    <textarea class="form-control <?= has_error('return_notes') ? 'is-invalid' : '' ?>" id="return_notes" name="return_notes" rows="4" required><?= e((string) old('return_notes', '')) ?></textarea>
                    <?php if (has_error('return_notes')): ?><div class="invalid-feedback"><?= e((string) field_error('return_notes')) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e(__('assets.return_submit', 'Save Return')) ?></button>
                <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($assignments)): ?>
    <div class="card mt-3">
        <div class="card-body">
            <div class="text-muted small"><?= e(__('movements.current_employees', 'Current Employees')) ?></div>
            <ul class="mt-2 mb-0">
                <?php foreach ($assignments as $assignment): ?>
                    <li><?= e($assignment['name']) ?><?= !empty($assignment['department']) ? ' - ' . e($assignment['department']) : '' ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
