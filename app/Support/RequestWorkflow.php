<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

class RequestWorkflow
{
    private const REQUESTER_OWN_STATUSES = ['draft', 'needs_info'];

    public static function configuration(): array
    {
        $settings = DataRepository::systemSettings();
        $financeMode = (string) ($settings['request_workflow_finance_mode'] ?? 'always');
        if (!in_array($financeMode, ['always', 'threshold', 'disabled'], true)) {
            $financeMode = 'always';
        }

        $defaultScenario = (string) ($settings['request_default_scenario'] ?? 'general');
        if (!in_array($defaultScenario, ['general', 'employee_onboarding', 'branch_deployment', 'replacement', 'stock_replenishment'], true)) {
            $defaultScenario = 'general';
        }

        $defaultUrgency = (string) ($settings['request_default_urgency'] ?? 'normal');
        if (!in_array($defaultUrgency, ['low', 'normal', 'high', 'critical'], true)) {
            $defaultUrgency = 'normal';
        }

        $financeThreshold = trim((string) ($settings['request_workflow_finance_threshold'] ?? '0'));

        return [
            'it_manager_required' => ($settings['request_workflow_it_manager_required'] ?? '1') === '1',
            'allow_storage_fulfillment' => ($settings['request_workflow_allow_storage_fulfillment'] ?? '1') === '1',
            'finance_mode' => $financeMode,
            'finance_threshold' => is_numeric($financeThreshold) ? max(0, (float) $financeThreshold) : 0.0,
            'auto_close_on_receive' => ($settings['request_workflow_auto_close_on_receive'] ?? '0') === '1',
            'default_scenario' => $defaultScenario,
            'default_urgency' => $defaultUrgency,
            'storage_fulfillment_status' => (($settings['request_workflow_it_manager_required'] ?? '1') === '1') ? 'pending_it_manager' : 'pending_it',
            'storage_fulfillment_role' => (($settings['request_workflow_it_manager_required'] ?? '1') === '1') ? 'it_manager' : 'technician',
            'storage_fulfillment_approval_step' => (($settings['request_workflow_it_manager_required'] ?? '1') === '1') ? 'it_manager' : 'it',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft' => ['label' => __('requests.status.draft', 'Draft'), 'badge' => 'secondary'],
            'pending_it' => ['label' => __('requests.status.pending_it', 'Pending IT'), 'badge' => 'warning'],
            'pending_it_manager' => ['label' => __('requests.status.pending_it_manager', 'Pending IT Manager'), 'badge' => 'warning'],
            'pending_finance' => ['label' => __('requests.status.pending_finance', 'Pending Finance'), 'badge' => 'warning'],
            'needs_info' => ['label' => __('requests.status.needs_info', 'Needs Info'), 'badge' => 'info'],
            'rejected' => ['label' => __('requests.status.rejected', 'Rejected'), 'badge' => 'danger'],
            'approved' => ['label' => __('requests.status.approved', 'Approved'), 'badge' => 'success'],
            'purchased' => ['label' => __('requests.status.purchased', 'Purchased'), 'badge' => 'primary'],
            'received' => ['label' => __('requests.status.received', 'Received'), 'badge' => 'primary'],
            'closed' => ['label' => __('requests.status.closed', 'Closed'), 'badge' => 'dark'],
        ];
    }

    public static function pendingRoleLabels(): array
    {
        return [
            'requester' => __('requests.pending.requester', 'Requester'),
            'technician' => __('requests.pending.technician', 'IT'),
            'it_manager' => __('requests.pending.it_manager', 'IT Manager'),
            'finance' => __('requests.pending.finance', 'Finance'),
            'none' => __('requests.pending.none', 'Completed'),
        ];
    }

    public static function urgencyLabels(): array
    {
        return [
            'low' => __('requests.urgency.low', 'Low'),
            'normal' => __('requests.urgency.normal', 'Normal'),
            'high' => __('requests.urgency.high', 'High'),
            'critical' => __('requests.urgency.critical', 'Critical'),
        ];
    }

    public static function requestTypeLabels(): array
    {
        return [
            'asset' => __('requests.type.asset', 'Asset'),
            'spare_part' => __('requests.type.spare_part', 'Spare Part'),
            'license' => __('requests.type.license', 'License'),
            'mixed' => __('requests.type.mixed', 'Mixed'),
        ];
    }

    public static function scenarioLabels(): array
    {
        return [
            'general' => __('requests.scenario.general', 'General Request'),
            'employee_onboarding' => __('requests.scenario.employee_onboarding', 'Employee Onboarding'),
            'branch_deployment' => __('requests.scenario.branch_deployment', 'Branch Deployment'),
            'replacement' => __('requests.scenario.replacement', 'Replacement'),
            'stock_replenishment' => __('requests.scenario.stock_replenishment', 'Stock Replenishment'),
        ];
    }

    public static function itemTypeLabels(): array
    {
        return [
            'asset' => __('requests.type.asset', 'Asset'),
            'spare_part' => __('requests.type.spare_part', 'Spare Part'),
            'license' => __('requests.type.license', 'License'),
        ];
    }

    public static function fulfillmentPreferenceLabels(): array
    {
        return [
            'either' => __('requests.fulfillment.either', 'Storage or Purchase'),
            'storage' => __('requests.fulfillment.storage', 'Storage'),
            'purchase' => __('requests.fulfillment.purchase', 'Purchase'),
        ];
    }

    public static function assignmentTargetLabels(): array
    {
        return [
            'employee' => __('requests.assignment.employee', 'Employee'),
            'branch' => __('requests.assignment.branch', 'Branch'),
            'stock' => __('requests.assignment.stock', 'Stock'),
        ];
    }

    public static function decisionOptions(): array
    {
        return ['approve', 'return', 'reject'];
    }

    public static function advanceOptions(): array
    {
        return [
            'approved' => 'purchased',
            'purchased' => 'received',
            'received' => 'closed',
        ];
    }

    public static function statusLabel(string $status): string
    {
        $statuses = self::statuses();
        return $statuses[$status]['label'] ?? $status;
    }

    public static function pendingRoleLabel(?string $role): string
    {
        $labels = self::pendingRoleLabels();
        return $labels[$role ?? 'none'] ?? ($role ?? __('requests.pending.none', 'Completed'));
    }

    public static function urgencyLabel(?string $urgency): string
    {
        $labels = self::urgencyLabels();
        return $labels[$urgency ?? 'normal'] ?? ($urgency ?? __('requests.urgency.normal', 'Normal'));
    }

    public static function approvalDecisionLabel(?string $decision): string
    {
        return match ($decision) {
            'approved' => __('requests.decision.approved', 'Approved'),
            'returned' => __('requests.decision.returned', 'Returned'),
            'rejected' => __('requests.decision.rejected', 'Rejected'),
            'skipped' => __('requests.decision.skipped', 'Skipped'),
            default => __('requests.waiting', 'Waiting'),
        };
    }

    public static function storageFulfillmentStatus(): string
    {
        return (string) self::configuration()['storage_fulfillment_status'];
    }

    public static function storageFulfillmentRole(): string
    {
        return (string) self::configuration()['storage_fulfillment_role'];
    }

    public static function timelineActionLabel(string $action): string
    {
        return match ($action) {
            'draft_created' => __('requests.timeline.draft_created', 'Draft created'),
            'draft_updated' => __('requests.timeline.draft_updated', 'Draft updated'),
            'submitted' => __('requests.timeline.submitted', 'Submitted'),
            'approved_it' => __('requests.timeline.approved_it', 'Approved by IT'),
            'approved_it_manager' => __('requests.timeline.approved_it_manager', 'Approved by IT Manager'),
            'approved_finance' => __('requests.timeline.approved_finance', 'Approved by Finance'),
            'returned_for_info' => __('requests.timeline.returned_for_info', 'Returned for more information'),
            'rejected' => __('requests.timeline.rejected', 'Rejected'),
            'marked_purchased' => __('requests.timeline.marked_purchased', 'Marked as purchased'),
            'marked_received' => __('requests.timeline.marked_received', 'Marked as received'),
            'marked_closed' => __('requests.timeline.marked_closed', 'Closed'),
            'fulfilled_asset_from_storage' => __('requests.timeline.fulfilled_asset_from_storage', 'Fulfilled from storage and assigned'),
            'fulfilled_spare_part_from_storage' => __('requests.timeline.fulfilled_spare_part_from_storage', 'Spare part issued from storage'),
            'fulfilled_license_from_stock' => __('requests.timeline.fulfilled_license_from_stock', 'License allocated from stock'),
            'linked_existing_asset' => __('requests.timeline.linked_existing_asset', 'Linked existing storage asset'),
            'stock_increased_spare_part' => __('requests.timeline.stock_increased_spare_part', 'Spare part stock increased'),
            'stock_increased_license' => __('requests.timeline.stock_increased_license', 'License stock increased'),
            default => ucwords(str_replace('_', ' ', $action)),
        };
    }

