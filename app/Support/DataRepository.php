<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

class DataRepository
{
    /**
     * Return a small set of counters for the dashboard.  Originally this method ran
     * four separate `COUNT(*)` queries which became expensive when the `assets`
     * table contained thousands of rows.  Switch to a single aggregation
     * query and cast the returned values to integers.  Keep the API the same so
     * that callers (the controller/view) are unaffected.
     *
     * Because this is executed on every page load there is still a small cost,
     * but the single-query form is roughly 4× faster and will make the dashboard
     * feel snappier when there is a lot of data.  An application-level cache can
     * be added later if cross-request caching is desired.
     */
    // path for a simple on‑disk cache; writable by the webserver
    private const DASHBOARD_CACHE = __DIR__ . '/../../storage/data/dashboard_stats.json';

    public static function dashboardStats(): array
    {
        // try to serve from cache first, valid for 60 seconds
        $path = self::DASHBOARD_CACHE;
        if (is_readable($path)) {
            $blob = json_decode((string) file_get_contents($path), true);
            if (is_array($blob)
                && isset($blob['ts'], $blob['stats'])
                && (time() - (int) $blob['ts']) < 60
                && is_array($blob['stats'])
            ) {
                return $blob['stats'];
            }
        }

        $pdo = Database::connect();
        if ($pdo instanceof PDO) {
            $sql = <<<'SQL'
                SELECT
                    COUNT(*) AS total_assets,
                    SUM(status = 'active') AS active_assets,
                    SUM(status = 'storage') AS in_storage,
                    SUM(status IN ('repair','broken')) AS attention_needed
                FROM assets
            SQL;

            $row = $pdo->query($sql)->fetch();
            if (!is_array($row)) {
                $result = ['total_assets' => 0, 'active_assets' => 0, 'in_storage' => 0, 'attention_needed' => 0];
            } else {
                // the database returns numeric strings; convert to ints to avoid overflow
                $result = [
                    'total_assets' => (int) $row['total_assets'],
                    'active_assets' => (int) $row['active_assets'],
                    'in_storage' => (int) $row['in_storage'],
                    'attention_needed' => (int) $row['attention_needed'],
                ];
            }

            // write back to cache (ignore failures)
            @file_put_contents($path, json_encode(['ts' => time(), 'stats' => $result]));
            return $result;
        }

        return ['total_assets' => 0, 'active_assets' => 0, 'in_storage' => 0, 'attention_needed' => 0];
    }

    public static function recentMovements(): array
    {
        $pdo = Database::connect();
        if ($pdo instanceof PDO) {
            $sql = <<<'SQL'
                SELECT assets.name AS asset,
                       COALESCE(fb.name, 'N/A') AS `from`,
                       COALESCE(tb.name, 'N/A') AS `to`,
                       DATE(asset_movements.moved_at) AS `date`
                FROM asset_movements
                JOIN assets ON assets.id = asset_movements.asset_id
                LEFT JOIN branches fb ON fb.id = asset_movements.from_branch_id
                LEFT JOIN branches tb ON tb.id = asset_movements.to_branch_id
                ORDER BY asset_movements.moved_at DESC
                LIMIT 5
            SQL;
            return $pdo->query($sql)->fetchAll() ?: [];
        }
        return [];
    }

