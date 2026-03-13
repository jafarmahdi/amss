<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><?= e(__('nav.spare_parts', 'Spare Parts')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('spare_parts.desc', 'Track replacement stock, minimum levels, and compatibility notes.')) ?></p>
    </div>
    <a href="<?= e(route('spare-parts.create')) ?>" class="btn btn-primary"><?= e(__('spare_parts.add', 'Add Spare Part')) ?></a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.total', 'Total')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['total_items']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('spare_parts.total_quantity', 'Total Quantity')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['total_quantity']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('spare_parts.low_stock', 'Low Stock')) ?></div><div class="fs-3 fw-semibold text-danger"><?= e((string) $summary['low_stock']) ?></div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('spare-parts.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/spare-parts">
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('spare_parts.search_placeholder', 'Part, code, category, vendor, location')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="category"><?= e(__('assets.category', 'Category')) ?></label>
                <select class="form-select" id="category" name="category">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="location"><?= e(__('common.location', 'Location')) ?></label>
                <select class="form-select" id="location" name="location">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($locationOptions as $location): ?>
                        <option value="<?= e($location) ?>" <?= $filters['location'] === $location ? 'selected' : '' ?>><?= e($location) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="stock"><?= e(__('spare_parts.stock_state', 'Stock State')) ?></label>
                <select class="form-select" id="stock" name="stock">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <option value="low" <?= $filters['stock'] === 'low' ? 'selected' : '' ?>><?= e(__('spare_parts.low_stock', 'Low Stock')) ?></option>
                    <option value="healthy" <?= $filters['stock'] === 'healthy' ? 'selected' : '' ?>><?= e(__('spare_parts.healthy_stock', 'Healthy Stock')) ?></option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('spare-parts.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12">
                <span class="surface-chip"><i class="bi bi-tools"></i> <?= e((string) count($parts)) ?> <?= e(__('spare_parts.visible_results', 'visible spare parts')) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('common.code', 'Code')) ?></th>
                    <th><?= e(__('assets.category', 'Category')) ?></th>
                    <th><?= e(__('common.location', 'Location')) ?></th>
                    <th><?= e(__('spare_parts.quantity', 'Quantity')) ?></th>
                    <th><?= e(__('spare_parts.compatible_with', 'Compatible With')) ?></th>
                    <th class="text-end"><?= e(__('common.actions', 'Actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($parts !== []): ?>
                    <?php foreach ($parts as $part): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= e($part['name']) ?></span><?= ($part['low_stock'] ?? false) ? ' <span class="badge text-bg-danger">' . e(__('spare_parts.low_stock', 'Low Stock')) . '</span>' : '' ?></td>
                            <td><?= e($part['part_number']) ?></td>
                            <td><?= e($part['category']) ?></td>
                            <td><?= e($part['location']) ?></td>
                            <td><?= e((string) $part['quantity']) ?> / <?= e((string) $part['min_quantity']) ?></td>
                            <td><?= e($part['compatible_with']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(route('spare-parts.edit', ['id' => $part['id']])) ?>" class="btn btn-sm btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
                                <form method="POST" action="<?= e(route('spare-parts.destroy', ['id' => $part['id']])) ?>" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this spare part?')"><?= e(__('actions.delete', 'Delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-muted"><?= e(__('spare_parts.empty', 'No spare parts added yet.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
