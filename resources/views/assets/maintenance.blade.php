<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('assets.maintenance_title', 'Preventive Maintenance')) ?></h2>
        <p class="text-muted mb-0"><?= e($asset['name']) ?> · <?= e($asset['tag']) ?></p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.schedule_maintenance', 'Schedule Maintenance')) ?></h5></div>
            <div class="card-body">
                <form method="POST" action="<?= e(route('assets.maintenance.store', ['id' => $asset['id']])) ?>" class="row g-3">
                    <input type="hidden" name="maintenance_mode" value="schedule">
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.maintenance_type', 'Maintenance Type')) ?></label>
                        <input type="text" name="maintenance_type" class="form-control <?= has_error('maintenance_type') ? 'is-invalid' : '' ?>" value="<?= e(old('maintenance_type', 'preventive')) ?>">
                        <?php if (has_error('maintenance_type')): ?><div class="invalid-feedback"><?= e(field_error('maintenance_type')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.scheduled_date', 'Scheduled Date')) ?></label>
                        <input type="date" name="scheduled_date" class="form-control <?= has_error('scheduled_date') ? 'is-invalid' : '' ?>" value="<?= e(old('scheduled_date', date('Y-m-d'))) ?>">
                        <?php if (has_error('scheduled_date')): ?><div class="invalid-feedback"><?= e(field_error('scheduled_date')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.technician_name', 'Technician')) ?></label>
                        <input type="text" name="technician_name" class="form-control" value="<?= e(old('technician_name')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.vendor', 'Vendor')) ?></label>
                        <input type="text" name="vendor_name" class="form-control" value="<?= e(old('vendor_name')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('assets.cost', 'Cost')) ?></label>
                        <input type="number" step="0.01" min="0" name="cost" class="form-control" value="<?= e(old('cost')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label>
                        <textarea name="notes" class="form-control" rows="3"><?= e(old('notes')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?= e(__('assets.schedule_maintenance', 'Schedule Maintenance')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.complete_maintenance', 'Complete Maintenance')) ?></h5></div>
            <div class="card-body">
                <?php if ($openMaintenance !== null): ?>
                    <form method="POST" action="<?= e(route('assets.maintenance.store', ['id' => $asset['id']])) ?>" class="row g-3">
                        <input type="hidden" name="maintenance_mode" value="complete">
                        <input type="hidden" name="maintenance_id" value="<?= e((string) $openMaintenance['id']) ?>">
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('assets.completed_date', 'Completed Date')) ?></label>
                            <input type="date" name="completed_date" class="form-control <?= has_error('completed_date') ? 'is-invalid' : '' ?>" value="<?= e(old('completed_date', date('Y-m-d'))) ?>">
                            <?php if (has_error('completed_date')): ?><div class="invalid-feedback"><?= e(field_error('completed_date')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('assets.next_service_date', 'Next Service Date')) ?></label>
                            <input type="date" name="next_service_date" class="form-control" value="<?= e(old('next_service_date')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('assets.technician_name', 'Technician')) ?></label>
                            <input type="text" name="technician_name" class="form-control" value="<?= e(old('technician_name', $openMaintenance['technician_name'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('assets.vendor', 'Vendor')) ?></label>
                            <input type="text" name="vendor_name" class="form-control" value="<?= e(old('vendor_name', $openMaintenance['vendor_name'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(__('assets.cost', 'Cost')) ?></label>
                            <input type="number" step="0.01" min="0" name="cost" class="form-control" value="<?= e((string) old('cost', (string) $openMaintenance['cost'])) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('assets.result_summary', 'Result Summary')) ?></label>
                            <textarea name="result_summary" class="form-control <?= has_error('result_summary') ? 'is-invalid' : '' ?>" rows="3"><?= e(old('result_summary')) ?></textarea>
                            <?php if (has_error('result_summary')): ?><div class="invalid-feedback"><?= e(field_error('result_summary')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label>
                            <textarea name="notes" class="form-control" rows="3"><?= e(old('notes', $openMaintenance['notes'])) ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success"><?= e(__('assets.complete_maintenance', 'Complete Maintenance')) ?></button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('assets.no_open_maintenance', 'There is no open maintenance record for this asset.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('assets.maintenance_history', 'Maintenance History')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('assets.maintenance_type', 'Maintenance Type')) ?></th>
                    <th><?= e(__('assets.scheduled_date', 'Scheduled Date')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th><?= e(__('assets.technician_name', 'Technician')) ?></th>
                    <th><?= e(__('assets.next_service_date', 'Next Service Date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records !== []): ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e($record['maintenance_type']) ?></td>
                            <td><?= e($record['scheduled_date']) ?><?= $record['completed_date'] !== '' ? ' / ' . e($record['completed_date']) : '' ?></td>
                            <td><?= e(__('status.' . $record['status'], ucfirst($record['status']))) ?></td>
                            <td><?= e($record['technician_name'] ?: '-') ?></td>
                            <td><?= e($record['next_service_date'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted"><?= e(__('assets.no_maintenance_records', 'No maintenance records yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