    public static function requests(array $filters = [], ?array $viewer = null): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $where = [];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $where[] = '(asset_requests.request_no LIKE :q
                OR asset_requests.title LIKE :q
                OR COALESCE(asset_requests.asset_specification, \'\') LIKE :q
                OR COALESCE(requested_by.name, \'\') LIKE :q
                OR COALESCE(requested_for.name, \'\') LIKE :q
                OR EXISTS (
                    SELECT 1
                    FROM asset_request_items
                    WHERE asset_request_items.request_id = asset_requests.id
                      AND (
                        asset_request_items.item_name LIKE :q
                        OR COALESCE(asset_request_items.specification, \'\') LIKE :q
                      )
                ))';
            $params['q'] = '%' . $query . '%';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'asset_requests.status = :status';
            $params['status'] = $status;
        }

        $mine = trim((string) ($filters['mine'] ?? ''));
        if ($mine === '1' && isset($viewer['id'])) {
            $where[] = 'asset_requests.requested_by_user_id = :mine_user_id';
            $params['mine_user_id'] = (int) $viewer['id'];
        } elseif (!self::hasGlobalAccess($viewer) && isset($viewer['id'])) {
            $where[] = 'asset_requests.requested_by_user_id = :viewer_user_id';
            $params['viewer_user_id'] = (int) $viewer['id'];
        }

        $sql = "
            SELECT asset_requests.id,
                   asset_requests.request_no,
                   asset_requests.request_type,
                   asset_requests.scenario,
                   asset_requests.title,
                   asset_requests.quantity,
                   COALESCE(asset_requests.estimated_cost, 0) AS estimated_cost,
                   asset_requests.purchase_price,
                   asset_requests.urgency,
                   asset_requests.status,
                   COALESCE(asset_requests.fulfillment_source, '') AS fulfillment_source,
                   asset_requests.current_pending_role,
                   asset_requests.current_pending_user_id,
                   COALESCE(DATE_FORMAT(asset_requests.needed_by_date, '%Y-%m-%d'), '') AS needed_by_date,
                   COALESCE(DATE_FORMAT(asset_requests.submitted_at, '%Y-%m-%d %H:%i'), '') AS submitted_at,
                   COALESCE(DATE_FORMAT(asset_requests.approved_at, '%Y-%m-%d %H:%i'), '') AS approved_at,
                   COALESCE(DATE_FORMAT(asset_requests.created_at, '%Y-%m-%d %H:%i'), '') AS created_at,
                   COALESCE(requested_by.name, '') AS requested_by_name,
                   COALESCE(requested_for.name, '') AS requested_for_name,
                   COALESCE(branches.name, '') AS branch_name,
                   COALESCE(asset_categories.name, '') AS category_name,
                   COALESCE(pending_user.name, '') AS pending_user_name
            FROM asset_requests
            JOIN users AS requested_by ON requested_by.id = asset_requests.requested_by_user_id
            LEFT JOIN employees AS requested_for ON requested_for.id = asset_requests.requested_for_employee_id
            LEFT JOIN branches ON branches.id = asset_requests.branch_id
            LEFT JOIN asset_categories ON asset_categories.id = asset_requests.category_id
            LEFT JOIN users AS pending_user ON pending_user.id = asset_requests.current_pending_user_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY asset_requests.id DESC';

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    public static function summary(?array $viewer = null): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $where = '';
        $params = [];
        if (!self::hasGlobalAccess($viewer) && isset($viewer['id'])) {
            $where = ' WHERE requested_by_user_id = :viewer_user_id';
            $params['viewer_user_id'] = (int) $viewer['id'];
        }

        $statement = $pdo->prepare(
            'SELECT status, COUNT(*) AS total
             FROM asset_requests' . $where . '
             GROUP BY status'
        );
        $statement->execute($params);
        $rows = $statement->fetchAll() ?: [];

        $summary = [];
        foreach ($rows as $row) {
            $summary[(string) $row['status']] = (int) $row['total'];
        }

        return $summary;
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT asset_requests.*,
                    COALESCE(DATE_FORMAT(asset_requests.needed_by_date, '%Y-%m-%d'), '') AS needed_by_date,
                    COALESCE(DATE_FORMAT(asset_requests.submitted_at, '%Y-%m-%d %H:%i'), '') AS submitted_at,
                    COALESCE(DATE_FORMAT(asset_requests.approved_at, '%Y-%m-%d %H:%i'), '') AS approved_at,
                    COALESCE(DATE_FORMAT(asset_requests.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                    COALESCE(DATE_FORMAT(asset_requests.received_date, '%Y-%m-%d'), '') AS received_date,
                    COALESCE(DATE_FORMAT(asset_requests.closed_at, '%Y-%m-%d %H:%i'), '') AS closed_at,
                    COALESCE(DATE_FORMAT(asset_requests.created_at, '%Y-%m-%d %H:%i'), '') AS created_at,
                    COALESCE(DATE_FORMAT(asset_requests.updated_at, '%Y-%m-%d %H:%i'), '') AS updated_at,
                    COALESCE(requested_by.name, '') AS requested_by_name,
                    COALESCE(requested_by.email, '') AS requested_by_email,
                    COALESCE(requested_for.name, '') AS requested_for_name,
                    COALESCE(requested_for.employee_code, '') AS requested_for_code,
                    COALESCE(branches.name, '') AS branch_name,
                    COALESCE(asset_categories.name, '') AS category_name,
                    COALESCE(pending_user.name, '') AS pending_user_name,
                    COALESCE(asset_requests.purchase_vendor, '') AS purchase_vendor,
                    COALESCE(asset_requests.purchase_reference, '') AS purchase_reference,
                    (
                        SELECT COUNT(*)
                        FROM asset_request_items
                        WHERE asset_request_items.request_id = asset_requests.id
                    ) AS items_count
             FROM asset_requests
             JOIN users AS requested_by ON requested_by.id = asset_requests.requested_by_user_id
             LEFT JOIN employees AS requested_for ON requested_for.id = asset_requests.requested_for_employee_id
             LEFT JOIN branches ON branches.id = asset_requests.branch_id
             LEFT JOIN asset_categories ON asset_categories.id = asset_requests.category_id
             LEFT JOIN users AS pending_user ON pending_user.id = asset_requests.current_pending_user_id
             WHERE asset_requests.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function timeline(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT asset_request_timeline.id,
                    asset_request_timeline.action,
                    COALESCE(asset_request_timeline.from_status, '') AS from_status,
                    COALESCE(asset_request_timeline.to_status, '') AS to_status,
                    COALESCE(asset_request_timeline.comment, '') AS comment,
                    COALESCE(asset_request_timeline.actor_role, '') AS actor_role,
                    COALESCE(users.name, 'System') AS actor_name,
                    DATE_FORMAT(asset_request_timeline.created_at, '%Y-%m-%d %H:%i') AS created_at
             FROM asset_request_timeline
             LEFT JOIN users ON users.id = asset_request_timeline.actor_user_id
             WHERE asset_request_timeline.request_id = :request_id
             ORDER BY asset_request_timeline.id DESC"
        );
        $statement->execute(['request_id' => $requestId]);
        return $statement->fetchAll() ?: [];
    }

    public static function approvals(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT asset_request_approvals.step,
                    asset_request_approvals.decision,
                    COALESCE(asset_request_approvals.comment, '') AS comment,
                    COALESCE(users.name, '') AS approver_name,
                    DATE_FORMAT(asset_request_approvals.acted_at, '%Y-%m-%d %H:%i') AS acted_at
             FROM asset_request_approvals
             LEFT JOIN users ON users.id = asset_request_approvals.approver_user_id
             WHERE asset_request_approvals.request_id = :request_id
             ORDER BY asset_request_approvals.id ASC"
        );
        $statement->execute(['request_id' => $requestId]);
        return $statement->fetchAll() ?: [];
    }

    public static function linkedAssetsCount(int $requestId): int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return 0;
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE request_id = :request_id');
        $statement->execute(['request_id' => $requestId]);
        return (int) $statement->fetchColumn();
    }

    public static function storageAssetOptions(array $request): array
    {
        $item = self::singleFulfillableItem($request);
        if ($item === null || (string) ($item['item_type'] ?? '') !== 'asset') {
            return [];
        }

        return self::storageAssetOptionsForItem($item);
    }

    public static function sparePartOptions(array $request): array
    {
        $item = self::singleFulfillableItem($request);
        if ($item === null || (string) ($item['item_type'] ?? '') !== 'spare_part') {
            return [];
        }

        return self::sparePartOptionsForItem($item);
    }

    public static function sparePartIssues(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT spare_part_issues.id,
                    spare_part_issues.spare_part_id,
                    spare_part_issues.employee_id,
                    spare_part_issues.quantity,
                    COALESCE(spare_part_issues.notes, '') AS notes,
                    DATE_FORMAT(spare_part_issues.issued_at, '%Y-%m-%d %H:%i') AS issued_at,
                    COALESCE(spare_parts.name, '') AS spare_part_name,
                    COALESCE(spare_parts.part_number, '') AS part_number,
                    COALESCE(employees.name, '') AS employee_name,
                    COALESCE(users.name, 'System') AS issued_by_name
             FROM spare_part_issues
             JOIN spare_parts ON spare_parts.id = spare_part_issues.spare_part_id
             JOIN employees ON employees.id = spare_part_issues.employee_id
             LEFT JOIN users ON users.id = spare_part_issues.issued_by
             WHERE spare_part_issues.request_id = :request_id
             ORDER BY spare_part_issues.id DESC"
        );
        $statement->execute(['request_id' => $requestId]);
        return array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function licenseStockOptions(array $request): array
    {
        $item = self::singleFulfillableItem($request);
        if ($item === null || (string) ($item['item_type'] ?? '') !== 'license') {
            return [];
        }

        return self::licenseStockOptionsForItem($item);
    }

    public static function storageFulfillmentRows(array $request): array
    {
        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return [];
        }

        $rows = [];
        foreach (self::requestItems($requestId) as $item) {
            $itemType = (string) ($item['item_type'] ?? '');
            if (!in_array($itemType, ['asset', 'spare_part', 'license'], true)) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $assetOptions = $itemType === 'asset' ? self::storageAssetOptionsForItem($item) : [];
            $allSparePartOptions = $itemType === 'spare_part' ? self::sparePartOptionsForItem($item, false) : [];
            $sparePartOptions = $itemType === 'spare_part' ? array_values(array_filter(
                $allSparePartOptions,
                static fn (array $option): bool => (int) ($option['quantity'] ?? 0) >= $quantity
            )) : [];
            $allLicenseOptions = $itemType === 'license' ? self::licenseStockOptionsForItem($item, false) : [];
            $licenseOptions = $itemType === 'license' ? array_values(array_filter(
                $allLicenseOptions,
                static fn (array $option): bool => (int) ($option['available_seats'] ?? 0) >= $quantity
            )) : [];
            $availableCount = match ($itemType) {
                'asset' => count($assetOptions),
                'spare_part' => $allSparePartOptions === [] ? 0 : max(array_map(static fn (array $option): int => (int) ($option['quantity'] ?? 0), $allSparePartOptions)),
                'license' => $allLicenseOptions === [] ? 0 : max(array_map(static fn (array $option): int => (int) ($option['available_seats'] ?? 0), $allLicenseOptions)),
                default => 0,
            };
            $canFulfill = match ($itemType) {
                'asset' => count($assetOptions) >= $quantity,
                'spare_part' => $sparePartOptions !== [],
                'license' => $licenseOptions !== [],
                default => false,
            };

            $rows[] = $item + [
                'storage_asset_options' => $assetOptions,
                'spare_part_options' => $sparePartOptions,
                'license_stock_options' => $licenseOptions,
                'available_stock' => $availableCount,
                'can_fulfill' => $canFulfill,
                'stock_warning' => $canFulfill
                    ? ''
                    : strtr(
                        __('requests.not_enough_stock_warning', 'Not enough stock. Required: :required, available: :available.'),
                        [
                            ':required' => (string) $quantity,
                            ':available' => (string) $availableCount,
                        ]
                    ),
            ];
        }

        return $rows;
    }

