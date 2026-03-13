<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($part ? __('spare_parts.edit', 'Edit Spare Part') : __('spare_parts.create', 'Create Spare Part')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('spare_parts.desc', 'Track replacement stock, minimum levels, and compatibility notes.')) ?></p>
    </div>
    <a href="<?= e(route('spare-parts.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($part ? route('spare-parts.update', ['id' => $part['id']]) : route('spare-parts.store')) ?>">
            <?php if ($part): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label"><?= e(__('common.name', 'Name')) ?></label><input type="text" name="name" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" value="<?= e((string) old('name', $part['name'] ?? '')) ?>"><?php if (has_error('name')): ?><div class="invalid-feedback"><?= e(field_error('name')) ?></div><?php endif; ?></div>
                <div class="col-md-6"><label class="form-label"><?= e(__('common.code', 'Code')) ?></label><input type="text" name="part_number" class="form-control" value="<?= e((string) old('part_number', $part['part_number'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= e(__('assets.category', 'Category')) ?></label><input type="text" name="category" class="form-control" value="<?= e((string) old('category', $part['category'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= e(__('licenses.vendor', 'Vendor')) ?></label><input type="text" name="vendor_name" class="form-control" value="<?= e((string) old('vendor_name', $part['vendor_name'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= e(__('common.location', 'Location')) ?></label><input type="text" name="location" class="form-control" value="<?= e((string) old('location', $part['location'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label"><?= e(__('spare_parts.quantity', 'Quantity')) ?></label><input type="number" min="0" name="quantity" class="form-control <?= has_error('quantity') ? 'is-invalid' : '' ?>" value="<?= e((string) old('quantity', (string) ($part['quantity'] ?? 0))) ?>"><?php if (has_error('quantity')): ?><div class="invalid-feedback"><?= e(field_error('quantity')) ?></div><?php endif; ?></div>
                <div class="col-md-3"><label class="form-label"><?= e(__('spare_parts.min_quantity', 'Minimum Quantity')) ?></label><input type="number" min="0" name="min_quantity" class="form-control <?= has_error('min_quantity') ? 'is-invalid' : '' ?>" value="<?= e((string) old('min_quantity', (string) ($part['min_quantity'] ?? 0))) ?>"><?php if (has_error('min_quantity')): ?><div class="invalid-feedback"><?= e(field_error('min_quantity')) ?></div><?php endif; ?></div>
                <div class="col-md-6"><label class="form-label"><?= e(__('spare_parts.compatible_with', 'Compatible With')) ?></label><input type="text" name="compatible_with" class="form-control" value="<?= e((string) old('compatible_with', $part['compatible_with'] ?? '')) ?>"></div>
                <div class="col-12"><label class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label><textarea name="notes" class="form-control" rows="4"><?= e((string) old('notes', $part['notes'] ?? '')) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($part ? __('actions.save', 'Save Changes') : __('spare_parts.add', 'Add Spare Part')) ?></button>
                <a href="<?= e(route('spare-parts.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
