<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($category ? __('form.edit_category', 'Edit Category') : __('form.create_category', 'Create Category')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('categories.desc', 'Organize inventory into consistent operational groups.')) ?></p>
    </div>
    <a href="<?= e(route('categories.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($category ? route('categories.update', ['id' => $category['id']]) : route('categories.store')) ?>">
            <?php if ($category): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name"><?= e(__('form.name', 'Name')) ?></label>
                    <input class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) old('name', $category['name'] ?? '')) ?>" required>
                    <?php if (has_error('name')): ?><div class="invalid-feedback"><?= e((string) field_error('name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="count"><?= e(__('form.asset_count', 'Asset Count')) ?></label>
                    <input class="form-control" id="count" name="count" type="number" min="0" value="<?= e((string) ($category['count'] ?? 0)) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description"><?= e(__('form.description', 'Description')) ?></label>
                    <textarea class="form-control <?= has_error('description') ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" required><?= e((string) old('description', $category['description'] ?? '')) ?></textarea>
                    <?php if (has_error('description')): ?><div class="invalid-feedback"><?= e((string) field_error('description')) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($category ? __('actions.save', 'Save Changes') : __('form.create_category', 'Create Category')) ?></button>
                <a href="<?= e(route('categories.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
