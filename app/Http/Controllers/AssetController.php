<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\SimplePdf;
use App\Support\UploadStore;

class AssetController extends Controller
{
    public function index(): void
    {
        $filters = $this->assetFiltersFromRequest();
        $assets = $this->filteredAssets(false, $filters);

        $this->render('assets.index', [
            'pageTitle' => __('nav.assets', 'Assets'),
            'assets' => $assets,
            'selectedStatus' => $filters['status'],
            'filters' => $filters,
            'statusOptions' => ['active', 'repair', 'broken', 'storage'],
            'categoryOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::categories()),
            'branchOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::branches()),
            'stageOptions' => ['ordered', 'received', 'deployed'],
            'archivedMode' => false,
        ]);
    }

    public function archived(): void
    {
        $filters = $this->assetFiltersFromRequest();
        $assets = $this->filteredAssets(true, $filters);

        $this->render('assets.index', [
            'pageTitle' => __('assets.archived_page', 'Archived Assets'),
            'assets' => $assets,
            'selectedStatus' => $filters['status'],
            'filters' => $filters,
            'statusOptions' => ['archived'],
            'categoryOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::categories()),
            'branchOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::branches()),
            'stageOptions' => ['ordered', 'received', 'deployed'],
            'archivedMode' => true,
        ]);
    }

    public function export(): void
    {
        $archivedMode = isset($_GET['archived']) && (string) $_GET['archived'] === '1';
        $filters = $this->assetFiltersFromRequest();
        $rows = $this->filteredAssets($archivedMode, $filters);
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xls')));
        $title = $archivedMode ? __('assets.archived_page', 'Archived Assets') : __('nav.assets', 'Assets');
        DataRepository::logAudit('export', 'assets', null, null, ['format' => $format, 'archived' => $archivedMode, 'filters' => $filters]);

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="alnahala-assets.pdf"');
            echo SimplePdf::fromLines($title, $this->assetPdfLines($title, $rows, $filters));
            return;
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-assets.xls"');
        echo $this->assetExcelMarkup($title, $rows, $filters);
    }

    public function bulkAction(): array
    {
        $assetIds = (array) ($_POST['asset_ids'] ?? []);
        $action = trim((string) ($_POST['bulk_action'] ?? ''));

        if ($assetIds === [] || $action === '') {
            flash('error', __('assets.bulk_missing', 'Select assets and a bulk action first.'));
            return $this->redirect('assets.index');
        }

        $result = DataRepository::bulkUpdateAssets($assetIds, $action, $_POST);
        DataRepository::logAudit('bulk_update', 'assets', null, null, ['action' => $action] + $result);
        flash('status', __('assets.bulk_done', 'Bulk action completed.') . ' ' . $result['updated'] . ' / ' . max(1, count($assetIds)));
        return $this->redirect('assets.index');
    }

