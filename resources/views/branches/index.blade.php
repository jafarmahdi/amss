<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('branches.title', 'Branches & Offices')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('branches.desc', 'Overview of organizational locations and assigned asset counts.')) ?></p>
    </div>
    <a href="<?= e(route('branches.create')) ?>" class="btn btn-primary"><?= e(__('branches.add', 'Add Branch')) ?></a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('branches.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/branches">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('branches.search_placeholder', 'Branch name, type, or address')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="type"><?= e(__('common.type', 'Type')) ?></label>
                <select class="form-select" id="type" name="type">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($typeOptions as $typeOption): ?>
                        <option value="<?= e($typeOption) ?>" <?= $filters['type'] === $typeOption ? 'selected' : '' ?>><?= e($typeOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('branches.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12">
                <span class="surface-chip"><i class="bi bi-diagram-3"></i> <?= e((string) count($branches)) ?> <?= e(__('branches.visible_results', 'visible branches')) ?></span>
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
                    <th><?= e(__('common.type', 'Type')) ?></th>
                    <th><?= e(__('common.address', 'Address')) ?></th>
                    <th><?= e(__('nav.assets', 'Assets')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($branches !== []): ?>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><a href="<?= e(route('branches.show', ['id' => $branch['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($branch['name']) ?></a></td>
                            <td><?= e($branch['type']) ?></td>
                            <td><?= e($branch['address']) ?></td>
                            <td><?= e((string) $branch['assets']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(route('branches.show', ['id' => $branch['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                                <a href="<?= e(route('branches.edit', ['id' => $branch['id']])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="<?= e(route('branches.destroy', ['id' => $branch['id']])) ?>" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this branch?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted py-5"><?= e(__('branches.no_results', 'No branches matched the current filters.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
