<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('categories.title', 'Asset Categories')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('categories.desc', 'Organize inventory into consistent operational groups.')) ?></p>
    </div>
    <a href="<?= e(route('categories.create')) ?>" class="btn btn-primary"><?= e(__('categories.add', 'Add Category')) ?></a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('categories.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/categories">
            <div class="col-md-7">
                <label class="form-label fw-semibold" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('categories.search_placeholder', 'Category name or description')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="usage"><?= e(__('categories.usage', 'Usage')) ?></label>
                <select class="form-select" id="usage" name="usage">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <option value="used" <?= $filters['usage'] === 'used' ? 'selected' : '' ?>><?= e(__('categories.used_only', 'Used categories')) ?></option>
                    <option value="empty" <?= $filters['usage'] === 'empty' ? 'selected' : '' ?>><?= e(__('categories.empty_only', 'Empty categories')) ?></option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('categories.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12">
                <span class="surface-chip"><i class="bi bi-grid"></i> <?= e((string) count($categories)) ?> <?= e(__('categories.visible_results', 'visible categories')) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <?php if ($categories !== []): ?>
        <?php foreach ($categories as $category): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><a href="<?= e(route('categories.show', ['id' => $category['id']])) ?>" class="text-decoration-none"><?= e($category['name']) ?></a></h5>
                            <span class="badge text-bg-light"><?= e((string) $category['count']) ?> <?= e(__('nav.assets', 'assets')) ?></span>
                        </div>
                        <p class="card-text text-muted mb-3"><?= e($category['description']) ?></p>
                        <div class="d-flex gap-2">
                            <a href="<?= e(route('categories.show', ['id' => $category['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                            <a href="<?= e(route('categories.edit', ['id' => $category['id']])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="<?= e(route('categories.destroy', ['id' => $category['id']])) ?>">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted py-5"><?= e(__('categories.no_results', 'No categories matched the current filters.')) ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
