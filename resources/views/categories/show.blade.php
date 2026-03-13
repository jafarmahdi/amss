<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($category['name']) ?></h2>
        <p class="text-muted mb-0"><?= e($category['description']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(route('categories.edit', ['id' => $category['id']])) ?>" class="btn btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <a href="<?= e(route('categories.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small"><?= e(__('nav.assets', 'Assets')) ?></div>
                <div class="fs-3 fw-semibold"><?= e((string) $category['count']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th><?= e(__('assets.name', 'Asset Name')) ?></th>
                    <th><?= e(__('common.branch', 'Branch')) ?></th>
                    <th><?= e(__('assets.assigned_to', 'Assigned To')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th><?= e(__('assets.purchase_date', 'Purchase Date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assets !== []): ?>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td>
                                <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="text-decoration-none"><?= e($asset['name']) ?></a>
                                <div class="small text-muted"><?= e($asset['tag']) ?></div>
                            </td>
                            <td><?= e($asset['branch']) ?></td>
                            <td><?= e($asset['assigned_to']) ?></td>
                            <td><?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?></td>
                            <td><?= e($asset['purchase_date'] !== '' ? $asset['purchase_date'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-muted"><?= e(__('categories.no_assets', 'No assets found in this category.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