    public function create(): void
    {
        $this->render('assets.form', [
            'pageTitle' => __('form.create_asset', 'Create Asset'),
            'asset' => null,
            'requestId' => trim((string) ($_GET['request_id'] ?? '')),
            'categories' => DataRepository::categoryNames(),
            'branches' => DataRepository::branchNames(),
            'employees' => DataRepository::activeEmployees(),
            'requestOptions' => DataRepository::assetRequestOptions(),
            'statuses' => ['active', 'repair', 'broken', 'storage'],
            'procurementStages' => ['ordered', 'received', 'deployed'],
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'request_id' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
            'category' => ['required'],
            'location' => ['required'],
            'status' => ['required', 'in:active,repair,broken,storage'],
            'procurement_stage' => ['required', 'in:ordered,received,deployed'],
        ]);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($quantity > 1 && trim((string) ($_POST['serial_number'] ?? '')) !== '') {
            $errors['serial_number'] = __('assets.batch_serial_not_allowed', 'Leave the serial number empty when creating multiple assets at once.');
        }
        if ($quantity > 1 && trim((string) ($_POST['assigned_to'] ?? '')) !== '') {
            $errors['assigned_to'] = __('assets.batch_assign_not_allowed', 'Multiple assets must be created in storage first, without direct employee assignment.');
        }
        if ($quantity > 1 && trim((string) ($_POST['status'] ?? 'storage')) !== 'storage') {
            $errors['status'] = __('assets.batch_status_storage_only', 'Multiple assets can only be created with storage status.');
        }
        $documentNames = $_FILES['documents']['name'] ?? [];
        $hasDocuments = is_array($documentNames)
            ? count(array_filter($documentNames, static fn (mixed $value): bool => trim((string) $value) !== '')) > 0
            : trim((string) $documentNames) !== '';
        if ($quantity > 1 && $hasDocuments) {
            $errors['documents'] = __('assets.batch_documents_not_allowed', 'Upload documents only when creating a single asset. For batch creation, add documents later if needed.');
        }
        if ($errors !== []) {
            return $this->validationRedirect('assets.create', $errors, $_POST);
        }
        $serialNumber = trim((string) ($_POST['serial_number'] ?? ''));
        if ($serialNumber !== '' && DataRepository::assetIdBySerial($serialNumber) !== null) {
            return $this->validationRedirect('assets.create', [
                'serial_number' => __('assets.serial_exists', 'An asset with this serial number already exists.'),
            ], $_POST);
        }
        $assetIds = [];
        for ($index = 0; $index < $quantity; $index++) {
            $payload = $_POST;
            if ($quantity > 1) {
                $payload['serial_number'] = '';
                $payload['assigned_to'] = '';
                $payload['status'] = 'storage';
            }

            $assetId = DataRepository::createAsset($payload);
            if ($quantity === 1) {
                $documents = UploadStore::saveAssetDocuments($assetId, $_FILES['documents'] ?? []);
                if ($documents !== []) {
                    DataRepository::updateAsset($assetId, $payload, $documents);
                }
            }
            $assetIds[] = $assetId;
            DataRepository::logAudit('create', 'assets', $assetId, null, [
                'name' => $_POST['name'] ?? '',
                'batch_quantity' => $quantity,
            ]);
        }

        flash('status', $quantity > 1
            ? strtr(__('assets.batch_created', 'Created :count assets successfully.'), [':count' => (string) $quantity])
            : __('flash.asset_created', 'Asset created successfully.'));

        return $quantity > 1
            ? $this->redirect('assets.index')
            : $this->redirect('assets.show', ['id' => $assetIds[0]]);
    }

    public function show(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        $this->render('assets.show', [
            'pageTitle' => $asset['name'],
            'asset' => $asset,
            'assignments' => DataRepository::assetAssignments((int) $id),
            'movements' => DataRepository::assetMovements((int) $id),
            'repairs' => DataRepository::assetRepairs((int) $id),
            'openRepair' => DataRepository::openRepair((int) $id),
            'handovers' => DataRepository::assetHandovers((int) $id),
            'maintenance' => DataRepository::assetMaintenance((int) $id),
        ]);
    }

    public function edit(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        $this->render('assets.form', [
            'pageTitle' => __('form.edit_asset', 'Edit Asset'),
            'asset' => $asset,
            'requestId' => (string) ($asset['request_id'] ?? ''),
            'categories' => DataRepository::categoryNames(),
            'branches' => DataRepository::branchNames(),
            'employees' => DataRepository::activeEmployees(),
            'requestOptions' => DataRepository::assetRequestOptions(),
            'statuses' => ['active', 'repair', 'broken', 'storage'],
            'procurementStages' => ['ordered', 'received', 'deployed'],
        ]);
    }

