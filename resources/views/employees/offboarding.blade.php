<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('employees.offboarding_title', 'Employee Offboarding')) ?></h2>
        <p class="text-muted mb-0"><?= e($employee['name']) ?> · <?= e($employee['employee_code']) ?></p>
    </div>
    <a href="<?= e(route('employees.show', ['id' => $employee['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('employees.open_assets', 'Open Assets')) ?></div><div class="fs-3 fw-semibold"><?= e((string) count($summary['active_assets'])) ?></div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('employees.open_licenses', 'Open Licenses')) ?></div><div class="fs-3 fw-semibold"><?= e((string) count($summary['active_licenses'])) ?></div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('employees.offboarding_status', 'Offboarding Status')) ?></div><div class="fs-5 fw-semibold"><?= e($summary['can_complete'] ? __('employees.ready_to_offboard', 'Ready to complete') : __('employees.clear_assignments_first', 'Clear assignments first')) ?></div></div></div></div>
</div>

<?php if (!$summary['can_complete']): ?>
    <div class="alert alert-warning mb-3"><?= e(__('employees.offboarding_blocked', 'This employee still has assigned assets or licenses. Clear them before offboarding.')) ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.open_assets', 'Open Assets')) ?></h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('common.name', 'Name')) ?></th>
                            <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                            <th><?= e(__('common.status', 'Status')) ?></th>
                            <th><?= e(__('employees.assigned_date', 'Assigned Date')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($summary['active_assets'] !== []): ?>
                            <?php foreach ($summary['active_assets'] as $asset): ?>
                                <tr>
                                    <td><a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="text-decoration-none"><?= e($asset['name']) ?></a></td>
                                    <td><?= e($asset['tag']) ?></td>
                                    <td><?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?></td>
                                    <td><?= e($asset['assigned_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted"><?= e(__('employees.no_open_assets', 'No active asset assignments.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.open_licenses', 'Open Licenses')) ?></h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('licenses.product', 'Product')) ?></th>
                            <th><?= e(__('licenses.type', 'Type')) ?></th>
                            <th><?= e(__('common.status', 'Status')) ?></th>
                            <th><?= e(__('licenses.expiry_date', 'Expiry Date')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($summary['active_licenses'] !== []): ?>
                            <?php foreach ($summary['active_licenses'] as $license): ?>
                                <tr>
                                    <td><a href="<?= e(route('licenses.show', ['id' => $license['id']])) ?>" class="text-decoration-none"><?= e($license['product_name']) ?></a></td>
                                    <td><?= e(__('licenses.type_' . $license['license_type'], ucfirst($license['license_type']))) ?></td>
                                    <td><?= e(__('licenses.status_' . $license['status'], ucfirst($license['status']))) ?></td>
                                    <td><?= e($license['expiry_date'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted"><?= e(__('employees.no_open_licenses', 'No active licenses assigned.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.complete_offboarding', 'Complete Offboarding')) ?></h5></div>
    <div class="card-body">
        <form method="POST" action="<?= e(route('employees.offboarding.store', ['id' => $employee['id']])) ?>" class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><?= e(__('employees.offboarding_reason', 'Reason')) ?></label>
                <input type="text" name="reason" class="form-control <?= has_error('reason') ? 'is-invalid' : '' ?>" value="<?= e(old('reason')) ?>">
                <?php if (has_error('reason')): ?><div class="invalid-feedback"><?= e(field_error('reason')) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?= e(__('employees.offboarded_at', 'Offboarded Date')) ?></label>
                <input type="date" name="offboarded_at" class="form-control <?= has_error('offboarded_at') ? 'is-invalid' : '' ?>" value="<?= e(old('offboarded_at', date('Y-m-d'))) ?>">
                <?php if (has_error('offboarded_at')): ?><div class="invalid-feedback"><?= e(field_error('offboarded_at')) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label>
                <textarea name="notes" class="form-control" rows="4"><?= e(old('notes')) ?></textarea>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input <?= has_error('confirm_offboarding') ? 'is-invalid' : '' ?>" type="checkbox" value="yes" id="confirm_offboarding" name="confirm_offboarding" <?= old('confirm_offboarding') === 'yes' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="confirm_offboarding"><?= e(__('employees.confirm_offboarding', 'I confirm that all IT assets and licenses have been cleared for this employee.')) ?></label>
                    <?php if (has_error('confirm_offboarding')): ?><div class="invalid-feedback d-block"><?= e(field_error('confirm_offboarding')) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-danger" <?= $summary['can_complete'] ? '' : 'disabled' ?>><?= e(__('employees.complete_offboarding', 'Complete Offboarding')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.offboarding_history', 'Offboarding History')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.date', 'Date')) ?></th>
                    <th><?= e(__('employees.offboarding_reason', 'Reason')) ?></th>
                    <th><?= e(__('assets.notes', 'Notes')) ?></th>
                    <th><?= e(__('assets.prepared_by', 'Prepared By')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history !== []): ?>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= e($entry['offboarded_at']) ?></td>
                            <td><?= e($entry['reason']) ?></td>
                            <td><?= e($entry['notes']) ?></td>
                            <td><?= e($entry['completed_by']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-muted"><?= e(__('employees.no_offboarding_history', 'No offboarding records yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
