<?php
$lineItems = array_values((array) old('items', $requestItems));
$defaultScenario = (string) ($workflowDefaults['default_scenario'] ?? 'general');
$defaultUrgency = (string) ($workflowDefaults['default_urgency'] ?? 'normal');
if ($lineItems === []) {
    $lineItems = [[
        'item_type' => 'asset',
        'item_name' => '',
        'category_id' => null,
        'quantity' => 1,
        'estimated_unit_cost' => null,
        'fulfillment_preference' => 'either',
        'assignment_target' => 'employee',
        'specification' => '',
        'notes' => '',
    ]];
}
?>

<datalist id="request-item-options-asset">
    <?php foreach ($assetCatalog as $assetOption): ?>
        <option value="<?= e((string) ($assetOption['name'] ?? '')) ?>" label="<?= e(trim((string) ($assetOption['tag'] ?? '') . ' | ' . (string) ($assetOption['category'] ?? '') . ' | ' . (string) ($assetOption['location'] ?? ''))) ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="request-item-options-spare_part">
    <?php foreach ($sparePartCatalog as $partOption): ?>
        <option value="<?= e((string) ($partOption['name'] ?? '')) ?>" label="<?= e(trim((string) ($partOption['part_number'] ?? '') . ' | ' . (string) ($partOption['category'] ?? '') . ' | ' . __('storage.quantity', 'Quantity') . ': ' . (string) ($partOption['quantity'] ?? 0))) ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="request-item-options-license">
    <?php foreach ($licenseCatalog as $licenseOption): ?>
        <option value="<?= e((string) ($licenseOption['product_name'] ?? '')) ?>" label="<?= e(trim((string) ($licenseOption['vendor_name'] ?? '') . ' | ' . __('requests.available_seats', 'Available Seats') . ': ' . (string) max(0, (int) ($licenseOption['seats_total'] ?? 0) - (int) ($licenseOption['seats_used'] ?? 0)))) ?>"></option>
    <?php endforeach; ?>
