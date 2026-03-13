<?php
$statusMeta = $statuses[$request['status']] ?? ['label' => $request['status'], 'badge' => 'secondary'];
$requestType = (string) ($request['request_type'] ?? 'asset');
$isAssetRequest = $requestType === 'asset';
$isSparePartRequest = $requestType === 'spare_part';
$isLicenseRequest = $requestType === 'license';
$requestTypeLabel = $requestTypes[$requestType] ?? $requestType;
$scenarioLabel = $scenarios[$request['scenario'] ?? 'general'] ?? ($request['scenario'] ?? 'general');
$nextWorkflowStatus = $advanceOptions[$request['status']] ?? '';
$isPurchasedStep = ($nextWorkflowStatus === 'purchased');
$isReceivedStep = ($nextWorkflowStatus === 'received');
$assetLinkRequirementMet = !$containsAssetItems || $linkedAssetsCount >= max(1, (int) $assetRequestedQuantity);
$canOfferNextStatus = !($nextWorkflowStatus === 'closed' && !$assetLinkRequirementMet);
$approveButtonLabel = ($canFulfillRequest && (string) ($request['status'] ?? '') === 'pending_it_manager')
    ? __('requests.approve_to_finance', 'Approve and Send to Finance')
    : __('requests.approve', 'Approve');
$oldFulfillmentItems = old('items', []);
?>

