<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e($branch['name']) ?></h2>
        <p class="text-muted mb-0"><?= e(__('branches.detail_desc', 'Branch assets, employees, and category distribution.')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(route('branches.edit', ['id' => $branch['id']])) ?>" class="btn btn-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <a href="<?= e(route('branches.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.type', 'Type')) ?></div><div class="fw-semibold"><?= e($branch['type']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.assets', 'Assets')) ?></div><div class="fw-semibold"><?= e((string) $branch['assets']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.employees', 'Employees')) ?></div><div class="fw-semibold"><?= e((string) $branch['employees']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('branches.broken_assets', 'Broken Assets')) ?></div><div class="fw-semibold"><?= e((string) $branch['broken_assets']) ?></div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="text-muted small"><?= e(__('common.address', 'Address')) ?></div>
        <div class="fw-semibold"><?= e($branch['address'] ?: '-') ?></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('nav.employees', 'Employees')) ?></h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th><?= e(__('common.name', 'Name')) ?></th><th><?= e(__('common.code', 'Code')) ?></th><th><?= e(__('employees.job_title', 'Job Title')) ?></th></tr></thead>
                    <tbody>
                        <?php if ($employees !== []): ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><a href="<?= e(route('employees.show', ['id' => $employee['id']])) ?>" class="text-decoration-none"><?= e($employee['name']) ?></a></td>
                                    <td><?= e($employee['employee_code']) ?></td>
                                    <td><?= e($employee['job_title']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-muted"><?= e(__('branches.no_employees', 'No employees found for this branch.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('branches.category_breakdown', 'Category Breakdown')) ?></h5></div>
            <div class="card-body">
                <?php if ($categories !== []): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($categories as $category): ?>
                            <div>
                                <div class="d-flex justify-content-between small mb-1"><span><?= e($category['category']) ?></span><span><?= e((string) $category['total']) ?></span></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar" style="width: <?= e((string) min(100, $branch['assets'] > 0 ? round(($category['total'] / $branch['assets']) * 100, 2) : 0)) ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('branches.no_categories', 'No assets available for category analysis.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><?= e(__('nav.assets', 'Assets')) ?></h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                    <th><?= e(__('assets.category', 'Category')) ?></th>
                    <th><?= e(__('assets.primary_employee', 'Primary Employee')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assets !== []): ?>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="text-decoration-none"><?= e($asset['name']) ?></a></td>
                            <td><?= e($asset['tag']) ?></td>
                            <td><?= e($asset['category']) ?></td>
                            <td><?= e($asset['assigned_to']) ?></td>
                            <td><?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted"><?= e(__('branches.no_assets', 'No assets found for this branch.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