</datalist>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($request ? __('requests.edit', 'Edit Request') : __('requests.create', 'Create Request')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('requests.form_desc', 'Save as draft first, then route through IT, IT Manager, and Finance approval. IT Manager can also fulfill directly from storage.')) ?></p>
    </div>
    <a href="<?= e(route('requests.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($request ? route('requests.update', ['id' => $request['id']]) : route('requests.store')) ?>">
            <?php if ($request): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="title"><?= e(__('requests.title_field', 'Request Title')) ?></label>
                    <input class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= e((string) old('title', $request['title'] ?? '')) ?>" required>
                    <?php if (has_error('title')): ?><div class="invalid-feedback"><?= e((string) field_error('title')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="scenario"><?= e(__('requests.scenario', 'Scenario')) ?></label>
                    <select class="form-select <?= has_error('scenario') ? 'is-invalid' : '' ?>" id="scenario" name="scenario" required>
                        <?php foreach ($scenarios as $scenarioKey => $scenarioLabel): ?>
                            <option value="<?= e($scenarioKey) ?>" <?= ((string) old('scenario', $request['scenario'] ?? $defaultScenario) === $scenarioKey) ? 'selected' : '' ?>><?= e($scenarioLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('scenario')): ?><div class="invalid-feedback"><?= e((string) field_error('scenario')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="urgency"><?= e(__('requests.urgency', 'Urgency')) ?></label>
                    <select class="form-select <?= has_error('urgency') ? 'is-invalid' : '' ?>" id="urgency" name="urgency" required>
                        <?php foreach ($urgencies as $urgency): ?>
                            <option value="<?= e($urgency) ?>" <?= ((string) old('urgency', $request['urgency'] ?? $defaultUrgency) === $urgency) ? 'selected' : '' ?>><?= e(\App\Support\RequestWorkflow::urgencyLabel($urgency)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('urgency')): ?><div class="invalid-feedback"><?= e((string) field_error('urgency')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="requested_for_employee_id"><?= e(__('requests.requested_for', 'Requested For')) ?></label>
                    <select class="form-select <?= has_error('requested_for_employee_id') ? 'is-invalid' : '' ?>" id="requested_for_employee_id" name="requested_for_employee_id">
                        <option value=""><?= e(__('requests.select_employee', 'Select employee')) ?></option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= e((string) $employee['id']) ?>" <?= ((string) old('requested_for_employee_id', $request['requested_for_employee_id'] ?? '') === (string) $employee['id']) ? 'selected' : '' ?>>
                                <?= e($employee['name'] . ' (' . $employee['employee_code'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('requested_for_employee_id')): ?><div class="invalid-feedback"><?= e((string) field_error('requested_for_employee_id')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e(__('requests.requested_for_help', 'Use this for employee onboarding or direct employee assignment. Leave empty for branch deployment.')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="branch_id"><?= e(__('common.branch', 'Branch')) ?></label>
                    <select class="form-select <?= has_error('branch_id') ? 'is-invalid' : '' ?>" id="branch_id" name="branch_id">
                        <option value=""><?= e(__('form.no_branch', 'No Branch')) ?></option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e((string) $branch['id']) ?>" <?= ((string) old('branch_id', $request['branch_id'] ?? '') === (string) $branch['id']) ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('branch_id')): ?><div class="invalid-feedback"><?= e((string) field_error('branch_id')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="needed_by_date"><?= e(__('requests.needed_by', 'Needed By')) ?></label>
                    <input type="date" class="form-control" id="needed_by_date" name="needed_by_date" value="<?= e((string) old('needed_by_date', $request['needed_by_date'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="asset_specification"><?= e(__('requests.scope_notes', 'Request Scope / Notes')) ?></label>
                    <textarea class="form-control" id="asset_specification" name="asset_specification" rows="3"><?= e((string) old('asset_specification', $request['asset_specification'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="justification"><?= e(__('requests.justification', 'Business Justification')) ?></label>
                    <textarea class="form-control <?= has_error('justification') ? 'is-invalid' : '' ?>" id="justification" name="justification" rows="4" required><?= e((string) old('justification', $request['justification'] ?? '')) ?></textarea>
                    <?php if (has_error('justification')): ?><div class="invalid-feedback"><?= e((string) field_error('justification')) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1"><?= e(__('requests.items', 'Request Items')) ?></h5>
                    <p class="text-muted mb-0"><?= e(__('requests.items_desc', 'Add all assets, spare parts, and licenses needed under this single request.')) ?></p>
                </div>
                <button type="button" class="btn btn-outline-primary" id="add-request-item"><?= e(__('requests.add_item', 'Add Item')) ?></button>
            </div>

            <?php if (has_error('items')): ?>
                <div class="alert alert-danger py-2"><?= e((string) field_error('items')) ?></div>
            <?php endif; ?>

            <div id="request-items" class="d-grid gap-3">
                <?php foreach ($lineItems as $index => $item): ?>
                    <div class="card request-item-row">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><?= e(__('requests.item', 'Item')) ?> #<?= e((string) ($index + 1)) ?></h6>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-request-item"><?= e(__('actions.delete', 'Delete')) ?></button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label"><?= e(__('requests.item_type', 'Item Type')) ?></label>
                                    <select class="form-select request-item-type-select" name="items[<?= e((string) $index) ?>][item_type]">
                                        <?php foreach ($itemTypes as $itemTypeKey => $itemTypeLabel): ?>
                                            <option value="<?= e($itemTypeKey) ?>" <?= ((string) ($item['item_type'] ?? 'asset') === $itemTypeKey) ? 'selected' : '' ?>><?= e($itemTypeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?= e(__('requests.item_name', 'Item Name')) ?></label>
                                    <input class="form-control request-item-name-input" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
                                    <div class="form-text"><?= e(__('requests.item_name_help', 'Start typing or select an existing item from system stock.')) ?></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= e(__('assets.category', 'Category')) ?></label>
                                    <select class="form-select request-item-category-select" name="items[<?= e((string) $index) ?>][category_id]">
                                        <option value=""><?= e(__('common.all', 'All')) ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e((string) $category['id']) ?>" <?= ((string) ($item['category_id'] ?? '') === (string) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label"><?= e(__('requests.quantity', 'Quantity')) ?></label>
                                    <input type="number" min="1" class="form-control" name="items[<?= e((string) $index) ?>][quantity]" value="<?= e((string) ($item['quantity'] ?? 1)) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= e(__('requests.unit_cost', 'Unit Cost')) ?></label>
                                    <input class="form-control" name="items[<?= e((string) $index) ?>][estimated_unit_cost]" value="<?= e((string) ($item['estimated_unit_cost'] ?? '')) ?>" placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= e(__('requests.fulfillment_preference', 'Fulfillment Preference')) ?></label>
                                    <select class="form-select" name="items[<?= e((string) $index) ?>][fulfillment_preference]">
                                        <?php foreach ($fulfillmentPreferences as $preferenceKey => $preferenceLabel): ?>
                                            <option value="<?= e($preferenceKey) ?>" <?= ((string) ($item['fulfillment_preference'] ?? 'either') === $preferenceKey) ? 'selected' : '' ?>><?= e($preferenceLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= e(__('requests.assignment_target', 'Assignment Target')) ?></label>
                                    <select class="form-select" name="items[<?= e((string) $index) ?>][assignment_target]">
                                        <?php foreach ($assignmentTargets as $targetKey => $targetLabel): ?>
                                            <option value="<?= e($targetKey) ?>" <?= ((string) ($item['assignment_target'] ?? 'employee') === $targetKey) ? 'selected' : '' ?>><?= e($targetLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= e(__('requests.specification', 'Specification')) ?></label>
                                    <input class="form-control" name="items[<?= e((string) $index) ?>][specification]" value="<?= e((string) ($item['specification'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?= e(__('common.notes', 'Notes')) ?></label>
                                    <textarea class="form-control" name="items[<?= e((string) $index) ?>][notes]" rows="2"><?= e((string) ($item['notes'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" name="workflow_action" value="draft" class="btn btn-outline-primary"><?= e(__('requests.save_draft', 'Save Draft')) ?></button>
                <button type="submit" name="workflow_action" value="submit" class="btn btn-primary"><?= e(__('requests.submit', 'Submit Request')) ?></button>
                <a href="<?= e($request ? route('requests.show', ['id' => $request['id']]) : route('requests.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>

<template id="request-item-template">
    <div class="card request-item-row">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><?= e(__('requests.item', 'Item')) ?> #<span class="request-item-number"></span></h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-request-item"><?= e(__('actions.delete', 'Delete')) ?></button>
            </div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?= e(__('requests.item_type', 'Item Type')) ?></label>
                    <select class="form-select request-item-type-select" name="items[__INDEX__][item_type]">
                        <?php foreach ($itemTypes as $itemTypeKey => $itemTypeLabel): ?>
                            <option value="<?= e($itemTypeKey) ?>"><?= e($itemTypeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(__('requests.item_name', 'Item Name')) ?></label>
                    <input class="form-control request-item-name-input" name="items[__INDEX__][item_name]">
                    <div class="form-text"><?= e(__('requests.item_name_help', 'Start typing or select an existing item from system stock.')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('assets.category', 'Category')) ?></label>
                    <select class="form-select request-item-category-select" name="items[__INDEX__][category_id]">
                        <option value=""><?= e(__('common.all', 'All')) ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label"><?= e(__('requests.quantity', 'Quantity')) ?></label>
                    <input type="number" min="1" class="form-control" name="items[__INDEX__][quantity]" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= e(__('requests.unit_cost', 'Unit Cost')) ?></label>
                    <input class="form-control" name="items[__INDEX__][estimated_unit_cost]" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('requests.fulfillment_preference', 'Fulfillment Preference')) ?></label>
                    <select class="form-select" name="items[__INDEX__][fulfillment_preference]">
                        <?php foreach ($fulfillmentPreferences as $preferenceKey => $preferenceLabel): ?>
                            <option value="<?= e($preferenceKey) ?>"><?= e($preferenceLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('requests.assignment_target', 'Assignment Target')) ?></label>
                    <select class="form-select" name="items[__INDEX__][assignment_target]">
                        <?php foreach ($assignmentTargets as $targetKey => $targetLabel): ?>
                            <option value="<?= e($targetKey) ?>" <?= $targetKey === 'employee' ? 'selected' : '' ?>><?= e($targetLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= e(__('requests.specification', 'Specification')) ?></label>
                    <input class="form-control" name="items[__INDEX__][specification]">
                </div>
                <div class="col-12">
                    <label class="form-label"><?= e(__('common.notes', 'Notes')) ?></label>
                    <textarea class="form-control" name="items[__INDEX__][notes]" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
  (function () {
    var container = document.getElementById('request-items');
    var addButton = document.getElementById('add-request-item');
    var template = document.getElementById('request-item-template');
    if (!container || !addButton || !template) {
      return;
    }

    function refreshNumbers() {
      var rows = container.querySelectorAll('.request-item-row');
      rows.forEach(function (row, index) {
        var numberNode = row.querySelector('.request-item-number');
        if (numberNode) {
          numberNode.textContent = String(index + 1);
        }
      });
    }

    function listIdForType(type) {
      if (type === 'license') {
        return 'request-item-options-license';
      }
      if (type === 'spare_part') {
        return 'request-item-options-spare_part';
      }
      return 'request-item-options-asset';
    }

    function syncItemNameList(row) {
      if (!(row instanceof HTMLElement)) {
        return;
      }

      var typeSelect = row.querySelector('.request-item-type-select');
      var nameInput = row.querySelector('.request-item-name-input');
      if (!(typeSelect instanceof HTMLSelectElement) || !(nameInput instanceof HTMLInputElement)) {
        return;
      }

      nameInput.setAttribute('list', listIdForType(typeSelect.value));
    }

    function syncCategoryVisibility(row) {
      if (!(row instanceof HTMLElement)) {
        return;
      }

      var typeSelect = row.querySelector('.request-item-type-select');
      var categorySelect = row.querySelector('.request-item-category-select');
      var categoryColumn = categorySelect ? categorySelect.closest('.col-md-3') : null;
      if (!(typeSelect instanceof HTMLSelectElement) || !(categorySelect instanceof HTMLSelectElement) || !(categoryColumn instanceof HTMLElement)) {
        return;
      }

      var isLicense = typeSelect.value === 'license';
      categoryColumn.classList.toggle('d-none', isLicense);
      categorySelect.disabled = isLicense;
      if (isLicense) {
        categorySelect.value = '';
      }
    }

    addButton.addEventListener('click', function () {
      var index = container.querySelectorAll('.request-item-row').length;
      var html = template.innerHTML.replaceAll('__INDEX__', String(index));
      container.insertAdjacentHTML('beforeend', html);
      var rows = container.querySelectorAll('.request-item-row');
      var row = rows[rows.length - 1];
      if (row instanceof HTMLElement) {
        syncItemNameList(row);
        syncCategoryVisibility(row);
      }
      refreshNumbers();
    });

    container.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains('remove-request-item')) {
        return;
      }

      var rows = container.querySelectorAll('.request-item-row');
      if (rows.length <= 1) {
        return;
      }

      var row = target.closest('.request-item-row');
      if (row) {
        row.remove();
        refreshNumbers();
      }
    });

    container.addEventListener('change', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains('request-item-type-select')) {
        return;
      }

      var row = target.closest('.request-item-row');
      if (row) {
        syncItemNameList(row);
        syncCategoryVisibility(row);
      }
    });

    container.querySelectorAll('.request-item-row').forEach(function (row) {
      syncItemNameList(row);
      syncCategoryVisibility(row);
    });
    refreshNumbers();
  }());
</script>
