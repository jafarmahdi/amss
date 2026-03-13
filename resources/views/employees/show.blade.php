<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e($employee['name']) ?></h2>
        <p class="text-muted mb-0"><?= e(__('employees.detail_desc', 'Employee profile, assigned assets, licenses, and handover history.')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (($employee['status'] ?? 'active') === 'active'): ?>
            <a href="<?= e(route('employees.offboarding', ['id' => $employee['id']])) ?>" class="btn btn-outline-danger"><?= e(__('employees.start_offboarding', 'Start Offboarding')) ?></a>
        <?php endif; ?>
        <a href="<?= e(route('employees.edit', ['id' => $employee['id']])) ?>" class="btn btn-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <a href="<?= e(route('employees.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.code', 'Code')) ?></div><div class="fw-semibold"><?= e($employee['employee_code']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('employees.company_email', 'Company Email')) ?></div><div class="fw-semibold"><?= e($employee['company_email'] ?: '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('employees.fingerprint_id', 'Fingerprint ID')) ?></div><div class="fw-semibold"><?= e($employee['fingerprint_id'] ?: '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.status', 'Status')) ?></div><div class="fw-semibold"><?= e(__('status.' . $employee['status'], ucfirst($employee['status']))) ?></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('common.branch', 'Branch')) ?></div><div class="fw-semibold"><?= e($employee['branch_name'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('employees.job_title', 'Job Title')) ?></div><div class="fw-semibold"><?= e($employee['job_title'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('employees.company_name', 'Company')) ?></div><div class="fw-semibold"><?= e($employee['company_name'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('employees.project_name', 'Project')) ?></div><div class="fw-semibold"><?= e($employee['project_name'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('employees.phone', 'Phone')) ?></div><div class="fw-semibold"><?= e($employee['phone'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('employees.appointment_order', 'Appointment Order')) ?></div><div class="fw-semibold">
                        <?php if ($employee['appointment_order_path'] !== ''): ?>
                            <a href="<?= e(base_url() . '/' . ltrim($employee['appointment_order_path'], '/')) ?>" target="_blank"><?= e($employee['appointment_order_name']) ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.assigned_licenses', 'Assigned Licenses')) ?></h5></div>
            <div class="card-body">
                <?php if ($licenses !== []): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($licenses as $license): ?>
                            <div class="border rounded-4 p-3">
                                <div class="fw-semibold"><a href="<?= e(route('licenses.show', ['id' => $license['id']])) ?>" class="text-decoration-none"><?= e($license['product_name']) ?></a></div>
                                <div class="small text-muted"><?= e($license['vendor_name']) ?> · <?= e($license['expiry_date'] ?: '-') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('employees.no_licenses', 'No licenses assigned to this employee.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.assigned_assets', 'Assigned Assets')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                    <th><?= e(__('assets.category', 'Category')) ?></th>
                    <th><?= e(__('common.branch', 'Branch')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th><?= e(__('employees.assigned_date', 'Assigned Date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assignments !== []): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><a href="<?= e(route('assets.show', ['id' => $assignment['id']])) ?>" class="text-decoration-none"><?= e($assignment['name']) ?></a></td>
                            <td><?= e($assignment['tag']) ?></td>
                            <td><?= e($assignment['category']) ?></td>
                            <td><?= e($assignment['branch_name']) ?></td>
                            <td><?= e(__('status.' . $assignment['status'], ucfirst($assignment['status']))) ?></td>
                            <td><?= e($assignment['assigned_at']) ?><?= $assignment['returned_at'] !== '' ? ' / ' . e($assignment['returned_at']) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-muted"><?= e(__('employees.no_assets', 'No asset assignments found for this employee.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('employees.handover_history', 'Handover History')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                    <th><?= e(__('assets.handover_type', 'Type')) ?></th>
                    <th><?= e(__('common.date', 'Date')) ?></th>
                    <th><?= e(__('assets.notes', 'Notes')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($handovers !== []): ?>
                    <?php foreach ($handovers as $handover): ?>
                        <tr>
                            <td><a href="<?= e(route('assets.show', ['id' => $handover['asset_id']])) ?>" class="text-decoration-none"><?= e($handover['asset_name']) ?></a></td>
                            <td><?= e($handover['tag']) ?></td>
                            <td><?= e(__('handover.' . $handover['handover_type'], ucfirst($handover['handover_type']))) ?></td>
                            <td><?= e($handover['handover_date']) ?></td>
                            <td><?= e($handover['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted"><?= e(__('employees.no_handovers', 'No handover records found for this employee.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($offboardingHistory !== []): ?>
    <div class="card mt-3">
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
                    <?php foreach ($offboardingHistory as $entry): ?>
                        <tr>
                            <td><?= e($entry['offboarded_at']) ?></td>
                            <td><?= e($entry['reason']) ?></td>
                            <td><?= e($entry['notes']) ?></td>
                            <td><?= e($entry['completed_by']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
