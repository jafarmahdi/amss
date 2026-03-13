<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

class Database
{
    private const REQUIRED_TABLES = [
        'users',
        'asset_requests',
        'asset_request_approvals',
        'asset_request_timeline',
        'branches',
        'employees',
        'asset_categories',
        'assets',
        'asset_documents',
        'asset_assignments',
        'asset_stock_groups',
        'asset_movements',
        'asset_movement_documents',
        'asset_repairs',
        'asset_handovers',
        'asset_maintenance',
        'employee_offboarding',
        'licenses',
        'license_renewals',
        'spare_parts',
        'notifications',
        'system_settings',
        'reports',
        'role_permissions',
        'audit_logs',
    ];

    public static function connect(): ?PDO
    {
        static $pdo = false;

        if ($pdo !== false) {
            return $pdo;
        }

        $database = env('DB_DATABASE', 'ams');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $socket = env('DB_SOCKET');

        $dsn = $socket
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socket, $database)
            : sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '3306'),
                $database
            );

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException) {
            $pdo = null;
        }

        return $pdo;
    }

    public static function status(): array
    {
        $database = env('DB_DATABASE', 'ams');
        $pdo = self::connect();

        if (!$pdo instanceof PDO) {
            return [
                'connected' => false,
                'database' => $database,
                'tables' => [],
                'missing_tables' => self::REQUIRED_TABLES,
            ];
        }

        $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
        $tables = array_map(static fn (array $row): string => (string) $row[0], $rows);
        sort($tables);

        return [
            'connected' => true,
            'database' => $database,
            'tables' => $tables,
            'missing_tables' => array_values(array_diff(self::REQUIRED_TABLES, $tables)),
        ];
    }
}
