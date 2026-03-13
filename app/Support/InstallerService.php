<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

class InstallerService
{
    public static function needsInstallation(): bool
    {
        $state = self::state();
        return (bool) ($state['requires_install'] ?? true);
    }

    public static function state(): array
    {
        $dbStatus = Database::status();
        $hasAdmin = false;

        if ($dbStatus['connected'] && $dbStatus['missing_tables'] === []) {
            $pdo = Database::connect();
            if ($pdo instanceof PDO) {
                try {
                    $hasAdmin = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
                } catch (\Throwable) {
                    $hasAdmin = false;
                }
            }
        }

        $requirements = [
            [
                'label' => __('install.requirement_php', 'PHP 8+'),
                'status' => PHP_VERSION_ID >= 80000,
                'detail' => PHP_VERSION,
            ],
            [
                'label' => __('install.requirement_pdo', 'PDO MySQL extension'),
                'status' => extension_loaded('pdo_mysql'),
                'detail' => extension_loaded('pdo_mysql') ? __('install.loaded', 'Loaded') : __('install.missing', 'Missing'),
            ],
            self::pathRequirement(__('install.requirement_sessions', 'Session directory'), base_path('storage/sessions')),
            self::pathRequirement(__('install.requirement_logs', 'Log directory'), base_path('storage/logs')),
            self::pathRequirement(__('install.requirement_uploads', 'Upload directory'), base_path('storage/uploads')),
        ];

        return [
            'requires_install' => !$dbStatus['connected'] || $dbStatus['missing_tables'] !== [] || !$hasAdmin,
            'database' => $dbStatus,
            'has_admin' => $hasAdmin,
            'requirements' => $requirements,
        ];
    }

    public static function install(array $input, array $files): array
    {
        self::ensureDirectories();

        $config = self::databaseConfig($input);
        $rootPdo = self::connect($config, false);
        $databaseName = str_replace('`', '``', $config['database']);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $pdo = self::connect($config, true);
        $pdo->beginTransaction();

        try {
            self::runSchema($pdo);
            $user = self::upsertAdminUser($pdo, $input);
            self::saveSystemSettings($pdo, $input);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        self::writeEnvironmentFile($input, $config);
        self::storeBrandingAssets($files);

        return $user;
    }

    private static function pathRequirement(string $label, string $path): array
    {
        $exists = is_dir($path) || @mkdir($path, 0777, true);
        if ($exists) {
            @chmod($path, 0777);
        }

        return [
            'label' => $label,
            'status' => $exists && is_writable($path),
            'detail' => $path,
        ];
    }

    private static function ensureDirectories(): void
    {
        foreach ([
            base_path('storage/sessions'),
            base_path('storage/uploads'),
            base_path('storage/logs'),
            base_path('storage/app/administrative-forms'),
            base_path('storage/system-backups'),
            base_path('storage/data'),
        ] as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            @chmod($directory, 0777);
        }
    }

    private static function databaseConfig(array $input): array
    {
        return [
            'host' => trim((string) ($input['db_host'] ?? '127.0.0.1')) ?: '127.0.0.1',
            'port' => trim((string) ($input['db_port'] ?? '3306')) ?: '3306',
            'database' => trim((string) ($input['db_database'] ?? '')),
            'username' => trim((string) ($input['db_username'] ?? '')),
            'password' => (string) ($input['db_password'] ?? ''),
            'socket' => trim((string) ($input['db_socket'] ?? '')),
        ];
    }

    private static function connect(array $config, bool $withDatabase): PDO
    {
        $dsn = '';
        if ($config['socket'] !== '') {
            $dsn = 'mysql:unix_socket=' . $config['socket'] . ';charset=utf8mb4';
        } else {
            $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';charset=utf8mb4';
        }

        if ($withDatabase) {
            $dsn .= ';dbname=' . $config['database'];
        }

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new \RuntimeException(__('install.database_connect_failed', 'Database connection failed.') . ' ' . $exception->getMessage());
        }
    }

    private static function runSchema(PDO $pdo): void
    {
        $schema = @file_get_contents(base_path('database/schema.sql'));
        if ($schema === false) {
            throw new \RuntimeException(__('install.schema_missing', 'Database schema file is missing.'));
        }

        $schema = preg_replace('/^\s*--.*$/m', '', $schema) ?? $schema;
        $schema = preg_replace('/CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $schema) ?? $schema;
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }
    }