    public function update(string $id): array
    {
        $assetId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'category' => ['required'],
            'location' => ['required'],
            'status' => ['required', 'in:active,repair,broken,storage'],
            'procurement_stage' => ['required', 'in:ordered,received,deployed'],
        ]);
        if (trim((string) ($_POST['request_id'] ?? '')) !== '') {
            $errors = array_merge($errors, $this->validate($_POST, [
                'request_id' => ['numeric'],
            ]));
        }
        if ($errors !== []) {
            return $this->validationRedirect('assets.edit', $errors, $_POST, ['id' => $assetId]);
        }
        $serialNumber = trim((string) ($_POST['serial_number'] ?? ''));
        $existingSerialAssetId = $serialNumber !== '' ? DataRepository::assetIdBySerial($serialNumber) : null;
        if ($existingSerialAssetId !== null && $existingSerialAssetId !== $assetId) {
            return $this->validationRedirect('assets.edit', [
                'serial_number' => __('assets.serial_exists', 'An asset with this serial number already exists.'),
            ], $_POST, ['id' => $assetId]);
        }
        $old = DataRepository::findAsset($assetId);
        $documents = UploadStore::saveAssetDocuments($assetId, $_FILES['documents'] ?? []);
        DataRepository::updateAsset($assetId, $_POST, $documents);

        DataRepository::logAudit('update', 'assets', $assetId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.asset_updated', 'Asset updated successfully.'));

        return $this->redirect('assets.show', ['id' => $assetId]);
    }

    public function destroy(string $id): array
    {
        $assetId = (int) $id;
        $old = DataRepository::findAsset($assetId);
        DataRepository::deleteAsset($assetId);
        DataRepository::logAudit('delete', 'assets', $assetId, $old, null);
        flash('status', __('flash.asset_removed', 'Asset removed successfully.'));

        return $this->redirect('assets.index');
    }

    public function moveForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.archived_locked', 'Archived assets cannot be moved or assigned again.'));
            header('Location: ' . route('assets.show', ['id' => (int) $id]));
            exit;
        }

        $this->render('assets.move', [
            'pageTitle' => __('assets.move_page', 'Move Asset'),
            'asset' => $asset,
            'branches' => DataRepository::branches(),
            'employees' => DataRepository::activeEmployees(),
            'assignments' => DataRepository::assetAssignments((int) $id),
        ]);
    }

    public function moveStore(string $id): array
    {
        $assetId = (int) $id;
        $asset = DataRepository::findAsset($assetId);
        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.archived_locked', 'Archived assets cannot be moved or assigned again.'));
            return $this->redirect('assets.show', ['id' => $assetId]);
        }
        $files = $_FILES['movement_documents'] ?? [];

        DataRepository::moveAsset($assetId, $_POST, $files);
        DataRepository::logAudit('move', 'assets', $assetId, null, ['name' => 'asset movement']);
        flash('status', __('movements.saved', 'Asset movement and assignment updated successfully.'));

        return $this->redirect('assets.show', ['id' => $assetId]);
    }

    public function returnForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.archived_locked', 'Archived assets cannot be moved or assigned again.'));
            header('Location: ' . route('assets.show', ['id' => (int) $id]));
            exit;
        }

        $this->render('assets.return', [
            'pageTitle' => __('assets.return_title', 'Return Asset'),
            'asset' => $asset,
            'branches' => DataRepository::branches(),
            'assignments' => DataRepository::assetAssignments((int) $id),
            'returnStatuses' => ['storage', 'broken', 'repair'],
        ]);
    }

    public function returnStore(string $id): array
    {
        $assetId = (int) $id;
        $files = $_FILES['return_documents'] ?? [];

        $errors = $this->validate($_POST, [
            'status' => ['required', 'in:storage,broken,repair'],
            'return_notes' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('assets.return', $errors, $_POST, ['id' => $assetId]);
        }

        $old = DataRepository::findAsset($assetId);
        DataRepository::returnAsset($assetId, $_POST, $files);
        DataRepository::logAudit('return', 'assets', $assetId, $old, ['status' => $_POST['status'] ?? 'storage']);
        $asset = DataRepository::findAsset($assetId);
        if ($asset !== null) {
            DataRepository::notifyAssetEvent(
                'asset_returned',
                $assetId,
                __('notifications.return_title', 'Asset returned'),
                (string) $asset['name'] . ' (' . (string) $asset['tag'] . ')',
                route('assets.show', ['id' => $assetId])
            );
        }
        flash('status', __('assets.returned_success', 'Asset returned successfully.'));

        return $this->redirect('assets.show', ['id' => $assetId]);
    }

    public function repairForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.archived_locked', 'Archived assets cannot be moved or assigned again.'));
            header('Location: ' . route('assets.show', ['id' => (int) $id]));
            exit;
        }

        $this->render('assets.repair', [
            'pageTitle' => __('assets.repair_title', 'Repair Workflow'),
            'asset' => $asset,
            'openRepair' => DataRepository::openRepair((int) $id),
            'repairs' => DataRepository::assetRepairs((int) $id),
            'completionStatuses' => ['storage', 'active'],
        ]);
    }

    public function repairStore(string $id): array
    {
        $assetId = (int) $id;
        $files = $_FILES['repair_documents'] ?? [];
        $mode = trim((string) ($_POST['repair_mode'] ?? 'send'));
        $openRepair = DataRepository::openRepair($assetId);

        if ($mode === 'complete' && $openRepair !== null) {
            $errors = $this->validate($_POST, [
                'outcome' => ['required', 'in:repaired,unrepairable'],
            ]);
            if (($_POST['outcome'] ?? '') === 'repaired') {
                $errors = array_merge($errors, $this->validate($_POST, [
                    'return_status' => ['required', 'in:storage,active'],
                ]));
            }
            if ($errors !== []) {
                return $this->validationRedirect('assets.repair', $errors, $_POST, ['id' => $assetId]);
            }

            $old = DataRepository::findAsset($assetId);
            DataRepository::completeAssetRepair($assetId, $_POST, $files);
            DataRepository::logAudit('repair_complete', 'assets', $assetId, $old, ['outcome' => $_POST['outcome'] ?? '']);
            $asset = DataRepository::findAsset($assetId);
            if ($asset !== null) {
                DataRepository::notifyAssetEvent(
                    'repair_completed',
                    $assetId,
                    __('notifications.repair_complete_title', 'Repair completed'),
                    (string) $asset['name'] . ' (' . (string) $asset['tag'] . ') - ' . (string) __('repair.' . ((string) ($_POST['outcome'] ?? 'repaired')), (string) ($_POST['outcome'] ?? 'repaired')),
                    route('assets.show', ['id' => $assetId])
                );
            }
            flash('status', __('assets.repair_completed_success', 'Repair completion saved successfully.'));
            return $this->redirect('assets.show', ['id' => $assetId]);
        }

        $errors = $this->validate($_POST, [
            'vendor_name' => ['required'],
            'repair_notes' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('assets.repair', $errors, $_POST, ['id' => $assetId]);
        }

        $old = DataRepository::findAsset($assetId);
        DataRepository::sendAssetToRepair($assetId, $_POST, $files);
        DataRepository::logAudit('repair_send', 'assets', $assetId, $old, ['vendor_name' => $_POST['vendor_name'] ?? '']);
        $asset = DataRepository::findAsset($assetId);
        if ($asset !== null) {
            DataRepository::notifyAssetEvent(
                'repair_sent',
                $assetId,
                __('notifications.repair_sent_title', 'Asset sent to repair'),
                (string) $asset['name'] . ' (' . (string) $asset['tag'] . ') - ' . trim((string) ($_POST['vendor_name'] ?? '')),
                route('assets.repair', ['id' => $assetId])
            );
        }
        flash('status', __('assets.repair_sent_success', 'Asset sent to repair successfully.'));

        return $this->redirect('assets.show', ['id' => $assetId]);
    }

    public function archiveForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);

        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.already_archived', 'This asset is already archived.'));
            header('Location: ' . route('assets.show', ['id' => (int) $id]));
            exit;
        }

        $this->render('assets.archive', [
            'pageTitle' => __('assets.archive_title', 'Archive Asset'),
            'asset' => $asset,
        ]);
    }

    public function archiveStore(string $id): array
    {
        $assetId = (int) $id;
        $asset = DataRepository::findAsset($assetId);
        if (($asset['status'] ?? '') === 'archived') {
            flash('error', __('assets.already_archived', 'This asset is already archived.'));
            return $this->redirect('assets.show', ['id' => $assetId]);
        }
        $files = $_FILES['archive_documents'] ?? [];
        $hasDocument = isset($files['name']) && array_filter((array) $files['name'], static fn ($name): bool => trim((string) $name) !== '') !== [];

        if (!$hasDocument) {
            flash('error', __('assets.archive_document_required', 'Upload an approval document before archiving this asset.'));
            return $this->redirect('assets.archive', ['id' => $assetId]);
        }

        $reason = trim((string) ($_POST['archive_reason'] ?? ''));
        if ($reason === '') {
            flash('error', __('assets.archive_reason_required', 'Enter the archive reason before archiving this asset.'));
            return $this->redirect('assets.archive', ['id' => $assetId]);
        }

        $old = DataRepository::findAsset($assetId);
        DataRepository::archiveAsset($assetId, $reason, $files);
        DataRepository::logAudit('archive', 'assets', $assetId, $old, ['status' => 'archived', 'reason' => $reason]);
        $asset = DataRepository::findAsset($assetId);
        if ($asset !== null) {
            DataRepository::notifyAssetEvent(
                'asset_archived',
                $assetId,
                __('notifications.archive_title', 'Asset archived'),
                (string) $asset['name'] . ' (' . (string) $asset['tag'] . ')',
                route('assets.show', ['id' => $assetId])
            );
        }
        flash('status', __('assets.archived_success', 'Asset archived successfully.'));

        return $this->redirect('assets.show', ['id' => $assetId]);
    }

    public function handoverForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);
        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        $this->render('assets.handover', [
            'pageTitle' => __('assets.handover_title', 'Asset Handover'),
            'asset' => $asset,
            'employees' => DataRepository::activeEmployees(),
            'handovers' => DataRepository::assetHandovers((int) $id),
            'assignments' => DataRepository::assetAssignments((int) $id),
        ]);
    }

    public function handoverStore(string $id): array
    {
        $assetId = (int) $id;
        $errors = $this->validate($_POST, [
            'employee_id' => ['required', 'numeric'],
            'handover_type' => ['required', 'in:issue,return'],
            'handover_date' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('assets.handover', $errors, $_POST, ['id' => $assetId]);
        }

        $handoverId = DataRepository::createAssetHandover($assetId, $_POST);
        DataRepository::logAudit('handover', 'asset_handovers', $handoverId, null, [
            'asset_id' => $assetId,
            'employee_id' => $_POST['employee_id'] ?? '',
            'handover_type' => $_POST['handover_type'] ?? '',
        ]);
        flash('status', __('assets.handover_saved', 'Handover form created successfully.'));

        return $this->redirect('assets.handover.print', ['id' => $handoverId]);
    }

    public function handoverPrint(string $id): void
    {
        $handover = DataRepository::findAssetHandover((int) $id);
        if ($handover === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Handover Not Found']);
            return;
        }

        $format = strtolower(trim((string) ($_GET['format'] ?? 'html')));
        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="asset-handover-' . (int) $handover['id'] . '.pdf"');
            echo SimplePdf::fromLines('Alnahala AMS Handover', [
                'Document: ' . __('assets.handover_title', 'Asset Handover'),
                'Type: ' . __('handover.' . ((string) $handover['handover_type']), (string) $handover['handover_type']),
                'Date: ' . (string) $handover['handover_date'],
                'Asset: ' . (string) $handover['asset_name'] . ' (' . (string) $handover['tag'] . ')',
                'Category: ' . (string) $handover['category_name'],
                'Branch: ' . (string) $handover['branch_name'],
                'Employee: ' . (string) $handover['employee_name'],
                'Employee Code: ' . (string) $handover['employee_code'],
                'Job Title: ' . (string) $handover['job_title'],
                'Email: ' . (string) $handover['company_email'],
                'Notes: ' . (string) $handover['notes'],
                'Prepared By: ' . (string) $handover['created_by'],
            ]);
            return;
        }

        $this->render('assets.handover-print', [
            'pageTitle' => __('assets.handover_title', 'Asset Handover'),
            'handover' => $handover,
        ]);
    }

    public function maintenanceForm(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);
        if ($asset === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Asset Not Found']);
            return;
        }

        $this->render('assets.maintenance', [
            'pageTitle' => __('assets.maintenance_title', 'Preventive Maintenance'),
            'asset' => $asset,
            'records' => DataRepository::assetMaintenance((int) $id),
            'openMaintenance' => DataRepository::openMaintenance((int) $id),
        ]);
    }

    public function maintenanceStore(string $id): array
    {
        $assetId = (int) $id;
        $mode = trim((string) ($_POST['maintenance_mode'] ?? 'schedule'));

        if ($mode === 'complete') {
            $errors = $this->validate($_POST, [
                'maintenance_id' => ['required', 'numeric'],
                'completed_date' => ['required'],
                'result_summary' => ['required'],
            ]);
            if ($errors !== []) {
                return $this->validationRedirect('assets.maintenance', $errors, $_POST, ['id' => $assetId]);
            }

            DataRepository::completeAssetMaintenance((int) $_POST['maintenance_id'], $_POST);
            DataRepository::logAudit('maintenance_complete', 'asset_maintenance', (int) $_POST['maintenance_id'], null, [
                'asset_id' => $assetId,
            ]);
            flash('status', __('assets.maintenance_completed', 'Maintenance record completed successfully.'));
            return $this->redirect('assets.maintenance', ['id' => $assetId]);
        }

        $errors = $this->validate($_POST, [
            'maintenance_type' => ['required'],
            'scheduled_date' => ['required'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('assets.maintenance', $errors, $_POST, ['id' => $assetId]);
        }

        $recordId = DataRepository::createAssetMaintenance($assetId, $_POST);
        DataRepository::logAudit('maintenance_schedule', 'asset_maintenance', $recordId, null, [
            'asset_id' => $assetId,
            'maintenance_type' => $_POST['maintenance_type'] ?? '',
        ]);
        flash('status', __('assets.maintenance_scheduled', 'Maintenance scheduled successfully.'));
        return $this->redirect('assets.maintenance', ['id' => $assetId]);
    }

    private function assetFiltersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'branch' => trim((string) ($_GET['branch'] ?? '')),
            'stage' => trim((string) ($_GET['stage'] ?? '')),
        ];
    }

    private function filteredAssets(bool $archivedMode, array $filters): array
    {
        $rows = DataRepository::assets();

        return array_values(array_filter($rows, static function (array $asset) use ($archivedMode, $filters): bool {
            $isArchived = (string) ($asset['status'] ?? '') === 'archived';
            if ($archivedMode !== $isArchived) {
                return false;
            }

            $haystack = strtolower(implode(' ', [
                (string) ($asset['name'] ?? ''),
                (string) ($asset['tag'] ?? ''),
                (string) ($asset['serial_number'] ?? ''),
                (string) ($asset['brand'] ?? ''),
                (string) ($asset['model'] ?? ''),
                (string) ($asset['vendor_name'] ?? ''),
                (string) ($asset['invoice_number'] ?? ''),
                (string) ($asset['category'] ?? ''),
                (string) ($asset['location'] ?? ''),
                (string) ($asset['assigned_to'] ?? ''),
                (string) ($asset['archive_reason'] ?? ''),
            ]));

            if ($filters['q'] !== '' && !str_contains($haystack, strtolower($filters['q']))) {
                return false;
            }

            if ($filters['status'] !== '' && (string) ($asset['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['category'] !== '' && (string) ($asset['category'] ?? '') !== $filters['category']) {
                return false;
            }

            if ($filters['branch'] !== '' && (string) ($asset['location'] ?? '') !== $filters['branch']) {
                return false;
            }

            if ($filters['stage'] !== '' && (string) ($asset['procurement_stage'] ?? '') !== $filters['stage']) {
                return false;
            }

            return true;
        }));
    }

    private function assetPdfLines(string $title, array $rows, array $filters): array
    {
        $lines = [
            'Alnahala AMS',
            $title,
            'Generated at: ' . date('Y-m-d H:i'),
            'Filters: ' . $this->filterSummary($filters),
            'Rows: ' . count($rows),
            '',
        ];

        if ($rows === []) {
            $lines[] = 'No matching records.';
            return $lines;
        }

        foreach ($rows as $index => $row) {
            $lines[] = sprintf(
                '%d. %s | %s | %s | %s | %s | %s',
                $index + 1,
                (string) $row['name'],
                (string) $row['tag'],
                (string) $row['category'],
                (string) $row['location'],
                (string) __('status.' . ((string) $row['status']), (string) $row['status']),
                (string) __('stage.' . ((string) $row['procurement_stage']), (string) $row['procurement_stage'])
            );
        }

        return $lines;
    }

    private function assetExcelMarkup(string $title, array $rows, array $filters): string
    {
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; color: #1f2937; }
                table { border-collapse: collapse; width: 100%; margin-top: 14px; }
                th, td { border: 1px solid #cbd5e1; padding: 8px 10px; }
                th { background: #dbeafe; text-align: left; }
            </style>
        </head>
        <body>
            <h2><?= e($title) ?></h2>
            <p>Generated <?= e(date('Y-m-d H:i')) ?> | <?= e($this->filterSummary($filters)) ?></p>
            <table>
                <tr>
                    <th><?= e(__('assets.name', 'Asset Name')) ?></th>
                    <th><?= e(__('assets.tag', 'Asset Tag')) ?></th>
                    <th><?= e(__('assets.category', 'Category')) ?></th>
                    <th><?= e(__('common.location', 'Location')) ?></th>
                    <th><?= e(__('assets.primary_employee', 'Primary Employee')) ?></th>
                    <th><?= e(__('assets.status', 'Operational Status')) ?></th>
                    <th><?= e(__('assets.stage', 'Procurement Stage')) ?></th>
                </tr>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7">No matching records.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e((string) $row['name']) ?></td>
                            <td><?= e((string) $row['tag']) ?></td>
                            <td><?= e((string) $row['category']) ?></td>
                            <td><?= e((string) $row['location']) ?></td>
                            <td><?= e((string) $row['assigned_to']) ?></td>
                            <td><?= e(__('status.' . ((string) $row['status']), (string) $row['status'])) ?></td>
                            <td><?= e(__('stage.' . ((string) $row['procurement_stage']), (string) $row['procurement_stage'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </body>
        </html>
        <?php
        return (string) ob_get_clean();
    }

    private function filterSummary(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if ($value !== '') {
                $parts[] = $key . ': ' . $value;
            }
        }

        return $parts === [] ? 'All records' : implode(' | ', $parts);
    }
}