    public static function dashboardOverview(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'kpis' => [
                    'total_branches' => 0,
                    'total_categories' => 0,
                    'total_employees' => 0,
                    'assigned_assets' => 0,
                    'unassigned_assets' => 0,
                    'ordered_assets' => 0,
                    'received_assets' => 0,
                    'deployed_assets' => 0,
                    'expiring_warranties' => 0,
                    'purchased_this_month' => 0,
                ],
                'status_breakdown' => [],
                'category_breakdown' => [],
                'branch_load' => [],
                'recent_assets' => [],
                'warranty_alerts' => [],
            ];
        }

        $kpis = $pdo->query(<<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM branches) AS total_branches,
                (SELECT COUNT(*) FROM asset_categories) AS total_categories,
                (SELECT COUNT(*) FROM employees WHERE status = 'active') AS total_employees,
                (SELECT COUNT(*) FROM assets WHERE assigned_employee_id IS NOT NULL) AS assigned_assets,
                (SELECT COUNT(*) FROM assets WHERE assigned_employee_id IS NULL) AS unassigned_assets,
                (SELECT COUNT(*) FROM assets WHERE procurement_stage = 'ordered') AS ordered_assets,
                (SELECT COUNT(*) FROM assets WHERE procurement_stage = 'received') AS received_assets,
                (SELECT COUNT(*) FROM assets WHERE procurement_stage = 'deployed') AS deployed_assets,
                (SELECT COUNT(*) FROM assets WHERE warranty_expiry IS NOT NULL AND warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring_warranties,
                (SELECT COUNT(*) FROM assets WHERE purchase_date IS NOT NULL AND YEAR(purchase_date) = YEAR(CURDATE()) AND MONTH(purchase_date) = MONTH(CURDATE())) AS purchased_this_month
        SQL)->fetch() ?: [];

        $statusBreakdown = $pdo->query(<<<'SQL'
            SELECT status, COUNT(*) AS total
            FROM assets
            GROUP BY status
            ORDER BY total DESC, status ASC
        SQL)->fetchAll() ?: [];

        $categoryBreakdown = $pdo->query(<<<'SQL'
            SELECT COALESCE(asset_categories.name, 'Uncategorized') AS category, COUNT(*) AS total
            FROM assets
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
            GROUP BY COALESCE(asset_categories.name, 'Uncategorized')
            ORDER BY total DESC, category ASC
            LIMIT 6
        SQL)->fetchAll() ?: [];

        $branchLoad = $pdo->query(<<<'SQL'
            SELECT
                COALESCE(branches.name, 'Unassigned') AS branch,
                COUNT(DISTINCT assets.id) AS asset_total,
                COUNT(DISTINCT CASE WHEN employees.status = 'active' THEN employees.id END) AS employee_total
            FROM branches
            LEFT JOIN assets ON assets.branch_id = branches.id
            LEFT JOIN employees ON employees.branch_id = branches.id
            GROUP BY branches.id, branches.name
            ORDER BY asset_total DESC, branch ASC
            LIMIT 8
        SQL)->fetchAll() ?: [];

        $recentAssets = $pdo->query(<<<'SQL'
            SELECT
                assets.id,
                assets.name,
                assets.tag,
                COALESCE(branches.name, 'Unassigned') AS branch,
                assets.status,
                COALESCE(assets.procurement_stage, 'received') AS procurement_stage,
                COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '-') AS purchase_date
            FROM assets
            LEFT JOIN branches ON branches.id = assets.branch_id
            ORDER BY assets.id DESC
            LIMIT 6
        SQL)->fetchAll() ?: [];

        $warrantyAlerts = $pdo->query(<<<'SQL'
            SELECT
                assets.id,
                assets.name,
                assets.tag,
                COALESCE(branches.name, 'Unassigned') AS branch,
                DATE_FORMAT(assets.warranty_expiry, '%Y-%m-%d') AS warranty_expiry,
                DATEDIFF(assets.warranty_expiry, CURDATE()) AS days_left
            FROM assets
            LEFT JOIN branches ON branches.id = assets.branch_id
            WHERE assets.warranty_expiry IS NOT NULL
            ORDER BY assets.warranty_expiry ASC
            LIMIT 8
        SQL)->fetchAll() ?: [];

        return [
            'kpis' => array_map(static fn ($value): int => (int) $value, $kpis),
            'status_breakdown' => array_map(static fn (array $row): array => [
                'status' => (string) $row['status'],
                'total' => (int) $row['total'],
            ], $statusBreakdown),
            'category_breakdown' => array_map(static fn (array $row): array => [
                'category' => (string) $row['category'],
                'total' => (int) $row['total'],
            ], $categoryBreakdown),
            'branch_load' => array_map(static fn (array $row): array => [
                'branch' => (string) $row['branch'],
                'asset_total' => (int) $row['asset_total'],
                'employee_total' => (int) $row['employee_total'],
            ], $branchLoad),
            'recent_assets' => $recentAssets,
            'warranty_alerts' => array_map(static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'tag' => (string) $row['tag'],
                'branch' => (string) $row['branch'],
                'warranty_expiry' => (string) $row['warranty_expiry'],
                'days_left' => (int) $row['days_left'],
            ], $warrantyAlerts),
        ];
    }

    public static function dashboardCharts(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'monthly_purchases' => [],
                'status_mix' => [],
                'branch_distribution' => [],
            ];
        }

        $monthlyRows = $pdo->query(<<<'SQL'
            SELECT DATE_FORMAT(purchase_date, '%Y-%m') AS period, COUNT(*) AS total
            FROM assets
            WHERE purchase_date IS NOT NULL
              AND purchase_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
            ORDER BY period ASC
        SQL)->fetchAll() ?: [];

        $monthlyMap = [];
        foreach ($monthlyRows as $row) {
            $monthlyMap[(string) $row['period']] = (int) $row['total'];
        }

        $monthlyPurchases = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $timestamp = strtotime('-' . $offset . ' month');
            $period = date('Y-m', $timestamp);
            $monthlyPurchases[] = [
                'label' => $period,
                'total' => $monthlyMap[$period] ?? 0,
            ];
        }

        $statusMix = $pdo->query(<<<'SQL'
            SELECT status, COUNT(*) AS total
            FROM assets
            GROUP BY status
            ORDER BY total DESC, status ASC
        SQL)->fetchAll() ?: [];

        $branchDistribution = $pdo->query(<<<'SQL'
            SELECT COALESCE(branches.name, 'Unassigned') AS branch, COUNT(*) AS total
            FROM assets
            LEFT JOIN branches ON branches.id = assets.branch_id
            WHERE assets.status <> 'archived'
            GROUP BY COALESCE(branches.name, 'Unassigned')
            ORDER BY total DESC, branch ASC
            LIMIT 6
        SQL)->fetchAll() ?: [];

        return [
            'monthly_purchases' => array_map(static fn (array $row): array => [
                'label' => (string) $row['label'],
                'total' => (int) $row['total'],
            ], $monthlyPurchases),
            'status_mix' => array_map(static fn (array $row): array => [
                'label' => (string) $row['status'],
                'total' => (int) $row['total'],
            ], $statusMix),
            'branch_distribution' => array_map(static fn (array $row): array => [
                'label' => (string) $row['branch'],
                'total' => (int) $row['total'],
            ], $branchDistribution),
        ];
    }

    public static function assets(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT
                assets.id,
                assets.tag,
                COALESCE(assets.barcode, '') AS barcode,
                assets.name,
                assets.request_id,
                assets.stock_group_id,
                assets.branch_id,
                assets.assigned_employee_id,
                COALESCE(asset_categories.name, 'Uncategorized') AS category,
                COALESCE(asset_stock_groups.display_name, '') AS stock_group,
                COALESCE(branches.name, 'Unassigned') AS location,
                assets.status,
                COALESCE(employees.name, 'Unassigned') AS assigned_to,
                COALESCE(assets.serial_number, '') AS serial_number,
                COALESCE(assets.brand, '') AS brand,
                COALESCE(assets.model, '') AS model,
                COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                COALESCE(DATE_FORMAT(assets.warranty_expiry, '%Y-%m-%d'), '') AS warranty_expiry,
                COALESCE(assets.procurement_stage, 'received') AS procurement_stage,
                COALESCE(assets.vendor_name, '') AS vendor_name,
                COALESCE(assets.invoice_number, '') AS invoice_number,
                COALESCE(DATE_FORMAT(assets.archived_at, '%Y-%m-%d'), '') AS archived_at,
                COALESCE(assets.archive_reason, '') AS archive_reason,
                COALESCE(assets.notes, '') AS notes,
                COUNT(asset_documents.id) AS documents_count
            FROM assets
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
            LEFT JOIN asset_stock_groups ON asset_stock_groups.id = assets.stock_group_id
            LEFT JOIN branches ON branches.id = assets.branch_id
            LEFT JOIN employees ON employees.id = assets.assigned_employee_id
            LEFT JOIN asset_documents ON asset_documents.asset_id = assets.id
            GROUP BY assets.id
            ORDER BY assets.id DESC
        SQL;

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    public static function findAsset(int $id): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $sql = <<<'SQL'
            SELECT
                assets.id,
                assets.tag,
                COALESCE(assets.barcode, '') AS barcode,
                assets.name,
                assets.request_id,
                assets.stock_group_id,
                assets.branch_id,
                assets.assigned_employee_id,
                COALESCE(asset_categories.name, 'Uncategorized') AS category,
                COALESCE(asset_stock_groups.display_name, '') AS stock_group,
                COALESCE(branches.name, 'Unassigned') AS location,
                assets.status,
                COALESCE(employees.name, 'Unassigned') AS assigned_to,
                COALESCE(assets.serial_number, '') AS serial_number,
                COALESCE(assets.brand, '') AS brand,
                COALESCE(assets.model, '') AS model,
                COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                COALESCE(DATE_FORMAT(assets.warranty_expiry, '%Y-%m-%d'), '') AS warranty_expiry,
                COALESCE(assets.procurement_stage, 'received') AS procurement_stage,
                COALESCE(assets.vendor_name, '') AS vendor_name,
                COALESCE(assets.invoice_number, '') AS invoice_number,
                COALESCE(asset_requests.request_no, '') AS request_no,
                COALESCE(asset_requests.title, '') AS request_title,
                COALESCE(DATE_FORMAT(assets.archived_at, '%Y-%m-%d'), '') AS archived_at,
                COALESCE(assets.archive_reason, '') AS archive_reason,
                COALESCE(assets.notes, '') AS notes
            FROM assets
            LEFT JOIN asset_requests ON asset_requests.id = assets.request_id
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
            LEFT JOIN asset_stock_groups ON asset_stock_groups.id = assets.stock_group_id
            LEFT JOIN branches ON branches.id = assets.branch_id
            LEFT JOIN employees ON employees.id = assets.assigned_employee_id
            WHERE assets.id = :id
            LIMIT 1
        SQL;

        $statement = $pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $asset = $statement->fetch();
        if (!is_array($asset)) {
            return null;
        }

        $docs = $pdo->prepare('SELECT document_name AS name, file_path AS path FROM asset_documents WHERE asset_id = :id ORDER BY id DESC');
        $docs->execute(['id' => $id]);
        $asset['documents'] = $docs->fetchAll() ?: [];

        return $asset;
    }

    public static function assetAssignments(int $assetId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT employees.id, employees.name, COALESCE(employees.department, '') AS department
             FROM asset_assignments
             JOIN employees ON employees.id = asset_assignments.employee_id
             WHERE asset_assignments.asset_id = :asset_id
               AND asset_assignments.returned_at IS NULL
             ORDER BY employees.name ASC"
        );
        $statement->execute(['asset_id' => $assetId]);
        return $statement->fetchAll() ?: [];
    }

    public static function assetMovements(int $assetId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            "SELECT asset_movements.id,
                    CASE
                        WHEN asset_movements.movement_type = 'request' OR asset_movements.notes LIKE '[REQUEST%' THEN 'request'
                        ELSE COALESCE(asset_movements.movement_type, 'manual')
                    END AS movement_type,
                    COALESCE(
                        asset_movements.request_id,
                        CASE WHEN asset_movements.notes LIKE '[REQUEST%' THEN asset_context.request_id ELSE NULL END
                    ) AS request_id,
                    COALESCE(asset_requests.request_no, '') AS request_no,
                    COALESCE(fb.name, 'N/A') AS `from`,
                    COALESCE(tb.name, 'N/A') AS `to`,
                    COALESCE(asset_movements.notes, '') AS notes,
                    DATE(asset_movements.moved_at) AS `date`,
                    COUNT(asset_movement_documents.id) AS documents_count
             FROM asset_movements
             LEFT JOIN assets asset_context ON asset_context.id = asset_movements.asset_id
             LEFT JOIN asset_requests ON asset_requests.id = COALESCE(
                 asset_movements.request_id,
                 CASE WHEN asset_movements.notes LIKE '[REQUEST%' THEN asset_context.request_id ELSE NULL END
             )
             LEFT JOIN branches fb ON fb.id = asset_movements.from_branch_id
             LEFT JOIN branches tb ON tb.id = asset_movements.to_branch_id
             LEFT JOIN asset_movement_documents ON asset_movement_documents.movement_id = asset_movements.id
             WHERE asset_movements.asset_id = :asset_id
             GROUP BY asset_movements.id,
                      CASE
                          WHEN asset_movements.movement_type = 'request' OR asset_movements.notes LIKE '[REQUEST%' THEN 'request'
                          ELSE COALESCE(asset_movements.movement_type, 'manual')
                      END,
                      COALESCE(
                          asset_movements.request_id,
                          CASE WHEN asset_movements.notes LIKE '[REQUEST%' THEN asset_context.request_id ELSE NULL END
                      ),
                      asset_requests.request_no,
                      fb.name,
                      tb.name,
                      asset_movements.notes,
                      DATE(asset_movements.moved_at)
             ORDER BY asset_movements.moved_at DESC, asset_movements.id DESC"
        );
        $statement->execute(['asset_id' => $assetId]);
        $movements = $statement->fetchAll() ?: [];
        $docs = $pdo->prepare('SELECT movement_id, document_name AS name, file_path AS path FROM asset_movement_documents WHERE movement_id IN (SELECT id FROM asset_movements WHERE asset_id = :asset_id) ORDER BY id DESC');
        $docs->execute(['asset_id' => $assetId]);
        $documentRows = $docs->fetchAll() ?: [];
        $documentsByMovement = [];
        foreach ($documentRows as $row) {
            $movementId = (int) $row['movement_id'];
            unset($row['movement_id']);
            $documentsByMovement[$movementId][] = $row;
        }

        return array_map(static function (array $movement) use ($documentsByMovement): array {
            $movement['documents'] = $documentsByMovement[(int) $movement['id']] ?? [];
            $movement['documents_count'] = (int) $movement['documents_count'];
            $movement['request_id'] = $movement['request_id'] === null ? null : (int) $movement['request_id'];
            return $movement;
        }, $movements);
    }

    public static function assetRepairs(int $assetId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    vendor_name,
                    COALESCE(reference_number, '') AS reference_number,
                    DATE_FORMAT(sent_at, '%Y-%m-%d %H:%i') AS sent_at,
                    COALESCE(DATE_FORMAT(completed_at, '%Y-%m-%d %H:%i'), '') AS completed_at,
                    outcome,
                    COALESCE(return_status, '') AS return_status,
                    COALESCE(notes, '') AS notes,
                    COALESCE(completion_notes, '') AS completion_notes
             FROM asset_repairs
             WHERE asset_id = :asset_id
             ORDER BY id DESC"
        );
        $statement->execute(['asset_id' => $assetId]);
        return $statement->fetchAll() ?: [];
    }

    public static function openRepair(int $assetId): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    vendor_name,
                    COALESCE(reference_number, '') AS reference_number,
                    DATE_FORMAT(sent_at, '%Y-%m-%d %H:%i') AS sent_at,
                    outcome,
                    COALESCE(notes, '') AS notes
             FROM asset_repairs
             WHERE asset_id = :asset_id
               AND outcome = 'in_progress'
             ORDER BY id DESC
             LIMIT 1"
        );
        $statement->execute(['asset_id' => $assetId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function assetHandovers(int $assetId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT asset_handovers.id,
                    asset_handovers.employee_id,
                    employees.name AS employee_name,
                    employees.employee_code,
                    asset_handovers.handover_type,
                    DATE_FORMAT(asset_handovers.handover_date, '%Y-%m-%d') AS handover_date,
                    COALESCE(asset_handovers.notes, '') AS notes,
                    COALESCE(users.name, 'System') AS created_by,
                    DATE_FORMAT(asset_handovers.created_at, '%Y-%m-%d %H:%i') AS created_at
             FROM asset_handovers
             JOIN employees ON employees.id = asset_handovers.employee_id
             LEFT JOIN users ON users.id = asset_handovers.created_by
             WHERE asset_handovers.asset_id = :asset_id
             ORDER BY asset_handovers.id DESC"
        );
        $statement->execute(['asset_id' => $assetId]);
        return $statement->fetchAll() ?: [];
    }

    public static function createAssetHandover(int $assetId, array $input): int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return 0;
        }

        $statement = $pdo->prepare(
            'INSERT INTO asset_handovers (asset_id, employee_id, handover_type, handover_date, notes, created_by, created_at, updated_at)
             VALUES (:asset_id, :employee_id, :handover_type, :handover_date, :notes, :created_by, NOW(), NOW())'
        );
        $statement->execute([
            'asset_id' => $assetId,
            'employee_id' => (int) ($input['employee_id'] ?? 0),
            'handover_type' => trim((string) ($input['handover_type'] ?? 'issue')),
            'handover_date' => self::nullableDate($input['handover_date'] ?? null) ?? date('Y-m-d'),
            'notes' => trim((string) ($input['notes'] ?? '')),
            'created_by' => auth_user()['id'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findAssetHandover(int $handoverId): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT asset_handovers.id,
                    asset_handovers.asset_id,
                    asset_handovers.employee_id,
                    asset_handovers.handover_type,
                    DATE_FORMAT(asset_handovers.handover_date, '%Y-%m-%d') AS handover_date,
                    COALESCE(asset_handovers.notes, '') AS notes,
                    DATE_FORMAT(asset_handovers.created_at, '%Y-%m-%d %H:%i') AS created_at,
                    assets.name AS asset_name,
                    assets.tag,
                    COALESCE(asset_categories.name, 'Uncategorized') AS category_name,
                    COALESCE(branches.name, 'Unassigned') AS branch_name,
                    employees.name AS employee_name,
                    employees.employee_code,
                    COALESCE(employees.job_title, '') AS job_title,
                    COALESCE(employees.company_email, '') AS company_email,
                    COALESCE(users.name, 'System') AS created_by
             FROM asset_handovers
             JOIN assets ON assets.id = asset_handovers.asset_id
             JOIN employees ON employees.id = asset_handovers.employee_id
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             LEFT JOIN branches ON branches.id = assets.branch_id
             LEFT JOIN users ON users.id = asset_handovers.created_by
             WHERE asset_handovers.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $handoverId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function assetMaintenance(int $assetId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    maintenance_type,
                    DATE_FORMAT(scheduled_date, '%Y-%m-%d') AS scheduled_date,
                    COALESCE(DATE_FORMAT(completed_date, '%Y-%m-%d'), '') AS completed_date,
                    CASE
                        WHEN status = 'completed' THEN 'completed'
                        WHEN scheduled_date < CURDATE() THEN 'overdue'
                        ELSE status
                    END AS status,
                    COALESCE(technician_name, '') AS technician_name,
                    COALESCE(vendor_name, '') AS vendor_name,
                    COALESCE(cost, 0) AS cost,
                    COALESCE(notes, '') AS notes,
                    COALESCE(result_summary, '') AS result_summary,
                    COALESCE(DATE_FORMAT(next_service_date, '%Y-%m-%d'), '') AS next_service_date
             FROM asset_maintenance
             WHERE asset_id = :asset_id
             ORDER BY scheduled_date DESC, id DESC"
        );
        $statement->execute(['asset_id' => $assetId]);

        return array_map(static function (array $row): array {
            $row['cost'] = (float) $row['cost'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function openMaintenance(int $assetId): ?array
    {
        $rows = self::assetMaintenance($assetId);
        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'completed') {
                return $row;
            }
        }
        return null;
    }

    public static function createAssetMaintenance(int $assetId, array $input): int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return 0;
        }

        $statement = $pdo->prepare(
            'INSERT INTO asset_maintenance (asset_id, maintenance_type, scheduled_date, status, technician_name, vendor_name, cost, notes, created_by, created_at, updated_at)
             VALUES (:asset_id, :maintenance_type, :scheduled_date, :status, :technician_name, :vendor_name, :cost, :notes, :created_by, NOW(), NOW())'
        );
        $statement->execute([
            'asset_id' => $assetId,
            'maintenance_type' => trim((string) ($input['maintenance_type'] ?? 'preventive')),
            'scheduled_date' => self::nullableDate($input['scheduled_date'] ?? null) ?? date('Y-m-d'),
            'status' => trim((string) ($input['status'] ?? 'scheduled')),
            'technician_name' => trim((string) ($input['technician_name'] ?? '')),
            'vendor_name' => trim((string) ($input['vendor_name'] ?? '')),
            'cost' => trim((string) ($input['cost'] ?? '')) === '' ? 0 : (float) $input['cost'],
            'notes' => trim((string) ($input['notes'] ?? '')),
            'created_by' => auth_user()['id'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function completeAssetMaintenance(int $maintenanceId, array $input): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $statement = $pdo->prepare(
            'UPDATE asset_maintenance
             SET status = :status,
                 completed_date = :completed_date,
                 technician_name = :technician_name,
                 vendor_name = :vendor_name,
                 cost = :cost,
                 result_summary = :result_summary,
                 notes = :notes,
                 next_service_date = :next_service_date,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $maintenanceId,
            'status' => 'completed',
            'completed_date' => self::nullableDate($input['completed_date'] ?? null) ?? date('Y-m-d'),
            'technician_name' => trim((string) ($input['technician_name'] ?? '')),
            'vendor_name' => trim((string) ($input['vendor_name'] ?? '')),
            'cost' => trim((string) ($input['cost'] ?? '')) === '' ? 0 : (float) $input['cost'],
            'result_summary' => trim((string) ($input['result_summary'] ?? '')),
            'notes' => trim((string) ($input['notes'] ?? '')),
            'next_service_date' => self::nullableDate($input['next_service_date'] ?? null),
        ]);
    }

    public static function createAsset(array $input, array $documents = []): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO assets (name, request_id, stock_group_id, category_id, brand, model, serial_number, purchase_date, warranty_expiry, procurement_stage, vendor_name, invoice_number, status, branch_id, assigned_employee_id, notes, created_at, updated_at)
             VALUES (:name, :request_id, NULL, :category_id, :brand, :model, :serial_number, :purchase_date, :warranty_expiry, :procurement_stage, :vendor_name, :invoice_number, :status, :branch_id, :assigned_employee_id, :notes, NOW(), NOW())'
        );
        $statement->execute(self::mapAssetDbParams($input));
        $id = (int) $pdo->lastInsertId();
        $tag = self::generatedAssetTag($id);
        $pdo->prepare('UPDATE assets SET tag = :tag, barcode = COALESCE(NULLIF(barcode, \'\'), :barcode) WHERE id = :id')->execute([
            'id' => $id,
            'tag' => $tag,
            'barcode' => $tag,
        ]);
        self::syncAssetInventoryStateWithPdo($pdo, $id);
        self::insertAssetDocuments($pdo, $id, $documents);
        return $id;
    }

    public static function updateAsset(int $id, array $input, array $newDocuments = []): void
    {
        $pdo = Database::connect();
        $params = self::mapAssetDbParams($input);
        $params['id'] = $id;
        $statement = $pdo->prepare(
            'UPDATE assets SET
                name = :name,
                request_id = :request_id,
                stock_group_id = NULL,
                category_id = :category_id,
                brand = :brand,
                model = :model,
                serial_number = :serial_number,
                purchase_date = :purchase_date,
                warranty_expiry = :warranty_expiry,
                procurement_stage = :procurement_stage,
                vendor_name = :vendor_name,
                invoice_number = :invoice_number,
                status = :status,
                branch_id = :branch_id,
                assigned_employee_id = :assigned_employee_id,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute($params);
        self::syncAssetInventoryStateWithPdo($pdo, $id);
        self::insertAssetDocuments($pdo, $id, $newDocuments);
    }

    public static function syncAssetInventoryState(int $assetId): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        self::syncAssetInventoryStateWithPdo($pdo, $assetId);
    }

    public static function deleteAsset(int $id): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($id);
        if ($asset !== null) {
            UploadStore::deleteAssetDocuments($asset['documents'] ?? []);
        }
        $pdo->prepare('DELETE FROM assets WHERE id = :id')->execute(['id' => $id]);
    }

    public static function moveAsset(int $assetId, array $input, array $files = []): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($assetId);
        if (!$pdo instanceof PDO || $asset === null) {
            return;
        }

        $newBranchId = isset($input['branch_id']) && $input['branch_id'] !== '' ? (int) $input['branch_id'] : self::findBranchIdByName((string) $asset['location']);
        $newStatus = trim((string) ($input['status'] ?? $asset['status']));
        $notes = trim((string) ($input['movement_notes'] ?? ''));
        $selectedEmployeeIds = array_values(array_unique(array_map('intval', (array) ($input['assigned_employee_ids'] ?? []))));
        $allowedEmployeeIds = array_map(
            static fn (array $employee): int => (int) $employee['id'],
            self::activeEmployeesByBranch($newBranchId)
        );
        $selectedEmployeeIds = array_values(array_intersect($selectedEmployeeIds, $allowedEmployeeIds));
        $currentAssignments = self::assetAssignments($assetId);
        $currentEmployeeIds = array_map(static fn (array $row): int => (int) $row['id'], $currentAssignments);
        $toReturn = array_diff($currentEmployeeIds, $selectedEmployeeIds);
        $toAdd = array_diff($selectedEmployeeIds, $currentEmployeeIds);
        $fromBranchId = self::findBranchIdByName((string) $asset['location']);
        $hasMovementChange = $fromBranchId !== $newBranchId
            || $newStatus !== (string) $asset['status']
            || $toReturn !== []
            || $toAdd !== [];

        $pdo->beginTransaction();
        try {
            if ($hasMovementChange) {
                $movement = $pdo->prepare('INSERT INTO asset_movements (asset_id, from_branch_id, to_branch_id, user_id, notes, moved_at) VALUES (:asset_id, :from_branch_id, :to_branch_id, NULL, :notes, NOW())');
                $movement->execute([
                    'asset_id' => $assetId,
                    'from_branch_id' => $fromBranchId,
                    'to_branch_id' => $newBranchId,
                    'notes' => $notes,
                ]);
                $movementId = (int) $pdo->lastInsertId();
                $documents = UploadStore::saveMovementDocuments($movementId, $files);
                self::insertMovementDocuments($pdo, $movementId, $documents);
            }

            $primaryEmployeeId = $selectedEmployeeIds[0] ?? null;
            $update = $pdo->prepare('UPDATE assets SET branch_id = :branch_id, assigned_employee_id = :assigned_employee_id, status = :status, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'id' => $assetId,
                'branch_id' => $newBranchId,
                'assigned_employee_id' => $primaryEmployeeId,
                'status' => $newStatus,
            ]);
            self::syncAssetInventoryStateWithPdo($pdo, $assetId);

            if ($toReturn !== []) {
                $return = $pdo->prepare('UPDATE asset_assignments SET returned_at = NOW() WHERE asset_id = :asset_id AND employee_id = :employee_id AND returned_at IS NULL');
                foreach ($toReturn as $employeeId) {
                    $return->execute(['asset_id' => $assetId, 'employee_id' => $employeeId]);
                }
            }

            if ($toAdd !== []) {
                $assign = $pdo->prepare('INSERT INTO asset_assignments (asset_id, employee_id, notes, assigned_at) VALUES (:asset_id, :employee_id, :notes, NOW())');
                foreach ($toAdd as $employeeId) {
                    $assign->execute([
                        'asset_id' => $assetId,
                        'employee_id' => $employeeId,
                        'notes' => $notes,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function archiveAsset(int $assetId, string $reason, array $files = []): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($assetId);
        if (!$pdo instanceof PDO || $asset === null) {
            return;
        }

        $fromBranchId = self::findBranchIdByName((string) $asset['location']);

        $pdo->beginTransaction();
        try {
            $movement = $pdo->prepare('INSERT INTO asset_movements (asset_id, from_branch_id, to_branch_id, user_id, notes, moved_at) VALUES (:asset_id, :from_branch_id, NULL, NULL, :notes, NOW())');
            $movement->execute([
                'asset_id' => $assetId,
                'from_branch_id' => $fromBranchId,
                'notes' => '[ARCHIVE] ' . $reason,
            ]);
            $movementId = (int) $pdo->lastInsertId();
            $documents = UploadStore::saveMovementDocuments($movementId, $files);
            self::insertMovementDocuments($pdo, $movementId, $documents);

            $pdo->prepare('UPDATE asset_assignments SET returned_at = NOW() WHERE asset_id = :asset_id AND returned_at IS NULL')
                ->execute(['asset_id' => $assetId]);

            $update = $pdo->prepare('UPDATE assets SET status = :status, assigned_employee_id = NULL, archived_at = NOW(), archive_reason = :archive_reason, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'id' => $assetId,
                'status' => 'archived',
                'archive_reason' => $reason,
            ]);
            self::syncAssetInventoryStateWithPdo($pdo, $assetId);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function returnAsset(int $assetId, array $input, array $files = []): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($assetId);
        if (!$pdo instanceof PDO || $asset === null) {
            return;
        }

        $targetBranchId = isset($input['branch_id']) && $input['branch_id'] !== '' ? (int) $input['branch_id'] : self::findBranchIdByName((string) $asset['location']);
        $returnStatus = trim((string) ($input['status'] ?? 'storage'));
        $notes = trim((string) ($input['return_notes'] ?? ''));
        $fromBranchId = self::findBranchIdByName((string) $asset['location']);

        $pdo->beginTransaction();
        try {
            $movement = $pdo->prepare('INSERT INTO asset_movements (asset_id, from_branch_id, to_branch_id, user_id, notes, moved_at) VALUES (:asset_id, :from_branch_id, :to_branch_id, NULL, :notes, NOW())');
            $movement->execute([
                'asset_id' => $assetId,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $targetBranchId,
                'notes' => '[RETURN] ' . $notes,
            ]);
            $movementId = (int) $pdo->lastInsertId();
            $documents = UploadStore::saveMovementDocuments($movementId, $files);
            self::insertMovementDocuments($pdo, $movementId, $documents);

            $pdo->prepare('UPDATE asset_assignments SET returned_at = NOW() WHERE asset_id = :asset_id AND returned_at IS NULL')
                ->execute(['asset_id' => $assetId]);

            $update = $pdo->prepare('UPDATE assets SET branch_id = :branch_id, assigned_employee_id = NULL, status = :status, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'id' => $assetId,
                'branch_id' => $targetBranchId,
                'status' => $returnStatus,
            ]);
            self::syncAssetInventoryStateWithPdo($pdo, $assetId);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function sendAssetToRepair(int $assetId, array $input, array $files = []): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($assetId);
        if (!$pdo instanceof PDO || $asset === null) {
            return;
        }

        $vendorName = trim((string) ($input['vendor_name'] ?? ''));
        $referenceNumber = trim((string) ($input['reference_number'] ?? ''));
        $notes = trim((string) ($input['repair_notes'] ?? ''));
        $fromBranchId = self::findBranchIdByName((string) $asset['location']);

        $pdo->beginTransaction();
        try {
            $movement = $pdo->prepare('INSERT INTO asset_movements (asset_id, from_branch_id, to_branch_id, user_id, notes, moved_at) VALUES (:asset_id, :from_branch_id, NULL, NULL, :notes, NOW())');
            $movement->execute([
                'asset_id' => $assetId,
                'from_branch_id' => $fromBranchId,
                'notes' => '[REPAIR SENT] Vendor: ' . $vendorName . ($referenceNumber !== '' ? ' | Ref: ' . $referenceNumber : '') . ($notes !== '' ? ' | ' . $notes : ''),
            ]);
            $movementId = (int) $pdo->lastInsertId();
            $documents = UploadStore::saveMovementDocuments($movementId, $files);
            self::insertMovementDocuments($pdo, $movementId, $documents);

            $repair = $pdo->prepare(
                'INSERT INTO asset_repairs (asset_id, vendor_name, reference_number, sent_at, outcome, notes, created_at, updated_at)
                 VALUES (:asset_id, :vendor_name, :reference_number, NOW(), :outcome, :notes, NOW(), NOW())'
            );
            $repair->execute([
                'asset_id' => $assetId,
                'vendor_name' => $vendorName,
                'reference_number' => $referenceNumber,
                'outcome' => 'in_progress',
                'notes' => $notes,
            ]);

            $pdo->prepare('UPDATE asset_assignments SET returned_at = NOW() WHERE asset_id = :asset_id AND returned_at IS NULL')
                ->execute(['asset_id' => $assetId]);

            $pdo->prepare('UPDATE assets SET assigned_employee_id = NULL, status = :status, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => $assetId,
                    'status' => 'repair',
                ]);
            self::syncAssetInventoryStateWithPdo($pdo, $assetId);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function completeAssetRepair(int $assetId, array $input, array $files = []): void
    {
        $pdo = Database::connect();
        $asset = self::findAsset($assetId);
        $openRepair = self::openRepair($assetId);
        if (!$pdo instanceof PDO || $asset === null || $openRepair === null) {
            return;
        }

        $outcome = trim((string) ($input['outcome'] ?? 'repaired'));
        $returnStatus = $outcome === 'repaired'
            ? trim((string) ($input['return_status'] ?? 'storage'))
            : 'broken';
        $notes = trim((string) ($input['completion_notes'] ?? ''));
        $branchId = self::findBranchIdByName((string) $asset['location']);

        $pdo->beginTransaction();
        try {
            $movement = $pdo->prepare('INSERT INTO asset_movements (asset_id, from_branch_id, to_branch_id, user_id, notes, moved_at) VALUES (:asset_id, NULL, :to_branch_id, NULL, :notes, NOW())');
            $movement->execute([
                'asset_id' => $assetId,
                'to_branch_id' => $branchId,
                'notes' => '[REPAIR COMPLETE] Outcome: ' . $outcome . ' | Status: ' . $returnStatus . ($notes !== '' ? ' | ' . $notes : ''),
            ]);
            $movementId = (int) $pdo->lastInsertId();
            $documents = UploadStore::saveMovementDocuments($movementId, $files);
            self::insertMovementDocuments($pdo, $movementId, $documents);

            $repair = $pdo->prepare(
                'UPDATE asset_repairs
                 SET completed_at = NOW(),
                     outcome = :outcome,
                     return_status = :return_status,
                     completion_notes = :completion_notes,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $repair->execute([
                'id' => (int) $openRepair['id'],
                'outcome' => $outcome,
                'return_status' => $returnStatus,
                'completion_notes' => $notes,
            ]);

            $pdo->prepare('UPDATE assets SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => $assetId,
                    'status' => $returnStatus,
                ]);
            self::syncAssetInventoryStateWithPdo($pdo, $assetId);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function branches(): array
    {
        $pdo = Database::connect();
        $sql = <<<'SQL'
            SELECT branches.id, branches.name, branches.type, COALESCE(branches.address, '') AS address, COUNT(assets.id) AS assets
            FROM branches
            LEFT JOIN assets ON assets.branch_id = branches.id
            GROUP BY branches.id
            ORDER BY branches.id DESC
        SQL;
        return $pdo->query($sql)->fetchAll() ?: [];
    }

    public static function branchNames(): array
    {
        return array_column(self::branches(), 'name');
    }

    public static function findBranch(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id, name, type, COALESCE(address, \'\') AS address FROM branches WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE branch_id = :id');
        $count->execute(['id' => $id]);
        $row['assets'] = (int) $count->fetchColumn();
        return $row;
    }

    public static function branchDetail(int $id): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT branches.id,
                    branches.name,
                    branches.type,
                    COALESCE(branches.address, '') AS address,
                    COUNT(DISTINCT assets.id) AS assets,
                    COUNT(DISTINCT employees.id) AS employees,
                    COUNT(DISTINCT CASE WHEN assets.status = 'broken' THEN assets.id END) AS broken_assets,
                    COUNT(DISTINCT CASE WHEN assets.status = 'storage' THEN assets.id END) AS storage_assets
             FROM branches
             LEFT JOIN assets ON assets.branch_id = branches.id
             LEFT JOIN employees ON employees.branch_id = branches.id
             WHERE branches.id = :id
             GROUP BY branches.id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'type' => (string) $row['type'],
            'address' => (string) $row['address'],
            'assets' => (int) $row['assets'],
            'employees' => (int) $row['employees'],
            'broken_assets' => (int) $row['broken_assets'],
            'storage_assets' => (int) $row['storage_assets'],
        ];
    }

    public static function branchEmployees(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    name,
                    employee_code,
                    COALESCE(job_title, '') AS job_title,
                    COALESCE(company_email, '') AS company_email,
                    status
             FROM employees
             WHERE branch_id = :id
             ORDER BY name ASC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function branchAssets(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.status,
                    COALESCE(asset_categories.name, 'Uncategorized') AS category,
                    COALESCE(employees.name, 'Unassigned') AS assigned_to,
                    COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date
             FROM assets
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             LEFT JOIN employees ON employees.id = assets.assigned_employee_id
             WHERE assets.branch_id = :id
             ORDER BY assets.id DESC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function branchCategoryBreakdown(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT COALESCE(asset_categories.name, 'Uncategorized') AS category, COUNT(*) AS total
             FROM assets
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             WHERE assets.branch_id = :id
             GROUP BY COALESCE(asset_categories.name, 'Uncategorized')
             ORDER BY total DESC, category ASC"
        );
        $statement->execute(['id' => $id]);
        return array_map(static fn (array $row): array => [
            'category' => (string) $row['category'],
            'total' => (int) $row['total'],
        ], $statement->fetchAll() ?: []);
    }

    public static function createBranch(array $input): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO branches (name, type, address, created_at, updated_at) VALUES (:name, :type, :address, NOW(), NOW())');
        $statement->execute([
            'name' => trim((string) ($input['name'] ?? '')),
            'type' => trim((string) ($input['type'] ?? 'Branch')),
            'address' => trim((string) ($input['address'] ?? '')),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateBranch(int $id, array $input): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE branches SET name = :name, type = :type, address = :address, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'name' => trim((string) ($input['name'] ?? '')),
            'type' => trim((string) ($input['type'] ?? 'Branch')),
            'address' => trim((string) ($input['address'] ?? '')),
        ]);
    }

    public static function deleteBranch(int $id): void
    {
        $pdo = Database::connect();
        $pdo->prepare('UPDATE assets SET branch_id = NULL WHERE branch_id = :id')->execute(['id' => $id]);
        $pdo->prepare('UPDATE employees SET branch_id = NULL WHERE branch_id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM branches WHERE id = :id')->execute(['id' => $id]);
    }

    public static function categories(): array
    {
        $pdo = Database::connect();
        $sql = <<<'SQL'
            SELECT asset_categories.id, asset_categories.name, COALESCE(asset_categories.description, '') AS description, COUNT(assets.id) AS count
            FROM asset_categories
            LEFT JOIN assets ON assets.category_id = asset_categories.id
            GROUP BY asset_categories.id
            ORDER BY asset_categories.id DESC
        SQL;
        return $pdo->query($sql)->fetchAll() ?: [];
    }

    public static function categoryNames(): array
    {
        return array_column(self::categories(), 'name');
    }

    public static function findCategory(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id, name, COALESCE(description, \'\') AS description FROM asset_categories WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE category_id = :id');
        $count->execute(['id' => $id]);
        $row['count'] = (int) $count->fetchColumn();
        return $row;
    }

    public static function categoryAssets(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.status,
                    COALESCE(branches.name, 'Unassigned') AS branch,
                    COALESCE(employees.name, 'Unassigned') AS assigned_to,
                    COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date
             FROM assets
             LEFT JOIN branches ON branches.id = assets.branch_id
             LEFT JOIN employees ON employees.id = assets.assigned_employee_id
             WHERE assets.category_id = :id
             ORDER BY assets.id DESC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function createCategory(array $input): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO asset_categories (name, description, created_at, updated_at) VALUES (:name, :description, NOW(), NOW())');
        $statement->execute([
            'name' => trim((string) ($input['name'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateCategory(int $id, array $input): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE asset_categories SET name = :name, description = :description, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'name' => trim((string) ($input['name'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
        ]);
    }

    public static function deleteCategory(int $id): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM asset_categories WHERE id = :id')->execute(['id' => $id]);
    }

    public static function employees(): array
    {
        $pdo = Database::connect();
        $sql = <<<'SQL'
            SELECT employees.id,
                   employees.name,
                   employees.employee_code,
                   COALESCE(employees.company_name, '') AS company_name,
                   COALESCE(employees.project_name, '') AS project_name,
                   COALESCE(employees.company_email, '') AS company_email,
                   COALESCE(employees.fingerprint_id, '') AS fingerprint_id,
                   COALESCE(employees.department, '') AS department,
                   COALESCE(employees.job_title, '') AS job_title,
                   COALESCE(employees.phone, '') AS phone,
                   COALESCE(employees.appointment_order_name, '') AS appointment_order_name,
                   COALESCE(employees.appointment_order_path, '') AS appointment_order_path,
                   employees.status,
                   employees.branch_id,
                   COALESCE(branches.name, '') AS branch_name
            FROM employees
            LEFT JOIN branches ON branches.id = employees.branch_id
            ORDER BY employees.id DESC
        SQL;
        return $pdo->query($sql)->fetchAll() ?: [];
    }

    public static function activeEmployees(): array
    {
        return array_values(array_filter(self::employees(), static fn (array $employee): bool => $employee['status'] === 'active'));
    }

    public static function activeEmployeesByBranch(?int $branchId): array
    {
        return array_values(array_filter(
            self::activeEmployees(),
            static fn (array $employee): bool => $branchId !== null && (int) ($employee['branch_id'] ?? 0) === $branchId
        ));
    }

    public static function findEmployee(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            "SELECT employees.id,
                    employees.name,
                    employees.employee_code,
                    COALESCE(employees.company_name, '') AS company_name,
                    COALESCE(employees.project_name, '') AS project_name,
                    COALESCE(employees.company_email, '') AS company_email,
                    COALESCE(employees.fingerprint_id, '') AS fingerprint_id,
                    COALESCE(employees.department, '') AS department,
                    COALESCE(employees.job_title, '') AS job_title,
                    COALESCE(employees.phone, '') AS phone,
                    COALESCE(employees.appointment_order_name, '') AS appointment_order_name,
                    COALESCE(employees.appointment_order_path, '') AS appointment_order_path,
                    employees.status,
                    employees.branch_id,
                    COALESCE(branches.name, '') AS branch_name
             FROM employees
             LEFT JOIN branches ON branches.id = employees.branch_id
             WHERE employees.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function employeeAssetAssignments(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.status,
                    COALESCE(asset_categories.name, 'Uncategorized') AS category,
                    COALESCE(branches.name, 'Unassigned') AS branch_name,
                    DATE_FORMAT(asset_assignments.assigned_at, '%Y-%m-%d') AS assigned_at,
                    COALESCE(DATE_FORMAT(asset_assignments.returned_at, '%Y-%m-%d'), '') AS returned_at
             FROM asset_assignments
             JOIN assets ON assets.id = asset_assignments.asset_id
             LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
             LEFT JOIN branches ON branches.id = assets.branch_id
             WHERE asset_assignments.employee_id = :id
             ORDER BY asset_assignments.id DESC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function employeeLicenseAssignments(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    product_name,
                    COALESCE(vendor_name, '') AS vendor_name,
                    license_type,
                    status,
                    seats_total,
                    seats_used,
                    COALESCE(DATE_FORMAT(expiry_date, '%Y-%m-%d'), '') AS expiry_date
             FROM licenses
             WHERE assigned_employee_id = :id
             ORDER BY id DESC"
        );
        $statement->execute(['id' => $id]);
        return array_map(static function (array $row): array {
            $row['seats_total'] = (int) $row['seats_total'];
            $row['seats_used'] = (int) $row['seats_used'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function employeeHandovers(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT asset_handovers.id,
                    assets.id AS asset_id,
                    assets.name AS asset_name,
                    assets.tag,
                    asset_handovers.handover_type,
                    DATE_FORMAT(asset_handovers.handover_date, '%Y-%m-%d') AS handover_date,
                    COALESCE(asset_handovers.notes, '') AS notes
             FROM asset_handovers
             JOIN assets ON assets.id = asset_handovers.asset_id
             WHERE asset_handovers.employee_id = :id
             ORDER BY asset_handovers.id DESC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function employeeOffboardingSummary(int $id): array
    {
        $assignments = self::employeeAssetAssignments($id);
        $licenses = self::employeeLicenseAssignments($id);

        $activeAssets = array_values(array_filter($assignments, static fn (array $row): bool => (string) ($row['returned_at'] ?? '') === ''));
        $returnedAssets = array_values(array_filter($assignments, static fn (array $row): bool => (string) ($row['returned_at'] ?? '') !== ''));

        return [
            'active_assets' => $activeAssets,
            'returned_assets' => $returnedAssets,
            'active_licenses' => $licenses,
            'can_complete' => $activeAssets === [] && $licenses === [],
        ];
    }

    public static function employeeOffboardingHistory(int $id): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT employee_offboarding.id,
                    DATE_FORMAT(employee_offboarding.offboarded_at, '%Y-%m-%d') AS offboarded_at,
                    COALESCE(employee_offboarding.reason, '') AS reason,
                    COALESCE(employee_offboarding.notes, '') AS notes,
                    COALESCE(users.name, 'System') AS completed_by
             FROM employee_offboarding
             LEFT JOIN users ON users.id = employee_offboarding.completed_by
             WHERE employee_offboarding.employee_id = :id
             ORDER BY employee_offboarding.id DESC"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll() ?: [];
    }

    public static function completeEmployeeOffboarding(int $id, array $input): int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return 0;
        }

        $summary = self::employeeOffboardingSummary($id);
        if (!$summary['can_complete']) {
            throw new \RuntimeException('Employee still has assigned assets or licenses.');
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'INSERT INTO employee_offboarding (employee_id, reason, notes, offboarded_at, completed_by, created_at, updated_at)
                 VALUES (:employee_id, :reason, :notes, :offboarded_at, :completed_by, NOW(), NOW())'
            );
            $statement->execute([
                'employee_id' => $id,
                'reason' => trim((string) ($input['reason'] ?? '')),
                'notes' => trim((string) ($input['notes'] ?? '')),
                'offboarded_at' => self::nullableDate($input['offboarded_at'] ?? null) ?? date('Y-m-d'),
                'completed_by' => auth_user()['id'] ?? null,
            ]);

            $pdo->prepare('UPDATE employees SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => $id,
                    'status' => 'inactive',
                ]);

            $pdo->commit();
            return (int) $pdo->lastInsertId();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function createEmployee(array $input): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO employees (name, employee_code, company_name, project_name, company_email, fingerprint_id, department, job_title, phone, branch_id, appointment_order_name, appointment_order_path, status, created_at, updated_at)
             VALUES (:name, :employee_code, :company_name, :project_name, :company_email, :fingerprint_id, :department, :job_title, :phone, :branch_id, :appointment_order_name, :appointment_order_path, :status, NOW(), NOW())'
        );
        $statement->execute([
            'name' => trim((string) ($input['name'] ?? '')),
            'employee_code' => trim((string) ($input['employee_code'] ?? '')),
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'project_name' => trim((string) ($input['project_name'] ?? '')),
            'company_email' => self::normalizeCompanyEmail((string) ($input['company_email'] ?? '')),
            'fingerprint_id' => self::nullableString($input['fingerprint_id'] ?? null),
            'department' => self::branchNameById(($input['branch_id'] ?? '') === '' ? null : (int) $input['branch_id']) ?? '',
            'job_title' => trim((string) ($input['job_title'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'branch_id' => ($input['branch_id'] ?? '') === '' ? null : (int) $input['branch_id'],
            'appointment_order_name' => trim((string) ($input['appointment_order_name'] ?? '')),
            'appointment_order_path' => trim((string) ($input['appointment_order_path'] ?? '')),
            'status' => trim((string) ($input['status'] ?? 'active')),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateEmployee(int $id, array $input): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'UPDATE employees SET
                name = :name,
                employee_code = :employee_code,
                company_name = :company_name,
                project_name = :project_name,
                company_email = :company_email,
                fingerprint_id = :fingerprint_id,
                department = :department,
                job_title = :job_title,
                phone = :phone,
                branch_id = :branch_id,
                appointment_order_name = :appointment_order_name,
                appointment_order_path = :appointment_order_path,
                status = :status,
                updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => trim((string) ($input['name'] ?? '')),
            'employee_code' => trim((string) ($input['employee_code'] ?? '')),
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'project_name' => trim((string) ($input['project_name'] ?? '')),
            'company_email' => self::normalizeCompanyEmail((string) ($input['company_email'] ?? '')),
            'fingerprint_id' => self::nullableString($input['fingerprint_id'] ?? null),
            'department' => self::branchNameById(($input['branch_id'] ?? '') === '' ? null : (int) $input['branch_id']) ?? '',
            'job_title' => trim((string) ($input['job_title'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'branch_id' => ($input['branch_id'] ?? '') === '' ? null : (int) $input['branch_id'],
            'appointment_order_name' => trim((string) ($input['appointment_order_name'] ?? '')),
            'appointment_order_path' => trim((string) ($input['appointment_order_path'] ?? '')),
            'status' => trim((string) ($input['status'] ?? 'active')),
        ]);
    }

    public static function deleteEmployee(int $id): void
    {
        $pdo = Database::connect();
        $employee = self::findEmployee($id);
        if (is_array($employee)) {
            UploadStore::deleteFile((string) ($employee['appointment_order_path'] ?? ''));
        }
        $pdo->prepare('UPDATE assets SET assigned_employee_id = NULL WHERE assigned_employee_id = :id')->execute(['id' => $id]);
        $pdo->prepare('UPDATE asset_assignments SET returned_at = NOW() WHERE employee_id = :id AND returned_at IS NULL')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM employees WHERE id = :id')->execute(['id' => $id]);
    }

    public static function users(): array
    {
        $pdo = Database::connect();
        return $pdo->query("SELECT id, name, email, role, status, locale, theme, password FROM users ORDER BY id DESC")->fetchAll() ?: [];
    }

    public static function findUser(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id, name, email, role, status, locale, theme, password FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function findUserByEmail(string $email): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id, name, email, role, status, locale, theme, password FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public static function createUser(array $input): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO users (name, email, password, role, status, locale, theme, created_at, updated_at) VALUES (:name, :email, :password, :role, :status, :locale, :theme, NOW(), NOW())');
        $statement->execute([
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'password' => password_hash((string) ($input['password'] ?? 'password123'), PASSWORD_DEFAULT),
            'role' => trim((string) ($input['role'] ?? 'viewer')),
            'status' => trim((string) ($input['status'] ?? 'active')),
            'locale' => trim((string) ($input['locale'] ?? 'en')),
            'theme' => trim((string) ($input['theme'] ?? 'light')),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateUser(int $id, array $input): void
    {
        $pdo = Database::connect();
        $baseSql = 'UPDATE users SET name = :name, email = :email, role = :role, status = :status, locale = :locale, theme = :theme';
        $params = [
            'id' => $id,
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'role' => trim((string) ($input['role'] ?? 'viewer')),
            'status' => trim((string) ($input['status'] ?? 'active')),
            'locale' => trim((string) ($input['locale'] ?? 'en')),
            'theme' => trim((string) ($input['theme'] ?? 'light')),
        ];
        if (trim((string) ($input['password'] ?? '')) !== '') {
            $baseSql .= ', password = :password';
            $params['password'] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        }
        $baseSql .= ', updated_at = NOW() WHERE id = :id';
        $statement = $pdo->prepare($baseSql);
        $statement->execute($params);
    }

    public static function deleteUser(int $id): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }

    public static function systemSettings(): array
    {
        $defaults = [
            'app_name' => 'Alnahala AMS',
            'company_name' => 'Alnahala',
            'support_email' => 'it@alnahala.com',
            'default_locale' => 'en',
            'default_theme' => 'light',
            'ldap_enabled' => '0',
            'ldap_host' => '',
            'ldap_port' => '389',
            'ldap_base_dn' => '',
            'ldap_bind_dn' => '',
            'ldap_bind_password' => '',
            'ldap_user_filter' => '(mail={username})',
            'sso_enabled' => '0',
            'sso_provider' => 'microsoft',
            'sso_tenant_id' => '',
            'sso_client_id' => '',
            'sso_client_secret' => '',
            'sso_redirect_uri' => app_url('index.php?route=/login'),
            'ssl_enabled' => '0',
            'ssl_force_https' => '0',
            'ssl_certificate_path' => '',
            'ssl_private_key_path' => '',
            'ssl_chain_path' => '',
            'backup_retention_days' => '14',
            'backup_include_uploads' => '1',
        ];

        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return $defaults;
        }

        try {
            $rows = $pdo->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll() ?: [];
        } catch (\Throwable) {
            return $defaults;
        }

        $values = $defaults;
        foreach ($rows as $row) {
            $values[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $values;
    }

    public static function saveSystemSettings(array $input): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $current = self::systemSettings();
        $values = array_intersect_key($input, $current);

        $statement = $pdo->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, updated_at)
             VALUES (:setting_key, :setting_value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($values as $key => $value) {
            $statement->execute([
                'setting_key' => (string) $key,
                'setting_value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
            ]);
        }
    }

    public static function spareParts(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        try {
            $rows = $pdo->query(
                "SELECT spare_parts.id,
                        spare_parts.name,
                        COALESCE(spare_parts.part_number, '') AS part_number,
                        COALESCE(spare_parts.category, '') AS category,
                        COALESCE(spare_parts.vendor_name, '') AS vendor_name,
                        COALESCE(spare_parts.location, '') AS location,
                        spare_parts.quantity,
                        spare_parts.min_quantity,
                        COALESCE(spare_parts.compatible_with, '') AS compatible_with,
                        COALESCE(spare_parts.notes, '') AS notes
                 FROM spare_parts
                 ORDER BY spare_parts.id DESC"
            )->fetchAll() ?: [];
        } catch (\Throwable) {
            return [];
        }

        return array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            $row['min_quantity'] = (int) $row['min_quantity'];
            $row['low_stock'] = $row['quantity'] <= $row['min_quantity'];
            return $row;
        }, $rows);
    }

    public static function sparePartsSummary(): array
    {
        $parts = self::spareParts();
        return [
            'total_items' => count($parts),
            'total_quantity' => array_sum(array_map(static fn (array $part): int => (int) $part['quantity'], $parts)),
            'low_stock' => count(array_filter($parts, static fn (array $part): bool => (bool) ($part['low_stock'] ?? false))),
        ];
    }

    public static function findSparePart(int $id): ?array
    {
        foreach (self::spareParts() as $part) {
            if ((int) ($part['id'] ?? 0) === $id) {
                return $part;
            }
        }
        return null;
    }

    public static function createSparePart(array $input): int
    {
        $pdo = Database::connect();
        $params = self::mapSparePartParams($input);
        $existingId = self::sparePartIdByNumberOrName($params['part_number'], $params['name']);
        if ($existingId !== null) {
            $statement = $pdo->prepare(
                'UPDATE spare_parts
                 SET category = :category,
                     vendor_name = :vendor_name,
                     location = :location,
                     quantity = quantity + :quantity,
                     min_quantity = CASE WHEN :min_quantity > 0 THEN :min_quantity ELSE min_quantity END,
                     compatible_with = :compatible_with,
                     notes = CASE WHEN :notes = \'\' THEN notes ELSE :notes END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute($params + ['id' => $existingId]);
            return $existingId;
        }

        $statement = $pdo->prepare(
            'INSERT INTO spare_parts (name, part_number, category, vendor_name, location, quantity, min_quantity, compatible_with, notes, created_at, updated_at)
             VALUES (:name, :part_number, :category, :vendor_name, :location, :quantity, :min_quantity, :compatible_with, :notes, NOW(), NOW())'
        );
        $statement->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public static function updateSparePart(int $id, array $input): void
    {
        $pdo = Database::connect();
        $params = self::mapSparePartParams($input);
        $params['id'] = $id;
        $statement = $pdo->prepare(
            'UPDATE spare_parts
             SET name = :name,
                 part_number = :part_number,
                 category = :category,
                 vendor_name = :vendor_name,
                 location = :location,
                 quantity = :quantity,
                 min_quantity = :min_quantity,
                 compatible_with = :compatible_with,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute($params);
    }

    public static function deleteSparePart(int $id): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM spare_parts WHERE id = :id')->execute(['id' => $id]);
    }

    public static function bulkUpdateAssets(array $assetIds, string $action, array $input = []): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || $assetIds === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $assetIds = array_values(array_unique(array_filter(array_map('intval', $assetIds), static fn (int $id): bool => $id > 0)));
        if ($assetIds === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $updated = 0;
        $skipped = 0;
        $status = trim((string) ($input['bulk_status'] ?? ''));
        $branchId = ($input['bulk_branch_id'] ?? '') === '' ? null : (int) $input['bulk_branch_id'];

        foreach ($assetIds as $assetId) {
            $asset = self::findAsset($assetId);
            if ($asset === null || ($asset['status'] ?? '') === 'archived') {
                $skipped++;
                continue;
            }

            if ($action === 'set_status' && $status !== '' && in_array($status, ['active', 'repair', 'broken', 'storage'], true)) {
                $pdo->prepare('UPDATE assets SET status = :status, updated_at = NOW() WHERE id = :id')
                    ->execute(['id' => $assetId, 'status' => $status]);
                $updated++;
                continue;
            }

            if ($action === 'move_branch' && $branchId !== null) {
                $pdo->prepare('UPDATE assets SET branch_id = :branch_id, updated_at = NOW() WHERE id = :id')
                    ->execute(['id' => $assetId, 'branch_id' => $branchId]);
                $updated++;
                continue;
            }

            $skipped++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    public static function licenses(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT licenses.id,
                   licenses.product_name,
                   COALESCE(licenses.vendor_name, '') AS vendor_name,
                   licenses.license_type,
                   COALESCE(licenses.license_key, '') AS license_key,
                   licenses.seats_total,
                   licenses.seats_used,
                   (licenses.seats_total - licenses.seats_used) AS available_seats,
                   COALESCE(DATE_FORMAT(licenses.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                   COALESCE(DATE_FORMAT(licenses.expiry_date, '%Y-%m-%d'), '') AS expiry_date,
                   licenses.status,
                   COALESCE(assets.name, '') AS asset_name,
                   COALESCE(employees.name, '') AS employee_name,
                   COALESCE(licenses.notes, '') AS notes,
                   CASE
                       WHEN licenses.expiry_date IS NULL THEN NULL
                       ELSE DATEDIFF(licenses.expiry_date, CURDATE())
                   END AS days_left
            FROM licenses
            LEFT JOIN assets ON assets.id = licenses.assigned_asset_id
            LEFT JOIN employees ON employees.id = licenses.assigned_employee_id
            ORDER BY licenses.id DESC
        SQL;

        return array_map(static function (array $row): array {
            $row['seats_total'] = (int) $row['seats_total'];
            $row['seats_used'] = (int) $row['seats_used'];
            $row['available_seats'] = (int) $row['available_seats'];
            $row['days_left'] = $row['days_left'] === null ? null : (int) $row['days_left'];
            return $row;
        }, $pdo->query($sql)->fetchAll() ?: []);
    }

    public static function licenseSummary(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'total' => 0,
                'active' => 0,
                'expiring' => 0,
                'expired' => 0,
                'overused' => 0,
            ];
        }

        $row = $pdo->query(<<<'SQL'
            SELECT COUNT(*) AS total,
                   SUM(status = 'active') AS active,
                   SUM(expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring,
                   SUM(expiry_date IS NOT NULL AND expiry_date < CURDATE()) AS expired,
                   SUM(seats_used > seats_total) AS overused
            FROM licenses
        SQL)->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'expiring' => (int) ($row['expiring'] ?? 0),
            'expired' => (int) ($row['expired'] ?? 0),
            'overused' => (int) ($row['overused'] ?? 0),
        ];
    }

    public static function findLicense(int $id): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT id,
                    product_name,
                    COALESCE(vendor_name, '') AS vendor_name,
                    license_type,
                    COALESCE(license_key, '') AS license_key,
                    seats_total,
                    seats_used,
                    COALESCE(DATE_FORMAT(purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                    COALESCE(DATE_FORMAT(expiry_date, '%Y-%m-%d'), '') AS expiry_date,
                    status,
                    assigned_asset_id,
                    assigned_employee_id,
                    COALESCE(notes, '') AS notes
             FROM licenses
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        $row['seats_total'] = (int) $row['seats_total'];
        $row['seats_used'] = (int) $row['seats_used'];
        return $row;
    }

    public static function licenseDetail(int $id): ?array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT licenses.id,
                    licenses.product_name,
                    COALESCE(licenses.vendor_name, '') AS vendor_name,
                    licenses.license_type,
                    COALESCE(licenses.license_key, '') AS license_key,
                    licenses.seats_total,
                    licenses.seats_used,
                    (licenses.seats_total - licenses.seats_used) AS available_seats,
                    COALESCE(DATE_FORMAT(licenses.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                    COALESCE(DATE_FORMAT(licenses.expiry_date, '%Y-%m-%d'), '') AS expiry_date,
                    licenses.status,
                    licenses.assigned_asset_id,
                    licenses.assigned_employee_id,
                    COALESCE(assets.name, '') AS asset_name,
                    COALESCE(employees.name, '') AS employee_name,
                    COALESCE(licenses.notes, '') AS notes
             FROM licenses
             LEFT JOIN assets ON assets.id = licenses.assigned_asset_id
             LEFT JOIN employees ON employees.id = licenses.assigned_employee_id
             WHERE licenses.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        $row['seats_total'] = (int) $row['seats_total'];
        $row['seats_used'] = (int) $row['seats_used'];
        $row['available_seats'] = (int) $row['available_seats'];
        return $row;
    }

    public static function licenseAllocations(int $licenseId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT license_allocations.id,
                    license_allocations.request_id,
                    license_allocations.employee_id,
                    license_allocations.branch_id,
                    license_allocations.quantity,
                    COALESCE(license_allocations.notes, '') AS notes,
                    DATE_FORMAT(license_allocations.allocated_at, '%Y-%m-%d %H:%i') AS allocated_at,
                    COALESCE(employees.name, '') AS employee_name,
                    COALESCE(branches.name, '') AS branch_name,
                    COALESCE(asset_requests.request_no, '') AS request_no
             FROM license_allocations
             LEFT JOIN employees ON employees.id = license_allocations.employee_id
             LEFT JOIN branches ON branches.id = license_allocations.branch_id
             LEFT JOIN asset_requests ON asset_requests.id = license_allocations.request_id
             WHERE license_allocations.license_id = :license_id
             ORDER BY license_allocations.id DESC"
        );
        $statement->execute(['license_id' => $licenseId]);
        return array_map(static function (array $row): array {
            $row['quantity'] = (int) $row['quantity'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function licenseRenewals(int $licenseId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT license_renewals.id,
                    COALESCE(DATE_FORMAT(previous_expiry_date, '%Y-%m-%d'), '') AS previous_expiry_date,
                    COALESCE(DATE_FORMAT(new_expiry_date, '%Y-%m-%d'), '') AS new_expiry_date,
                    COALESCE(previous_license_key, '') AS previous_license_key,
                    COALESCE(new_license_key, '') AS new_license_key,
                    previous_seats_total,
                    new_seats_total,
                    COALESCE(renewal_cost, 0) AS renewal_cost,
                    COALESCE(notes, '') AS notes,
                    DATE_FORMAT(renewed_at, '%Y-%m-%d') AS renewed_at,
                    COALESCE(users.name, 'System') AS renewed_by
             FROM license_renewals
             LEFT JOIN users ON users.id = license_renewals.renewed_by
             WHERE license_id = :license_id
             ORDER BY renewed_at DESC, id DESC"
        );
        $statement->execute(['license_id' => $licenseId]);

        return array_map(static function (array $row): array {
            $row['previous_seats_total'] = (int) $row['previous_seats_total'];
            $row['new_seats_total'] = (int) $row['new_seats_total'];
            $row['renewal_cost'] = (float) $row['renewal_cost'];
            return $row;
        }, $statement->fetchAll() ?: []);
    }

    public static function renewLicense(int $id, array $input): void
    {
        $pdo = Database::connect();
        $license = self::findLicense($id);
        if (!$pdo instanceof PDO || $license === null) {
            return;
        }

        $newExpiryDate = self::nullableDate($input['new_expiry_date'] ?? null);
        $newLicenseKey = trim((string) ($input['new_license_key'] ?? ''));
        $newSeatsTotal = max(1, (int) ($input['new_seats_total'] ?? $license['seats_total']));
        $renewedAt = self::nullableDate($input['renewed_at'] ?? null) ?? date('Y-m-d');
        $renewalCost = trim((string) ($input['renewal_cost'] ?? '')) === '' ? 0 : (float) $input['renewal_cost'];
        $notes = trim((string) ($input['renewal_notes'] ?? ''));

        $pdo->beginTransaction();
        try {
            $history = $pdo->prepare(
                'INSERT INTO license_renewals
                 (license_id, previous_expiry_date, new_expiry_date, previous_license_key, new_license_key, previous_seats_total, new_seats_total, renewal_cost, notes, renewed_at, renewed_by, created_at, updated_at)
                 VALUES
                 (:license_id, :previous_expiry_date, :new_expiry_date, :previous_license_key, :new_license_key, :previous_seats_total, :new_seats_total, :renewal_cost, :notes, :renewed_at, :renewed_by, NOW(), NOW())'
            );
            $history->execute([
                'license_id' => $id,
                'previous_expiry_date' => self::nullableDate($license['expiry_date'] ?? null),
                'new_expiry_date' => $newExpiryDate,
                'previous_license_key' => (string) ($license['license_key'] ?? ''),
                'new_license_key' => $newLicenseKey !== '' ? $newLicenseKey : (string) ($license['license_key'] ?? ''),
                'previous_seats_total' => (int) ($license['seats_total'] ?? 1),
                'new_seats_total' => $newSeatsTotal,
                'renewal_cost' => $renewalCost,
                'notes' => $notes,
                'renewed_at' => $renewedAt,
                'renewed_by' => auth_user()['id'] ?? null,
            ]);

            $status = 'active';
            if ($newExpiryDate !== null && $newExpiryDate < date('Y-m-d')) {
                $status = 'expired';
            }

            $update = $pdo->prepare(
                'UPDATE licenses
                 SET expiry_date = :expiry_date,
                     license_key = :license_key,
                     seats_total = :seats_total,
                     status = :status,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $id,
                'expiry_date' => $newExpiryDate,
                'license_key' => $newLicenseKey !== '' ? $newLicenseKey : (string) ($license['license_key'] ?? ''),
                'seats_total' => $newSeatsTotal,
                'status' => $status,
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function createLicense(array $input): int
    {
        $pdo = Database::connect();
        $params = self::mapLicenseParams($input);
        $existingId = self::existingLicenseIdForIntake($params);
        if ($existingId !== null) {
            $statement = $pdo->prepare(
                'UPDATE licenses
                 SET vendor_name = :vendor_name,
                     license_type = :license_type,
                     license_key = CASE WHEN :license_key = \'\' THEN license_key ELSE :license_key END,
                     seats_total = seats_total + :seats_total,
                     purchase_date = COALESCE(:purchase_date, purchase_date),
                     expiry_date = COALESCE(:expiry_date, expiry_date),
                     status = :status,
                     notes = CASE WHEN :notes = \'\' THEN notes ELSE :notes END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute($params + ['id' => $existingId]);
            return $existingId;
        }

        $statement = $pdo->prepare(
            'INSERT INTO licenses (product_name, vendor_name, license_type, license_key, seats_total, seats_used, purchase_date, expiry_date, status, assigned_asset_id, assigned_employee_id, notes, created_at, updated_at)
             VALUES (:product_name, :vendor_name, :license_type, :license_key, :seats_total, :seats_used, :purchase_date, :expiry_date, :status, :assigned_asset_id, :assigned_employee_id, :notes, NOW(), NOW())'
        );
        $statement->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public static function assetIdBySerial(string $serialNumber): ?int
    {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return null;
        }

        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare('SELECT id FROM assets WHERE serial_number = :serial_number LIMIT 1');
        $statement->execute(['serial_number' => $serialNumber]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    public static function updateLicense(int $id, array $input): void
    {
        $pdo = Database::connect();
        $params = self::mapLicenseParams($input);
        $params['id'] = $id;
        $statement = $pdo->prepare(
            'UPDATE licenses
             SET product_name = :product_name,
                 vendor_name = :vendor_name,
                 license_type = :license_type,
                 license_key = :license_key,
                 seats_total = :seats_total,
                 seats_used = :seats_used,
                 purchase_date = :purchase_date,
                 expiry_date = :expiry_date,
                 status = :status,
                 assigned_asset_id = :assigned_asset_id,
                 assigned_employee_id = :assigned_employee_id,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute($params);
    }

    public static function deleteLicense(int $id): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM licenses WHERE id = :id')->execute(['id' => $id]);
    }

    public static function storageItems(): array
    {
        return self::storageOverview()['storage_stock'];
    }

    public static function storageOverview(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'storage_stock' => [],
                'spare_parts_stock' => [],
                'license_stock' => [],
                'broken_assets' => [],
                'repair_queue' => [],
                'received_assets' => [],
                'summary' => [
                    'storage_count' => 0,
                    'storage_groups' => 0,
                    'spare_parts_count' => 0,
                    'spare_parts_quantity' => 0,
                    'license_count' => 0,
                    'license_available_seats' => 0,
                    'broken_count' => 0,
                    'repair_count' => 0,
                    'received_count' => 0,
                ],
            ];
        }

        $storageStock = $pdo->query(<<<'SQL'
            SELECT MIN(assets.id) AS sample_asset_id,
                   asset_stock_groups.id AS stock_group_id,
                   COALESCE(NULLIF(asset_stock_groups.display_name, ''), assets.name) AS item,
                   COUNT(*) AS qty,
                   assets.status,
                   asset_categories.id AS category_id,
                   COALESCE(asset_categories.name, 'Uncategorized') AS category,
                   branches.id AS branch_id,
                   COALESCE(branches.name, 'Unassigned') AS branch,
                   SUBSTRING_INDEX(
                     GROUP_CONCAT(COALESCE(NULLIF(assets.barcode, ''), assets.tag) ORDER BY assets.id ASC SEPARATOR ' | '),
                     ' | ',
                     3
                   ) AS barcode_preview
            FROM assets
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
            LEFT JOIN asset_stock_groups ON asset_stock_groups.id = assets.stock_group_id
            LEFT JOIN branches ON branches.id = assets.branch_id
            WHERE assets.status = 'storage'
            GROUP BY asset_stock_groups.id, asset_stock_groups.display_name, assets.name, assets.status, asset_categories.id, asset_categories.name, branches.id, branches.name
            ORDER BY qty DESC, item ASC, branch ASC
        SQL)->fetchAll() ?: [];

        $sparePartsStock = $pdo->query(<<<'SQL'
            SELECT id,
                   name,
                   COALESCE(part_number, '') AS part_number,
                   COALESCE(category, '') AS category,
                   COALESCE(location, '') AS location,
                   quantity,
                   min_quantity
            FROM spare_parts
            WHERE quantity > 0
            ORDER BY quantity DESC, name ASC
        SQL)->fetchAll() ?: [];

        $licenseStock = $pdo->query(<<<'SQL'
            SELECT id,
                   product_name,
                   COALESCE(vendor_name, '') AS vendor_name,
                   license_type,
                   status,
                   seats_total,
                   seats_used,
                   (seats_total - seats_used) AS available_seats
            FROM licenses
            WHERE seats_total > 0
            ORDER BY available_seats DESC, product_name ASC
        SQL)->fetchAll() ?: [];

        $assetRows = $pdo->query(<<<'SQL'
            SELECT assets.id,
                   assets.name,
                   assets.tag,
                   assets.status,
                   branches.id AS branch_id,
                   COALESCE(branches.name, 'Unassigned') AS branch,
                   asset_categories.id AS category_id,
                   COALESCE(asset_categories.name, 'Uncategorized') AS category,
                   COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '-') AS purchase_date
            FROM assets
            LEFT JOIN branches ON branches.id = assets.branch_id
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id
            WHERE assets.status IN ('broken', 'repair')
               OR (
                    assets.procurement_stage = 'received'
                    AND assets.status NOT IN ('broken', 'repair', 'storage')
               )
            ORDER BY assets.updated_at DESC, assets.id DESC
        SQL)->fetchAll() ?: [];

        $brokenAssets = array_values(array_filter($assetRows, static fn (array $row): bool => (string) $row['status'] === 'broken'));
        $repairQueue = array_values(array_filter($assetRows, static fn (array $row): bool => (string) $row['status'] === 'repair'));
        $receivedAssets = array_values(array_filter($assetRows, static fn (array $row): bool => (string) ($row['status'] ?? '') !== 'broken' && (string) ($row['status'] ?? '') !== 'repair'));

        return [
            'storage_stock' => array_map(static fn (array $row): array => [
                'sample_asset_id' => (int) $row['sample_asset_id'],
                'stock_group_id' => $row['stock_group_id'] === null ? null : (int) $row['stock_group_id'],
                'item' => (string) $row['item'],
                'qty' => (int) $row['qty'],
                'status' => ucfirst((string) $row['status']),
                'category_id' => $row['category_id'] === null ? null : (int) $row['category_id'],
                'category' => (string) $row['category'],
                'branch_id' => $row['branch_id'] === null ? null : (int) $row['branch_id'],
                'branch' => (string) $row['branch'],
                'barcode_preview' => (string) ($row['barcode_preview'] ?? ''),
            ], $storageStock),
            'spare_parts_stock' => array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'part_number' => (string) $row['part_number'],
                    'category' => (string) $row['category'],
                    'location' => (string) $row['location'],
                    'quantity' => (int) $row['quantity'],
                    'min_quantity' => (int) $row['min_quantity'],
                    'low_stock' => (int) $row['quantity'] <= (int) $row['min_quantity'],
                ];
            }, $sparePartsStock),
            'license_stock' => array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'product_name' => (string) $row['product_name'],
                    'vendor_name' => (string) $row['vendor_name'],
                    'license_type' => (string) $row['license_type'],
                    'status' => (string) $row['status'],
                    'seats_total' => (int) $row['seats_total'],
                    'seats_used' => (int) $row['seats_used'],
                    'available_seats' => (int) $row['available_seats'],
                ];
            }, $licenseStock),
            'broken_assets' => $brokenAssets,
            'repair_queue' => $repairQueue,
            'received_assets' => $receivedAssets,
            'summary' => [
                'storage_count' => array_sum(array_map(static fn (array $row): int => (int) $row['qty'], $storageStock)),
                'storage_groups' => count($storageStock),
                'spare_parts_count' => count($sparePartsStock),
                'spare_parts_quantity' => array_sum(array_map(static fn (array $row): int => (int) $row['quantity'], $sparePartsStock)),
                'license_count' => count($licenseStock),
                'license_available_seats' => array_sum(array_map(static fn (array $row): int => (int) $row['available_seats'], $licenseStock)),
                'broken_count' => count($brokenAssets),
                'repair_count' => count($repairQueue),
                'received_count' => count($receivedAssets),
            ],
        ];
    }

    public static function reports(): array
    {
        $pdo = Database::connect();
        $rows = $pdo->query("SELECT type AS name, DATE_FORMAT(created_at, '%Y-%m-%d') AS updated_at, 'FILE' AS format FROM reports ORDER BY id DESC LIMIT 10")->fetchAll();
        return $rows ?: [];
    }

    public static function notificationsForCurrentUser(int $limit = 8): array
    {
        $pdo = Database::connect();
        $user = auth_user();
        if (!$pdo instanceof PDO || !is_array($user) || empty($user['id'])) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT id, type, data, read_at, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT :limit"
        );
        $statement->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll() ?: [];

        return array_map(static function (array $row): array {
            $payload = json_decode((string) ($row['data'] ?? '{}'), true);
            return [
                'id' => (int) $row['id'],
                'type' => (string) $row['type'],
                'data' => is_array($payload) ? $payload : [],
                'read_at' => $row['read_at'],
                'created_at' => (string) $row['created_at'],
            ];
        }, $rows);
    }

    public static function administrativeForms(): array
    {
        $definitions = self::baseAdministrativeFormDefinitions();
        $entries = self::administrativeFormEntries();

        $forms = [];
        foreach ($definitions as $id => $definition) {
            $override = $entries[$id] ?? null;
            $effective = is_array($override)
                ? array_merge($definition, array_diff_key($override, ['source' => true]) + ['source' => 'override'])
                : $definition;
            $hydrated = self::hydrateAdministrativeForm($id, $effective);
            if ($hydrated !== null) {
                $forms[] = $hydrated;
            }
        }

        foreach ($entries as $id => $definition) {
            if (isset($definitions[$id])) {
                continue;
            }
            $hydrated = self::hydrateAdministrativeForm((string) $id, $definition);
            if ($hydrated !== null) {
                $forms[] = $hydrated;
            }
        }

        usort($forms, static function (array $left, array $right): int {
            return strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
        });

        return $forms;
    }

    public static function findAdministrativeForm(string $id): ?array
    {
        foreach (self::administrativeForms() as $form) {
            if ((string) $form['id'] === $id) {
                return $form;
            }
        }

        return null;
    }

    public static function findAdministrativeFormFile(string $id, string $variant): ?array
    {
        $form = self::findAdministrativeForm($id);
        if ($form === null) {
            return null;
        }

        $file = $form['files'][$variant] ?? null;
        return is_array($file) ? $file : null;
    }

    public static function createAdministrativeForm(array $input, array $uploadedFiles): string
    {
        $id = 'custom-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $files = UploadStore::saveAdministrativeLibraryFiles($id, $uploadedFiles);
        if ($files === []) {
            throw new \RuntimeException('No files were uploaded.');
        }

        $entry = [
            'kind' => trim((string) ($input['kind'] ?? 'book')) === 'form' ? 'form' : 'book',
            'title' => trim((string) ($input['title'] ?? '')),
            'category' => trim((string) ($input['category'] ?? '')) !== '' ? trim((string) $input['category']) : __('administrative_forms.category_books', 'Administrative Books'),
            'description' => trim((string) ($input['description'] ?? '')),
            'related_route_name' => self::administrativeFormRelatedRouteName((string) ($input['related_route_name'] ?? '')),
            'files' => [],
            'source' => 'custom',
        ];

        foreach ($files as $index => $file) {
            $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $variant = $extension !== '' ? $extension : ('file' . $index);
            while (isset($entry['files'][$variant])) {
                $variant .= '_alt';
            }

            $entry['files'][$variant] = [
                'path' => (string) ($file['path'] ?? ''),
                'download_name' => (string) ($file['name'] ?? basename((string) ($file['path'] ?? 'document'))),
            ];
        }

        $entries = self::administrativeFormEntries();
        $entries[$id] = $entry;
        self::saveAdministrativeFormEntries($entries);

        return $id;
    }

    public static function updateAdministrativeForm(string $id, array $input, array $uploadedFiles, array $removeVariants = []): void
    {
        $entries = self::administrativeFormEntries();
        $current = self::findAdministrativeForm($id);
        if ($current === null) {
            throw new \RuntimeException('Administrative form not found or is not editable.');
        }

        $baseDefinitions = self::baseAdministrativeFormDefinitions();
        $isBuiltin = isset($baseDefinitions[$id]);
        $entry = $entries[$id] ?? [];
        $files = self::toStorableAdministrativeFiles((array) ($current['files'] ?? []));
        $removeVariants = array_values(array_filter(array_map('strval', $removeVariants), static fn (string $value): bool => $value !== ''));

        foreach ($removeVariants as $variant) {
            if (!isset($files[$variant])) {
                continue;
            }

            if (self::isCustomAdministrativeStoragePath((string) ($files[$variant]['path'] ?? ''))) {
                UploadStore::deleteFile((string) ($files[$variant]['path'] ?? ''));
            }
            unset($files[$variant]);
        }

        $newFiles = UploadStore::saveAdministrativeLibraryFiles($id, $uploadedFiles);
        foreach ($newFiles as $index => $file) {
            $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $baseVariant = $extension !== '' ? $extension : ('file' . $index);
            $variant = $baseVariant;

            if (isset($files[$variant])) {
                if (self::isCustomAdministrativeStoragePath((string) ($files[$variant]['path'] ?? ''))) {
                    UploadStore::deleteFile((string) ($files[$variant]['path'] ?? ''));
                }
            } else {
                while (isset($files[$variant])) {
                    $variant .= '_alt';
                }
            }

            $files[$variant] = [
                'path' => (string) ($file['path'] ?? ''),
                'download_name' => (string) ($file['name'] ?? basename((string) ($file['path'] ?? 'document'))),
            ];
        }

        if ($files === []) {
            throw new \RuntimeException('Administrative form must keep at least one file.');
        }

        $entry['kind'] = trim((string) ($input['kind'] ?? 'book')) === 'form' ? 'form' : 'book';
        $entry['title'] = trim((string) ($input['title'] ?? ''));
        $entry['category'] = trim((string) ($input['category'] ?? '')) !== '' ? trim((string) $input['category']) : __('administrative_forms.category_books', 'Administrative Books');
        $entry['description'] = trim((string) ($input['description'] ?? ''));
        $entry['related_route_name'] = self::administrativeFormRelatedRouteName((string) ($input['related_route_name'] ?? ''));
        $entry['files'] = $files;
        $entry['source'] = $isBuiltin ? 'override' : 'custom';

        $entries[$id] = $entry;
        self::saveAdministrativeFormEntries($entries);
    }

    public static function deleteAdministrativeForm(string $id): void
    {
        $entries = self::administrativeFormEntries();
        $entry = $entries[$id] ?? null;
        $baseDefinitions = self::baseAdministrativeFormDefinitions();
        $isBuiltin = isset($baseDefinitions[$id]);
        if (!is_array($entry) && !$isBuiltin) {
            throw new \RuntimeException('Administrative form not found or is not deletable.');
        }

        if (is_array($entry)) {
            self::deleteAdministrativeFiles((array) ($entry['files'] ?? []));
        }

        $directory = base_path('storage/app/administrative-forms/custom/' . $id);
        if (is_dir($directory)) {
            foreach (glob($directory . '/*') ?: [] as $filePath) {
                @unlink($filePath);
            }
            @rmdir($directory);
        }

        unset($entries[$id]);
        self::saveAdministrativeFormEntries($entries);
    }

    public static function administrativeFormRouteOptions(): array
    {
        return [
            'dashboard' => __('nav.dashboard', 'Dashboard'),
            'requests.index' => __('nav.requests', 'Requests'),
            'employees.index' => __('nav.employees', 'Employees'),
            'assets.index' => __('nav.assets', 'Assets'),
            'settings' => __('nav.settings', 'Settings'),
        ];
    }

    public static function unreadNotificationsCount(): int
    {
        $pdo = Database::connect();
        $user = auth_user();
        if (!$pdo instanceof PDO || !is_array($user) || empty($user['id'])) {
            return 0;
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['user_id' => (int) $user['id']]);
        return (int) $statement->fetchColumn();
    }

    public static function markAllNotificationsRead(): void
    {
        $pdo = Database::connect();
        $user = auth_user();
        if (!$pdo instanceof PDO || !is_array($user) || empty($user['id'])) {
            return;
        }

        $statement = $pdo->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['user_id' => (int) $user['id']]);
    }

    public static function refreshSystemNotifications(): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $userIds = self::activeUserIds();
        if ($userIds === []) {
            return;
        }

        $warrantyRows = $pdo->query(
            "SELECT id, name, tag, DATE_FORMAT(warranty_expiry, '%Y-%m-%d') AS warranty_expiry, DATEDIFF(warranty_expiry, CURDATE()) AS days_left
             FROM assets
             WHERE status <> 'archived'
               AND warranty_expiry IS NOT NULL
               AND warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchAll() ?: [];
        foreach ($warrantyRows as $row) {
            $uniqueKey = 'warranty:' . (int) $row['id'] . ':' . (string) $row['warranty_expiry'];
            self::createNotificationOnceForUsers('warranty_expiring', $uniqueKey, [
                'title' => __('notifications.warranty_title', 'Warranty expiring soon'),
                'message' => (string) $row['name'] . ' (' . (string) $row['tag'] . ') - ' . (int) $row['days_left'] . ' ' . __('notifications.days_left', 'days left'),
                'route' => route('assets.show', ['id' => (int) $row['id']]),
                'asset_id' => (int) $row['id'],
            ], $userIds);
        }

        $brokenRows = $pdo->query(
            "SELECT id, name, tag
             FROM assets
             WHERE status = 'broken'
               AND archived_at IS NULL"
        )->fetchAll() ?: [];
        foreach ($brokenRows as $row) {
            $uniqueKey = 'broken:' . (int) $row['id'];
            self::createNotificationOnceForUsers('asset_broken', $uniqueKey, [
                'title' => __('notifications.broken_title', 'Broken asset requires action'),
                'message' => (string) $row['name'] . ' (' . (string) $row['tag'] . ')',
                'route' => route('assets.show', ['id' => (int) $row['id']]),
                'asset_id' => (int) $row['id'],
            ], $userIds);
        }

        $repairRows = $pdo->query(
            "SELECT asset_repairs.id, asset_repairs.asset_id, asset_repairs.vendor_name, assets.name, assets.tag
             FROM asset_repairs
             JOIN assets ON assets.id = asset_repairs.asset_id
             WHERE asset_repairs.outcome = 'in_progress'"
        )->fetchAll() ?: [];
        foreach ($repairRows as $row) {
            $uniqueKey = 'repair:' . (int) $row['id'];
            self::createNotificationOnceForUsers('repair_open', $uniqueKey, [
                'title' => __('notifications.repair_title', 'Open repair in progress'),
                'message' => (string) $row['name'] . ' (' . (string) $row['tag'] . ') - ' . (string) $row['vendor_name'],
                'route' => route('assets.repair', ['id' => (int) $row['asset_id']]),
                'asset_id' => (int) $row['asset_id'],
            ], $userIds);
        }

        $licenseRows = $pdo->query(
            "SELECT id, product_name, expiry_date, DATEDIFF(expiry_date, CURDATE()) AS days_left
             FROM licenses
             WHERE status = 'active'
               AND expiry_date IS NOT NULL
               AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchAll() ?: [];
        foreach ($licenseRows as $row) {
            $uniqueKey = 'license-expiry:' . (int) $row['id'] . ':' . (string) $row['expiry_date'];
            self::createNotificationOnceForUsers('license_expiring', $uniqueKey, [
                'title' => __('notifications.license_title', 'License expiring soon'),
                'message' => (string) $row['product_name'] . ' - ' . (int) $row['days_left'] . ' ' . __('notifications.days_left', 'days left'),
                'route' => route('licenses.index'),
            ], $userIds);
        }

        $expiredLicenseRows = $pdo->query(
            "SELECT id, product_name, expiry_date
             FROM licenses
             WHERE expiry_date IS NOT NULL
               AND expiry_date < CURDATE()"
        )->fetchAll() ?: [];
        foreach ($expiredLicenseRows as $row) {
            $uniqueKey = 'license-expired:' . (int) $row['id'] . ':' . (string) $row['expiry_date'];
            self::createNotificationOnceForUsers('license_expired', $uniqueKey, [
                'title' => __('notifications.license_expired_title', 'License expired'),
                'message' => (string) $row['product_name'] . ' - ' . (string) $row['expiry_date'],
                'route' => route('licenses.index'),
            ], $userIds);
        }

        $overusedLicenseRows = $pdo->query(
            "SELECT id, product_name, seats_total, seats_used
             FROM licenses
             WHERE seats_used > seats_total"
        )->fetchAll() ?: [];
        foreach ($overusedLicenseRows as $row) {
            $uniqueKey = 'license-overused:' . (int) $row['id'] . ':' . (int) $row['seats_used'];
            self::createNotificationOnceForUsers('license_overused', $uniqueKey, [
                'title' => __('notifications.license_overused_title', 'License seats exceeded'),
                'message' => (string) $row['product_name'] . ' - ' . (int) $row['seats_used'] . '/' . (int) $row['seats_total'],
                'route' => route('licenses.index'),
            ], $userIds);
        }
    }

    public static function notifyAssetEvent(string $type, int $assetId, string $title, string $message, string $routeUrl): void
    {
        $userIds = self::activeUserIds();
        if ($userIds === []) {
            return;
        }

        $uniqueKey = $type . ':' . $assetId . ':' . date('YmdHis');
        self::createNotificationForUsers($type, [
            'unique_key' => $uniqueKey,
            'title' => $title,
            'message' => $message,
            'route' => $routeUrl,
            'asset_id' => $assetId,
        ], $userIds);
    }

    public static function reportSummary(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'assets' => 0,
                'employees' => 0,
                'licenses' => 0,
                'branches' => 0,
                'categories' => 0,
                'movements' => 0,
                'assets_with_docs' => 0,
            ];
        }

        $row = $pdo->query(<<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM assets) AS assets,
                (SELECT COUNT(*) FROM employees) AS employees,
                (SELECT COUNT(*) FROM licenses) AS licenses,
                (SELECT COUNT(*) FROM branches) AS branches,
                (SELECT COUNT(*) FROM asset_categories) AS categories,
                (SELECT COUNT(*) FROM asset_movements) AS movements,
                (SELECT COUNT(DISTINCT asset_id) FROM asset_documents) AS assets_with_docs
        SQL)->fetch() ?: [];

        return array_map(static fn ($value): int => (int) $value, $row);
    }

    public static function globalSearch(string $query, array $filters = []): array
    {
        $term = trim($query);
        $section = trim((string) ($filters['section'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $branchId = trim((string) ($filters['branch_id'] ?? ''));
        $categoryId = trim((string) ($filters['category_id'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $fromDate = trim((string) ($filters['from_date'] ?? ''));
        $toDate = trim((string) ($filters['to_date'] ?? ''));

        if ($term === '' && $section === '' && $status === '' && $branchId === '' && $categoryId === '' && $role === '' && $fromDate === '' && $toDate === '') {
            return [
                'assets' => [],
                'employees' => [],
                'licenses' => [],
                'branches' => [],
                'categories' => [],
                'users' => [],
                'movements' => [],
            ];
        }

        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'assets' => [],
                'employees' => [],
                'licenses' => [],
                'branches' => [],
                'categories' => [],
                'users' => [],
                'movements' => [],
            ];
        }

        $like = '%' . $term . '%';

        $assetsWhere = [];
        $assetsParams = [];
        if ($term !== '') {
            $assetsWhere[] = '(assets.name LIKE :term OR assets.tag LIKE :term OR COALESCE(assets.serial_number, \'\') LIKE :term OR COALESCE(assets.invoice_number, \'\') LIKE :term)';
            $assetsParams['term'] = $like;
        }
        if ($status !== '') {
            $assetsWhere[] = 'assets.status = :status';
            $assetsParams['status'] = $status;
        }
        if ($branchId !== '') {
            $assetsWhere[] = 'assets.branch_id = :branch_id';
            $assetsParams['branch_id'] = (int) $branchId;
        }
        if ($categoryId !== '') {
            $assetsWhere[] = 'assets.category_id = :category_id';
            $assetsParams['category_id'] = (int) $categoryId;
        }
        if ($fromDate !== '') {
            $assetsWhere[] = 'assets.purchase_date >= :assets_from_date';
            $assetsParams['assets_from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $assetsWhere[] = 'assets.purchase_date <= :assets_to_date';
            $assetsParams['assets_to_date'] = $toDate;
        }
        $assetsSql = 'SELECT assets.id, assets.name, assets.tag, assets.status, COALESCE(branches.name, \'Unassigned\') AS branch, COALESCE(asset_categories.name, \'Uncategorized\') AS category
            FROM assets
            LEFT JOIN branches ON branches.id = assets.branch_id
            LEFT JOIN asset_categories ON asset_categories.id = assets.category_id';
        if ($assetsWhere !== []) {
            $assetsSql .= ' WHERE ' . implode(' AND ', $assetsWhere);
        }
        $assetsSql .= ' ORDER BY assets.id DESC LIMIT 25';
        $assets = $pdo->prepare($assetsSql);
        $assets->execute($assetsParams);

        $employeesWhere = [];
        $employeesParams = [];
        if ($term !== '') {
            $employeesWhere[] = '(employees.name LIKE :term OR employees.employee_code LIKE :term OR COALESCE(employees.department, \'\') LIKE :term)';
            $employeesParams['term'] = $like;
        }
        if ($status !== '') {
            $employeesWhere[] = 'employees.status = :status';
            $employeesParams['status'] = $status;
        }
        if ($branchId !== '') {
            $employeesWhere[] = 'employees.branch_id = :branch_id';
            $employeesParams['branch_id'] = (int) $branchId;
        }
        $employeesSql = 'SELECT employees.id, employees.name, employees.employee_code, employees.status, COALESCE(employees.department, \'\') AS department, COALESCE(branches.name, \'Unassigned\') AS branch
            FROM employees
            LEFT JOIN branches ON branches.id = employees.branch_id';
        if ($employeesWhere !== []) {
            $employeesSql .= ' WHERE ' . implode(' AND ', $employeesWhere);
        }
        $employeesSql .= ' ORDER BY employees.id DESC LIMIT 25';
        $employees = $pdo->prepare($employeesSql);
        $employees->execute($employeesParams);

        $licensesWhere = [];
        $licensesParams = [];
        if ($term !== '') {
            $licensesWhere[] = '(licenses.product_name LIKE :term OR COALESCE(licenses.vendor_name, \'\') LIKE :term OR COALESCE(licenses.license_key, \'\') LIKE :term)';
            $licensesParams['term'] = $like;
        }
        if ($status !== '') {
            $licensesWhere[] = 'licenses.status = :status';
            $licensesParams['status'] = $status;
        }
        if ($fromDate !== '') {
            $licensesWhere[] = 'licenses.purchase_date >= :licenses_from_date';
            $licensesParams['licenses_from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $licensesWhere[] = 'licenses.expiry_date <= :licenses_to_date';
            $licensesParams['licenses_to_date'] = $toDate;
        }
        $licensesSql = 'SELECT licenses.id,
                               licenses.product_name,
                               COALESCE(licenses.vendor_name, \'\') AS vendor_name,
                               licenses.license_type,
                               licenses.status,
                               licenses.seats_total,
                               licenses.seats_used,
                               COALESCE(DATE_FORMAT(licenses.expiry_date, \'%Y-%m-%d\'), \'\') AS expiry_date
                        FROM licenses';
        if ($licensesWhere !== []) {
            $licensesSql .= ' WHERE ' . implode(' AND ', $licensesWhere);
        }
        $licensesSql .= ' ORDER BY licenses.id DESC LIMIT 25';
        $licenses = $pdo->prepare($licensesSql);
        $licenses->execute($licensesParams);

        $branchesWhere = [];
        $branchesParams = [];
        if ($term !== '') {
            $branchesWhere[] = '(name LIKE :term OR type LIKE :term OR COALESCE(address, \'\') LIKE :term)';
            $branchesParams['term'] = $like;
        }
        $branchesSql = 'SELECT id, name, type, COALESCE(address, \'\') AS address FROM branches';
        if ($branchesWhere !== []) {
            $branchesSql .= ' WHERE ' . implode(' AND ', $branchesWhere);
        }
        $branchesSql .= ' ORDER BY id DESC LIMIT 25';
        $branches = $pdo->prepare($branchesSql);
        $branches->execute($branchesParams);

        $categoriesWhere = [];
        $categoriesParams = [];
        if ($term !== '') {
            $categoriesWhere[] = '(name LIKE :term OR COALESCE(description, \'\') LIKE :term)';
            $categoriesParams['term'] = $like;
        }
        if ($categoryId !== '') {
            $categoriesWhere[] = 'id = :category_id';
            $categoriesParams['category_id'] = (int) $categoryId;
        }
        $categoriesSql = 'SELECT id, name, COALESCE(description, \'\') AS description FROM asset_categories';
        if ($categoriesWhere !== []) {
            $categoriesSql .= ' WHERE ' . implode(' AND ', $categoriesWhere);
        }
        $categoriesSql .= ' ORDER BY id DESC LIMIT 25';
        $categories = $pdo->prepare($categoriesSql);
        $categories->execute($categoriesParams);

        $usersWhere = [];
        $usersParams = [];
        if ($term !== '') {
            $usersWhere[] = '(name LIKE :term OR email LIKE :term OR role LIKE :term)';
            $usersParams['term'] = $like;
        }
        if ($role !== '') {
            $usersWhere[] = 'role = :role';
            $usersParams['role'] = $role;
        }
        if ($status !== '') {
            $usersWhere[] = 'status = :status';
            $usersParams['status'] = $status;
        }
        $usersSql = 'SELECT id, name, email, role, status FROM users';
        if ($usersWhere !== []) {
            $usersSql .= ' WHERE ' . implode(' AND ', $usersWhere);
        }
        $usersSql .= ' ORDER BY id DESC LIMIT 25';
        $users = $pdo->prepare($usersSql);
        $users->execute($usersParams);

        $movementsWhere = [];
        $movementsParams = [];
        if ($term !== '') {
            $movementsWhere[] = '(assets.name LIKE :term OR COALESCE(asset_movements.notes, \'\') LIKE :term OR COALESCE(fb.name, \'\') LIKE :term OR COALESCE(tb.name, \'\') LIKE :term)';
            $movementsParams['term'] = $like;
        }
        if ($branchId !== '') {
            $movementsWhere[] = '(asset_movements.from_branch_id = :branch_id OR asset_movements.to_branch_id = :branch_id)';
            $movementsParams['branch_id'] = (int) $branchId;
        }
        if ($fromDate !== '') {
            $movementsWhere[] = 'DATE(asset_movements.moved_at) >= :movements_from_date';
            $movementsParams['movements_from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $movementsWhere[] = 'DATE(asset_movements.moved_at) <= :movements_to_date';
            $movementsParams['movements_to_date'] = $toDate;
        }
        $movementsSql = 'SELECT asset_movements.id,
                   assets.name AS asset_name,
                   COALESCE(fb.name, \'N/A\') AS from_branch,
                   COALESCE(tb.name, \'N/A\') AS to_branch,
                   COALESCE(asset_movements.notes, \'\') AS notes,
                   DATE(asset_movements.moved_at) AS moved_at
            FROM asset_movements
            JOIN assets ON assets.id = asset_movements.asset_id
            LEFT JOIN branches fb ON fb.id = asset_movements.from_branch_id
            LEFT JOIN branches tb ON tb.id = asset_movements.to_branch_id';
        if ($movementsWhere !== []) {
            $movementsSql .= ' WHERE ' . implode(' AND ', $movementsWhere);
        }
        $movementsSql .= ' ORDER BY asset_movements.id DESC LIMIT 25';
        $movements = $pdo->prepare($movementsSql);
        $movements->execute($movementsParams);

        $results = [
            'assets' => $assets->fetchAll() ?: [],
            'employees' => $employees->fetchAll() ?: [],
            'licenses' => $licenses->fetchAll() ?: [],
            'branches' => $branches->fetchAll() ?: [],
            'categories' => $categories->fetchAll() ?: [],
            'users' => $users->fetchAll() ?: [],
            'movements' => $movements->fetchAll() ?: [],
        ];

        if ($section !== '' && isset($results[$section])) {
            foreach (array_keys($results) as $key) {
                if ($key !== $section) {
                    $results[$key] = [];
                }
            }
        }

        return $results;
    }

    public static function permissionDefinitions(): array
    {
        return [
            'dashboard.view' => __('perm.dashboard_view', 'View dashboard'),
            'requests.view' => __('perm.requests_view', 'View requests'),
            'requests.manage' => __('perm.requests_manage', 'Create and manage requests'),
            'requests.approve' => __('perm.requests_approve', 'Approve requests'),
            'assets.view' => __('perm.assets_view', 'View assets'),
            'assets.manage' => __('perm.assets_manage', 'Manage assets'),
            'assets.move' => __('perm.assets_move', 'Move assets'),
            'branches.view' => __('perm.branches_view', 'View branches'),
            'branches.manage' => __('perm.branches_manage', 'Manage branches'),
            'categories.view' => __('perm.categories_view', 'View categories'),
            'categories.manage' => __('perm.categories_manage', 'Manage categories'),
            'employees.view' => __('perm.employees_view', 'View employees'),
            'employees.manage' => __('perm.employees_manage', 'Manage employees'),
            'licenses.view' => __('perm.licenses_view', 'View licenses'),
            'licenses.manage' => __('perm.licenses_manage', 'Manage licenses'),
            'spare_parts.view' => __('perm.spare_parts_view', 'View spare parts'),
            'spare_parts.manage' => __('perm.spare_parts_manage', 'Manage spare parts'),
            'storage.view' => __('perm.storage_view', 'View storage'),
            'forms.view' => __('perm.forms_view', 'View administrative forms'),
            'forms.manage' => __('perm.forms_manage', 'Manage administrative forms'),
            'reports.view' => __('perm.reports_view', 'View reports'),
            'reports.export' => __('perm.reports_export', 'Export reports'),
            'users.view' => __('perm.users_view', 'View users'),
            'users.manage' => __('perm.users_manage', 'Manage users'),
            'audit.view' => __('perm.audit_view', 'View audit logs'),
            'settings.manage' => __('perm.settings_manage', 'Manage settings'),
            'system.check' => __('perm.system_check', 'View system check'),
            'api.docs' => __('perm.api_docs', 'View API docs'),
        ];
    }

    public static function defaultRolePermissions(): array
    {
        $all = array_fill_keys(array_keys(self::permissionDefinitions()), true);

        return [
            'admin' => $all,
            'it_manager' => [
                'dashboard.view' => true,
                'requests.view' => true,
                'requests.manage' => true,
                'requests.approve' => true,
                'assets.view' => true,
                'assets.manage' => true,
                'assets.move' => true,
                'branches.view' => true,
                'branches.manage' => true,
                'categories.view' => true,
                'categories.manage' => true,
                'employees.view' => true,
                'employees.manage' => true,
                'licenses.view' => true,
                'licenses.manage' => true,
                'spare_parts.view' => true,
                'spare_parts.manage' => true,
                'storage.view' => true,
                'forms.view' => true,
                'forms.manage' => false,
                'reports.view' => true,
                'reports.export' => true,
                'users.view' => false,
                'users.manage' => false,
                'audit.view' => false,
                'settings.manage' => false,
                'system.check' => true,
                'api.docs' => true,
            ],
            'technician' => [
                'dashboard.view' => true,
                'requests.view' => true,
                'requests.manage' => true,
                'requests.approve' => true,
                'assets.view' => true,
                'assets.manage' => false,
                'assets.move' => true,
                'branches.view' => true,
                'branches.manage' => false,
                'categories.view' => true,
                'categories.manage' => false,
                'employees.view' => true,
                'employees.manage' => false,
                'licenses.view' => true,
                'licenses.manage' => true,
                'spare_parts.view' => true,
                'spare_parts.manage' => true,
                'storage.view' => true,
                'forms.view' => true,
                'forms.manage' => false,
                'reports.view' => true,
                'reports.export' => false,
                'users.view' => false,
                'users.manage' => false,
                'audit.view' => false,
                'settings.manage' => false,
                'system.check' => false,
                'api.docs' => true,
            ],
            'finance' => [
                'dashboard.view' => true,
                'requests.view' => true,
                'requests.manage' => true,
                'requests.approve' => true,
                'assets.view' => true,
                'assets.manage' => false,
                'assets.move' => false,
                'branches.view' => true,
                'branches.manage' => false,
                'categories.view' => true,
                'categories.manage' => false,
                'employees.view' => true,
                'employees.manage' => false,
                'licenses.view' => true,
                'licenses.manage' => false,
                'spare_parts.view' => true,
                'spare_parts.manage' => false,
                'storage.view' => true,
                'forms.view' => true,
                'forms.manage' => false,
                'reports.view' => true,
                'reports.export' => false,
                'users.view' => false,
                'users.manage' => false,
                'audit.view' => false,
                'settings.manage' => false,
                'system.check' => false,
                'api.docs' => true,
            ],
            'viewer' => [
                'dashboard.view' => true,
                'requests.view' => true,
                'requests.manage' => true,
                'requests.approve' => false,
                'assets.view' => true,
                'assets.manage' => false,
                'assets.move' => false,
                'branches.view' => true,
                'branches.manage' => false,
                'categories.view' => true,
                'categories.manage' => false,
                'employees.view' => true,
                'employees.manage' => false,
                'licenses.view' => true,
                'licenses.manage' => false,
                'spare_parts.view' => true,
                'spare_parts.manage' => false,
                'storage.view' => true,
                'forms.view' => true,
                'forms.manage' => false,
                'reports.view' => true,
                'reports.export' => false,
                'users.view' => false,
                'users.manage' => false,
                'audit.view' => false,
                'settings.manage' => false,
                'system.check' => false,
                'api.docs' => true,
            ],
        ];
    }

    public static function rolePermissions(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        try {
            $rows = $pdo->query('SELECT role_name, permission_key, allowed FROM role_permissions ORDER BY role_name, permission_key')->fetchAll() ?: [];
        } catch (\Throwable) {
            return [];
        }
        $matrix = self::defaultRolePermissions();
        foreach ($rows as $row) {
            $matrix[(string) $row['role_name']][(string) $row['permission_key']] = (int) $row['allowed'] === 1;
        }

        return $matrix;
    }

    public static function roleHasPermission(string $role, string $permission): bool
    {
        $matrix = self::rolePermissions();
        return (bool) ($matrix[$role][$permission] ?? false);
    }

    public static function saveRolePermissions(array $matrix): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM role_permissions');
            $statement = $pdo->prepare('INSERT INTO role_permissions (role_name, permission_key, allowed, created_at, updated_at) VALUES (:role_name, :permission_key, :allowed, NOW(), NOW())');
            foreach ($matrix as $roleName => $permissions) {
                foreach ($permissions as $permissionKey => $allowed) {
                    $statement->execute([
                        'role_name' => $roleName,
                        'permission_key' => $permissionKey,
                        'allowed' => $allowed ? 1 : 0,
                    ]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function auditSummary(array $filters = []): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'total' => 0,
                'create_count' => 0,
                'update_count' => 0,
                'delete_count' => 0,
                'export_count' => 0,
                'actors_count' => 0,
                'modules_count' => 0,
            ];
        }

        [$whereSql, $params] = self::buildAuditFilterSql($filters);
        $statement = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(action = \'create\') AS create_count,
                    SUM(action = \'update\') AS update_count,
                    SUM(action = \'delete\') AS delete_count,
                    SUM(action = \'export\') AS export_count,
                    COUNT(DISTINCT COALESCE(user_id, 0)) AS actors_count,
                    COUNT(DISTINCT table_name) AS modules_count
             FROM audit_logs' . $whereSql
        );
        $statement->execute($params);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'create_count' => (int) ($row['create_count'] ?? 0),
            'update_count' => (int) ($row['update_count'] ?? 0),
            'delete_count' => (int) ($row['delete_count'] ?? 0),
            'export_count' => (int) ($row['export_count'] ?? 0),
            'actors_count' => (int) ($row['actors_count'] ?? 0),
            'modules_count' => (int) ($row['modules_count'] ?? 0),
        ];
    }

    public static function auditFilterOptions(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return ['actors' => [], 'actions' => [], 'tables' => []];
        }

        $actors = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll() ?: [];
        $actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll() ?: [];
        $tables = $pdo->query("SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name ASC")->fetchAll() ?: [];

        return [
            'actors' => array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => (string) $row['name']], $actors),
            'actions' => array_map(static fn (array $row): string => (string) $row['action'], $actions),
            'tables' => array_map(static fn (array $row): string => (string) $row['table_name'], $tables),
        ];
    }

    public static function auditLogs(array $filters = [], int $limit = 200): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        [$whereSql, $params] = self::buildAuditFilterSql($filters);
        $sql = <<<'SQL'
            SELECT audit_logs.id,
                   audit_logs.user_id,
                   COALESCE(users.name, 'System') AS actor,
                   audit_logs.action,
                   audit_logs.table_name,
                   audit_logs.record_id,
                   COALESCE(JSON_UNQUOTE(JSON_EXTRACT(audit_logs.new_values, '$.name')), JSON_UNQUOTE(JSON_EXTRACT(audit_logs.old_values, '$.name')), '') AS record_name,
                   COALESCE(audit_logs.old_values, '') AS old_values,
                   COALESCE(audit_logs.new_values, '') AS new_values,
                   DATE_FORMAT(audit_logs.created_at, '%Y-%m-%d %H:%i') AS created_at
            FROM audit_logs
            LEFT JOIN users ON users.id = audit_logs.user_id
        SQL;
        $sql .= $whereSql . ' ORDER BY audit_logs.id DESC LIMIT ' . max(1, min($limit, 1000));
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public static function logAudit(string $action, string $tableName, ?int $recordId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $statement = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, created_at)
             VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, NOW())'
        );
        $statement->execute([
            'user_id' => auth_user()['id'] ?? null,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function importBranches(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $payload = [
                'name' => $name,
                'type' => trim((string) ($row['type'] ?? 'Branch')),
                'address' => trim((string) ($row['address'] ?? '')),
            ];
            $existingId = self::branchIdByName($name);
            if ($existingId !== null) {
                self::updateBranch($existingId, $payload);
                $result['updated']++;
            } else {
                self::createBranch($payload);
                $result['created']++;
            }
        }

        return $result;
    }

    public static function importCategories(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $payload = [
                'name' => $name,
                'description' => trim((string) ($row['description'] ?? '')),
            ];
            $existingId = self::categoryIdByName($name);
            if ($existingId !== null) {
                self::updateCategory($existingId, $payload);
                $result['updated']++;
            } else {
                self::createCategory($payload);
                $result['created']++;
            }
        }

        return $result;
    }

    public static function importEmployees(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $employeeCode = trim((string) ($row['employee_code'] ?? ''));
            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $payload = [
                'name' => $name,
                'employee_code' => $employeeCode,
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'project_name' => trim((string) ($row['project_name'] ?? '')),
                'company_email' => trim((string) ($row['company_email'] ?? '')),
                'fingerprint_id' => trim((string) ($row['fingerprint_id'] ?? '')),
                'department' => trim((string) ($row['department'] ?? '')),
                'job_title' => trim((string) ($row['job_title'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'branch_id' => self::findBranchIdByName(trim((string) ($row['branch'] ?? ''))) ?? '',
                'status' => trim((string) ($row['status'] ?? 'active')),
            ];
            $existingId = self::employeeIdByCodeOrName($employeeCode, $name);
            if ($existingId !== null) {
                self::updateEmployee($existingId, $payload);
                $result['updated']++;
            } else {
                self::createEmployee($payload);
                $result['created']++;
            }
        }

        return $result;
    }

    public static function importSpareParts(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $partNumber = trim((string) ($row['part_number'] ?? ''));

            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $payload = [
                'name' => $name,
                'part_number' => $partNumber,
                'category' => trim((string) ($row['category'] ?? '')),
                'vendor_name' => trim((string) ($row['vendor_name'] ?? '')),
                'location' => trim((string) ($row['location'] ?? '')),
                'quantity' => trim((string) ($row['quantity'] ?? '0')),
                'min_quantity' => trim((string) ($row['min_quantity'] ?? '0')),
                'compatible_with' => trim((string) ($row['compatible_with'] ?? '')),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];

            $existingId = self::sparePartIdByNumberOrName($partNumber, $name);
            if ($existingId !== null) {
                self::updateSparePart($existingId, $payload);
                $result['updated']++;
            } else {
                self::createSparePart($payload);
                $result['created']++;
            }
        }

        return $result;
    }

    public static function importAssets(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $payload = [
                'name' => $name,
                'category' => trim((string) ($row['category'] ?? '')),
                'brand' => trim((string) ($row['brand'] ?? '')),
                'model' => trim((string) ($row['model'] ?? '')),
                'serial_number' => trim((string) ($row['serial_number'] ?? '')),
                'purchase_date' => trim((string) ($row['purchase_date'] ?? '')),
                'warranty_expiry' => trim((string) ($row['warranty_expiry'] ?? '')),
                'procurement_stage' => trim((string) ($row['procurement_stage'] ?? 'received')),
                'vendor_name' => trim((string) ($row['vendor_name'] ?? '')),
                'invoice_number' => trim((string) ($row['invoice_number'] ?? '')),
                'status' => trim((string) ($row['status'] ?? 'storage')),
                'location' => trim((string) ($row['location'] ?? '')),
                'assigned_to' => trim((string) ($row['assigned_to'] ?? '')),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];
            $existingId = self::findAssetIdForImport(
                $payload['serial_number'],
                $payload['name'],
                $payload['brand'],
                $payload['model']
            );
            if ($existingId !== null) {
                self::updateAsset($existingId, $payload);
                $result['updated']++;
            } else {
                self::createAsset($payload);
                $result['created']++;
            }
        }

        return $result;
    }

    private static function activeUserIds(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $rows = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll() ?: [];
        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    private static function buildAuditFilterSql(array $filters): array
    {
        $where = [];
        $params = [];
        $query = trim((string) ($filters['q'] ?? ''));
        $actorId = trim((string) ($filters['actor_id'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));
        $table = trim((string) ($filters['table_name'] ?? ''));
        $fromDate = trim((string) ($filters['from_date'] ?? ''));
        $toDate = trim((string) ($filters['to_date'] ?? ''));

        if ($query !== '') {
            $where[] = "(COALESCE(users.name, 'System') LIKE :audit_q OR audit_logs.action LIKE :audit_q OR audit_logs.table_name LIKE :audit_q OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT(audit_logs.new_values, '$.name')), '') LIKE :audit_q)";
            $params['audit_q'] = '%' . $query . '%';
        }
        if ($actorId !== '') {
            $where[] = 'audit_logs.user_id = :audit_actor_id';
            $params['audit_actor_id'] = (int) $actorId;
        }
        if ($action !== '') {
            $where[] = 'audit_logs.action = :audit_action';
            $params['audit_action'] = $action;
        }
        if ($table !== '') {
            $where[] = 'audit_logs.table_name = :audit_table_name';
            $params['audit_table_name'] = $table;
        }
        if ($fromDate !== '') {
            $where[] = 'DATE(audit_logs.created_at) >= :audit_from_date';
            $params['audit_from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $where[] = 'DATE(audit_logs.created_at) <= :audit_to_date';
            $params['audit_to_date'] = $toDate;
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    private static function createNotificationForUsers(string $type, array $data, array $userIds): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || $userIds === []) {
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

    private static function createNotificationOnceForUsers(string $type, string $uniqueKey, array $data, array $userIds): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO || $userIds === []) {
            return;
        }

        $check = $pdo->prepare(
            "SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :user_id
               AND type = :type
               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.unique_key')) = :unique_key"
        );

        foreach ($userIds as $userId) {
            $check->execute([
                'user_id' => $userId,
                'type' => $type,
                'unique_key' => $uniqueKey,
            ]);
            if ((int) $check->fetchColumn() > 0) {
                continue;
            }

            self::createNotificationForUsers($type, ['unique_key' => $uniqueKey] + $data, [$userId]);
        }
    }

    public static function recordReport(string $type, array $filters = [], string $filePath = ''): void
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return;
        }

        $statement = $pdo->prepare(
            'INSERT INTO reports (type, filters, generated_by, file_path, created_at)
             VALUES (:type, :filters, :generated_by, :file_path, NOW())'
        );
        $statement->execute([
            'type' => $type,
            'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'generated_by' => auth_user()['id'] ?? null,
            'file_path' => $filePath,
        ]);
    }

    private static function insertAssetDocuments(PDO $pdo, int $assetId, array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $statement = $pdo->prepare('INSERT INTO asset_documents (asset_id, document_name, file_path) VALUES (:asset_id, :document_name, :file_path)');
        foreach ($documents as $document) {
            $statement->execute([
                'asset_id' => $assetId,
                'document_name' => $document['name'],
                'file_path' => $document['path'],
            ]);
        }
    }

    private static function insertMovementDocuments(PDO $pdo, int $movementId, array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $statement = $pdo->prepare('INSERT INTO asset_movement_documents (movement_id, document_name, file_path) VALUES (:movement_id, :document_name, :file_path)');
        foreach ($documents as $document) {
            $statement->execute([
                'movement_id' => $movementId,
                'document_name' => $document['name'],
                'file_path' => $document['path'],
            ]);
        }
    }

    private static function mapAssetDbParams(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'request_id' => ($input['request_id'] ?? '') === '' ? null : (int) $input['request_id'],
            'category_id' => self::findCategoryIdByName(trim((string) ($input['category'] ?? ''))),
            'brand' => trim((string) ($input['brand'] ?? '')),
            'model' => trim((string) ($input['model'] ?? '')),
            'serial_number' => trim((string) ($input['serial_number'] ?? '')),
            'purchase_date' => self::nullableDate($input['purchase_date'] ?? null),
            'warranty_expiry' => self::nullableDate($input['warranty_expiry'] ?? null),
            'procurement_stage' => trim((string) ($input['procurement_stage'] ?? 'received')),
            'vendor_name' => trim((string) ($input['vendor_name'] ?? '')),
            'invoice_number' => trim((string) ($input['invoice_number'] ?? '')),
            'status' => trim((string) ($input['status'] ?? 'storage')),
            'branch_id' => self::findBranchIdByName(trim((string) ($input['location'] ?? ''))),
            'assigned_employee_id' => self::findEmployeeIdByName(trim((string) ($input['assigned_to'] ?? ''))),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    public static function assetRequestOptions(): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $rows = $pdo->query(
            "SELECT id,
                    request_no,
                    title,
                    status
             FROM asset_requests
             ORDER BY id DESC"
        )->fetchAll() ?: [];

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'request_no' => (string) $row['request_no'],
            'title' => (string) $row['title'],
            'status' => (string) $row['status'],
        ], $rows);
    }

    public static function assetsForRequest(int $requestId): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $statement = $pdo->prepare(
            "SELECT assets.id,
                    assets.name,
                    assets.tag,
                    assets.status,
                    COALESCE(branches.name, 'Unassigned') AS branch_name,
                    COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date
             FROM assets
             LEFT JOIN branches ON branches.id = assets.branch_id
             WHERE assets.request_id = :request_id
             ORDER BY assets.id DESC"
        );
        $statement->execute(['request_id' => $requestId]);
        return $statement->fetchAll() ?: [];
    }

    public static function reconcileAssetInventory(bool $apply = false, ?int $limit = null): array
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return [
                'apply' => $apply,
                'processed' => 0,
                'barcodes_backfilled' => 0,
                'request_movements_backfilled' => 0,
                'stock_groups_linked' => 0,
                'stock_groups_cleared' => 0,
                'sample_groups' => [],
            ];
        }

        $sql = <<<'SQL'
            SELECT assets.id
            FROM assets
            WHERE assets.status = 'storage'
               OR assets.request_id IS NOT NULL
               OR assets.stock_group_id IS NOT NULL
               OR assets.barcode IS NULL
               OR assets.barcode = ''
               OR assets.tag IS NULL
               OR assets.tag = ''
            ORDER BY assets.id ASC
        SQL;

        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $assetIds = array_map(static fn (array $row): int => (int) $row['id'], $pdo->query($sql)->fetchAll() ?: []);
        $summary = [
            'apply' => $apply,
            'processed' => count($assetIds),
            'barcodes_backfilled' => 0,
            'request_movements_backfilled' => 0,
            'stock_groups_linked' => 0,
            'stock_groups_cleared' => 0,
            'sample_groups' => [],
        ];

        if ($assetIds === []) {
            return $summary;
        }

        if ($apply) {
            $pdo->beginTransaction();
        }

        try {
            foreach ($assetIds as $assetId) {
                $result = self::syncAssetInventoryStateWithPdo($pdo, $assetId, $apply);
                $summary['barcodes_backfilled'] += $result['barcode_updated'] ? 1 : 0;
                $summary['request_movements_backfilled'] += $result['request_movement_created'] ? 1 : 0;
                $summary['stock_groups_linked'] += $result['stock_group_linked'] ? 1 : 0;
                $summary['stock_groups_cleared'] += $result['stock_group_cleared'] ? 1 : 0;

                if ($result['stock_group_name'] !== '' && count($summary['sample_groups']) < 8) {
                    $summary['sample_groups'][] = [
                        'asset_id' => $assetId,
                        'stock_group' => $result['stock_group_name'],
                        'barcode' => $result['barcode'],
                    ];
                }
            }

            if ($apply) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($apply && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        return $summary;
    }

    private static function mapLicenseParams(array $input): array
    {
        return [
            'product_name' => trim((string) ($input['product_name'] ?? '')),
            'vendor_name' => trim((string) ($input['vendor_name'] ?? '')),
            'license_type' => trim((string) ($input['license_type'] ?? 'subscription')),
            'license_key' => trim((string) ($input['license_key'] ?? '')),
            'seats_total' => max(1, (int) ($input['seats_total'] ?? 1)),
            'seats_used' => max(0, (int) ($input['seats_used'] ?? 0)),
            'purchase_date' => self::nullableDate($input['purchase_date'] ?? null),
            'expiry_date' => self::nullableDate($input['expiry_date'] ?? null),
            'status' => trim((string) ($input['status'] ?? 'active')),
            'assigned_asset_id' => ($input['assigned_asset_id'] ?? '') === '' ? null : (int) $input['assigned_asset_id'],
            'assigned_employee_id' => ($input['assigned_employee_id'] ?? '') === '' ? null : (int) $input['assigned_employee_id'],
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    private static function mapSparePartParams(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'part_number' => trim((string) ($input['part_number'] ?? '')),
            'category' => trim((string) ($input['category'] ?? '')),
            'vendor_name' => trim((string) ($input['vendor_name'] ?? '')),
            'location' => trim((string) ($input['location'] ?? '')),
            'quantity' => max(0, (int) ($input['quantity'] ?? 0)),
            'min_quantity' => max(0, (int) ($input['min_quantity'] ?? 0)),
            'compatible_with' => trim((string) ($input['compatible_with'] ?? '')),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    private static function generatedAssetTag(int $id): string
    {
        return 'AST-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private static function syncAssetInventoryStateWithPdo(PDO $pdo, int $assetId, bool $apply = true): array
    {
        $asset = self::assetInventoryRow($pdo, $assetId);
        if ($asset === null) {
            return [
                'barcode_updated' => false,
                'request_movement_created' => false,
                'stock_group_linked' => false,
                'stock_group_cleared' => false,
                'stock_group_name' => '',
                'barcode' => '',
            ];
        }

        $tag = trim((string) ($asset['tag'] ?? ''));
        if ($tag === '') {
            $tag = self::generatedAssetTag($assetId);
            if ($apply) {
                $pdo->prepare('UPDATE assets SET tag = :tag, updated_at = NOW() WHERE id = :id')->execute([
                    'id' => $assetId,
                    'tag' => $tag,
                ]);
            }
            $asset['tag'] = $tag;
        }

        $barcode = trim((string) ($asset['barcode'] ?? ''));
        $barcodeUpdated = false;
        if ($barcode === '') {
            $barcode = $tag;
            if ($apply) {
                $pdo->prepare('UPDATE assets SET barcode = :barcode, updated_at = NOW() WHERE id = :id')->execute([
                    'id' => $assetId,
                    'barcode' => $barcode,
                ]);
            }
            $asset['barcode'] = $barcode;
            $barcodeUpdated = true;
        }

        $requestMovementCreated = self::ensureRequestMovementForAsset($pdo, $asset, $apply);
        $stockGroupResult = self::syncStockGroupForAsset($pdo, $asset, $apply);

        return [
            'barcode_updated' => $barcodeUpdated,
            'request_movement_created' => $requestMovementCreated,
            'stock_group_linked' => $stockGroupResult['linked'],
            'stock_group_cleared' => $stockGroupResult['cleared'],
            'stock_group_name' => $stockGroupResult['name'],
            'barcode' => $barcode,
        ];
    }

    private static function assetInventoryRow(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare(
            "SELECT assets.id,
                    COALESCE(assets.tag, '') AS tag,
                    COALESCE(assets.barcode, '') AS barcode,
                    assets.request_id,
                    COALESCE(asset_requests.request_no, '') AS request_no,
                    assets.stock_group_id,
                    assets.category_id,
                    assets.branch_id,
                    assets.assigned_employee_id,
                    assets.status,
                    COALESCE(assets.name, '') AS name,
                    COALESCE(assets.brand, '') AS brand,
                    COALESCE(assets.model, '') AS model,
                    COALESCE(DATE_FORMAT(assets.purchase_date, '%Y-%m-%d'), '') AS purchase_date,
                    COALESCE(DATE_FORMAT(assets.created_at, '%Y-%m-%d %H:%i:%s'), '') AS created_at
             FROM assets
             LEFT JOIN asset_requests ON asset_requests.id = assets.request_id
             WHERE assets.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $assetId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private static function ensureRequestMovementForAsset(PDO $pdo, array $asset, bool $apply): bool
    {
        $requestId = (int) ($asset['request_id'] ?? 0);
        if ($requestId <= 0) {
            return false;
        }

        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM asset_movements
             WHERE asset_id = :asset_id
               AND request_id = :request_id
               AND movement_type = 'request'"
        );
        $statement->execute([
            'asset_id' => (int) $asset['id'],
            'request_id' => $requestId,
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            return false;
        }

        if ($apply) {
            $requestNo = trim((string) ($asset['request_no'] ?? ''));
            $note = '[REQUEST REGISTRATION] ' . ($requestNo !== '' ? $requestNo : ('Request #' . $requestId));
            $movedAt = trim((string) ($asset['purchase_date'] ?? '')) !== ''
                ? ((string) $asset['purchase_date'] . ' 00:00:00')
                : (trim((string) ($asset['created_at'] ?? '')) !== '' ? (string) $asset['created_at'] : date('Y-m-d H:i:s'));

            $insert = $pdo->prepare(
                'INSERT INTO asset_movements (asset_id, request_id, movement_type, from_branch_id, to_branch_id, user_id, notes, moved_at)
                 VALUES (:asset_id, :request_id, :movement_type, :from_branch_id, :to_branch_id, :user_id, :notes, :moved_at)'
            );
            $insert->execute([
                'asset_id' => (int) $asset['id'],
                'request_id' => $requestId,
                'movement_type' => 'request',
                'from_branch_id' => null,
                'to_branch_id' => ($asset['branch_id'] ?? '') === '' ? null : (int) $asset['branch_id'],
                'user_id' => null,
                'notes' => $note,
                'moved_at' => $movedAt,
            ]);
        }

        return true;
    }

    private static function syncStockGroupForAsset(PDO $pdo, array $asset, bool $apply): array
    {
        $currentGroupId = ($asset['stock_group_id'] ?? '') === '' ? null : (int) $asset['stock_group_id'];
        $isStorageEligible = (string) ($asset['status'] ?? '') === 'storage' && empty($asset['assigned_employee_id']);

        if (!$isStorageEligible) {
            if ($currentGroupId !== null && $apply) {
                $pdo->prepare('UPDATE assets SET stock_group_id = NULL, updated_at = NOW() WHERE id = :id')->execute([
                    'id' => (int) $asset['id'],
                ]);
            }

            return [
                'linked' => false,
                'cleared' => $currentGroupId !== null,
                'name' => '',
            ];
        }

        $groupName = self::stockGroupDisplayName($asset);
        $normalizedKey = self::stockGroupKey($asset);
        $existingGroupId = self::findStockGroupIdByKey($pdo, $normalizedKey);
        $groupId = $apply
            ? self::ensureStockGroupId($pdo, [
                'display_name' => $groupName,
                'normalized_key' => $normalizedKey,
                'category_id' => ($asset['category_id'] ?? '') === '' ? null : (int) $asset['category_id'],
                'branch_id' => ($asset['branch_id'] ?? '') === '' ? null : (int) $asset['branch_id'],
                'brand' => trim((string) ($asset['brand'] ?? '')),
                'model' => trim((string) ($asset['model'] ?? '')),
            ])
            : $existingGroupId;

        if ($apply && $groupId !== null && $groupId !== $currentGroupId) {
            $pdo->prepare('UPDATE assets SET stock_group_id = :stock_group_id, updated_at = NOW() WHERE id = :id')->execute([
                'id' => (int) $asset['id'],
                'stock_group_id' => $groupId,
            ]);
        }

        return [
            'linked' => $currentGroupId === null || ($groupId !== null && $groupId !== $currentGroupId),
            'cleared' => false,
            'name' => $groupName,
        ];
    }

    private static function ensureStockGroupId(PDO $pdo, array $group): ?int
    {
        $normalizedKey = trim((string) ($group['normalized_key'] ?? ''));
        if ($normalizedKey === '') {
            return null;
        }

        $statement = $pdo->prepare(
            'INSERT INTO asset_stock_groups (display_name, normalized_key, category_id, branch_id, brand, model, created_at, updated_at)
             VALUES (:display_name, :normalized_key, :category_id, :branch_id, :brand, :model, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                display_name = VALUES(display_name),
                category_id = VALUES(category_id),
                branch_id = VALUES(branch_id),
                brand = VALUES(brand),
                model = VALUES(model),
                updated_at = NOW()'
        );
        $statement->execute([
            'display_name' => (string) $group['display_name'],
            'normalized_key' => $normalizedKey,
            'category_id' => $group['category_id'],
            'branch_id' => $group['branch_id'],
            'brand' => (string) ($group['brand'] ?? ''),
            'model' => (string) ($group['model'] ?? ''),
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function findStockGroupIdByKey(PDO $pdo, string $normalizedKey): ?int
    {
        if ($normalizedKey === '') {
            return null;
        }

        $statement = $pdo->prepare('SELECT id FROM asset_stock_groups WHERE normalized_key = :normalized_key LIMIT 1');
        $statement->execute(['normalized_key' => $normalizedKey]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    private static function stockGroupDisplayName(array $asset): string
    {
        $name = trim((string) ($asset['name'] ?? ''));
        $brandModel = trim(trim((string) ($asset['brand'] ?? '')) . ' ' . trim((string) ($asset['model'] ?? '')));

        if ($name === '') {
            return $brandModel;
        }

        if ($brandModel === '' || stripos($name, $brandModel) !== false) {
            return $name;
        }

        return $name . ' / ' . $brandModel;
    }

    private static function stockGroupKey(array $asset): string
    {
        return implode('|', [
            (string) (($asset['branch_id'] ?? '') === '' ? 0 : (int) $asset['branch_id']),
            (string) (($asset['category_id'] ?? '') === '' ? 0 : (int) $asset['category_id']),
            self::normalizeStockGroupText((string) ($asset['name'] ?? '')),
            self::normalizeStockGroupText((string) ($asset['brand'] ?? '')),
            self::normalizeStockGroupText((string) ($asset['model'] ?? '')),
        ]);
    }

    private static function normalizeStockGroupText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value;
    }

    private static function mimeTypeForAdministrativeForm(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            default => 'application/octet-stream',
        };
    }

    private static function hydrateAdministrativeForm(string $id, array $definition): ?array
    {
        $files = [];
        $latestUpdate = '';
        foreach ((array) ($definition['files'] ?? []) as $variant => $file) {
            $path = trim((string) ($file['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $absolutePath = str_starts_with($path, '/')
                ? $path
                : base_path($path);

            if (!is_file($absolutePath)) {
                continue;
            }

            $updatedAt = date('Y-m-d H:i', (int) filemtime($absolutePath));
            if ($updatedAt > $latestUpdate) {
                $latestUpdate = $updatedAt;
            }

            $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
            $files[$variant] = [
                'variant' => $variant,
                'path' => $absolutePath,
                'relative_path' => str_starts_with($path, '/')
                    ? ltrim(str_replace(base_path(), '', $absolutePath), '/')
                    : ltrim($path, '/'),
                'extension' => $extension,
                'download_name' => (string) ($file['download_name'] ?? basename($absolutePath)),
                'mime' => self::mimeTypeForAdministrativeForm($extension),
                'size' => filesize($absolutePath) ?: 0,
                'updated_at' => $updatedAt,
                'download_route' => route('administrative-forms.download', ['id' => $id, 'variant' => (string) $variant]),
            ];
        }

        if ($files === []) {
            return null;
        }

        $relatedRouteName = self::administrativeFormRelatedRouteName((string) ($definition['related_route_name'] ?? 'dashboard'));
        $routeOptions = self::administrativeFormRouteOptions();
        $kind = trim((string) ($definition['kind'] ?? 'form')) === 'book' ? 'book' : 'form';

        return [
            'id' => $id,
            'kind' => $kind,
            'kind_label' => $kind === 'book'
                ? __('administrative_forms.kind_book', 'Administrative Book')
                : __('administrative_forms.kind_form', 'Administrative Form'),
            'title' => (string) ($definition['title'] ?? ''),
            'category' => (string) ($definition['category'] ?? ''),
            'description' => (string) ($definition['description'] ?? ''),
            'related_route_name' => $relatedRouteName,
            'related_route' => route($relatedRouteName),
            'related_label' => $routeOptions[$relatedRouteName] ?? __('nav.dashboard', 'Dashboard'),
            'files' => $files,
            'files_count' => count($files),
            'primary_variant' => array_key_first($files),
            'updated_at' => $latestUpdate,
            'is_builtin' => isset(self::baseAdministrativeFormDefinitions()[$id]),
            'is_custom' => (string) ($definition['source'] ?? '') === 'custom',
            'has_override' => (string) ($definition['source'] ?? '') === 'override',
            'is_editable' => true,
        ];
    }

    private static function administrativeFormRelatedRouteName(string $routeName): string
    {
        $options = self::administrativeFormRouteOptions();
        return isset($options[$routeName]) ? $routeName : 'dashboard';
    }

    private static function administrativeFormEntries(): array
    {
        $path = self::administrativeFormsEntriesPath();
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private static function saveAdministrativeFormEntries(array $entries): void
    {
        $path = self::administrativeFormsEntriesPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create administrative forms metadata directory.');
        }

        file_put_contents($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private static function administrativeFormsEntriesPath(): string
    {
        return base_path('storage/data/administrative_forms.json');
    }

    private static function baseAdministrativeFormDefinitions(): array
    {
        $basePath = base_path('storage/app/administrative-forms');

        return [
            'asset-request' => [
                'kind' => 'form',
                'title' => __('administrative_forms.asset_request_title', 'Asset Request Form'),
                'category' => __('administrative_forms.category_operations', 'Operations'),
                'description' => __('administrative_forms.asset_request_desc', 'Formal request form for devices, computers, and related user assets.'),
                'related_route_name' => 'requests.index',
                'source' => 'system',
                'files' => [
                    'docx' => ['path' => $basePath . '/asset-request-form.docx', 'download_name' => 'asset-request-form.docx'],
                ],
            ],
            'access-request' => [
                'kind' => 'form',
                'title' => __('administrative_forms.access_request_title', 'Access Request Form'),
                'category' => __('administrative_forms.category_identity', 'Identity & Access'),
                'description' => __('administrative_forms.access_request_desc', 'Request form for system access, permissions, and account enablement.'),
                'related_route_name' => 'licenses.index',
                'source' => 'system',
                'files' => [
                    'docx' => ['path' => $basePath . '/access-request-form.docx', 'download_name' => 'access-request-form.docx'],
                    'pdf' => ['path' => $basePath . '/access-request-form.pdf', 'download_name' => 'access-request-form.pdf'],
                ],
            ],
            'clearance' => [
                'kind' => 'form',
                'title' => __('administrative_forms.clearance_title', 'Clearance Form'),
                'category' => __('administrative_forms.category_hr', 'HR / Offboarding'),
                'description' => __('administrative_forms.clearance_desc', 'Clearance and handover form used when closing employee obligations and returned items.'),
                'related_route_name' => 'employees.index',
                'source' => 'system',
                'files' => [
                    'docx' => ['path' => $basePath . '/clearance-form.docx', 'download_name' => 'clearance-form.docx'],
                ],
            ],
        ];
    }

    private static function toStorableAdministrativeFiles(array $files): array
    {
        $stored = [];
        foreach ($files as $variant => $file) {
            if (!is_array($file)) {
                continue;
            }

            $stored[$variant] = [
                'path' => (string) ($file['relative_path'] ?? $file['path'] ?? ''),
                'download_name' => (string) ($file['download_name'] ?? ''),
            ];
        }

        return $stored;
    }

    private static function deleteAdministrativeFiles(array $files): void
    {
        foreach ($files as $file) {
            $path = (string) ($file['path'] ?? '');
            if (self::isCustomAdministrativeStoragePath($path)) {
                UploadStore::deleteFile($path);
            }
        }
    }

    private static function isCustomAdministrativeStoragePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        return str_contains($normalized, 'storage/app/administrative-forms/custom/');
    }

    public static function branchIdByName(string $name): ?int
    {
        return self::findBranchIdByName(trim($name));
    }

    public static function categoryIdByName(string $name): ?int
    {
        return self::findCategoryIdByName(trim($name));
    }

    public static function employeeIdByName(string $name): ?int
    {
        return self::findEmployeeIdByName(trim($name));
    }

    private static function findCategoryIdByName(string $name): ?int
    {
        if ($name === '') {
            return null;
        }
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id FROM asset_categories WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function findBranchIdByName(string $name): ?int
    {
        if ($name === '' || strcasecmp($name, 'Unassigned') === 0) {
            return null;
        }
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id FROM branches WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function findEmployeeIdByName(string $name): ?int
    {
        if ($name === '' || strcasecmp($name, 'Unassigned') === 0) {
            return null;
        }
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id FROM employees WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function branchNameById(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }
        $statement = $pdo->prepare('SELECT name FROM branches WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    private static function normalizeCompanyEmail(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (!str_contains($value, '@')) {
            $value .= '@alnahala.com';
        }
        return strtolower($value);
    }

    private static function employeeIdByCodeOrName(string $employeeCode, string $name): ?int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        if ($employeeCode !== '') {
            $statement = $pdo->prepare('SELECT id FROM employees WHERE employee_code = :employee_code LIMIT 1');
            $statement->execute(['employee_code' => $employeeCode]);
            $value = $statement->fetchColumn();
            if ($value !== false) {
                return (int) $value;
            }
        }

        return self::findEmployeeIdByName($name);
    }

    private static function sparePartIdByNumberOrName(string $partNumber, string $name): ?int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        if ($partNumber !== '') {
            $statement = $pdo->prepare('SELECT id FROM spare_parts WHERE part_number = :part_number LIMIT 1');
            $statement->execute(['part_number' => $partNumber]);
            $value = $statement->fetchColumn();
            if ($value !== false) {
                return (int) $value;
            }
        }

        $statement = $pdo->prepare('SELECT id FROM spare_parts WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function existingLicenseIdForIntake(array $params): ?int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        if (($params['license_key'] ?? '') !== '') {
            $statement = $pdo->prepare('SELECT id FROM licenses WHERE license_key = :license_key LIMIT 1');
            $statement->execute(['license_key' => $params['license_key']]);
            $value = $statement->fetchColumn();
            if ($value !== false) {
                return (int) $value;
            }
        }

        $statement = $pdo->prepare(
            'SELECT id
             FROM licenses
             WHERE product_name = :product_name
               AND COALESCE(vendor_name, \'\') = :vendor_name
               AND license_type = :license_type
             LIMIT 1'
        );
        $statement->execute([
            'product_name' => $params['product_name'] ?? '',
            'vendor_name' => $params['vendor_name'] ?? '',
            'license_type' => $params['license_type'] ?? 'subscription',
        ]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function findAssetIdForImport(string $serialNumber, string $name, string $brand, string $model): ?int
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return null;
        }

        if ($serialNumber !== '') {
            $statement = $pdo->prepare('SELECT id FROM assets WHERE serial_number = :serial_number LIMIT 1');
            $statement->execute(['serial_number' => $serialNumber]);
            $value = $statement->fetchColumn();
            if ($value !== false) {
                return (int) $value;
            }
        }

        $statement = $pdo->prepare(
            'SELECT id
             FROM assets
             WHERE name = :name
               AND COALESCE(brand, \'\') = :brand
               AND COALESCE(model, \'\') = :model
             LIMIT 1'
        );
        $statement->execute([
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
        ]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private static function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
