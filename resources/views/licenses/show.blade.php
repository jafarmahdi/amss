<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e($license['product_name']) ?></h2>
        <p class="text-muted mb-0"><?= e(__('licenses.detail_desc', 'License detail, assignment context, and renewal history.')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(route('licenses.edit', ['id' => $license['id']])) ?>" class="btn btn-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <a href="<?= e(route('licenses.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.type', 'Type')) ?></div><div class="fw-semibold"><?= e(__('licenses.type_' . $license['license_type'], ucfirst($license['license_type']))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.seats', 'Seats')) ?></div><div class="fw-semibold"><?= e((string) $license['seats_used']) ?> / <?= e((string) $license['seats_total']) ?></div><div class="small text-muted mt-1"><?= e(__('licenses.available_seats', 'Available Seats')) ?>: <?= e((string) ($license['available_seats'] ?? max(0, (int) $license['seats_total'] - (int) $license['seats_used']))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.expiry_date', 'Expiry Date')) ?></div><div class="fw-semibold"><?= e($license['expiry_date'] ?: '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.status', 'Status')) ?></div><div class="fw-semibold"><?= e(__('licenses.status_' . $license['status'], ucfirst($license['status']))) ?></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('licenses.vendor', 'Vendor')) ?></div><div class="fw-semibold"><?= e($license['vendor_name'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('licenses.purchase_date', 'Purchase Date')) ?></div><div class="fw-semibold"><?= e($license['purchase_date'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('licenses.assignment', 'Assignment')) ?></div><div class="fw-semibold"><?= e($license['employee_name'] ?: ($license['asset_name'] ?: '-')) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('licenses.key', 'License Key')) ?></div><div class="fw-semibold"><?= e($license['license_key'] ?: '-') ?></div></div>
                    <div class="col-12"><div class="text-muted small"><?= e(__('assets.notes', 'Notes')) ?></div><div class="fw-semibold"><?= e($license['notes'] ?: '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('licenses.renew', 'Renew License')) ?></h5></div>
            <div class="card-body">
                <form method="POST" action="<?= e(route('licenses.renew', ['id' => $license['id']])) ?>" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?= e(__('licenses.new_expiry_date', 'New Expiry Date')) ?></label>
                        <input type="date" name="new_expiry_date" class="form-control <?= has_error('new_expiry_date') ? 'is-invalid' : '' ?>" value="<?= e(old('new_expiry_date', $license['expiry_date'])) ?>">
                        <?php if (has_error('new_expiry_date')): ?><div class="invalid-feedback"><?= e(field_error('new_expiry_date')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= e(__('licenses.renewed_at', 'Renewed At')) ?></label>
                        <input type="date" name="renewed_at" class="form-control <?= has_error('renewed_at') ? 'is-invalid' : '' ?>" value="<?= e(old('renewed_at', date('Y-m-d'))) ?>">
                        <?php if (has_error('renewed_at')): ?><div class="invalid-feedback"><?= e(field_error('renewed_at')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= e(__('licenses.new_seats_total', 'New Total Seats')) ?></label>
                        <input type="number" min="1" name="new_seats_total" class="form-control <?= has_error('new_seats_total') ? 'is-invalid' : '' ?>" value="<?= e((string) old('new_seats_total', (string) $license['seats_total'])) ?>">
                        <?php if (has_error('new_seats_total')): ?><div class="invalid-feedback"><?= e(field_error('new_seats_total')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('licenses.new_license_key', 'New License Key')) ?></label>
                        <input type="text" name="new_license_key" class="form-control" value="<?= e(old('new_license_key')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('licenses.renewal_cost', 'Renewal Cost')) ?></label>
                        <input type="number" step="0.01" min="0" name="renewal_cost" class="form-control" value="<?= e(old('renewal_cost')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('licenses.renewal_notes', 'Renewal Notes')) ?></label>
                        <textarea name="renewal_notes" class="form-control" rows="3"><?= e(old('renewal_notes')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?= e(__('licenses.renew', 'Renew License')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('licenses.allocations', 'Allocations')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('requests.quantity', 'Quantity')) ?></th>
                    <th><?= e(__('requests.requested_for', 'Requested For')) ?></th>
                    <th><?= e(__('common.branch', 'Branch')) ?></th>
                    <th><?= e(__('requests.number', 'Request No')) ?></th>
                    <th><?= e(__('common.date', 'Date')) ?></th>
                    <th><?= e(__('assets.notes', 'Notes')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allocations !== []): ?>
                    <?php foreach ($allocations as $allocation): ?>
                        <tr>
                            <td><?= e((string) $allocation['quantity']) ?></td>
                            <td><?= e($allocation['employee_name'] ?: '-') ?></td>
                            <td><?= e($allocation['branch_name'] ?: '-') ?></td>
                            <td><?= e($allocation['request_no'] ?: '-') ?></td>
                            <td><?= e($allocation['allocated_at']) ?></td>
                            <td><?= e($allocation['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-muted"><?= e(__('licenses.no_allocations', 'No license allocations recorded yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('licenses.renewal_history', 'Renewal History')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('licenses.renewed_at', 'Renewed At')) ?></th>
                    <th><?= e(__('licenses.expiry_date', 'Expiry Date')) ?></th>
                    <th><?= e(__('licenses.new_seats_total', 'New Total Seats')) ?></th>
                    <th><?= e(__('licenses.renewal_cost', 'Renewal Cost')) ?></th>
                    <th><?= e(__('assets.notes', 'Notes')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($renewals !== []): ?>
                    <?php foreach ($renewals as $renewal): ?>
                        <tr>
                            <td><?= e($renewal['renewed_at']) ?></td>
                            <td><?= e(($renewal['previous_expiry_date'] ?: '-') . ' → ' . ($renewal['new_expiry_date'] ?: '-')) ?></td>
                            <td><?= e((string) $renewal['previous_seats_total']) ?> → <?= e((string) $renewal['new_seats_total']) ?></td>
                            <td><?= e(number_format($renewal['renewal_cost'], 2)) ?></td>
                            <td><?= e($renewal['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted"><?= e(__('licenses.no_renewals', 'No renewal history recorded yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
