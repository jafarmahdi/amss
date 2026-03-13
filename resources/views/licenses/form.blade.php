<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($license ? __('licenses.edit', 'Edit License') : __('licenses.create', 'Create License')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('licenses.desc', 'Track software licenses, seat usage, assignments, and renewal dates.')) ?></p>
    </div>
    <a href="<?= e(route('licenses.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($license ? route('licenses.update', ['id' => $license['id']]) : route('licenses.store')) ?>">
            <?php if ($license): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="product_name"><?= e(__('licenses.product', 'Product')) ?></label>
                    <input class="form-control <?= has_error('product_name') ? 'is-invalid' : '' ?>" id="product_name" name="product_name" value="<?= e((string) old('product_name', $license['product_name'] ?? '')) ?>" required>
                    <?php if (has_error('product_name')): ?><div class="invalid-feedback"><?= e((string) field_error('product_name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="vendor_name"><?= e(__('licenses.vendor', 'Vendor')) ?></label>
                    <input class="form-control" id="vendor_name" name="vendor_name" value="<?= e((string) old('vendor_name', $license['vendor_name'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="license_type"><?= e(__('licenses.type', 'Type')) ?></label>
                    <select class="form-select <?= has_error('license_type') ? 'is-invalid' : '' ?>" id="license_type" name="license_type" required>
                        <?php foreach ($licenseTypes as $type): ?>
                            <option value="<?= e($type) ?>" <?= (string) old('license_type', $license['license_type'] ?? 'subscription') === $type ? 'selected' : '' ?>><?= e(__('licenses.type_' . $type, ucfirst($type))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('license_type')): ?><div class="invalid-feedback"><?= e((string) field_error('license_type')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="license_key"><?= e(__('licenses.key', 'License Key')) ?></label>
                    <input class="form-control" id="license_key" name="license_key" value="<?= e((string) old('license_key', $license['license_key'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="seats_total"><?= e(__('licenses.total_seats', 'Total Seats')) ?></label>
                    <input class="form-control <?= has_error('seats_total') ? 'is-invalid' : '' ?>" id="seats_total" name="seats_total" type="number" min="1" value="<?= e((string) old('seats_total', $license['seats_total'] ?? 1)) ?>" required>
                    <?php if (has_error('seats_total')): ?><div class="invalid-feedback"><?= e((string) field_error('seats_total')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="seats_used"><?= e(__('licenses.used_seats', 'Used Seats')) ?></label>
                    <input class="form-control <?= has_error('seats_used') ? 'is-invalid' : '' ?>" id="seats_used" name="seats_used" type="number" min="0" value="<?= e((string) old('seats_used', $license['seats_used'] ?? 0)) ?>" required>
                    <?php if (has_error('seats_used')): ?><div class="invalid-feedback"><?= e((string) field_error('seats_used')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status"><?= e(__('common.status', 'Status')) ?></label>
                    <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= e($status) ?>" <?= (string) old('status', $license['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= e(__('licenses.status_' . $status, ucfirst($status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('status')): ?><div class="invalid-feedback"><?= e((string) field_error('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="purchase_date"><?= e(__('assets.purchase_date', 'Purchase Date')) ?></label>
                    <input class="form-control" id="purchase_date" name="purchase_date" type="date" value="<?= e((string) old('purchase_date', $license['purchase_date'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="expiry_date"><?= e(__('licenses.expiry_date', 'Expiry Date')) ?></label>
                    <input class="form-control" id="expiry_date" name="expiry_date" type="date" value="<?= e((string) old('expiry_date', $license['expiry_date'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="assigned_asset_id"><?= e(__('licenses.asset_assignment', 'Assigned Asset')) ?></label>
                    <select class="form-select" id="assigned_asset_id" name="assigned_asset_id">
                        <option value=""><?= e(__('licenses.unassigned', 'Unassigned')) ?></option>
                        <?php foreach ($assets as $asset): ?>
                            <option value="<?= e((string) $asset['id']) ?>" <?= (string) old('assigned_asset_id', $license['assigned_asset_id'] ?? '') === (string) $asset['id'] ? 'selected' : '' ?>><?= e($asset['name'] . ' (' . $asset['tag'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="assigned_employee_id"><?= e(__('licenses.employee_assignment', 'Assigned Employee')) ?></label>
                    <select class="form-select" id="assigned_employee_id" name="assigned_employee_id">
                        <option value=""><?= e(__('licenses.unassigned', 'Unassigned')) ?></option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= e((string) $employee['id']) ?>" <?= (string) old('assigned_employee_id', $license['assigned_employee_id'] ?? '') === (string) $employee['id'] ? 'selected' : '' ?>><?= e($employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes"><?= e(__('form.description', 'Description')) ?></label>
                    <textarea class="form-control" id="notes" name="notes" rows="4"><?= e((string) old('notes', $license['notes'] ?? '')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($license ? __('actions.save', 'Save Changes') : __('licenses.create', 'Create License')) ?></button>
                <a href="<?= e(route('licenses.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
