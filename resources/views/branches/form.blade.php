<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($branch ? __('form.edit_branch', 'Edit Branch') : __('form.create_branch', 'Create Branch')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('branches.desc', 'Overview of organizational locations and assigned asset counts.')) ?></p>
    </div>
    <a href="<?= e(route('branches.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($branch ? route('branches.update', ['id' => $branch['id']]) : route('branches.store')) ?>">
            <?php if ($branch): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name"><?= e(__('form.name', 'Name')) ?></label>
                    <input class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) old('name', $branch['name'] ?? '')) ?>" required>
                    <?php if (has_error('name')): ?><div class="invalid-feedback"><?= e((string) field_error('name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="type"><?= e(__('common.type', 'Type')) ?></label>
                    <select class="form-select <?= has_error('type') ? 'is-invalid' : '' ?>" id="type" name="type" required>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= e($type) ?>" <?= ((string) old('type', $branch['type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('type')): ?><div class="invalid-feedback"><?= e((string) field_error('type')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="assets"><?= e(__('form.assets_count', 'Assets Count')) ?></label>
                    <input class="form-control" id="assets" name="assets" type="number" min="0" value="<?= e((string) ($branch['assets'] ?? 0)) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="address"><?= e(__('common.address', 'Address')) ?></label>
                    <textarea class="form-control <?= has_error('address') ? 'is-invalid' : '' ?>" id="address" name="address" rows="3" required><?= e((string) old('address', $branch['address'] ?? '')) ?></textarea>
                    <?php if (has_error('address')): ?><div class="invalid-feedback"><?= e((string) field_error('address')) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($branch ? __('actions.save', 'Save Changes') : __('form.create_branch', 'Create Branch')) ?></button>
                <a href="<?= e(route('branches.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
