<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><?= e(__('licenses.title', 'License Manager')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('licenses.desc', 'Track software licenses, seat usage, assignments, and renewal dates.')) ?></p>
    </div>
    <a href="<?= e(route('licenses.create')) ?>" class="btn btn-primary"><?= e(__('licenses.add', 'Add License')) ?></a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.total', 'Total')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['total']) ?></div></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.active', 'Active')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['active']) ?></div></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.expiring', 'Expiring')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['expiring']) ?></div></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.expired', 'Expired')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['expired']) ?></div></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('licenses.overused', 'Overused')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['overused']) ?></div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('licenses.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/licenses">
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('licenses.search_placeholder', 'Product, vendor, key, employee, asset')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="type"><?= e(__('licenses.type', 'Type')) ?></label>
                <select class="form-select" id="type" name="type">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($typeOptions as $type): ?>
                        <option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e(__('licenses.type_' . $type, ucfirst($type))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="status"><?= e(__('common.status', 'Status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(__('licenses.status_' . $status, ucfirst($status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="availability"><?= e(__('licenses.capacity', 'Capacity')) ?></label>
                <select class="form-select" id="availability" name="availability">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <option value="available" <?= $filters['availability'] === 'available' ? 'selected' : '' ?>><?= e(__('licenses.available_only', 'Has available seats')) ?></option>
                    <option value="full" <?= $filters['availability'] === 'full' ? 'selected' : '' ?>><?= e(__('licenses.full_only', 'No seats available')) ?></option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('licenses.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12">
                <span class="surface-chip"><i class="bi bi-key"></i> <?= e((string) count($licenses)) ?> <?= e(__('licenses.visible_results', 'visible licenses')) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th><?= e(__('licenses.product', 'Product')) ?></th>
                    <th><?= e(__('licenses.type', 'Type')) ?></th>
                    <th><?= e(__('licenses.seats', 'Seats')) ?></th>
                    <th><?= e(__('licenses.assignment', 'Assignment')) ?></th>
                    <th><?= e(__('licenses.expiry_date', 'Expiry Date')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($licenses !== []): ?>
                    <?php foreach ($licenses as $license): ?>
                        <?php
                        $seatPercent = $license['seats_total'] > 0 ? min(100, round(($license['seats_used'] / $license['seats_total']) * 100, 2)) : 0;
                        $isOverused = $license['seats_used'] > $license['seats_total'];
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><a href="<?= e(route('licenses.show', ['id' => $license['id']])) ?>" class="text-decoration-none"><?= e($license['product_name']) ?></a></div>
                                <div class="small text-muted"><?= e($license['vendor_name']) ?></div>
                            </td>
                            <td><?= e(__('licenses.type_' . $license['license_type'], ucfirst($license['license_type']))) ?></td>
                            <td style="min-width: 170px;">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= e((string) $license['seats_used']) ?> / <?= e((string) $license['seats_total']) ?></span>
                                    <?php if ($isOverused): ?><span class="text-danger"><?= e(__('licenses.overused', 'Overused')) ?></span><?php endif; ?>
                                </div>
                                <div class="small text-muted mb-1"><?= e(__('licenses.available_seats', 'Available Seats')) ?>: <?= e((string) ($license['available_seats'] ?? max(0, (int) $license['seats_total'] - (int) $license['seats_used']))) ?></div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= $isOverused ? 'bg-danger' : '' ?>" style="width: <?= e((string) $seatPercent) ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($license['employee_name'] !== ''): ?>
                                    <div><?= e($license['employee_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($license['asset_name'] !== ''): ?>
                                    <div class="small text-muted"><?= e($license['asset_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($license['employee_name'] === '' && $license['asset_name'] === ''): ?>
                                    <span class="text-muted"><?= e(__('licenses.unassigned', 'Unassigned')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($license['expiry_date'] !== '' ? $license['expiry_date'] : '-') ?>
                                <?php if ($license['days_left'] !== null): ?>
                                    <div class="small text-muted"><?= e((string) $license['days_left']) ?> <?= e(__('notifications.days_left', 'days left')) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge text-bg-light"><?= e(__('licenses.status_' . $license['status'], ucfirst($license['status']))) ?></span></td>
                            <td class="text-end">
                                <a href="<?= e(route('licenses.show', ['id' => $license['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                                <a href="<?= e(route('licenses.edit', ['id' => $license['id']])) ?>" class="btn btn-sm btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
                                <form method="POST" action="<?= e(route('licenses.destroy', ['id' => $license['id']])) ?>" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this license?')"><?= e(__('actions.delete', 'Delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-muted"><?= e(__('licenses.empty', 'No licenses added yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
