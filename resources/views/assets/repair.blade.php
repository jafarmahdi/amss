<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('assets.repair_title', 'Repair Workflow')) ?></h2>
        <p class="text-muted mb-0"><?= e($asset['name']) ?> (<?= e($asset['tag']) ?>)</p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<?php if ($openRepair === null): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= e(route('assets.repair.store', ['id' => $asset['id']])) ?>" enctype="multipart/form-data">
                <input type="hidden" name="repair_mode" value="send">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="vendor_name"><?= e(__('assets.repair_vendor', 'Repair Vendor / Service Center')) ?></label>
                        <input class="form-control <?= has_error('vendor_name') ? 'is-invalid' : '' ?>" id="vendor_name" name="vendor_name" value="<?= e((string) old('vendor_name', '')) ?>" required>
                        <?php if (has_error('vendor_name')): ?><div class="invalid-feedback"><?= e((string) field_error('vendor_name')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reference_number"><?= e(__('assets.repair_reference', 'Reference Number')) ?></label>
                        <input class="form-control" id="reference_number" name="reference_number" value="<?= e((string) old('reference_number', '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="repair_documents"><?= e(__('assets.repair_documents', 'Repair Documents')) ?></label>
                        <input class="form-control <?= has_error('repair_documents') ? 'is-invalid' : '' ?>" type="file" id="repair_documents" name="repair_documents[]" multiple>
                        <?php if (has_error('repair_documents')): ?><div class="invalid-feedback"><?= e((string) field_error('repair_documents')) ?></div><?php endif; ?>
                        <div class="form-text"><?= e(__('assets.repair_documents_help', 'Upload the repair request, vendor receipt, or diagnostic report.')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="repair_notes"><?= e(__('assets.repair_notes', 'Repair Notes')) ?></label>
                        <textarea class="form-control <?= has_error('repair_notes') ? 'is-invalid' : '' ?>" id="repair_notes" name="repair_notes" rows="4" required><?= e((string) old('repair_notes', '')) ?></textarea>
                        <?php if (has_error('repair_notes')): ?><div class="invalid-feedback"><?= e((string) field_error('repair_notes')) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-warning"><?= e(__('assets.repair_send_submit', 'Send To Repair')) ?></button>
                    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <?= e(__('assets.repair_open_info', 'This asset already has an open repair record. Complete it below.')) ?>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small"><?= e(__('assets.repair_vendor', 'Repair Vendor / Service Center')) ?></div><div class="fw-semibold"><?= e($openRepair['vendor_name']) ?></div></div>
                <div class="col-md-4"><div class="text-muted small"><?= e(__('assets.repair_reference', 'Reference Number')) ?></div><div class="fw-semibold"><?= e($openRepair['reference_number']) ?></div></div>
                <div class="col-md-4"><div class="text-muted small"><?= e(__('assets.repair_sent_at', 'Sent At')) ?></div><div class="fw-semibold"><?= e($openRepair['sent_at']) ?></div></div>
                <div class="col-12"><div class="text-muted small"><?= e(__('assets.repair_notes', 'Repair Notes')) ?></div><div class="fw-semibold"><?= e($openRepair['notes']) ?></div></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= e(route('assets.repair.store', ['id' => $asset['id']])) ?>" enctype="multipart/form-data">
                <input type="hidden" name="repair_mode" value="complete">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="outcome"><?= e(__('assets.repair_outcome', 'Repair Outcome')) ?></label>
                        <select class="form-select <?= has_error('outcome') ? 'is-invalid' : '' ?>" id="outcome" name="outcome" required>
                            <option value="repaired" <?= old('outcome', 'repaired') === 'repaired' ? 'selected' : '' ?>><?= e(__('assets.repair_outcome_repaired', 'Repaired')) ?></option>
                            <option value="unrepairable" <?= old('outcome', '') === 'unrepairable' ? 'selected' : '' ?>><?= e(__('assets.repair_outcome_unrepairable', 'Unrepairable')) ?></option>
                        </select>
                        <?php if (has_error('outcome')): ?><div class="invalid-feedback"><?= e((string) field_error('outcome')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="return_status"><?= e(__('assets.repair_return_status', 'Return Status After Repair')) ?></label>
                        <select class="form-select <?= has_error('return_status') ? 'is-invalid' : '' ?>" id="return_status" name="return_status">
                            <?php foreach ($completionStatuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= old('return_status', 'storage') === $status ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('return_status')): ?><div class="invalid-feedback"><?= e((string) field_error('return_status')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="repair_documents"><?= e(__('assets.repair_documents', 'Repair Documents')) ?></label>
                        <input class="form-control <?= has_error('repair_documents') ? 'is-invalid' : '' ?>" type="file" id="repair_documents" name="repair_documents[]" multiple>
                        <?php if (has_error('repair_documents')): ?><div class="invalid-feedback"><?= e((string) field_error('repair_documents')) ?></div><?php endif; ?>
                        <div class="form-text"><?= e(__('assets.repair_completion_documents_help', 'Upload the service report, completion note, or vendor decision.')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="completion_notes"><?= e(__('assets.repair_completion_notes', 'Completion Notes')) ?></label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="4"><?= e((string) old('completion_notes', '')) ?></textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><?= e(__('assets.repair_complete_submit', 'Complete Repair')) ?></button>
                    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($repairs !== []): ?>
    <div class="card mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('assets.repair_history', 'Repair History')) ?></h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3">
                <?php foreach ($repairs as $repair): ?>
                    <div class="border rounded-4 p-3">
                        <div class="fw-semibold"><?= e($repair['vendor_name']) ?><?= $repair['reference_number'] !== '' ? ' · ' . e($repair['reference_number']) : '' ?></div>
                        <div class="small text-muted"><?= e($repair['sent_at']) ?><?= $repair['completed_at'] !== '' ? ' → ' . e($repair['completed_at']) : '' ?></div>
                        <div class="small mt-1"><?= e(__('assets.repair_outcome', 'Repair Outcome')) ?>: <?= e(__('repair.' . $repair['outcome'], $repair['outcome'])) ?></div>
                        <?php if ($repair['notes'] !== ''): ?><div class="small mt-1"><?= e($repair['notes']) ?></div><?php endif; ?>
                        <?php if ($repair['completion_notes'] !== ''): ?><div class="small mt-1"><?= e($repair['completion_notes']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