    private static function upsertAdminUser(PDO $pdo, array $input): array
    {
        $name = trim((string) ($input['admin_name'] ?? 'Administrator'));
        $email = trim((string) ($input['admin_email'] ?? ''));
        $password = (string) ($input['admin_password'] ?? '');
        $locale = in_array((string) ($input['default_locale'] ?? 'en'), ['en', 'ar'], true) ? (string) $input['default_locale'] : 'en';
        $theme = in_array((string) ($input['default_theme'] ?? 'light'), ['light', 'dark'], true) ? (string) $input['default_theme'] : 'light';

        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        $id = (int) ($existing->fetchColumn() ?: 0);

        if ($id > 0) {
            $statement = $pdo->prepare(
                'UPDATE users
                 SET name = :name,
                     password = :password,
                     role = :role,
                     status = :status,
                     locale = :locale,
                     theme = :theme,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'name' => $name,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active',
                'locale' => $locale,
                'theme' => $theme,
            ]);
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, password, role, status, locale, theme, created_at, updated_at)
                 VALUES (:name, :email, :password, :role, :status, :locale, :theme, NOW(), NOW())'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active',
                'locale' => $locale,
                'theme' => $theme,
            ]);
            $id = (int) $pdo->lastInsertId();
        }

        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'status' => 'active',
            'locale' => $locale,
            'theme' => $theme,
        ];
    }

    private static function saveSystemSettings(PDO $pdo, array $input): void
    {
        $settings = [
            'app_name' => trim((string) ($input['app_name'] ?? 'Asset Management System')),
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'support_email' => trim((string) ($input['support_email'] ?? '')),
            'default_locale' => in_array((string) ($input['default_locale'] ?? 'en'), ['en', 'ar'], true) ? (string) $input['default_locale'] : 'en',
            'default_theme' => in_array((string) ($input['default_theme'] ?? 'light'), ['light', 'dark'], true) ? (string) $input['default_theme'] : 'light',
        ];

        $statement = $pdo->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, updated_at)
             VALUES (:setting_key, :setting_value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($settings as $key => $value) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }

    private static function writeEnvironmentFile(array $input, array $config): void
    {
        $lines = [
            'APP_NAME=' . self::envValue(trim((string) ($input['app_name'] ?? 'Asset Management System'))),
            'APP_LOCALE=' . self::envValue(in_array((string) ($input['default_locale'] ?? 'en'), ['en', 'ar'], true) ? (string) $input['default_locale'] : 'en'),
            'DB_HOST=' . self::envValue($config['host']),
            'DB_PORT=' . self::envValue($config['port']),
            'DB_DATABASE=' . self::envValue($config['database']),
            'DB_USERNAME=' . self::envValue($config['username']),
            'DB_PASSWORD=' . self::envValue($config['password']),
            'DB_SOCKET=' . self::envValue($config['socket']),
        ];

        if (file_put_contents(base_path('.env'), implode(PHP_EOL, $lines) . PHP_EOL) === false) {
            throw new \RuntimeException(__('install.env_write_failed', 'Unable to write the environment file.'));
        }
    }

    private static function envValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#"\'=]/', $value) !== 1) {
            return $value;
        }

        return '"' . addcslashes($value, "\\\"") . '"';
    }

    private static function storeBrandingAssets(array $files): void
    {
        if (isset($files['logo_file']) && is_array($files['logo_file']) && (int) ($files['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            self::moveUploadedFile($files['logo_file']['tmp_name'], base_path('logo.png'));
        }

        if (isset($files['favicon_file']) && is_array($files['favicon_file']) && (int) ($files['favicon_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            self::moveUploadedFile($files['favicon_file']['tmp_name'], base_path('favicon.ico'));
        }
    }

    private static function moveUploadedFile(string $source, string $destination): void
    {
        if (!@move_uploaded_file($source, $destination) && !@rename($source, $destination) && !@copy($source, $destination)) {
            throw new \RuntimeException(__('install.upload_failed', 'Unable to store one of the branding files.'));
        }
    }
}