    public static function licenseIssues(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT license_allocations.id,
                    license_allocations.license_id,
                    license_allocations.quantity,
                    COALESCE(licenses.product_name, '') AS product_name,
                    COALESCE(licenses.vendor_name, '') AS vendor_name,
                    COALESCE(employees.name, '') AS employee_name,
                    COALESCE(branches.name, '') AS branch_name,
                    DATE_FORMAT(license_allocations.allocated_at, '%Y-%m-%d %H:%i') AS allocated_at
             FROM license_allocations
             JOIN licenses ON licenses.id = license_allocations.license_id
             LEFT JOIN employees ON employees.id = license_allocations.employee_id
             LEFT JOIN branches ON branches.id = license_allocations.branch_id
             WHERE license_allocations.request_id = :request_id
             ORDER BY license_allocations.id DESC"
        );
        $statement->execute(['request_id' => $requestId]);
        return array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function requestItems(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT asset_request_items.id,
                    asset_request_items.item_type,
                    asset_request_items.item_name,
                    asset_request_items.category_id,
                    COALESCE(asset_categories.name, '') AS category_name,
                    asset_request_items.quantity,
                    asset_request_items.estimated_unit_cost,
                    asset_request_items.fulfillment_preference,
                    asset_request_items.assignment_target,
                    COALESCE(asset_request_items.specification, '') AS specification,
                    COALESCE(asset_request_items.notes, '') AS notes
             FROM asset_request_items
             LEFT JOIN asset_categories ON asset_categories.id = asset_request_items.category_id
             WHERE asset_request_items.request_id = :request_id
             ORDER BY asset_request_items.id ASC"
        );
        $statement->execute(['request_id' => $requestId]);
        $items = array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            $row['estimated_unit_cost'] = $row['estimated_unit_cost'] === null ? null : (float) $row['estimated_unit_cost'];
            return $row;
        }, $statement->fetchAll() ?: []);

        if ($items !== []) {
            return $items;
        }

        $request = self::find($requestId);
        if ($request === null) {
            return [];
        }

        return [self::legacyItemFromRequest($request)];
    }

    public static function create(array $input, int $requestedByUserId, bool $submitNow = false): int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database connection failed.');
        }

        $payload = self::normalizePayload($input);
        $items = self::normalizeItemsPayload($input);
        $payload['request_type'] = self::deriveRequestType($items);
        $payload['category_id'] = self::derivePrimaryCategoryId($items, $payload['category_id']);
        $payload['quantity'] = self::deriveTotalQuantity($items);
        $payload['estimated_cost'] = self::deriveEstimatedCost($items, $payload['estimated_cost']);
        $requestNo = self::generateRequestNo($pdo);

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'INSERT INTO asset_requests (
                    request_no,
                    requested_by_user_id,
                    requested_for_employee_id,
                    request_type,
                    scenario,
                    branch_id,
                    category_id,
                    title,
                    asset_specification,
                    justification,
                    quantity,
                    estimated_cost,
                    urgency,
                    needed_by_date,
                    status,
                    current_pending_role,
                    current_pending_user_id,
                    created_at,
                    updated_at
                 ) VALUES (
                    :request_no,
                    :requested_by_user_id,
                    :requested_for_employee_id,
                    :request_type,
                    :scenario,
                    :branch_id,
                    :category_id,
                    :title,
                    :asset_specification,
                    :justification,
                    :quantity,
                    :estimated_cost,
                    :urgency,
                    :needed_by_date,
                    :status,
                    :current_pending_role,
                    :current_pending_user_id,
                    NOW(),
                    NOW()
                 )'
            );

            $statement->execute([
                'request_no' => $requestNo,
                'requested_by_user_id' => $requestedByUserId,
                'requested_for_employee_id' => $payload['requested_for_employee_id'],
                'request_type' => $payload['request_type'],
                'scenario' => $payload['scenario'],
                'branch_id' => $payload['branch_id'],
                'category_id' => $payload['category_id'],
                'title' => $payload['title'],
                'asset_specification' => $payload['asset_specification'],
                'justification' => $payload['justification'],
                'quantity' => $payload['quantity'],
                'estimated_cost' => $payload['estimated_cost'],
                'urgency' => $payload['urgency'],
                'needed_by_date' => $payload['needed_by_date'],
                'status' => 'draft',
                'current_pending_role' => 'requester',
                'current_pending_user_id' => $requestedByUserId,
            ]);

            $requestId = (int) $pdo->lastInsertId();
            self::writeItems($pdo, $requestId, $items);

            self::addTimeline($pdo, $requestId, 'draft_created', null, 'draft', $payload['justification']);

            if ($submitNow) {
                self::submitInternal($pdo, $requestId, $requestedByUserId);
            }

            $pdo->commit();
            return $requestId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function update(int $id, array $input, bool $submitNow = false): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database connection failed.');
        }

        $request = self::find($id);
        if ($request === null) {
            throw new \RuntimeException('Request not found.');
        }

        $payload = self::normalizePayload($input);
        $items = self::normalizeItemsPayload($input);
        $payload['request_type'] = self::deriveRequestType($items);
        $payload['category_id'] = self::derivePrimaryCategoryId($items, $payload['category_id']);
        $payload['quantity'] = self::deriveTotalQuantity($items);
        $payload['estimated_cost'] = self::deriveEstimatedCost($items, $payload['estimated_cost']);

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'UPDATE asset_requests
                 SET requested_for_employee_id = :requested_for_employee_id,
                     request_type = :request_type,
                     scenario = :scenario,
                     branch_id = :branch_id,
                     category_id = :category_id,
                     title = :title,
                     asset_specification = :asset_specification,
                     justification = :justification,
                     quantity = :quantity,
                     estimated_cost = :estimated_cost,
                     urgency = :urgency,
                     needed_by_date = :needed_by_date,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'requested_for_employee_id' => $payload['requested_for_employee_id'],
                'request_type' => $payload['request_type'],
                'scenario' => $payload['scenario'],
                'branch_id' => $payload['branch_id'],
                'category_id' => $payload['category_id'],
                'title' => $payload['title'],
                'asset_specification' => $payload['asset_specification'],
                'justification' => $payload['justification'],
                'quantity' => $payload['quantity'],
                'estimated_cost' => $payload['estimated_cost'],
                'urgency' => $payload['urgency'],
                'needed_by_date' => $payload['needed_by_date'],
            ]);

            self::writeItems($pdo, $id, $items);

            self::addTimeline($pdo, $id, 'draft_updated', (string) $request['status'], (string) $request['status'], '');

            if ($submitNow && in_array((string) $request['status'], ['draft', 'needs_info'], true)) {
                self::submitInternal($pdo, $id, (int) ($request['requested_by_user_id'] ?? 0));
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $statement = $pdo->prepare('DELETE FROM asset_requests WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public static function submit(int $id): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database connection failed.');
        }

        $request = self::find($id);
        if ($request === null) {
            throw new \RuntimeException('Request not found.');
        }

        $pdo->beginTransaction();
        try {
            self::submitInternal($pdo, $id, (int) ($request['requested_by_user_id'] ?? 0));
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function decision(int $id, string $decision, string $comment = ''): void
    {
        $pdo = Database::connect();
        $request = self::find($id);
        if (!$pdo instanceof PDO || $request === null) {
            throw new \RuntimeException('Request not found.');
        }

        $actor = auth_user();
        if (!self::canApprove($request, $actor)) {
            throw new \RuntimeException('You are not allowed to approve this request.');
        }

        $step = self::approvalStepForStatus((string) $request['status'], $request);
        if ($step === null) {
            throw new \RuntimeException('This request is not awaiting approval.');
        }

        $actorId = (int) ($actor['id'] ?? 0);
        $comment = trim($comment);

        $pdo->beginTransaction();
        try {
            if ($decision === 'approve') {
                $nextStatus = $step['next_status'];
                $nextPendingRole = $step['next_pending_role'];
                $nextPendingUserId = self::pendingAssigneeId($pdo, $nextPendingRole);
                $approvedAt = $nextStatus === 'approved' ? date('Y-m-d H:i:s') : null;

                if ($nextStatus === 'approved') {
                    $nextPendingRole = 'finance';
                    $nextPendingUserId = self::pendingAssigneeId($pdo, 'finance');
                }

                $statement = $pdo->prepare(
                    'UPDATE asset_requests
                     SET status = :status,
                         current_pending_role = :current_pending_role,
                         current_pending_user_id = :current_pending_user_id,
                         approved_at = COALESCE(:approved_at, approved_at),
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'status' => $nextStatus,
                    'current_pending_role' => $nextPendingRole,
                    'current_pending_user_id' => $nextPendingUserId,
                    'approved_at' => $approvedAt,
                ]);

                self::addApproval($pdo, $id, $step['approval_step'], $actorId, 'approved', $comment);
                self::addTimeline($pdo, $id, 'approved_' . $step['approval_step'], (string) $request['status'], $nextStatus, $comment);

                if ($nextStatus === 'approved') {
                    if ($step['approval_step'] === 'finance') {
                        self::notifyUsers($pdo, [(int) $request['requested_by_user_id']], 'request_approved', [
                            'title' => __('requests.notifications.approved_title', 'Request approved'),
                            'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.approved_message', 'Finance approved the request.'),
                            'route' => route('requests.show', ['id' => $id]),
                        ]);
                    } else {
                        self::notifyRole($pdo, 'finance', 'request_pending', [
                            'title' => __('requests.notifications.pending_title', 'Request awaiting approval'),
                            'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.ready_for_procurement', 'Approved and ready for procurement actions.'),
                            'route' => route('requests.show', ['id' => $id]),
                        ]);
                        self::notifyUsers($pdo, [(int) $request['requested_by_user_id']], 'request_approved', [
                            'title' => __('requests.notifications.approved_title', 'Request approved'),
                            'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.approved_waiting_execution', 'The request is approved and waiting for purchase, receive, or close actions.'),
                            'route' => route('requests.show', ['id' => $id]),
                        ]);
                    }
                } else {
                    self::notifyRole($pdo, $nextPendingRole, 'request_pending', [
                        'title' => __('requests.notifications.pending_title', 'Request awaiting approval'),
                        'message' => (string) $request['request_no'] . ' - ' . self::statusLabel($nextStatus),
                        'route' => route('requests.show', ['id' => $id]),
                    ]);
                }
            } elseif ($decision === 'return') {
                $statement = $pdo->prepare(
                    'UPDATE asset_requests
                     SET status = :status,
                         current_pending_role = :current_pending_role,
                         current_pending_user_id = :current_pending_user_id,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'status' => 'needs_info',
                    'current_pending_role' => 'requester',
                    'current_pending_user_id' => (int) $request['requested_by_user_id'],
                ]);

                self::addApproval($pdo, $id, $step['approval_step'], $actorId, 'returned', $comment);
                self::addTimeline($pdo, $id, 'returned_for_info', (string) $request['status'], 'needs_info', $comment);

                self::notifyUsers($pdo, [(int) $request['requested_by_user_id']], 'request_returned', [
                    'title' => __('requests.notifications.returned_title', 'Request returned'),
                    'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.returned_message', 'Additional information is required.'),
                    'route' => route('requests.show', ['id' => $id]),
                ]);
            } elseif ($decision === 'reject') {
                $statement = $pdo->prepare(
                    'UPDATE asset_requests
                     SET status = :status,
                         current_pending_role = :current_pending_role,
                         current_pending_user_id = NULL,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'status' => 'rejected',
                    'current_pending_role' => 'none',
                ]);

                self::addApproval($pdo, $id, $step['approval_step'], $actorId, 'rejected', $comment);
                self::addTimeline($pdo, $id, 'rejected', (string) $request['status'], 'rejected', $comment);

                self::notifyUsers($pdo, [(int) $request['requested_by_user_id']], 'request_rejected', [
                    'title' => __('requests.notifications.rejected_title', 'Request rejected'),
                    'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.rejected_message', 'The request was rejected.'),
                    'route' => route('requests.show', ['id' => $id]),
                ]);
            } else {
                throw new \RuntimeException('Invalid decision.');
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function advance(int $id, array $input): void
    {
        $pdo = Database::connect();
        $request = self::find($id);
        if (!$pdo instanceof PDO || $request === null) {
            throw new \RuntimeException('Request not found.');
        }

        $actor = auth_user();
        if (!self::canAdvance($request, $actor)) {
            throw new \RuntimeException('You are not allowed to advance this request.');
        }

        $nextStatus = trim((string) ($input['next_status'] ?? ''));
        $allowedNext = self::advanceOptions()[(string) $request['status']] ?? null;
        if ($allowedNext !== $nextStatus) {
            throw new \RuntimeException('Invalid workflow transition.');
        }

        $comment = trim((string) ($input['comment'] ?? ''));
        $purchasePrice = self::normalizeAmount($input['purchase_price'] ?? null);
        $purchaseVendor = trim((string) ($input['purchase_vendor'] ?? ''));
        $purchaseReference = trim((string) ($input['purchase_reference'] ?? ''));
        $purchaseDate = self::normalizeDate($input['purchase_date'] ?? null);
        $receivedDate = self::normalizeDate($input['received_date'] ?? null);
        $currentPendingRole = $nextStatus === 'closed' ? 'none' : 'finance';
        $currentPendingUserId = $nextStatus === 'closed' ? null : (int) ($actor['id'] ?? 0);

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'UPDATE asset_requests
                 SET status = :status,
                     current_pending_role = :current_pending_role,
                     current_pending_user_id = :current_pending_user_id,
                     fulfillment_source = CASE WHEN :status = \'purchased\' THEN \'purchase\' ELSE fulfillment_source END,
                     purchase_price = CASE WHEN :status = \'purchased\' THEN :purchase_price ELSE purchase_price END,
                     purchase_vendor = CASE WHEN :status = \'purchased\' THEN :purchase_vendor ELSE purchase_vendor END,
                     purchase_reference = CASE WHEN :status = \'purchased\' THEN :purchase_reference ELSE purchase_reference END,
                     purchase_date = CASE WHEN :status = \'purchased\' THEN :purchase_date ELSE purchase_date END,
                     received_date = CASE WHEN :status = \'received\' THEN :received_date ELSE received_date END,
                     closed_at = CASE WHEN :status = \'closed\' THEN NOW() ELSE closed_at END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'status' => $nextStatus,
                'current_pending_role' => $currentPendingRole,
                'current_pending_user_id' => $currentPendingUserId,
                'purchase_price' => $purchasePrice,
                'purchase_vendor' => $purchaseVendor,
                'purchase_reference' => $purchaseReference,
                'purchase_date' => $purchaseDate,
                'received_date' => $receivedDate,
            ]);

            $timelineComment = $comment;
            if ($nextStatus === 'purchased' && $purchasePrice !== null) {
                $timelineComment = trim($comment . ' ' . __('requests.timeline.purchase_price_note', 'Purchase price') . ': ' . number_format((float) $purchasePrice, 2));
            }

            if ($nextStatus === 'received') {
                $inventoryComment = self::receiveIntoInventory($pdo, $request, $input);
                if ($inventoryComment !== '') {
                    $timelineComment = trim($timelineComment . ($timelineComment !== '' ? ' | ' : '') . $inventoryComment);
                }
            }

            self::addTimeline($pdo, $id, 'marked_' . $nextStatus, (string) $request['status'], $nextStatus, $timelineComment);

            $notificationStatus = $nextStatus;
            if (
                $nextStatus === 'received'
                && (self::configuration()['auto_close_on_receive'] ?? false)
                && (
                    !self::requestContainsItemType($request, 'asset')
                    || self::linkedAssetsCount($id) >= max(1, self::requestItemQuantityByType($request, 'asset'))
                )
            ) {
                $pdo->prepare(
                    'UPDATE asset_requests
                     SET status = :status,
                         current_pending_role = :current_pending_role,
                         current_pending_user_id = NULL,
                         closed_at = COALESCE(closed_at, NOW()),
                         updated_at = NOW()
                     WHERE id = :id'
                )->execute([
                    'id' => $id,
                    'status' => 'closed',
                    'current_pending_role' => 'none',
                ]);

                self::addTimeline($pdo, $id, 'marked_closed', 'received', 'closed', __('settings.workflow_auto_close_note', 'Automatically closed after receive.'));
                $notificationStatus = 'closed';
            }

            self::notifyUsers($pdo, [(int) $request['requested_by_user_id']], 'request_progress', [
                'title' => __('requests.notifications.progress_title', 'Request updated'),
                'message' => (string) $request['request_no'] . ' - ' . self::statusLabel($notificationStatus),
                'route' => route('requests.show', ['id' => $id]),
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function validateAdvanceInput(array $request, array $input): array
    {
        $errors = [];
        $nextStatus = trim((string) ($input['next_status'] ?? ''));
        $allowedNext = self::advanceOptions()[(string) ($request['status'] ?? '')] ?? null;

        if ($nextStatus === '' || $allowedNext !== $nextStatus) {
            $errors['next_status'] = __('requests.invalid_next_step', 'Choose a valid next step.');
            return $errors;
        }

        if ($nextStatus === 'purchased') {
            $purchasePrice = trim((string) ($input['purchase_price'] ?? ''));
            if ($purchasePrice === '') {
                $errors['purchase_price'] = __('requests.purchase_price_required', 'Enter the actual purchase price.');
            } elseif (preg_match('/^\d+(\.\d{1,2})?$/', $purchasePrice) !== 1) {
                $errors['purchase_price'] = __('requests.purchase_price_invalid', 'Enter a valid purchase price.');
            }
        }

        if ($nextStatus === 'received') {
            $receivedDate = trim((string) ($input['received_date'] ?? ''));
            if ($receivedDate === '') {
                $errors['received_date'] = __('requests.received_date_required', 'Enter the receive date.');
            }
        }

        if (
            $nextStatus === 'closed'
            && self::requestContainsItemType($request, 'asset')
            && self::linkedAssetsCount((int) ($request['id'] ?? 0)) < max(1, self::requestItemQuantityByType($request, 'asset'))
        ) {
            $errors['next_status'] = __('requests.asset_link_required', 'Register and link at least one asset to this request before closing it.');
        }

        return $errors;
    }

    public static function canView(array $request, ?array $user = null): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        if (self::hasGlobalAccess($user)) {
            return true;
        }

        return (int) $request['requested_by_user_id'] === (int) $user['id'];
    }

    public static function canEdit(array $request, ?array $user = null): bool
    {
        return self::canView($request, $user)
            && (int) ($request['requested_by_user_id'] ?? 0) === (int) ($user['id'] ?? 0)
            && in_array((string) ($request['status'] ?? ''), self::REQUESTER_OWN_STATUSES, true);
    }

    public static function canDelete(array $request, ?array $user = null): bool
    {
        return in_array((string) ($request['status'] ?? ''), self::REQUESTER_OWN_STATUSES, true)
            && (self::canEdit($request, $user) || (($user['role'] ?? '') === 'admin'));
    }

    public static function canSubmit(array $request, ?array $user = null): bool
    {
        return self::canEdit($request, $user);
    }

    public static function canApprove(array $request, ?array $user = null): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        if (($user['role'] ?? '') === 'admin') {
            return self::approvalStepForStatus((string) ($request['status'] ?? '')) !== null;
        }

        $step = self::approvalStepForStatus((string) ($request['status'] ?? ''), $request);
        if ($step === null) {
            return false;
        }

        if ((string) ($user['role'] ?? '') !== $step['role']) {
            return false;
        }

        if (!self::matchesPendingRole($request, $step['role'])) {
            return false;
        }

        return self::matchesPendingUser($request, $user);
    }

    public static function canAdvance(array $request, ?array $user = null): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        if (!isset(self::advanceOptions()[(string) ($request['status'] ?? '')])) {
            return false;
        }

        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        if ((string) ($user['role'] ?? '') !== 'finance') {
            return false;
        }

        if (!self::matchesPendingRole($request, 'finance')) {
            return false;
        }

        return self::matchesPendingUser($request, $user);
    }

    public static function canFulfillFromStorage(array $request, ?array $user = null): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        $configuration = self::configuration();
        if (!($configuration['allow_storage_fulfillment'] ?? true)) {
            return false;
        }

        $storageStatus = (string) ($configuration['storage_fulfillment_status'] ?? 'pending_it_manager');
        $storageRole = (string) ($configuration['storage_fulfillment_role'] ?? 'it_manager');

        if ((string) ($request['status'] ?? '') !== $storageStatus) {
            return false;
        }

        if (($user['role'] ?? '') === 'admin') {
            return self::storageFulfillmentRows($request) !== [];
        }

        if ((string) ($user['role'] ?? '') !== $storageRole) {
            return false;
        }

        if (!self::matchesPendingRole($request, $storageRole) || !self::matchesPendingUser($request, $user)) {
            return false;
        }

        return self::storageFulfillmentRows($request) !== [];
    }

    public static function canFullyFulfillFromStorage(array $request): bool
    {
        $rows = self::storageFulfillmentRows($request);
        if ($rows === []) {
            return false;
        }

        foreach ($rows as $row) {
            if (empty($row['can_fulfill'])) {
                return false;
            }
        }

        return true;
    }

    public static function canLinkExistingAssets(array $request, ?array $user = null): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        if (!in_array((string) ($request['status'] ?? ''), ['approved', 'purchased', 'received'], true)) {
            return false;
        }

        if (($user['role'] ?? '') === 'admin') {
            return self::requestContainsItemType($request, 'asset')
                && self::linkableStorageAssets($request) !== []
                && self::linkedAssetsCount((int) ($request['id'] ?? 0)) < max(1, self::requestItemQuantityByType($request, 'asset'));
        }

        if ((string) ($user['role'] ?? '') !== 'finance') {
            return false;
        }

        if (!self::matchesPendingRole($request, 'finance') || !self::matchesPendingUser($request, $user)) {
            return false;
        }

        return self::requestContainsItemType($request, 'asset')
            && self::linkableStorageAssets($request) !== []
            && self::linkedAssetsCount((int) ($request['id'] ?? 0)) < max(1, self::requestItemQuantityByType($request, 'asset'));
    }

    public static function linkableStorageAssets(array $request): array
    {
        $pdo = Database::connect();
        $requestId = (int) ($request['id'] ?? 0);
        if (!$pdo instanceof PDO || $requestId <= 0) {
            return [];
        }

        $assetCategoryIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item): ?int => (string) ($item['item_type'] ?? '') === 'asset' && ($item['category_id'] ?? null) !== null
                ? (int) $item['category_id']
                : null,
            self::requestItems($requestId)
        ))));

        $where = [
            "assets.status = 'storage'",
            'assets.assigned_employee_id IS NULL',
            'assets.request_id IS NULL',
        ];
        $params = [];

        if ($assetCategoryIds !== []) {
            $placeholders = [];
            foreach ($assetCategoryIds as $index => $categoryId) {
                $key = 'category_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $categoryId;
            }
            $where[] = 'assets.category_id IN (' . implode(', ', $placeholders) . ')';
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.category_id,
                    COALESCE(asset_categories.name, 'Uncategorized') AS category_name,
                    COALESCE(branches.name, 'Unassigned') AS branch_name
             FROM assets
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             LEFT JOIN branches ON branches.id = assets.branch_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY assets.id DESC"
        );
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    public static function validateLinkExistingAssetsInput(array $request, array $input): array
    {
        $errors = [];
        $requestId = (int) ($request['id'] ?? 0);
        $remainingNeeded = max(0, self::requestItemQuantityByType($request, 'asset') - self::linkedAssetsCount($requestId));
        $assetIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['asset_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
        $availableIds = array_map(static fn (array $asset): int => (int) $asset['id'], self::linkableStorageAssets($request));

        if ($assetIds === []) {
            $errors['asset_ids'] = __('requests.link_existing_asset_required', 'Select at least one existing storage asset.');
            return $errors;
        }

        if ($remainingNeeded > 0 && count($assetIds) > $remainingNeeded) {
            $errors['asset_ids'] = strtr(
                __('requests.link_existing_asset_limit', 'You only need :count more asset(s) for this request.'),
                [':count' => (string) $remainingNeeded]
            );
            return $errors;
        }

        foreach ($assetIds as $assetId) {
            if (!in_array($assetId, $availableIds, true)) {
                $errors['asset_ids'] = __('requests.link_existing_asset_invalid', 'Select only available storage assets that match this request.');
                break;
            }
        }

        return $errors;
    }

    public static function linkExistingAssets(int $id, array $input): void
    {
        $pdo = Database::connect();
        $request = self::find($id);
        if (!$pdo instanceof PDO || $request === null) {
            throw new \RuntimeException('Request not found.');
        }
        if (!self::canLinkExistingAssets($request, auth_user())) {
            throw new \RuntimeException('You are not allowed to link assets to this request.');
        }

        $assetIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['asset_ids'] ?? [])), static fn (int $value): bool => $value > 0)));
        if ($assetIds === []) {
            throw new \RuntimeException('Select at least one asset to link.');
        }

        $comment = trim((string) ($input['comment'] ?? ''));

        $pdo->beginTransaction();
        try {
            $requestRow = self::findForUpdate($pdo, $id);
            if ($requestRow === null) {
                throw new \RuntimeException('Request not found.');
            }

            $requestedEmployeeId = (int) ($requestRow['requested_for_employee_id'] ?? 0);
            $statement = $pdo->prepare(
                'UPDATE assets
                 SET request_id = :request_id,
                     branch_id = :branch_id,
                     updated_at = NOW()
                 WHERE id = :id
                   AND status = \'storage\'
                   AND assigned_employee_id IS NULL
                   AND request_id IS NULL'
            );
            $movement = $pdo->prepare(
                'INSERT INTO asset_movements (asset_id, request_id, movement_type, from_branch_id, to_branch_id, user_id, notes, moved_at)
                 VALUES (:asset_id, :request_id, :movement_type, :from_branch_id, :to_branch_id, :user_id, :notes, NOW())'
            );
            $actorId = (int) (auth_user()['id'] ?? 0);

            $linkedNames = [];
            foreach ($assetIds as $assetId) {
                $asset = self::storageAssetForUpdate($pdo, $assetId);
                if ($asset === null || !empty($asset['request_id'])) {
                    throw new \RuntimeException('One of the selected assets is no longer available to link.');
                }

                $targetBranchId = self::fulfillmentBranchId($pdo, $asset, $requestRow, $requestedEmployeeId);
                $movement->execute([
                    'asset_id' => $assetId,
                    'request_id' => $id,
                    'movement_type' => 'request',
                    'from_branch_id' => $asset['branch_id'] === null ? null : (int) $asset['branch_id'],
                    'to_branch_id' => $targetBranchId,
                    'user_id' => $actorId > 0 ? $actorId : null,
                    'notes' => '[REQUEST LINK] ' . (string) ($requestRow['request_no'] ?? ''),
                ]);

                $statement->execute([
                    'request_id' => $id,
                    'branch_id' => $targetBranchId,
                    'id' => $assetId,
                ]);
                DataRepository::syncAssetInventoryState($assetId);
                $linkedNames[] = trim((string) $asset['name'] . ' ' . (string) $asset['tag']);
            }

            self::addTimeline(
                $pdo,
                $id,
                'linked_existing_asset',
                (string) ($requestRow['status'] ?? ''),
                (string) ($requestRow['status'] ?? ''),
                trim(implode(', ', $linkedNames) . ($comment !== '' ? ' | ' . $comment : ''))
            );

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function validateFulfillmentInput(array $request, array $input): array
    {
        $errors = [];
        $rows = self::storageFulfillmentRows($request);
        if ($rows === []) {
            $errors['items'] = __('requests.fulfill_no_items', 'This request does not contain any stock-fulfillable items.');
            return $errors;
        }

        $submittedItems = (array) ($input['items'] ?? []);
        $selectedAssetIds = [];

        foreach ($rows as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $itemType = (string) ($item['item_type'] ?? 'asset');
            $requestedQuantity = max(1, (int) ($item['quantity'] ?? 1));
            $assignmentTarget = (string) ($item['assignment_target'] ?? 'employee');
            $itemInput = is_array($submittedItems[$itemId] ?? null) ? $submittedItems[$itemId] : [];

            if (empty($item['can_fulfill'])) {
                $errors['items.' . $itemId . '.selection'] = __('requests.fulfill_item_not_available', 'This line does not have enough stock to complete from storage.');
                continue;
            }

            if ($itemType === 'asset') {
                $assetIds = array_values(array_unique(array_filter(array_map('intval', (array) ($itemInput['asset_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
                if ($assetIds === []) {
                    $errors['items.' . $itemId . '.asset_ids'] = __('requests.fulfill_asset_required', 'Select the storage asset or assets to assign.');
                } elseif (count($assetIds) !== $requestedQuantity) {
                    $errors['items.' . $itemId . '.asset_ids'] = __('requests.fulfill_asset_quantity_mismatch', 'Select exactly the same number of storage assets as the requested quantity.');
                } else {
                    $availableIds = array_map(static fn (array $option): int => (int) $option['id'], (array) ($item['storage_asset_options'] ?? []));
                    foreach ($assetIds as $assetId) {
                        if (!in_array($assetId, $availableIds, true)) {
                            $errors['items.' . $itemId . '.asset_ids'] = __('requests.fulfill_asset_invalid', 'Select only available storage assets for this line.');
                            break;
                        }

                        if (in_array($assetId, $selectedAssetIds, true)) {
                            $errors['items.' . $itemId . '.asset_ids'] = __('requests.fulfill_asset_duplicate', 'The same storage asset cannot be used on multiple lines.');
                            break;
                        }

                        $selectedAssetIds[] = $assetId;
                    }
                }

                if ($assignmentTarget === 'employee' && empty($request['requested_for_employee_id'])) {
                    $errors['requested_for_employee_id'] = __('requests.requested_for_required', 'Select the employee who will receive this request.');
                }

                continue;
            }

            if ($itemType === 'spare_part') {
                $sparePartId = (int) ($itemInput['spare_part_id'] ?? 0);
                if ($sparePartId <= 0) {
                    $errors['items.' . $itemId . '.spare_part_id'] = __('requests.fulfill_spare_part_required', 'Select a spare part from storage.');
                } else {
                    $availableIds = array_map(static fn (array $option): int => (int) $option['id'], (array) ($item['spare_part_options'] ?? []));
                    if (!in_array($sparePartId, $availableIds, true)) {
                        $errors['items.' . $itemId . '.spare_part_id'] = __('requests.fulfill_spare_part_invalid', 'Select a spare part with enough available quantity.');
                    }
                }

                continue;
            }

            $licenseId = (int) ($itemInput['license_id'] ?? 0);
            if ($licenseId <= 0) {
                $errors['items.' . $itemId . '.license_id'] = __('requests.fulfill_license_required', 'Select a license from available stock.');
            } else {
                $availableIds = array_map(static fn (array $option): int => (int) $option['id'], (array) ($item['license_stock_options'] ?? []));
                if (!in_array($licenseId, $availableIds, true)) {
                    $errors['items.' . $itemId . '.license_id'] = __('requests.fulfill_license_invalid', 'Select a license with enough available seats.');
                }
            }
        }

        return $errors;
    }

    public static function fulfillFromStorage(int $id, array $input): void
    {
        $pdo = Database::connect();
        $request = self::find($id);
        if (!$pdo instanceof PDO || $request === null) {
            throw new \RuntimeException('Request not found.');
        }

        if (!self::canFulfillFromStorage($request, auth_user())) {
            throw new \RuntimeException('You are not allowed to fulfill this request from storage.');
        }

        $configuration = self::configuration();
        $storageStatus = (string) ($configuration['storage_fulfillment_status'] ?? 'pending_it_manager');
        $approvalStep = (string) ($configuration['storage_fulfillment_approval_step'] ?? 'it_manager');

        if ((string) ($request['status'] ?? '') !== $storageStatus) {
            throw new \RuntimeException('This request cannot be fulfilled from storage.');
        }

        $rows = self::storageFulfillmentRows($request);
        if ($rows === []) {
            throw new \RuntimeException('This request does not contain any stock-fulfillable items.');
        }

        $comment = trim((string) ($input['comment'] ?? ''));
        $actor = auth_user();
        $actorId = (int) ($actor['id'] ?? 0);
        $requestedEmployeeId = (int) ($request['requested_for_employee_id'] ?? 0);
        $submittedItems = (array) ($input['items'] ?? []);

        $pdo->beginTransaction();
        try {
            $lockedRequest = self::findForUpdate($pdo, $id);
            if ($lockedRequest === null || (string) ($lockedRequest['status'] ?? '') !== $storageStatus) {
                throw new \RuntimeException('This request is no longer waiting for the configured approval stage.');
            }

            self::addApproval($pdo, $id, $approvalStep, $actorId, 'approved', $comment);
            self::addTimeline($pdo, $id, 'approved_' . $approvalStep, $storageStatus, $storageStatus, $comment);

            $usedAssetIds = [];
            foreach ($rows as $item) {
                $itemId = (int) ($item['id'] ?? 0);
                $itemType = (string) ($item['item_type'] ?? 'asset');
                $requestedQuantity = max(1, (int) ($item['quantity'] ?? 1));
                $assignmentTarget = (string) ($item['assignment_target'] ?? 'employee');
                $itemInput = is_array($submittedItems[$itemId] ?? null) ? $submittedItems[$itemId] : [];
                $itemLabel = trim((string) ($item['item_name'] ?? ''));

                if ($itemType === 'asset') {
                    $assetIds = array_values(array_unique(array_filter(array_map('intval', (array) ($itemInput['asset_ids'] ?? [])), static fn (int $value): bool => $value > 0)));
                    if (count($assetIds) !== $requestedQuantity) {
                        throw new \RuntimeException('Selected asset count does not match the request quantity.');
                    }

                    $assetNames = [];
                    foreach ($assetIds as $assetId) {
                        if (in_array($assetId, $usedAssetIds, true)) {
                            throw new \RuntimeException('The same storage asset cannot be used on multiple request lines.');
                        }

                        $asset = self::storageAssetForUpdate($pdo, $assetId);
                        if ($asset === null) {
                            throw new \RuntimeException('One of the selected assets is not available in storage.');
                        }

                        $usedAssetIds[] = $assetId;
                        $targetBranchId = self::fulfillmentBranchId($pdo, $asset, $lockedRequest, $requestedEmployeeId);
                        $movement = $pdo->prepare(
                            'INSERT INTO asset_movements (asset_id, request_id, movement_type, from_branch_id, to_branch_id, user_id, notes, moved_at)
                             VALUES (:asset_id, :request_id, :movement_type, :from_branch_id, :to_branch_id, :user_id, :notes, NOW())'
                        );
                        $movement->execute([
                            'asset_id' => $assetId,
                            'request_id' => $id,
                            'movement_type' => 'request',
                            'from_branch_id' => $asset['branch_id'] === null ? null : (int) $asset['branch_id'],
                            'to_branch_id' => $targetBranchId,
                            'user_id' => $actorId > 0 ? $actorId : null,
                            'notes' => '[REQUEST FULFILLMENT] ' . (string) $lockedRequest['request_no'],
                        ]);

                        $updateAsset = $pdo->prepare(
                            'UPDATE assets
                             SET request_id = :request_id,
                                 branch_id = :branch_id,
                                 assigned_employee_id = :assigned_employee_id,
                                 status = :status,
                                 procurement_stage = :procurement_stage,
                                 updated_at = NOW()
                             WHERE id = :id'
                        );
                        $updateAsset->execute([
                            'id' => $assetId,
                            'request_id' => $id,
                            'branch_id' => $targetBranchId,
                            'assigned_employee_id' => $assignmentTarget === 'employee' ? $requestedEmployeeId : null,
                            'status' => $assignmentTarget === 'employee' ? 'active' : 'storage',
                            'procurement_stage' => 'deployed',
                        ]);
                        DataRepository::syncAssetInventoryState($assetId);

                        if ($assignmentTarget === 'employee') {
                            $pdo->prepare(
                                'UPDATE asset_assignments
                                 SET returned_at = NOW()
                                 WHERE asset_id = :asset_id
                                   AND returned_at IS NULL'
                            )->execute([
                                'asset_id' => $assetId,
                            ]);

                            $assign = $pdo->prepare(
                                'INSERT INTO asset_assignments (asset_id, employee_id, notes, assigned_at)
                                 VALUES (:asset_id, :employee_id, :notes, NOW())'
                            );
                            $assign->execute([
                                'asset_id' => $assetId,
                                'employee_id' => $requestedEmployeeId,
                                'notes' => trim('[REQUEST] ' . (string) $lockedRequest['request_no'] . ($comment !== '' ? ' | ' . $comment : '')),
                            ]);

                            $handover = $pdo->prepare(
                                'INSERT INTO asset_handovers (asset_id, employee_id, handover_type, handover_date, notes, created_by, created_at, updated_at)
                                 VALUES (:asset_id, :employee_id, :handover_type, :handover_date, :notes, :created_by, NOW(), NOW())'
                            );
                            $handover->execute([
                                'asset_id' => $assetId,
                                'employee_id' => $requestedEmployeeId,
                                'handover_type' => 'issue',
                                'handover_date' => date('Y-m-d'),
                                'notes' => trim('[REQUEST] ' . (string) $lockedRequest['request_no'] . ($comment !== '' ? ' | ' . $comment : '')),
                                'created_by' => $actorId > 0 ? $actorId : null,
                            ]);
                        }

                        $assetNames[] = trim((string) $asset['name'] . ' ' . (string) $asset['tag']);
                    }

                    self::addTimeline(
                        $pdo,
                        $id,
                        'fulfilled_asset_from_storage',
                        $storageStatus,
                        $storageStatus,
                        trim($itemLabel . ': ' . implode(', ', $assetNames) . ($comment !== '' ? ' | ' . $comment : ''))
                    );
                    continue;
                }

                if ($itemType === 'spare_part') {
                    $sparePartId = (int) ($itemInput['spare_part_id'] ?? 0);
                    $part = self::sparePartForUpdate($pdo, $sparePartId);
                    if ($part === null) {
                        throw new \RuntimeException('The selected spare part is not available.');
                    }

                    if ((int) $part['quantity'] < $requestedQuantity) {
                        throw new \RuntimeException('The selected spare part does not have enough quantity in storage.');
                    }

                    $updatePart = $pdo->prepare(
                        'UPDATE spare_parts
                         SET quantity = quantity - :quantity,
                             updated_at = NOW()
                         WHERE id = :id'
                    );
                    $updatePart->execute([
                        'id' => $sparePartId,
                        'quantity' => $requestedQuantity,
                    ]);

                    $issue = $pdo->prepare(
                        'INSERT INTO spare_part_issues (request_id, spare_part_id, employee_id, quantity, notes, issued_by, issued_at, created_at, updated_at)
                         VALUES (:request_id, :spare_part_id, :employee_id, :quantity, :notes, :issued_by, NOW(), NOW(), NOW())'
                    );
                    $issue->execute([
                        'request_id' => $id,
                        'spare_part_id' => $sparePartId,
                        'employee_id' => $requestedEmployeeId > 0 ? $requestedEmployeeId : null,
                        'quantity' => $requestedQuantity,
                        'notes' => trim($comment),
                        'issued_by' => $actorId > 0 ? $actorId : null,
                    ]);

                    self::addTimeline(
                        $pdo,
                        $id,
                        'fulfilled_spare_part_from_storage',
                        $storageStatus,
                        $storageStatus,
                        trim($itemLabel . ': ' . (string) $part['name'] . ' x' . $requestedQuantity . ($comment !== '' ? ' | ' . $comment : ''))
                    );
                    continue;
                }

                $licenseId = (int) ($itemInput['license_id'] ?? 0);
                $license = self::licenseForUpdate($pdo, $licenseId);
                if ($license === null) {
                    throw new \RuntimeException('The selected license is not available.');
                }

                if ((int) $license['available_seats'] < $requestedQuantity) {
                    throw new \RuntimeException('The selected license does not have enough available seats.');
                }

                $pdo->prepare(
                    'UPDATE licenses
                     SET seats_used = seats_used + :quantity,
                         updated_at = NOW()
                     WHERE id = :id'
                )->execute([
                    'id' => $licenseId,
                    'quantity' => $requestedQuantity,
                ]);

                $allocation = $pdo->prepare(
                    'INSERT INTO license_allocations (license_id, request_id, employee_id, branch_id, quantity, notes, allocated_by, allocated_at, created_at, updated_at)
                     VALUES (:license_id, :request_id, :employee_id, :branch_id, :quantity, :notes, :allocated_by, NOW(), NOW(), NOW())'
                );
                $allocation->execute([
                    'license_id' => $licenseId,
                    'request_id' => $id,
                    'employee_id' => $requestedEmployeeId > 0 ? $requestedEmployeeId : null,
                    'branch_id' => ($lockedRequest['branch_id'] ?? '') === '' ? null : (int) $lockedRequest['branch_id'],
                    'quantity' => $requestedQuantity,
                    'notes' => trim($comment),
                    'allocated_by' => $actorId > 0 ? $actorId : null,
                ]);

                self::addTimeline(
                    $pdo,
                    $id,
                    'fulfilled_license_from_stock',
                    $storageStatus,
                    $storageStatus,
                    trim($itemLabel . ': ' . (string) $license['product_name'] . ' x' . $requestedQuantity . ($comment !== '' ? ' | ' . $comment : ''))
                );
            }

            $updateRequest = $pdo->prepare(
                'UPDATE asset_requests
                 SET status = :status,
                     current_pending_role = :current_pending_role,
                     current_pending_user_id = NULL,
                     fulfillment_source = :fulfillment_source,
                     approved_at = COALESCE(approved_at, NOW()),
                     closed_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $updateRequest->execute([
                'id' => $id,
                'status' => 'closed',
                'current_pending_role' => 'none',
                'fulfillment_source' => 'storage',
            ]);

            self::addTimeline($pdo, $id, 'marked_closed', $storageStatus, 'closed', $comment);

            self::notifyUsers($pdo, [(int) $lockedRequest['requested_by_user_id']], 'request_fulfilled', [
                'title' => __('requests.notifications.fulfilled_title', 'Request fulfilled from storage'),
                'message' => (string) $lockedRequest['request_no'] . ' - ' . __('requests.notifications.fulfilled_message', 'The request was fulfilled directly from storage.'),
                'route' => route('requests.show', ['id' => $id]),
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private static function normalizePayload(array $input): array
    {
        $configuration = self::configuration();
        $scenario = (string) ($input['scenario'] ?? ($configuration['default_scenario'] ?? 'general'));
        $urgency = (string) ($input['urgency'] ?? ($configuration['default_urgency'] ?? 'normal'));

        return [
            'requested_for_employee_id' => trim((string) ($input['requested_for_employee_id'] ?? '')) === '' ? null : (int) $input['requested_for_employee_id'],
            'request_type' => 'asset',
            'scenario' => in_array($scenario, ['general', 'employee_onboarding', 'branch_deployment', 'replacement', 'stock_replenishment'], true)
                ? $scenario
                : (string) ($configuration['default_scenario'] ?? 'general'),
            'branch_id' => trim((string) ($input['branch_id'] ?? '')) === '' ? null : (int) $input['branch_id'],
            'category_id' => trim((string) ($input['category_id'] ?? '')) === '' ? null : (int) $input['category_id'],
            'title' => trim((string) ($input['title'] ?? '')),
            'asset_specification' => trim((string) ($input['asset_specification'] ?? '')),
            'justification' => trim((string) ($input['justification'] ?? '')),
            'quantity' => max(1, (int) ($input['quantity'] ?? 1)),
            'estimated_cost' => trim((string) ($input['estimated_cost'] ?? '')) === '' ? null : number_format((float) $input['estimated_cost'], 2, '.', ''),
            'urgency' => in_array($urgency, ['low', 'normal', 'high', 'critical'], true)
                ? $urgency
                : (string) ($configuration['default_urgency'] ?? 'normal'),
            'needed_by_date' => trim((string) ($input['needed_by_date'] ?? '')) === '' ? null : trim((string) $input['needed_by_date']),
        ];
    }

    private static function normalizeItemsPayload(array $input): array
    {
        $items = [];
        foreach ((array) ($input['items'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemName = trim((string) ($row['item_name'] ?? ''));
            $quantity = max(0, (int) ($row['quantity'] ?? 0));
            if ($itemName === '' && $quantity === 0) {
                continue;
            }

            $estimatedUnitCostRaw = trim((string) ($row['estimated_unit_cost'] ?? ''));
            $items[] = [
                'item_type' => in_array((string) ($row['item_type'] ?? 'asset'), ['asset', 'spare_part', 'license'], true)
                    ? (string) $row['item_type']
                    : 'asset',
                'item_name' => $itemName,
                'category_id' => trim((string) ($row['category_id'] ?? '')) === '' ? null : (int) $row['category_id'],
                'quantity' => max(1, $quantity),
                'estimated_unit_cost' => $estimatedUnitCostRaw === '' ? null : number_format((float) $estimatedUnitCostRaw, 2, '.', ''),
                'fulfillment_preference' => in_array((string) ($row['fulfillment_preference'] ?? 'either'), ['purchase', 'storage', 'either'], true)
                    ? (string) $row['fulfillment_preference']
                    : 'either',
                'assignment_target' => in_array((string) ($row['assignment_target'] ?? 'employee'), ['employee', 'branch', 'stock'], true)
                    ? (string) $row['assignment_target']
                    : 'employee',
                'specification' => trim((string) ($row['specification'] ?? '')),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];
        }

        return $items;
    }

    private static function deriveRequestType(array $items): string
    {
        $itemTypes = array_values(array_unique(array_map(static fn (array $item): string => (string) ($item['item_type'] ?? 'asset'), $items)));
        if ($itemTypes === []) {
            return 'asset';
        }

        return count($itemTypes) === 1 ? $itemTypes[0] : 'mixed';
    }

    private static function derivePrimaryCategoryId(array $items, ?int $fallback = null): ?int
    {
        $categoryIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item): ?int => isset($item['category_id']) && $item['category_id'] !== null ? (int) $item['category_id'] : null,
            $items
        ))));

        if (count($categoryIds) === 1) {
            return $categoryIds[0];
        }

        return $fallback;
    }

    private static function deriveTotalQuantity(array $items): int
    {
        $total = array_sum(array_map(static fn (array $item): int => max(1, (int) ($item['quantity'] ?? 1)), $items));
        return max(1, $total);
    }

    private static function deriveEstimatedCost(array $items, ?string $fallback): ?string
    {
        $hasItemCost = false;
        $total = 0.0;
        foreach ($items as $item) {
            if (($item['estimated_unit_cost'] ?? null) === null) {
                continue;
            }
            $hasItemCost = true;
            $total += ((float) $item['estimated_unit_cost']) * max(1, (int) ($item['quantity'] ?? 1));
        }

        if ($hasItemCost) {
            return number_format($total, 2, '.', '');
        }

        return $fallback;
    }

    private static function writeItems(PDO $pdo, int $requestId, array $items): void
    {
        $pdo->prepare('DELETE FROM asset_request_items WHERE request_id = :request_id')->execute(['request_id' => $requestId]);
        if ($items === []) {
            return;
        }

        $statement = $pdo->prepare(
            'INSERT INTO asset_request_items (
                request_id,
                item_type,
                item_name,
                category_id,
                quantity,
                estimated_unit_cost,
                fulfillment_preference,
                assignment_target,
                specification,
                notes,
                created_at,
                updated_at
             ) VALUES (
                :request_id,
                :item_type,
                :item_name,
                :category_id,
                :quantity,
                :estimated_unit_cost,
                :fulfillment_preference,
                :assignment_target,
                :specification,
                :notes,
                NOW(),
                NOW()
             )'
        );

        foreach ($items as $item) {
            $statement->execute([
                'request_id' => $requestId,
                'item_type' => $item['item_type'],
                'item_name' => $item['item_name'],
                'category_id' => $item['category_id'],
                'quantity' => $item['quantity'],
                'estimated_unit_cost' => $item['estimated_unit_cost'],
                'fulfillment_preference' => $item['fulfillment_preference'],
                'assignment_target' => $item['assignment_target'],
                'specification' => $item['specification'],
                'notes' => $item['notes'],
            ]);
        }
    }

    private static function legacyItemFromRequest(array $request): array
    {
        $estimatedCost = isset($request['estimated_cost']) && $request['estimated_cost'] !== null
            ? (float) $request['estimated_cost']
            : null;
        $quantity = max(1, (int) ($request['quantity'] ?? 1));

        return [
            'id' => 0,
            'item_type' => in_array((string) ($request['request_type'] ?? 'asset'), ['asset', 'spare_part', 'license'], true)
                ? (string) $request['request_type']
                : 'asset',
            'item_name' => (string) ($request['title'] ?? ''),
            'category_id' => ($request['category_id'] ?? '') === '' ? null : (int) $request['category_id'],
            'category_name' => (string) ($request['category_name'] ?? ''),
            'quantity' => $quantity,
            'estimated_unit_cost' => $estimatedCost !== null ? ($estimatedCost / max(1, $quantity)) : null,
            'fulfillment_preference' => 'either',
            'assignment_target' => 'employee',
            'specification' => (string) ($request['asset_specification'] ?? ''),
            'notes' => '',
        ];
    }

    private static function singleFulfillableItem(array $request): ?array
    {
        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return null;
        }

        $items = self::requestItems($requestId);
        if (count($items) !== 1) {
            return null;
        }

        $item = $items[0];
        return in_array((string) ($item['item_type'] ?? ''), ['asset', 'spare_part', 'license'], true) ? $item : null;
    }

    private static function singleReceivableItem(array $request): ?array
    {
        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return null;
        }

        $items = self::requestItems($requestId);
        if (count($items) !== 1) {
            return null;
        }

        $item = $items[0];
        return in_array((string) ($item['item_type'] ?? ''), ['spare_part', 'license'], true) ? $item : null;
    }

    private static function requestContainsItemType(array $request, string $itemType): bool
    {
        return self::requestItemQuantityByType($request, $itemType) > 0;
    }

    private static function requestItemQuantityByType(array $request, string $itemType): int
    {
        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return 0;
        }

        $quantity = 0;
        foreach (self::requestItems($requestId) as $item) {
            if ((string) ($item['item_type'] ?? '') === $itemType) {
                $quantity += max(1, (int) ($item['quantity'] ?? 1));
            }
        }

        return $quantity;
    }

    private static function storageAssetOptionsForItem(array $item): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || (string) ($item['item_type'] ?? '') !== 'asset') {
            return [];
        }

        $params = [];
        $where = [
            "assets.status = 'storage'",
            'assets.assigned_employee_id IS NULL',
        ];

        if (!empty($item['category_id'])) {
            $where[] = 'assets.category_id = :category_id';
            $params['category_id'] = (int) $item['category_id'];
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.request_id,
                    COALESCE(branches.name, 'Unassigned') AS branch_name,
                    COALESCE(asset_categories.name, 'Uncategorized') AS category_name
             FROM assets
             LEFT JOIN branches ON branches.id = assets.branch_id
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY assets.id DESC"
        );
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    private static function sparePartOptionsForItem(array $item, bool $strictEnough = true): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || (string) ($item['item_type'] ?? '') !== 'spare_part') {
            return [];
        }

        $searchTerm = trim((string) ($item['item_name'] ?? ''));
        $categoryName = trim((string) ($item['category_name'] ?? ''));
        $requestedQuantity = max(1, (int) ($item['quantity'] ?? 1));
        $params = [];

        $orderSql = 'ORDER BY spare_parts.quantity DESC, spare_parts.name ASC';
        if ($searchTerm !== '' || $categoryName !== '') {
            $orderSql = 'ORDER BY
                CASE
                    WHEN ' . ($searchTerm !== '' ? '(spare_parts.name LIKE :search OR COALESCE(spare_parts.part_number, \'\') LIKE :search OR COALESCE(spare_parts.compatible_with, \'\') LIKE :search)' : '0 = 1') . '
                    THEN 0
                    WHEN ' . ($categoryName !== '' ? 'COALESCE(spare_parts.category, \'\') = :category_name' : '0 = 1') . '
                    THEN 1
                    ELSE 2
                END,
                spare_parts.quantity DESC,
                spare_parts.name ASC';
            if ($searchTerm !== '') {
                $params['search'] = '%' . $searchTerm . '%';
            }
            if ($categoryName !== '') {
                $params['category_name'] = $categoryName;
            }
        }

        $statement = $pdo->prepare(
            "SELECT spare_parts.id,
                    spare_parts.name,
                    COALESCE(spare_parts.part_number, '') AS part_number,
                    COALESCE(spare_parts.category, '') AS category,
                    COALESCE(spare_parts.location, '') AS location,
                    spare_parts.quantity,
                    spare_parts.min_quantity,
                    COALESCE(spare_parts.compatible_with, '') AS compatible_with
             FROM spare_parts
             WHERE spare_parts.quantity " . ($strictEnough ? '>= :requested_quantity' : '> 0') . "
             " . $orderSql
        );
        if ($strictEnough) {
            $params['requested_quantity'] = $requestedQuantity;
        }
        $statement->execute($params);
        return array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            $row['min_quantity'] = (int) $row['min_quantity'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    private static function licenseStockOptionsForItem(array $item, bool $strictEnough = true): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || (string) ($item['item_type'] ?? '') !== 'license') {
            return [];
        }

        $searchTerm = trim((string) ($item['item_name'] ?? ''));
        $requestedQuantity = max(1, (int) ($item['quantity'] ?? 1));
        $params = [];
        $where = [
            'licenses.seats_total > licenses.seats_used',
        ];
        if ($strictEnough) {
            $where[] = '(licenses.seats_total - licenses.seats_used) >= :requested_quantity';
            $params['requested_quantity'] = $requestedQuantity;
        }

        $orderSql = 'ORDER BY available_seats DESC, licenses.product_name ASC';
        if ($searchTerm !== '') {
            $params['search'] = '%' . $searchTerm . '%';
            $orderSql = 'ORDER BY
                CASE
                    WHEN (licenses.product_name LIKE :search OR COALESCE(licenses.vendor_name, \'\') LIKE :search OR COALESCE(licenses.license_key, \'\') LIKE :search)
                    THEN 0
                    ELSE 1
                END,
                available_seats DESC,
                licenses.product_name ASC';
        }

        $statement = $pdo->prepare(
            "SELECT licenses.id,
                    licenses.product_name,
                    COALESCE(licenses.vendor_name, '') AS vendor_name,
                    licenses.license_type,
                    COALESCE(licenses.license_key, '') AS license_key,
                    licenses.seats_total,
                    licenses.seats_used,
                    (licenses.seats_total - licenses.seats_used) AS available_seats
             FROM licenses
             WHERE " . implode(' AND ', $where) . "
             " . $orderSql
        );
        $statement->execute($params);
        return array_map(static function (array $row): array {
            $row['seats_total'] = (int) $row['seats_total'];
            $row['seats_used'] = (int) $row['seats_used'];
            $row['available_seats'] = (int) $row['available_seats'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    private static function normalizeAmount(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private static function submitInternal(PDO $pdo, int $requestId, int $requestedByUserId): void
    {
        $request = self::findForUpdate($pdo, $requestId);
        if ($request === null) {
            throw new \RuntimeException('Request not found.');
        }

        if (!in_array((string) $request['status'], ['draft', 'needs_info'], true)) {
            throw new \RuntimeException('Only drafts can be submitted.');
        }

        $pendingUserId = self::pendingAssigneeId($pdo, 'technician');
        $statement = $pdo->prepare(
            'UPDATE asset_requests
             SET status = :status,
                 current_pending_role = :current_pending_role,
                 current_pending_user_id = :current_pending_user_id,
                 submitted_at = COALESCE(submitted_at, NOW()),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $requestId,
            'status' => 'pending_it',
            'current_pending_role' => 'technician',
            'current_pending_user_id' => $pendingUserId,
        ]);

        self::addTimeline($pdo, $requestId, 'submitted', (string) $request['status'], 'pending_it', '');

        self::notifyRole($pdo, 'technician', 'request_pending', [
            'title' => __('requests.notifications.pending_title', 'Request awaiting approval'),
            'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.pending_it_message', 'A request is waiting for IT review.'),
            'route' => route('requests.show', ['id' => $requestId]),
        ]);

        self::notifyUsers($pdo, [$requestedByUserId], 'request_submitted', [
            'title' => __('requests.notifications.submitted_title', 'Request submitted'),
            'message' => (string) $request['request_no'] . ' - ' . __('requests.notifications.submitted_message', 'The request has been sent to IT.'),
            'route' => route('requests.show', ['id' => $requestId]),
        ]);
    }

    private static function approvalStepForStatus(string $status, array $request = []): ?array
    {
        $configuration = self::configuration();
        $needsManager = (bool) ($configuration['it_manager_required'] ?? true);
        $needsFinanceApproval = self::financeApprovalRequired($request);

        return match ($status) {
            'pending_it' => [
                'approval_step' => 'it',
                'role' => 'technician',
                'next_status' => $needsManager
                    ? 'pending_it_manager'
                    : ($needsFinanceApproval ? 'pending_finance' : 'approved'),
                'next_pending_role' => $needsManager
                    ? 'it_manager'
                    : ($needsFinanceApproval ? 'finance' : 'none'),
            ],
            'pending_it_manager' => [
                'approval_step' => 'it_manager',
                'role' => 'it_manager',
                'next_status' => $needsFinanceApproval ? 'pending_finance' : 'approved',
                'next_pending_role' => $needsFinanceApproval ? 'finance' : 'none',
            ],
            'pending_finance' => [
                'approval_step' => 'finance',
                'role' => 'finance',
                'next_status' => 'approved',
                'next_pending_role' => 'none',
            ],
            default => null,
        };
    }

    private static function financeApprovalRequired(array $request): bool
    {
        $configuration = self::configuration();
        $mode = (string) ($configuration['finance_mode'] ?? 'always');
        if ($mode === 'disabled') {
            return false;
        }

        if ($mode === 'threshold') {
            return (float) ($request['estimated_cost'] ?? 0) >= (float) ($configuration['finance_threshold'] ?? 0);
        }

        return true;
    }

    private static function hasGlobalAccess(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        return ($user['role'] ?? '') === 'admin' || can('requests.approve');
    }

    private static function matchesPendingRole(array $request, string $role): bool
    {
        $pendingRole = (string) ($request['current_pending_role'] ?? '');
        return $pendingRole === '' || $pendingRole === $role;
    }

    private static function matchesPendingUser(array $request, ?array $user): bool
    {
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        $pendingUserId = (int) ($request['current_pending_user_id'] ?? 0);
        return $pendingUserId <= 0 || $pendingUserId === (int) $user['id'];
    }

    private static function generateRequestNo(PDO $pdo): string
    {
        $nextId = (int) ($pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM asset_requests')->fetchColumn() ?: 1);
        return sprintf('REQ-%s-%04d', date('Y'), $nextId);
    }

    private static function findForUpdate(PDO $pdo, int $id): ?array
    {
        $statement = $pdo->prepare('SELECT * FROM asset_requests WHERE id = :id LIMIT 1 FOR UPDATE');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private static function storageAssetForUpdate(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    COALESCE(assets.tag, '') AS tag,
                    assets.request_id,
                    assets.branch_id,
                    assets.category_id,
                    assets.status,
                    assets.assigned_employee_id
             FROM assets
             WHERE assets.id = :id
             LIMIT 1
             FOR UPDATE"
        );
        $statement->execute(['id' => $assetId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        if ((string) ($row['status'] ?? '') !== 'storage' || !empty($row['assigned_employee_id']) || !empty($row['request_id'])) {
            return null;
        }

        return $row;
    }

    private static function licenseForUpdate(PDO $pdo, int $licenseId): ?array
    {
        $statement = $pdo->prepare(
            "SELECT id,
                    product_name,
                    seats_total,
                    seats_used,
                    (seats_total - seats_used) AS available_seats
             FROM licenses
             WHERE id = :id
             LIMIT 1
             FOR UPDATE"
        );
        $statement->execute(['id' => $licenseId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private static function sparePartForUpdate(PDO $pdo, int $sparePartId): ?array
    {
        $statement = $pdo->prepare(
            "SELECT id,
                    name,
                    COALESCE(part_number, '') AS part_number,
                    quantity
             FROM spare_parts
             WHERE id = :id
             LIMIT 1
             FOR UPDATE"
        );
        $statement->execute(['id' => $sparePartId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private static function receiveIntoInventory(PDO $pdo, array $request, array $input): string
    {
        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return '';
        }

        $purchaseVendor = trim((string) ($input['purchase_vendor'] ?? ($request['purchase_vendor'] ?? '')));
        $purchaseDate = self::normalizeDate($input['purchase_date'] ?? ($request['purchase_date'] ?? null));
        $receivedDate = self::normalizeDate($input['received_date'] ?? ($request['received_date'] ?? null));
        $requestedEmployeeId = (int) ($request['requested_for_employee_id'] ?? 0);
        $requestBranchId = ($request['branch_id'] ?? '') === '' ? null : (int) $request['branch_id'];
        $messages = [];

        foreach (self::requestItems($requestId) as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $itemType = (string) ($item['item_type'] ?? '');
            if ($itemType === 'spare_part') {
                DataRepository::createSparePart([
                    'name' => $item['item_name'] ?? '',
                    'part_number' => '',
                    'category' => $item['category_name'] ?? '',
                    'vendor_name' => $purchaseVendor,
                    'location' => $request['branch_name'] ?? '',
                    'quantity' => $quantity,
                    'min_quantity' => 0,
                    'compatible_with' => $item['specification'] ?? '',
                    'notes' => trim((string) ($item['notes'] ?? '')),
                ]);

                self::addTimeline(
                    $pdo,
                    $requestId,
                    'stock_increased_spare_part',
                    (string) ($request['status'] ?? ''),
                    'received',
                    trim((string) ($item['item_name'] ?? '') . ' x' . $quantity)
                );

                $messages[] = __('requests.stock_received_spare_part', 'Spare part stock was increased on receive.');
                continue;
            }

            if ($itemType !== 'license') {
                continue;
            }

            $licenseId = DataRepository::createLicense([
                'product_name' => $item['item_name'] ?? '',
                'vendor_name' => $purchaseVendor,
                'license_type' => 'subscription',
                'license_key' => '',
                'seats_total' => $quantity,
                'seats_used' => 0,
                'purchase_date' => $purchaseDate ?? $receivedDate,
                'expiry_date' => null,
                'status' => 'active',
                'assigned_asset_id' => '',
                'assigned_employee_id' => '',
                'notes' => trim((string) ($item['specification'] ?? '')),
            ]);

            self::addTimeline(
                $pdo,
                $requestId,
                'stock_increased_license',
                (string) ($request['status'] ?? ''),
                'received',
                trim((string) ($item['item_name'] ?? '') . ' x' . $quantity)
            );

            $assignmentTarget = (string) ($item['assignment_target'] ?? 'employee');
            $shouldAllocate = ($assignmentTarget === 'employee' && $requestedEmployeeId > 0)
                || ($assignmentTarget === 'branch' && $requestBranchId !== null);

            if ($shouldAllocate) {
                $pdo->prepare(
                    'UPDATE licenses
                     SET seats_used = seats_used + :quantity,
                         updated_at = NOW()
                     WHERE id = :id'
                )->execute([
                    'id' => $licenseId,
                    'quantity' => $quantity,
                ]);

                $allocation = $pdo->prepare(
                    'INSERT INTO license_allocations (license_id, request_id, employee_id, branch_id, quantity, notes, allocated_by, allocated_at, created_at, updated_at)
                     VALUES (:license_id, :request_id, :employee_id, :branch_id, :quantity, :notes, :allocated_by, NOW(), NOW(), NOW())'
                );
                $allocation->execute([
                    'license_id' => $licenseId,
                    'request_id' => $requestId,
                    'employee_id' => $assignmentTarget === 'employee' ? $requestedEmployeeId : null,
                    'branch_id' => $assignmentTarget === 'branch' ? $requestBranchId : $requestBranchId,
                    'quantity' => $quantity,
                    'notes' => trim('[PURCHASE RECEIVE] ' . (string) ($request['request_no'] ?? '')),
                    'allocated_by' => auth_user()['id'] ?? null,
                ]);
            }

            $messages[] = $shouldAllocate
                ? __('requests.stock_received_license_allocated', 'License seats were added and allocated on receive.')
                : __('requests.stock_received_license', 'License seats were increased on receive.');
        }

        $messages = array_values(array_unique(array_filter($messages, static fn (string $message): bool => $message !== '')));
        return implode(' | ', $messages);
    }

    private static function fulfillmentBranchId(PDO $pdo, array $asset, array $request, int $employeeId): ?int
    {
        if (!empty($request['branch_id'])) {
            return (int) $request['branch_id'];
        }

        $statement = $pdo->prepare('SELECT branch_id FROM employees WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $employeeId]);
        $branchId = $statement->fetchColumn();
        if ($branchId !== false && $branchId !== null) {
            return (int) $branchId;
        }

        return isset($asset['branch_id']) && $asset['branch_id'] !== null ? (int) $asset['branch_id'] : null;
    }

    private static function pendingAssigneeId(PDO $pdo, string $role): ?int
    {
        if ($role === 'none') {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE role = :role
               AND status = 'active'
             ORDER BY id ASC
             LIMIT 1"
        );
        $statement->execute(['role' => $role]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function addApproval(PDO $pdo, int $requestId, string $step, int $actorId, string $decision, string $comment): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO asset_request_approvals (request_id, step, approver_user_id, decision, comment, acted_at, created_at, updated_at)
             VALUES (:request_id, :step, :approver_user_id, :decision, :comment, NOW(), NOW(), NOW())'
        );
        $statement->execute([
            'request_id' => $requestId,
            'step' => $step,
            'approver_user_id' => $actorId,
            'decision' => $decision,
            'comment' => $comment,
        ]);
    }

    private static function addTimeline(PDO $pdo, int $requestId, string $action, ?string $fromStatus, ?string $toStatus, string $comment): void
    {
        $actor = auth_user();
        $statement = $pdo->prepare(
            'INSERT INTO asset_request_timeline (request_id, actor_user_id, actor_role, action, from_status, to_status, comment, created_at)
             VALUES (:request_id, :actor_user_id, :actor_role, :action, :from_status, :to_status, :comment, NOW())'
        );
        $statement->execute([
            'request_id' => $requestId,
            'actor_user_id' => $actor['id'] ?? null,
            'actor_role' => $actor['role'] ?? 'system',
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
        ]);
    }

    private static function notifyRole(PDO $pdo, string $role, string $type, array $data): void
    {
        if ($role === 'none') {
            return;
        }

        $statement = $pdo->prepare("SELECT id FROM users WHERE role = :role AND status = 'active'");
        $statement->execute(['role' => $role]);
        $userIds = array_map(static fn (array $row): int => (int) $row['id'], $statement->fetchAll() ?: []);
        self::notifyUsers($pdo, $userIds, $type, $data);
    }

    private static function notifyUsers(PDO $pdo, array $userIds, string $type, array $data): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if ($userIds === []) {
            return;
        }

        $statement = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, data, read_at, created_at)
             VALUES (:user_id, :type, :data, NULL, NOW())'
        );

        foreach ($userIds as $userId) {
            $statement->execute([
                'user_id' => $userId,
                'type' => $type,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
}
