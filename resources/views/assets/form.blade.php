<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($asset ? __('form.edit_asset', 'Edit Asset') : __('form.create_asset', 'Create Asset')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('assets.form_desc', 'Register the asset at purchase or receipt time and attach the supporting documents immediately.')) ?></p>
    </div>
    <a href="<?= e(route('assets.index')) ?>" class="btn btn-outline-secondary"><?= e(__('assets.back_to_assets', 'Back to Assets')) ?></a>
</div>

<div class="alert alert-info">
    <?= e(__('assets.form_help', 'Create the asset when the purchase is approved or the item is received. Upload invoice, warranty, quotation, and delivery documents in the same step.')) ?>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($asset ? route('assets.update', ['id' => $asset['id']]) : route('assets.store')) ?>" enctype="multipart/form-data">
            <?php if ($asset): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label"><?= e(__('assets.name', 'Asset Name')) ?></label>
                    <input type="text" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) old('name', $asset['name'] ?? '')) ?>" required>
                    <?php if (has_error('name')): ?><div class="invalid-feedback"><?= e((string) field_error('name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="request_id" class="form-label"><?= e(__('assets.request_id', 'Request ID')) ?></label>
                    <select class="form-select <?= has_error('request_id') ? 'is-invalid' : '' ?>" id="request_id" name="request_id" <?= $asset ? '' : 'required' ?>>
                        <option value=""><?= e(__('assets.select_request', 'Select request')) ?></option>
                        <?php foreach ($requestOptions as $requestOption): ?>
                            <option value="<?= e((string) $requestOption['id']) ?>" <?= ((string) old('request_id', $requestId ?? ($asset['request_id'] ?? '')) === (string) $requestOption['id']) ? 'selected' : '' ?>>
                                <?= e($requestOption['request_no'] . ' - ' . $requestOption['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('request_id')): ?><div class="invalid-feedback"><?= e((string) field_error('request_id')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e(__('assets.request_link_help', 'Link this asset to the originating request.')) ?></div>
                </div>
                <?php if (!$asset): ?>
                    <div class="col-md-2">
                        <label for="quantity" class="form-label"><?= e(__('storage.quantity', 'Quantity')) ?></label>
                        <input type="number" min="1" class="form-control <?= has_error('quantity') ? 'is-invalid' : '' ?>" id="quantity" name="quantity" value="<?= e((string) old('quantity', '1')) ?>" required>
                        <?php if (has_error('quantity')): ?><div class="invalid-feedback"><?= e((string) field_error('quantity')) ?></div><?php endif; ?>
                        <div class="form-text"><?= e(__('assets.batch_quantity_help', 'Use quantity to register multiple identical storage assets in one step.')) ?></div>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="tag_preview" class="form-label"><?= e(__('assets.tag', 'Asset Tag')) ?></label>
                    <input type="text" class="form-control" id="tag_preview" value="<?= e($asset['tag'] ?? __('assets.tag_auto', 'Auto generated')) ?>" readonly disabled>
                    <div class="form-text"><?= e(__('assets.tag_auto_help', 'The system creates the asset tag automatically and users cannot edit it.')) ?></div>
                </div>
                <div class="col-md-3">
                    <label for="serial_number" class="form-label"><?= e(__('assets.serial', 'Serial Number')) ?></label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?= e((string) old('serial_number', $asset['serial_number'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="brand" class="form-label"><?= e(__('common.brand', 'Brand')) ?></label>
                    <input type="text" class="form-control" id="brand" name="brand" value="<?= e((string) old('brand', $asset['brand'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="model" class="form-label"><?= e(__('common.model', 'Model')) ?></label>
                    <input type="text" class="form-control" id="model" name="model" value="<?= e((string) old('model', $asset['model'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="assigned_to" class="form-label"><?= e(__('assets.assigned_to', 'Assigned To')) ?></label>
                    <input type="text" class="form-control" id="assigned_to" name="assigned_to" list="employee-options" value="<?= e((string) old('assigned_to', (($asset['assigned_to'] ?? '') === __('assets.unassigned', 'Unassigned') ? '' : ($asset['assigned_to'] ?? ''))) ) ?>" placeholder="<?= e(__('assets.employee_search_placeholder', 'Type employee name to search')) ?>" autocomplete="off">
                    <datalist id="employee-options">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= e($employee['name']) ?>" label="<?= e(trim(($employee['employee_code'] ?? '') . ' ' . (($employee['department'] ?? '') !== '' ? '• ' . $employee['department'] : '') . (($employee['branch_name'] ?? '') !== '' ? ' • ' . $employee['branch_name'] : ''))) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text"><?= e(__('assets.employee_search_help', 'Start typing an employee name and select from the live search list.')) ?></div>
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label"><?= e(__('assets.category', 'Category')) ?></label>
                    <select class="form-select <?= has_error('category') ? 'is-invalid' : '' ?>" id="category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category) ?>" <?= ((string) old('category', $asset['category'] ?? '') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('category')): ?><div class="invalid-feedback"><?= e((string) field_error('category')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="location" class="form-label"><?= e(__('assets.location', 'Location')) ?></label>
                    <select class="form-select <?= has_error('location') ? 'is-invalid' : '' ?>" id="location" name="location" required>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e($branch) ?>" <?= ((string) old('location', $asset['location'] ?? '') === $branch) ? 'selected' : '' ?>><?= e($branch) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('location')): ?><div class="invalid-feedback"><?= e((string) field_error('location')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label"><?= e(__('assets.status', 'Operational Status')) ?></label>
                    <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= ((string) old('status', $asset['status'] ?? 'storage') === $status) ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('status')): ?><div class="invalid-feedback"><?= e((string) field_error('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="procurement_stage" class="form-label"><?= e(__('assets.stage', 'Procurement Stage')) ?></label>
                    <select class="form-select <?= has_error('procurement_stage') ? 'is-invalid' : '' ?>" id="procurement_stage" name="procurement_stage" required>
                        <?php foreach ($procurementStages as $stage): ?>
                            <option value="<?= e($stage) ?>" <?= ((string) old('procurement_stage', $asset['procurement_stage'] ?? 'received') === $stage) ? 'selected' : '' ?>><?= e(__('stage.' . $stage, ucfirst($stage))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('procurement_stage')): ?><div class="invalid-feedback"><?= e((string) field_error('procurement_stage')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="purchase_date" class="form-label"><?= e(__('assets.purchase_date', 'Purchase Date')) ?></label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= e((string) old('purchase_date', $asset['purchase_date'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="warranty_expiry" class="form-label"><?= e(__('assets.warranty', 'Warranty Expiry')) ?></label>
                    <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" value="<?= e((string) old('warranty_expiry', $asset['warranty_expiry'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label for="vendor_name" class="form-label"><?= e(__('assets.vendor_name', 'Vendor Name')) ?></label>
                    <input type="text" class="form-control" id="vendor_name" name="vendor_name" value="<?= e((string) old('vendor_name', $asset['vendor_name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label for="invoice_number" class="form-label"><?= e(__('assets.invoice_number', 'Invoice Number')) ?></label>
                    <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?= e((string) old('invoice_number', $asset['invoice_number'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label for="documents" class="form-label"><?= e(__('assets.documents', 'Documents')) ?></label>
                    <input type="file" class="form-control <?= has_error('documents') ? 'is-invalid' : '' ?>" id="documents" name="documents[]" multiple>
                    <?php if (has_error('documents')): ?><div class="invalid-feedback"><?= e((string) field_error('documents')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e(__('assets.documents_help', 'Upload invoice, warranty, quotation, delivery note, or serial sheet. You can add more files later when editing.')) ?></div>
                </div>
                <?php if ($asset && !empty($asset['documents'])): ?>
                    <div class="col-12">
                        <div class="small text-muted mb-2"><?= e(__('assets.existing_documents', 'Existing Documents')) ?></div>
                        <ul class="mb-0">
                            <?php foreach ($asset['documents'] as $document): ?>
                                <li><a href="<?= e(base_url() . '/' . ltrim($document['path'], '/')) ?>" target="_blank"><?= e($document['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <label for="notes" class="form-label"><?= e(__('assets.notes', 'Notes')) ?></label>
                    <textarea class="form-control" id="notes" name="notes" rows="4"><?= e((string) old('notes', $asset['notes'] ?? '')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($asset ? __('actions.save', 'Save Changes') : __('form.create_asset', 'Create Asset')) ?></button>
                <a href="<?= e(route('assets.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
