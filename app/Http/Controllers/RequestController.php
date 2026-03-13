<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\RequestWorkflow;

class RequestController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'mine' => trim((string) ($_GET['mine'] ?? '')),
        ];

        $this->render('requests.index', [
            'pageTitle' => __('nav.requests', 'Requests'),
            'requests' => RequestWorkflow::requests($filters, auth_user()),
            'summary' => RequestWorkflow::summary(auth_user()),
            'filters' => $filters,
            'statuses' => RequestWorkflow::statuses(),
            'requestTypes' => RequestWorkflow::requestTypeLabels(),
            'pendingLabels' => RequestWorkflow::pendingRoleLabels(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm(null);
    }

    public function store(): array
    {
        $errors = $this->validateForm($_POST);
        if ($errors !== []) {
            return $this->validationRedirect('requests.create', $errors, $_POST);
        }

        $submitNow = (string) ($_POST['workflow_action'] ?? 'draft') === 'submit';
        $requestId = RequestWorkflow::create($_POST, (int) (auth_user()['id'] ?? 0), $submitNow);
        DataRepository::logAudit('create', 'asset_requests', $requestId, null, [
            'request_no' => RequestWorkflow::find($requestId)['request_no'] ?? '',
            'status' => $submitNow ? 'pending_it' : 'draft',
        ]);
        flash('status', $submitNow
            ? __('requests.submitted_success', 'Request submitted successfully.')
            : __('requests.draft_saved', 'Draft saved successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function edit(string $id): void
    {
        $request = $this->findRequestOrFail((int) $id);
        if ($request === null) {
            return;
        }

        if (!RequestWorkflow::canEdit($request, auth_user())) {
            $this->forbidden();
            return;
        }

        $this->renderForm($request);
    }

    public function update(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canEdit($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $errors = $this->validateForm($_POST);
        if ($errors !== []) {
            return $this->validationRedirect('requests.edit', $errors, $_POST, ['id' => $requestId]);
        }

        $submitNow = (string) ($_POST['workflow_action'] ?? 'draft') === 'submit';
        RequestWorkflow::update($requestId, $_POST, $submitNow);
        DataRepository::logAudit('update', 'asset_requests', $requestId, ['status' => $request['status'] ?? ''], [
            'status' => $submitNow && in_array((string) ($request['status'] ?? ''), ['draft', 'needs_info'], true) ? 'pending_it' : $request['status'],
        ]);
        flash('status', $submitNow
            ? __('requests.submitted_success', 'Request submitted successfully.')
            : __('requests.updated_success', 'Request updated successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function destroy(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canDelete($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        RequestWorkflow::delete($requestId);
        DataRepository::logAudit('delete', 'asset_requests', $requestId, $request, null);
        flash('status', __('requests.deleted_success', 'Draft deleted successfully.'));

        return $this->redirect('requests.index');
    }

    public function show(string $id): void
    {
        $request = $this->findRequestOrFail((int) $id);
        if ($request === null) {
            return;
        }

        if (!RequestWorkflow::canView($request, auth_user())) {
            $this->forbidden();
            return;
        }

        $approvals = RequestWorkflow::approvals((int) $id);
        $financeRecord = $this->approvalRecord($approvals, 'finance');
        if ($financeRecord === null && (string) ($request['status'] ?? '') === 'closed' && (string) ($request['fulfillment_source'] ?? '') === 'storage') {
            $financeRecord = [
                'decision' => 'skipped',
                'comment' => __('requests.finance_skipped_storage', 'Skipped because the request was fulfilled directly from storage.'),
                'approver_name' => '',
                'acted_at' => $request['closed_at'] ?? '',
            ];
        }
        $approvalSteps = [
            'it' => ['label' => __('requests.step.it', 'IT'), 'record' => $this->approvalRecord($approvals, 'it')],
            'it_manager' => ['label' => __('requests.step.it_manager', 'IT Manager'), 'record' => $this->approvalRecord($approvals, 'it_manager')],
            'finance' => ['label' => __('requests.step.finance', 'Finance'), 'record' => $financeRecord],
        ];

        $linkedAssets = DataRepository::assetsForRequest((int) $id);
        $sparePartIssues = RequestWorkflow::sparePartIssues((int) $id);
        $licenseIssues = RequestWorkflow::licenseIssues((int) $id);
        $requestItems = RequestWorkflow::requestItems((int) $id);
        $containsAssetItems = count(array_filter($requestItems, static fn (array $item): bool => (string) ($item['item_type'] ?? '') === 'asset')) > 0;
        $containsSparePartItems = count(array_filter($requestItems, static fn (array $item): bool => (string) ($item['item_type'] ?? '') === 'spare_part')) > 0;
        $containsLicenseItems = count(array_filter($requestItems, static fn (array $item): bool => (string) ($item['item_type'] ?? '') === 'license')) > 0;
        $assetRequestedQuantity = array_sum(array_map(
            static fn (array $item): int => (string) ($item['item_type'] ?? '') === 'asset' ? max(1, (int) ($item['quantity'] ?? 1)) : 0,
            $requestItems
        ));
        $storageFulfillmentRows = RequestWorkflow::storageFulfillmentRows($request);
        $storageRowsByItemId = [];
        foreach ($storageFulfillmentRows as $storageRow) {
            $storageRowsByItemId[(int) ($storageRow['id'] ?? 0)] = $storageRow;
        }

        $purchaseReviewItems = array_map(static function (array $item) use ($storageRowsByItemId): array {
            $itemId = (int) ($item['id'] ?? 0);
            $storageRow = $storageRowsByItemId[$itemId] ?? null;
            $availableStock = (int) ($storageRow['available_stock'] ?? 0);
            $preference = (string) ($item['fulfillment_preference'] ?? 'either');
            $recommendedSource = 'purchase';
            $reviewNote = __('requests.purchase_review_buy', 'Purchase this item.');

            if ($preference === 'storage' && $storageRow !== null && !empty($storageRow['can_fulfill'])) {
                $recommendedSource = 'storage';
                $reviewNote = __('requests.purchase_review_storage', 'Available in storage. No purchase needed.');
            } elseif ($preference === 'either' && $storageRow !== null && !empty($storageRow['can_fulfill'])) {
                $recommendedSource = 'storage';
                $reviewNote = __('requests.purchase_review_storage_optional', 'Stock is available. Use storage unless there is a business reason to purchase.');
            } elseif ($preference === 'purchase') {
                $reviewNote = __('requests.purchase_review_requested', 'Requested as purchase.');
            } elseif ($storageRow !== null && empty($storageRow['can_fulfill'])) {
                $reviewNote = (string) ($storageRow['stock_warning'] ?? __('requests.purchase_review_buy', 'Purchase this item.'));
            }

            return $item + [
                'available_stock' => $availableStock,
                'recommended_source' => $recommendedSource,
                'review_note' => $reviewNote,
            ];
        }, $requestItems);

        $this->render('requests.show', [
            'pageTitle' => (string) $request['request_no'],
            'request' => $request,
            'requestItems' => $requestItems,
            'timeline' => RequestWorkflow::timeline((int) $id),
            'linkedAssets' => $linkedAssets,
            'hasLinkedAssets' => $linkedAssets !== [],
            'linkedAssetsCount' => count($linkedAssets),
            'containsAssetItems' => $containsAssetItems,
            'containsSparePartItems' => $containsSparePartItems,
            'containsLicenseItems' => $containsLicenseItems,
            'assetRequestedQuantity' => $assetRequestedQuantity,
            'sparePartIssues' => $sparePartIssues,
            'licenseIssues' => $licenseIssues,
            'approvalSteps' => $approvalSteps,
            'statuses' => RequestWorkflow::statuses(),
            'requestTypes' => RequestWorkflow::requestTypeLabels(),
            'scenarios' => RequestWorkflow::scenarioLabels(),
            'itemTypes' => RequestWorkflow::itemTypeLabels(),
            'fulfillmentPreferences' => RequestWorkflow::fulfillmentPreferenceLabels(),
            'assignmentTargets' => RequestWorkflow::assignmentTargetLabels(),
            'urgencies' => RequestWorkflow::urgencyLabels(),
            'pendingLabels' => RequestWorkflow::pendingRoleLabels(),
            'canEditRequest' => RequestWorkflow::canEdit($request, auth_user()),
            'canDeleteRequest' => RequestWorkflow::canDelete($request, auth_user()),
            'canSubmitRequest' => RequestWorkflow::canSubmit($request, auth_user()),
            'canApproveRequest' => RequestWorkflow::canApprove($request, auth_user()),
            'canAdvanceRequest' => RequestWorkflow::canAdvance($request, auth_user()),
            'canFulfillRequest' => RequestWorkflow::canFulfillFromStorage($request, auth_user()),
            'canFullyFulfillRequest' => RequestWorkflow::canFullyFulfillFromStorage($request),
            'advanceOptions' => RequestWorkflow::advanceOptions(),
            'storageAssetOptions' => RequestWorkflow::storageAssetOptions($request),
            'sparePartOptions' => RequestWorkflow::sparePartOptions($request),
            'licenseStockOptions' => RequestWorkflow::licenseStockOptions($request),
            'storageFulfillmentRows' => $storageFulfillmentRows,
            'purchaseReviewItems' => $purchaseReviewItems,
            'canLinkExistingAssets' => RequestWorkflow::canLinkExistingAssets($request, auth_user()),
            'linkableAssetOptions' => RequestWorkflow::linkableStorageAssets($request),
        ]);
    }

    public function submit(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canSubmit($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        RequestWorkflow::submit($requestId);
        DataRepository::logAudit('submit', 'asset_requests', $requestId, ['status' => $request['status'] ?? 'draft'], ['status' => 'pending_it']);
        flash('status', __('requests.submitted_success', 'Request submitted successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function decision(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canApprove($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $decision = trim((string) ($_POST['decision'] ?? ''));
        if (!in_array($decision, RequestWorkflow::decisionOptions(), true)) {
            flash('error', __('requests.invalid_decision', 'Choose a valid decision.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        RequestWorkflow::decision($requestId, $decision, trim((string) ($_POST['comment'] ?? '')));
        DataRepository::logAudit($decision, 'asset_requests', $requestId, ['status' => $request['status'] ?? ''], ['status' => RequestWorkflow::find($requestId)['status'] ?? '']);

        $message = match ($decision) {
            'approve' => __('requests.approved_success', 'Approval saved successfully.'),
            'return' => __('requests.returned_success', 'Request returned to requester.'),
            default => __('requests.rejected_success', 'Request rejected successfully.'),
        };
        flash('status', $message);

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function fulfillStorage(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canFulfillFromStorage($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $errors = RequestWorkflow::validateFulfillmentInput($request, $_POST);
        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        RequestWorkflow::fulfillFromStorage($requestId, $_POST);
        DataRepository::logAudit('fulfill_from_storage', 'asset_requests', $requestId, ['status' => $request['status'] ?? ''], [
            'status' => 'closed',
            'fulfillment_source' => 'storage',
            'request_type' => $request['request_type'] ?? 'asset',
        ]);
        flash('status', __('requests.fulfilled_success', 'Request fulfilled from storage successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function linkExistingAssets(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canLinkExistingAssets($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $errors = RequestWorkflow::validateLinkExistingAssetsInput($request, $_POST);
        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        RequestWorkflow::linkExistingAssets($requestId, $_POST);
        DataRepository::logAudit('link_existing_assets', 'asset_requests', $requestId, null, [
            'asset_ids' => $_POST['asset_ids'] ?? [],
        ]);
        flash('status', __('requests.link_existing_assets_success', 'Existing storage assets were linked to the request successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    public function advance(string $id): array
    {
        $requestId = (int) $id;
        $request = $this->findRequestOrFail($requestId);
        if ($request === null) {
            return $this->redirect('requests.index');
        }

        if (!RequestWorkflow::canAdvance($request, auth_user())) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $errors = RequestWorkflow::validateAdvanceInput($request, $_POST);
        if ($errors !== []) {
            set_validation_errors($errors);
            set_old_input($_POST);
            flash('error', __('validation.fix_errors', 'Please fix the highlighted fields and try again.'));
            return $this->redirect('requests.show', ['id' => $requestId]);
        }

        $nextStatus = trim((string) ($_POST['next_status'] ?? ''));
        RequestWorkflow::advance($requestId, $_POST);
        DataRepository::logAudit('advance', 'asset_requests', $requestId, ['status' => $request['status'] ?? ''], [
            'status' => $nextStatus,
            'purchase_price' => $_POST['purchase_price'] ?? null,
            'purchase_vendor' => $_POST['purchase_vendor'] ?? null,
            'purchase_reference' => $_POST['purchase_reference'] ?? null,
            'purchase_date' => $_POST['purchase_date'] ?? null,
            'received_date' => $_POST['received_date'] ?? null,
        ]);
        flash('status', __('requests.progress_saved', 'Request progress updated successfully.'));

        return $this->redirect('requests.show', ['id' => $requestId]);
    }

    private function renderForm(?array $request): void
    {
        $requestItems = $request !== null ? RequestWorkflow::requestItems((int) $request['id']) : [[
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

        $this->render('requests.form', [
            'pageTitle' => $request === null ? __('requests.create', 'Create Request') : __('requests.edit', 'Edit Request'),
            'request' => $request,
            'requestItems' => $requestItems,
            'employees' => DataRepository::activeEmployees(),
            'branches' => DataRepository::branches(),
            'categories' => DataRepository::categories(),
            'assetCatalog' => DataRepository::assets(),
            'sparePartCatalog' => DataRepository::spareParts(),
            'licenseCatalog' => DataRepository::licenses(),
            'requestTypes' => RequestWorkflow::requestTypeLabels(),
            'scenarios' => RequestWorkflow::scenarioLabels(),
            'itemTypes' => RequestWorkflow::itemTypeLabels(),
            'fulfillmentPreferences' => RequestWorkflow::fulfillmentPreferenceLabels(),
            'assignmentTargets' => RequestWorkflow::assignmentTargetLabels(),
            'urgencies' => ['low', 'normal', 'high', 'critical'],
        ]);
    }

    private function validateForm(array $input): array
    {
        $errors = $this->validate($input, [
            'title' => ['required'],
            'scenario' => ['required', 'in:general,employee_onboarding,branch_deployment,replacement,stock_replenishment'],
            'urgency' => ['required', 'in:low,normal,high,critical'],
            'justification' => ['required', 'min:5'],
        ]);

        $scenario = trim((string) ($input['scenario'] ?? 'general'));
        if (in_array($scenario, ['employee_onboarding', 'replacement'], true) && trim((string) ($input['requested_for_employee_id'] ?? '')) === '') {
            $errors['requested_for_employee_id'] = __('requests.requested_for_required', 'Select the employee who will receive this request.');
        }

        if ($scenario === 'branch_deployment' && trim((string) ($input['branch_id'] ?? '')) === '') {
            $errors['branch_id'] = __('requests.branch_required', 'Select the target branch for this request.');
        }

        $items = array_values(array_filter((array) ($input['items'] ?? []), static function (mixed $row): bool {
            if (!is_array($row)) {
                return false;
            }

            return trim((string) ($row['item_name'] ?? '')) !== ''
                || trim((string) ($row['quantity'] ?? '')) !== ''
                || trim((string) ($row['specification'] ?? '')) !== '';
        }));

        if ($items === []) {
            $errors['items'] = __('requests.items_required', 'Add at least one request item.');
            return $errors;
        }

        foreach ($items as $index => $item) {
            if (trim((string) ($item['item_name'] ?? '')) === '') {
                $errors['items'] = __('requests.item_name_required', 'Each request item must have a name.');
                break;
            }

            if (!in_array((string) ($item['item_type'] ?? ''), ['asset', 'spare_part', 'license'], true)) {
                $errors['items'] = __('requests.item_type_invalid', 'Each request item must have a valid type.');
                break;
            }

            if (preg_match('/^[0-9]+$/', trim((string) ($item['quantity'] ?? ''))) !== 1 || (int) ($item['quantity'] ?? 0) <= 0) {
                $errors['items'] = __('requests.item_quantity_invalid', 'Each request item must have a valid quantity.');
                break;
            }

            $estimatedUnitCost = trim((string) ($item['estimated_unit_cost'] ?? ''));
            if ($estimatedUnitCost !== '' && preg_match('/^\d+(\.\d{1,2})?$/', $estimatedUnitCost) !== 1) {
                $errors['items'] = __('requests.item_cost_invalid', 'Each request item cost must be a valid amount.');
                break;
            }

            if ((string) ($item['assignment_target'] ?? 'employee') === 'employee' && trim((string) ($input['requested_for_employee_id'] ?? '')) === '') {
                $errors['requested_for_employee_id'] = __('requests.requested_for_required', 'Select the employee who will receive this request.');
            }
        }

        return $errors;
    }

    private function findRequestOrFail(int $id): ?array
    {
        $request = RequestWorkflow::find($id);
        if ($request === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => __('requests.not_found', 'Request Not Found')]);
            return null;
        }

        return $request;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $this->render('errors/403', ['pageTitle' => __('errors.403_page', 'Access Denied')]);
    }

    private function approvalRecord(array $approvals, string $step): ?array
    {
        foreach ($approvals as $approval) {
            if (($approval['step'] ?? '') === $step) {
                return $approval;
            }
        }

        return null;
    }
}