<style>
  .request-kpi {
    border-radius: 20px;
    padding: 18px;
    background: linear-gradient(180deg, rgba(255,255,255,0.7), rgba(255,255,255,0.45));
    border: 1px solid var(--app-border);
  }

  body[data-theme="dark"] .request-kpi {
    background: linear-gradient(180deg, rgba(15,23,42,0.9), rgba(15,23,42,0.72));
  }

  .request-stage {
    position: relative;
    border-radius: 18px;
    border: 1px solid var(--app-border);
    background: var(--app-surface);
    padding: 14px 16px;
  }

  .request-stage.is-done {
    border-color: rgba(15, 157, 119, 0.35);
    background: linear-gradient(180deg, rgba(15,157,119,0.1), rgba(15,157,119,0.03));
  }

  .request-stage.is-current {
    border-color: rgba(15, 98, 254, 0.35);
    background: linear-gradient(180deg, rgba(15,98,254,0.12), rgba(15,98,254,0.04));
  }

  .request-timeline-item {
    position: relative;
    padding-inline-start: 22px;
  }

  .request-timeline-item::before {
    content: "";
    position: absolute;
    inset-inline-start: 0;
    top: 7px;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: var(--app-primary);
    box-shadow: 0 0 0 6px rgba(15, 98, 254, 0.12);
  }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="text-muted small mb-2"><?= e(__('requests.number', 'Request No')) ?></div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h2 class="mb-0"><?= e($request['request_no']) ?></h2>
            <span class="badge text-bg-<?= e($statusMeta['badge']) ?>"><?= e($statusMeta['label']) ?></span>
        </div>
        <div class="text-muted">
            <?= e(__('requests.pending_with', 'Pending With')) ?>:
            <?= e($request['pending_user_name'] ?: ($pendingLabels[$request['current_pending_role']] ?? $request['current_pending_role'])) ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($containsAssetItems && in_array((string) $request['status'], ['approved', 'purchased', 'received'], true)): ?>
            <a href="<?= e(route('assets.create') . '&request_id=' . (int) $request['id']) ?>" class="btn btn-primary"><?= e(__('requests.register_asset', 'Register Asset')) ?></a>
        <?php endif; ?>
        <?php if ($canEditRequest): ?>
            <a href="<?= e(route('requests.edit', ['id' => $request['id']])) ?>" class="btn btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <?php endif; ?>
        <?php if ($canSubmitRequest): ?>
            <form method="POST" action="<?= e(route('requests.submit', ['id' => $request['id']])) ?>">
                <button type="submit" class="btn btn-primary"><?= e(__('requests.submit', 'Submit Request')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($canDeleteRequest): ?>
            <form method="POST" action="<?= e(route('requests.destroy', ['id' => $request['id']])) ?>">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this draft?')"><?= e(__('actions.delete', 'Delete')) ?></button>
            </form>
        <?php endif; ?>
        <a href="<?= e(route('requests.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="request-kpi h-100">
            <div class="text-muted small"><?= e(__('requests.requested_by', 'Requested By')) ?></div>
            <div class="fw-bold fs-5"><?= e($request['requested_by_name']) ?></div>
            <div class="small text-muted"><?= e($request['requested_by_email']) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="request-kpi h-100">
            <div class="text-muted small"><?= e(__('requests.requested_for', 'Requested For')) ?></div>
            <div class="fw-bold fs-5"><?= e($request['requested_for_name'] ?: '-') ?></div>
            <div class="small text-muted"><?= e($request['requested_for_code'] ?: '-') ?></div>
            <div class="small text-muted mt-1"><?= e(__('requests.request_type', 'Request Type')) ?>: <?= e($requestTypeLabel) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="request-kpi h-100">
            <div class="text-muted small"><?= e(__('requests.estimated_cost', 'Estimated Cost')) ?></div>
            <div class="fw-bold fs-5"><?= e($request['estimated_cost'] !== null ? number_format((float) $request['estimated_cost'], 2) : '-') ?></div>
            <div class="small text-muted"><?= e(__('requests.purchase_price', 'Purchase Price')) ?>: <?= e($request['purchase_price'] !== null ? number_format((float) $request['purchase_price'], 2) : '-') ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="request-kpi h-100">
            <div class="text-muted small"><?= e(__('requests.timeline', 'Timeline')) ?></div>
            <div class="fw-bold fs-5"><?= e($request['submitted_at'] ?: $request['created_at']) ?></div>
            <div class="small text-muted"><?= e(__('requests.needed_by', 'Needed By')) ?>: <?= e($request['needed_by_date'] ?: '-') ?></div>
        </div>
    </div>
</div>

<?php if ($containsAssetItems && in_array((string) $request['status'], ['approved', 'purchased', 'received'], true) && !$assetLinkRequirementMet): ?>
    <div class="alert alert-info mb-4">
        <?= e(__('requests.procurement_note', 'Register at least one asset and link it to this request before you close the workflow.')) ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= e(__('requests.approval_path', 'Approval Path')) ?></h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php
            $stageMap = [
                'pending_it' => 'it',
                'pending_it_manager' => 'it_manager',
                'pending_finance' => 'finance',
                'approved' => 'finance',
                'purchased' => 'finance',
                'received' => 'finance',
                'closed' => 'finance',
            ];
            $currentApprovalStage = $stageMap[$request['status']] ?? null;
            ?>
            <?php foreach ($approvalSteps as $stepKey => $step): ?>
                <?php
                $record = $step['record'];
                $className = 'request-stage';
                if ($record !== null && ($record['decision'] ?? '') === 'approved') {
                    $className .= ' is-done';
                } elseif ($currentApprovalStage === $stepKey) {
                    $className .= ' is-current';
                }
                ?>
                <div class="col-md-4">
                    <div class="<?= e($className) ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold"><?= e($step['label']) ?></div>
                            <span class="badge text-bg-<?= $record === null ? 'secondary' : (($record['decision'] ?? '') === 'approved' ? 'success' : (($record['decision'] ?? '') === 'returned' ? 'info' : 'danger')) ?>">
                                <?= e(\App\Support\RequestWorkflow::approvalDecisionLabel($record['decision'] ?? null)) ?>
                            </span>
                        </div>
                        <div class="small text-muted"><?= e($record['approver_name'] ?? __('requests.waiting_actor', 'Awaiting action')) ?></div>
                        <?php if (!empty($record['acted_at'] ?? '')): ?>
                            <div class="small text-muted mt-1"><?= e($record['acted_at']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($record['comment'] ?? '')): ?>
                            <div class="small mt-2"><?= e($record['comment']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e(__('requests.request_summary', 'Request Summary')) ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.title_field', 'Request Title')) ?></div>
                        <div class="fw-semibold"><?= e($request['title']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small"><?= e(__('requests.quantity', 'Quantity')) ?></div>
                        <div class="fw-semibold"><?= e((string) $request['quantity']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small"><?= e(__('requests.urgency', 'Urgency')) ?></div>
                        <div class="fw-semibold"><?= e($urgencies[$request['urgency']] ?? ucfirst((string) $request['urgency'])) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small"><?= e(__('requests.request_type', 'Request Type')) ?></div>
                        <div class="fw-semibold"><?= e($requestTypeLabel) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small"><?= e(__('requests.scenario', 'Scenario')) ?></div>
                        <div class="fw-semibold"><?= e($scenarioLabel) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small"><?= e(__('common.branch', 'Branch')) ?></div>
                        <div class="fw-semibold"><?= e($request['branch_name'] ?: __('form.no_branch', 'No Branch')) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small"><?= e(__('assets.category', 'Category')) ?></div>
                        <div class="fw-semibold"><?= e($request['category_name'] ?: __('common.all', 'All')) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small"><?= e(__('requests.submitted_at', 'Submitted At')) ?></div>
                        <div class="fw-semibold"><?= e($request['submitted_at'] ?: '-') ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small"><?= e(__('requests.specification', 'Asset Specification')) ?></div>
                        <div class="fw-semibold"><?= nl2br(e($request['asset_specification'] ?: '-')) ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small"><?= e(__('requests.justification', 'Business Justification')) ?></div>
                        <div class="fw-semibold"><?= nl2br(e($request['justification'] ?: '-')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e(__('requests.procurement_summary', 'Procurement Summary')) ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.fulfillment_source', 'Fulfillment Source')) ?></div>
                        <div class="fw-semibold"><?= e($request['fulfillment_source'] === 'storage' ? __('requests.fulfillment.storage', 'Storage') : ($request['fulfillment_source'] === 'purchase' ? __('requests.fulfillment.purchase', 'Purchase') : '-')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.purchase_price', 'Purchase Price')) ?></div>
                        <div class="fw-semibold"><?= e($request['purchase_price'] !== null ? number_format((float) $request['purchase_price'], 2) : '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.purchase_date', 'Purchase Date')) ?></div>
                        <div class="fw-semibold"><?= e($request['purchase_date'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.purchase_vendor', 'Vendor')) ?></div>
                        <div class="fw-semibold"><?= e($request['purchase_vendor'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.purchase_reference', 'Purchase Reference')) ?></div>
                        <div class="fw-semibold"><?= e($request['purchase_reference'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.received_date', 'Received Date')) ?></div>
                        <div class="fw-semibold"><?= e($request['received_date'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('requests.closed_at', 'Closed At')) ?></div>
                        <div class="fw-semibold"><?= e($request['closed_at'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= e(__('requests.items', 'Request Items')) ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('requests.item_type', 'Item Type')) ?></th>
                        <th><?= e(__('requests.item_name', 'Item Name')) ?></th>
                        <th><?= e(__('assets.category', 'Category')) ?></th>
                        <th><?= e(__('requests.quantity', 'Quantity')) ?></th>
                        <th><?= e(__('requests.unit_cost', 'Unit Cost')) ?></th>
                        <th><?= e(__('requests.fulfillment_preference', 'Fulfillment Preference')) ?></th>
                        <th><?= e(__('requests.assignment_target', 'Assignment Target')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requestItems as $item): ?>
                        <tr>
                            <td><?= e($itemTypes[$item['item_type']] ?? $item['item_type']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($item['item_name']) ?></div>
                                <?php if (($item['specification'] ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= e($item['specification']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['category_name'] ?: __('common.all', 'All')) ?></td>
                            <td><?= e((string) $item['quantity']) ?></td>
                            <td><?= e($item['estimated_unit_cost'] !== null ? number_format((float) $item['estimated_unit_cost'], 2) : '-') ?></td>
                            <td><?= e($fulfillmentPreferences[$item['fulfillment_preference']] ?? $item['fulfillment_preference']) ?></td>
                            <td><?= e($assignmentTargets[$item['assignment_target']] ?? $item['assignment_target']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= e(__('requests.purchase_review', 'Finance Purchase Review')) ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('requests.item_name', 'Item Name')) ?></th>
                        <th><?= e(__('requests.item_type', 'Item Type')) ?></th>
                        <th><?= e(__('requests.quantity', 'Quantity')) ?></th>
                        <th><?= e(__('requests.available_in_stock', 'Available in Stock')) ?></th>
                        <th><?= e(__('requests.recommended_source', 'Recommended Source')) ?></th>
                        <th><?= e(__('common.notes', 'Notes')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchaseReviewItems as $reviewItem): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($reviewItem['item_name']) ?></div>
                                <?php if (($reviewItem['specification'] ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= e($reviewItem['specification']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($itemTypes[$reviewItem['item_type']] ?? $reviewItem['item_type']) ?></td>
                            <td><?= e((string) $reviewItem['quantity']) ?></td>
                            <td><?= e((string) ($reviewItem['available_stock'] ?? 0)) ?></td>
                            <td>
                                <span class="badge text-bg-<?= e(($reviewItem['recommended_source'] ?? 'purchase') === 'storage' ? 'success' : 'warning') ?>">
                                    <?= e(($reviewItem['recommended_source'] ?? 'purchase') === 'storage'
                                        ? __('requests.fulfillment.storage', 'Storage')
                                        : __('requests.fulfillment.purchase', 'Purchase')) ?>
                                </span>
                            </td>
                            <td><?= e((string) ($reviewItem['review_note'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($containsAssetItems): ?>
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= e(__('requests.linked_assets', 'Linked Assets')) ?></h5>
            <div class="d-flex gap-2">
                <?php if ($canLinkExistingAssets): ?>
                    <span class="badge text-bg-info"><?= e(__('requests.link_existing_assets_hint', 'You can link existing storage assets instead of registering new ones.')) ?></span>
                <?php endif; ?>
                <a href="<?= e(route('assets.create') . '&request_id=' . (int) $request['id']) ?>" class="btn btn-sm btn-outline-primary"><?= e(__('requests.register_asset', 'Register Asset')) ?></a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($canLinkExistingAssets): ?>
                <form method="POST" action="<?= e(route('requests.link_existing_assets', ['id' => $request['id']])) ?>" class="border rounded-3 p-3 mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label class="form-label" for="existing_asset_ids"><?= e(__('requests.link_existing_assets', 'Link Existing Storage Assets')) ?></label>
                            <select class="form-select <?= has_error('asset_ids') ? 'is-invalid' : '' ?>" id="existing_asset_ids" name="asset_ids[]" multiple size="<?= e((string) min(8, max(3, count($linkableAssetOptions)))) ?>">
                                <?php foreach ($linkableAssetOptions as $assetOption): ?>
                                    <option value="<?= e((string) $assetOption['id']) ?>" <?= in_array((string) $assetOption['id'], array_map('strval', (array) old('asset_ids', [])), true) ? 'selected' : '' ?>>
                                        <?= e($assetOption['name'] . ' | ' . $assetOption['tag'] . ' | ' . $assetOption['category_name'] . ' | ' . $assetOption['branch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (has_error('asset_ids')): ?><div class="invalid-feedback d-block"><?= e((string) field_error('asset_ids')) ?></div><?php endif; ?>
                            <div class="form-text"><?= e(__('requests.link_existing_assets_help', 'Select existing unassigned storage assets and link them directly to this request.')) ?></div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label" for="link_assets_comment"><?= e(__('common.notes', 'Notes')) ?></label>
                            <textarea class="form-control" id="link_assets_comment" name="comment" rows="3"><?= e((string) old('comment')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-success"><?= e(__('requests.link_existing_assets', 'Link Existing Storage Assets')) ?></button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($linkedAssets !== []): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th><?= e(__('assets.name', 'Asset Name')) ?></th>
                                <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                                <th><?= e(__('common.branch', 'Branch')) ?></th>
                                <th><?= e(__('common.status', 'Status')) ?></th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($linkedAssets as $linkedAsset): ?>
                                <tr>
                                    <td><?= e($linkedAsset['name']) ?></td>
                                    <td><?= e($linkedAsset['tag']) ?></td>
                                    <td><?= e($linkedAsset['branch_name']) ?></td>
                                    <td><?= e(__('status.' . $linkedAsset['status'], ucfirst((string) $linkedAsset['status']))) ?></td>
                                    <td class="text-end"><a href="<?= e(route('assets.show', ['id' => $linkedAsset['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= e(__('requests.no_linked_assets', 'No assets are linked to this request yet.')) ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($containsSparePartItems): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('requests.issued_spare_parts', 'Issued Spare Parts')) ?></h5>
        </div>
        <div class="card-body">
            <?php if ($sparePartIssues !== []): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th><?= e(__('spare_parts.name', 'Spare Part')) ?></th>
                                <th><?= e(__('spare_parts.part_number', 'Part Number')) ?></th>
                                <th><?= e(__('requests.quantity', 'Quantity')) ?></th>
                                <th><?= e(__('requests.requested_for', 'Requested For')) ?></th>
                                <th><?= e(__('common.date', 'Date')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sparePartIssues as $issue): ?>
                                <tr>
                                    <td><?= e($issue['spare_part_name']) ?></td>
                                    <td><?= e($issue['part_number']) ?></td>
                                    <td><?= e((string) $issue['quantity']) ?></td>
                                    <td><?= e($issue['employee_name']) ?></td>
                                    <td><?= e($issue['issued_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= e(__('requests.no_spare_part_issues', 'No spare parts have been issued for this request yet.')) ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($containsLicenseItems): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('requests.issued_licenses', 'Issued Licenses')) ?></h5>
        </div>
        <div class="card-body">
            <?php if ($licenseIssues !== []): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th><?= e(__('licenses.product_name', 'License')) ?></th>
                                <th><?= e(__('requests.quantity', 'Quantity')) ?></th>
                                <th><?= e(__('requests.requested_for', 'Requested For')) ?></th>
                                <th><?= e(__('common.branch', 'Branch')) ?></th>
                                <th><?= e(__('common.date', 'Date')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($licenseIssues as $issue): ?>
                                <tr>
                                    <td><?= e(trim($issue['product_name'] . ($issue['vendor_name'] !== '' ? ' | ' . $issue['vendor_name'] : ''))) ?></td>
                                    <td><?= e((string) $issue['quantity']) ?></td>
                                    <td><?= e($issue['employee_name'] ?: '-') ?></td>
                                    <td><?= e($issue['branch_name'] ?: '-') ?></td>
                                    <td><?= e($issue['allocated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= e(__('requests.no_license_issues', 'No license allocations have been recorded for this request yet.')) ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php if ($canApproveRequest): ?>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?= e(__('requests.approval_action', 'Approval Action')) ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route('requests.decision', ['id' => $request['id']])) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="comment"><?= e(__('common.notes', 'Notes')) ?></label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="<?= e(__('requests.comment_placeholder', 'Add an approval note or rejection reason.')) ?>"></textarea>
                        </div>
                        <?php if ($canFulfillRequest && (string) ($request['status'] ?? '') === 'pending_it_manager'): ?>
                            <div class="alert alert-warning small py-2">
                                <?= e(__('requests.approve_to_finance_help', 'Use this action only when the request needs purchasing. If all lines are available in stock, use Fulfill from Storage below.')) ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="decision" value="approve" class="btn btn-success"><?= e($approveButtonLabel) ?></button>
                            <button type="submit" name="decision" value="return" class="btn btn-outline-primary"><?= e(__('requests.return_for_info', 'Return for Info')) ?></button>
                            <button type="submit" name="decision" value="reject" class="btn btn-outline-danger"><?= e(__('requests.reject', 'Reject')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canFulfillRequest): ?>
        <div class="col-xl-4">
            <div class="card border-success">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?= e(__('requests.fulfill_from_storage', 'Fulfill from Storage')) ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small"><?= e(__('requests.fulfill_from_storage_help', 'IT Manager can assign existing storage stock directly to this request and close it without sending it to Finance.')) ?></p>
                    <?php if (!$canFullyFulfillRequest): ?>
                        <div class="alert alert-warning small py-2">
                            <?= e(__('requests.fulfill_not_ready_help', 'Some request lines do not have enough stock yet. Complete the missing stock or send the request to Finance for purchasing.')) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (has_error('items')): ?>
                        <div class="alert alert-danger small py-2"><?= e((string) field_error('items')) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="<?= e(route('requests.fulfill_storage', ['id' => $request['id']])) ?>">
                        <?php foreach ($storageFulfillmentRows as $fulfillmentItem): ?>
                            <?php
                            $itemId = (int) ($fulfillmentItem['id'] ?? 0);
                            $itemOld = is_array($oldFulfillmentItems[$itemId] ?? null) ? $oldFulfillmentItems[$itemId] : [];
                            $itemType = (string) ($fulfillmentItem['item_type'] ?? 'asset');
                            $selectionErrorKey = 'items.' . $itemId . '.selection';
                            ?>
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                    <div>
                                        <div class="fw-semibold"><?= e($fulfillmentItem['item_name']) ?></div>
                                        <div class="text-muted small">
                                            <?= e($itemTypes[$itemType] ?? $itemType) ?>
                                            | <?= e(__('requests.quantity', 'Quantity')) ?>: <?= e((string) $fulfillmentItem['quantity']) ?>
                                            | <?= e(__('requests.assignment_target', 'Assignment Target')) ?>: <?= e($assignmentTargets[$fulfillmentItem['assignment_target']] ?? $fulfillmentItem['assignment_target']) ?>
                                        </div>
                                    </div>
                                    <?php if (!$fulfillmentItem['can_fulfill']): ?>
                                        <span class="badge text-bg-warning"><?= e(__('requests.stock_not_ready', 'Stock Not Ready')) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$fulfillmentItem['can_fulfill'] && (string) ($fulfillmentItem['stock_warning'] ?? '') !== ''): ?>
                                    <div class="alert alert-warning small py-2"><?= e((string) $fulfillmentItem['stock_warning']) ?></div>
                                <?php endif; ?>

                                <?php if (has_error($selectionErrorKey)): ?>
                                    <div class="alert alert-danger small py-2"><?= e((string) field_error($selectionErrorKey)) ?></div>
                                <?php endif; ?>

                                <?php if ($itemType === 'asset'): ?>
                                    <?php $assetErrorKey = 'items.' . $itemId . '.asset_ids'; ?>
                                    <div class="mb-2">
                                        <label class="form-label" for="asset_ids_<?= e((string) $itemId) ?>"><?= e(__('requests.select_storage_assets', 'Select Storage Assets')) ?></label>
                                        <?php $assetOptions = (array) ($fulfillmentItem['storage_asset_options'] ?? []); ?>
                                        <select class="form-select <?= has_error($assetErrorKey) ? 'is-invalid' : '' ?>" id="asset_ids_<?= e((string) $itemId) ?>" name="items[<?= e((string) $itemId) ?>][asset_ids][]" multiple size="<?= e((string) min(6, max(3, count($assetOptions)))) ?>" <?= $assetOptions === [] ? 'disabled' : '' ?>>
                                            <?php foreach ($assetOptions as $assetOption): ?>
                                                <option value="<?= e((string) $assetOption['id']) ?>" <?= in_array((string) $assetOption['id'], array_map('strval', (array) ($itemOld['asset_ids'] ?? [])), true) ? 'selected' : '' ?>>
                                                    <?= e($assetOption['name'] . ' | ' . $assetOption['tag'] . ' | ' . $assetOption['branch_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (has_error($assetErrorKey)): ?><div class="invalid-feedback d-block"><?= e((string) field_error($assetErrorKey)) ?></div><?php endif; ?>
                                        <?php if ($assetOptions === []): ?><div class="form-text text-danger"><?= e(__('requests.no_storage_assets_available', 'No matching storage assets are available for this request.')) ?></div><?php endif; ?>
                                        <div class="form-text"><?= e(__('requests.fulfill_quantity_help', 'Select exactly the same number of items as the request quantity.')) ?>: <?= e((string) $fulfillmentItem['quantity']) ?></div>
                                    </div>
                                <?php elseif ($itemType === 'spare_part'): ?>
                                    <?php $sparePartErrorKey = 'items.' . $itemId . '.spare_part_id'; ?>
                                    <?php $partOptions = (array) ($fulfillmentItem['spare_part_options'] ?? []); ?>
                                    <div class="mb-2">
                                        <label class="form-label" for="spare_part_id_<?= e((string) $itemId) ?>"><?= e(__('requests.select_spare_part', 'Select Spare Part')) ?></label>
                                        <select class="form-select <?= has_error($sparePartErrorKey) ? 'is-invalid' : '' ?>" id="spare_part_id_<?= e((string) $itemId) ?>" name="items[<?= e((string) $itemId) ?>][spare_part_id]" <?= $partOptions === [] ? 'disabled' : '' ?>>
                                            <option value=""><?= e(__('common.select', 'Select')) ?></option>
                                            <?php foreach ($partOptions as $partOption): ?>
                                                <option value="<?= e((string) $partOption['id']) ?>" <?= (string) ($itemOld['spare_part_id'] ?? '') === (string) $partOption['id'] ? 'selected' : '' ?>>
                                                    <?= e($partOption['name'] . ' | ' . $partOption['part_number'] . ' | ' . __('storage.quantity', 'Quantity') . ': ' . $partOption['quantity']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (has_error($sparePartErrorKey)): ?><div class="invalid-feedback d-block"><?= e((string) field_error($sparePartErrorKey)) ?></div><?php endif; ?>
                                        <?php if ($partOptions === []): ?><div class="form-text text-danger"><?= e(__('requests.no_spare_parts_available', 'No spare parts with available quantity were found in storage.')) ?></div><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php $licenseErrorKey = 'items.' . $itemId . '.license_id'; ?>
                                    <?php $licenseOptions = (array) ($fulfillmentItem['license_stock_options'] ?? []); ?>
                                    <div class="mb-2">
                                        <label class="form-label" for="license_id_<?= e((string) $itemId) ?>"><?= e(__('requests.select_license', 'Select License')) ?></label>
                                        <select class="form-select <?= has_error($licenseErrorKey) ? 'is-invalid' : '' ?>" id="license_id_<?= e((string) $itemId) ?>" name="items[<?= e((string) $itemId) ?>][license_id]" <?= $licenseOptions === [] ? 'disabled' : '' ?>>
                                            <option value=""><?= e(__('common.select', 'Select')) ?></option>
                                            <?php foreach ($licenseOptions as $licenseOption): ?>
                                                <option value="<?= e((string) $licenseOption['id']) ?>" <?= (string) ($itemOld['license_id'] ?? '') === (string) $licenseOption['id'] ? 'selected' : '' ?>>
                                                    <?= e($licenseOption['product_name'] . ' | ' . $licenseOption['vendor_name'] . ' | ' . __('requests.available_seats', 'Available Seats') . ': ' . $licenseOption['available_seats']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (has_error($licenseErrorKey)): ?><div class="invalid-feedback d-block"><?= e((string) field_error($licenseErrorKey)) ?></div><?php endif; ?>
                                        <?php if ($licenseOptions === []): ?><div class="form-text text-danger"><?= e(__('requests.no_license_stock_available', 'No licenses with available seats were found.')) ?></div><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="mb-3">
                            <label class="form-label" for="fulfill_comment"><?= e(__('common.notes', 'Notes')) ?></label>
                            <textarea class="form-control" id="fulfill_comment" name="comment" rows="3"><?= e((string) old('comment')) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" <?= !$canFullyFulfillRequest ? 'disabled' : '' ?>><?= e(__('requests.fulfill_and_close', 'Assign from Storage and Close')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canAdvanceRequest): ?>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?= e(__('requests.operations', 'Operations Progress')) ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route('requests.advance', ['id' => $request['id']])) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="next_status"><?= e(__('requests.next_step', 'Next Step')) ?></label>
                            <select class="form-select <?= has_error('next_status') ? 'is-invalid' : '' ?>" id="next_status" name="next_status" required>
                                <option value=""><?= e(__('requests.select_next_step', 'Select next step')) ?></option>
                                <?php if ($nextWorkflowStatus !== '' && $canOfferNextStatus): ?>
                                    <?php $nextStatus = $nextWorkflowStatus; ?>
                                    <option value="<?= e($nextStatus) ?>" <?= old('next_status') === $nextStatus ? 'selected' : '' ?>><?= e($statuses[$nextStatus]['label'] ?? $nextStatus) ?></option>
                                <?php endif; ?>
                            </select>
                            <?php if (has_error('next_status')): ?><div class="invalid-feedback"><?= e((string) field_error('next_status')) ?></div><?php endif; ?>
                            <?php if ($nextWorkflowStatus === 'closed' && !$assetLinkRequirementMet): ?>
                                <div class="form-text text-danger"><?= e(__('requests.asset_link_required', 'Register and link at least one asset to this request before closing it.')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div id="purchase-fields" class="<?= $isPurchasedStep || old('next_status') === 'purchased' ? '' : 'd-none' ?>">
                            <div class="mb-3">
                                <label class="form-label" for="purchase_price"><?= e(__('requests.purchase_price', 'Purchase Price')) ?></label>
                                <input type="text" class="form-control <?= has_error('purchase_price') ? 'is-invalid' : '' ?>" id="purchase_price" name="purchase_price" value="<?= e((string) old('purchase_price')) ?>" placeholder="0.00">
                                <?php if (has_error('purchase_price')): ?><div class="invalid-feedback"><?= e((string) field_error('purchase_price')) ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="purchase_vendor"><?= e(__('requests.purchase_vendor', 'Vendor')) ?></label>
                                <input type="text" class="form-control" id="purchase_vendor" name="purchase_vendor" value="<?= e((string) old('purchase_vendor')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="purchase_reference"><?= e(__('requests.purchase_reference', 'Purchase Reference')) ?></label>
                                <input type="text" class="form-control" id="purchase_reference" name="purchase_reference" value="<?= e((string) old('purchase_reference')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="purchase_date"><?= e(__('requests.purchase_date', 'Purchase Date')) ?></label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= e((string) old('purchase_date', date('Y-m-d'))) ?>">
                            </div>
                        </div>

                        <div id="received-fields" class="<?= $isReceivedStep || old('next_status') === 'received' ? '' : 'd-none' ?>">
                            <div class="mb-3">
                                <label class="form-label" for="received_date"><?= e(__('requests.received_date', 'Received Date')) ?></label>
                                <input type="date" class="form-control <?= has_error('received_date') ? 'is-invalid' : '' ?>" id="received_date" name="received_date" value="<?= e((string) old('received_date', date('Y-m-d'))) ?>">
                                <?php if (has_error('received_date')): ?><div class="invalid-feedback"><?= e((string) field_error('received_date')) ?></div><?php endif; ?>
                            </div>
                            <?php if ($isSparePartRequest || $isLicenseRequest): ?>
                                <div class="form-text"><?= e(__('requests.receive_updates_stock', 'When you mark this request as received, the system will increase the related stock automatically.')) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="advance_comment"><?= e(__('common.notes', 'Notes')) ?></label>
                            <textarea class="form-control" id="advance_comment" name="comment" rows="3"><?= e((string) old('comment')) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= e(__('requests.save_progress', 'Save Progress')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?= ($canApproveRequest || $canAdvanceRequest || $canFulfillRequest) ? 'col-xl-4' : 'col-xl-12' ?>">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e(__('requests.timeline', 'Timeline')) ?></h5>
            </div>
            <div class="card-body">
                <?php if ($timeline !== []): ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($timeline as $event): ?>
                            <div class="request-timeline-item">
                                <div class="fw-semibold"><?= e(\App\Support\RequestWorkflow::timelineActionLabel((string) $event['action'])) ?></div>
                                <div class="small text-muted"><?= e($event['actor_name']) ?><?= $event['actor_role'] !== '' ? ' | ' . e($event['actor_role']) : '' ?> | <?= e($event['created_at']) ?></div>
                                <?php if ($event['from_status'] !== '' || $event['to_status'] !== ''): ?>
                                    <div class="small mt-1">
                                        <?= e($statuses[$event['from_status']]['label'] ?? ($event['from_status'] ?: '-')) ?>
                                        ->
                                        <?= e($statuses[$event['to_status']]['label'] ?? ($event['to_status'] ?: '-')) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($event['comment'] !== ''): ?>
                                    <div class="small mt-1"><?= e($event['comment']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('requests.no_timeline', 'No timeline entries yet.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    var nextStatus = document.getElementById('next_status');
    if (!nextStatus) {
      return;
    }

    var purchaseFields = document.getElementById('purchase-fields');
    var receivedFields = document.getElementById('received-fields');

    function syncWorkflowFields() {
      var value = nextStatus.value;
      if (purchaseFields) {
        purchaseFields.classList.toggle('d-none', value !== 'purchased');
      }
      if (receivedFields) {
        receivedFields.classList.toggle('d-none', value !== 'received');
      }
    }

    nextStatus.addEventListener('change', syncWorkflowFields);
    syncWorkflowFields();
  }());
</script>
