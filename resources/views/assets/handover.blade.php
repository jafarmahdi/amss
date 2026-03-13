<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('assets.handover_title', 'Asset Handover')) ?></h2>
        <p class="text-muted mb-0"><?= e($asset['name']) ?> · <?= e($asset['tag']) ?></p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.handover_create', 'Create Handover')) ?></h5></div>
            <div class="card-body">
                <form method="POST" action="<?= e(route('assets.handover.store', ['id' => $asset['id']])) ?>" class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><?= e(__('nav.employees', 'Employees')) ?></label>
                        <select name="employee_id" class="form-select <?= has_error('employee_id') ? 'is-invalid' : '' ?>">
                            <option value=""><?= e(__('common.select', 'Select')) ?></option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= e((string) $employee['id']) ?>" <?= (string) old('employee_id') === (string) $employee['id'] ? 'selected' : '' ?>>
                                    <?= e($employee['name']) ?> · <?= e($employee['employee_code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('employee_id')): ?><div class="invalid-feedback"><?= e(field_error('employee_id')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.handover_type', 'Type')) ?></label>
                        <select name="handover_type" class="form-select <?= has_error('handover_type') ? 'is-invalid' : '' ?>">
                            <option value="issue" <?= old('handover_type', 'issue') === 'issue' ? 'selected' : '' ?>><?= e(__('handover.issue', 'Issue')) ?></option>
                            <option value="return" <?= old('handover_type') === 'return' ? 'selected' : '' ?>><?= e(__('handover.return', 'Return')) ?></option>
                        </select>
                        <?php if (has_error('handover_type')): ?><div class="invalid-feedback"><?= e(field_error('handover_type')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('common.date', 'Date')) ?></label>
                        <input type="date" name="handover_date" class="form-control <?= has_error('handover_date') ? 'is-invalid' : '' ?>" value="<?= e(old('handover_date', date('Y-m-d'))) ?>">
                        <?php if (has_error('handover_date')): ?><div class="invalid-feedback"><?= e(field_error('handover_date')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label>
                        <textarea name="notes" class="form-control" rows="4"><?= e(old('notes')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?= e(__('assets.handover_button', 'Handover Form')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.current_assignments', 'Current Assignments')) ?></h5></div>
            <div class="card-body">
                <?php if ($assignments !== []): ?>
                    <ul class="mb-0">
                        <?php foreach ($assignments as $assignment): ?>
                            <li><?= e($assignment['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('assets.no_assignments', 'No employees currently assigned.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.handover_history', 'Handover History')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('common.code', 'Code')) ?></th>
                    <th><?= e(__('assets.handover_type', 'Type')) ?></th>
                    <th><?= e(__('common.date', 'Date')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($handovers !== []): ?>
                    <?php foreach ($handovers as $handover): ?>
                        <tr>
                            <td><?= e($handover['employee_name']) ?></td>
                            <td><?= e($handover['employee_code']) ?></td>
                            <td><?= e(__('handover.' . $handover['handover_type'], ucfirst($handover['handover_type']))) ?></td>
                            <td><?= e($handover['handover_date']) ?></td>
                            <td class="text-end"><a href="<?= e(route('assets.handover.print', ['id' => $handover['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted"><?= e(__('assets.no_handovers', 'No handover forms created yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
