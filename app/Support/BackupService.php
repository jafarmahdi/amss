<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class BackupService
{
    public static function listBackups(): array
    {
        $directory = self::backupDirectory();
        if (!is_dir($directory)) {
            return [];
        }

        $files = array_values(array_filter(scandir($directory) ?: [], static fn (string $file): bool => !in_array($file, ['.', '..'], true)));
        rsort($files);

        return array_map(static function (string $file) use ($directory): array {
            $path = $directory . '/' . $file;
            return [
                'name' => $file,
                'path' => 'storage/system-backups/' . $file,
                'size' => is_file($path) ? (int) filesize($path) : 0,
                'modified_at' => is_file($path) ? date('Y-m-d H:i', (int) filemtime($path)) : '',
            ];
        }, $files);
    }

    public static function createDeploymentBackup(bool $includeUploads = true): array
    {
        $directory = self::backupDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create backup directory.');
        }

        $timestamp = date('Ymd-His');
        $sqlFilename = 'alnahala-ams-db-' . $timestamp . '.sql';
        $zipFilename = 'alnahala-ams-deploy-' . $timestamp . '.zip';
        $sqlPath = $directory . '/' . $sqlFilename;
        $zipPath = $directory . '/' . $zipFilename;

        if (file_put_contents($sqlPath, self::databaseDump()) === false) {
            throw new \RuntimeException('Unable to write database backup file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create deployment zip.');
        }

        $basePath = rtrim(base_path(), '/');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            $fullPath = $fileInfo->getPathname();
            $relativePath = ltrim(str_replace($basePath, '', $fullPath), '/');

            if (
                str_starts_with($relativePath, 'storage/backups/')
                || str_starts_with($relativePath, 'storage/system-backups/')
                || str_starts_with($relativePath, 'storage/sessions/')
            ) {
                continue;
            }

            if (!$includeUploads && str_starts_with($relativePath, 'storage/uploads/')) {
                continue;
            }

            if (str_starts_with($relativePath, '.git/')) {
                continue;
            }

            if (!$fileInfo->isFile()) {
                continue;
            }

            if (!is_readable($fullPath)) {
                continue;
            }

            if (!$zip->addFile($fullPath, $relativePath)) {
                $zip->close();
                @unlink($zipPath);
                throw new \RuntimeException('Unable to add file to deployment zip: ' . $relativePath);
            }
        }

        if (!$zip->addFile($sqlPath, 'deployment/' . $sqlFilename)) {
            $zip->close();
            @unlink($zipPath);
            throw new \RuntimeException('Unable to embed database backup in deployment zip.');
        }

        if (!$zip->addFromString('deployment/manifest.json', json_encode([
            'generated_at' => date('c'),
            'app_name' => DataRepository::systemSettings()['app_name'] ?? 'Alnahala AMS',
            'database' => env('DB_DATABASE', 'ams'),
            'include_uploads' => $includeUploads,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $zip->close();
            @unlink($zipPath);
            throw new \RuntimeException('Unable to add manifest to deployment zip.');
        }

        if (!$zip->close()) {
            @unlink($zipPath);
            throw new \RuntimeException('Unable to finalize deployment zip.');
        }

        return [
            'sql' => 'storage/system-backups/' . $sqlFilename,
            'zip' => 'storage/system-backups/' . $zipFilename,
        ];
    }

    private static function backupDirectory(): string
    {
        return base_path('storage/system-backups');
    }

    private static function databaseDump(): string
    {
        $pdo = Database::connect();
        if (!$pdo instanceof PDO) {
            return "-- Database connection unavailable\n";
        }

        $lines = [
            '-- Alnahala AMS database backup',
            '-- Generated at ' . date('Y-m-d H:i:s'),
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) ?: [];
        foreach ($tables as $tableRow) {
            $table = (string) ($tableRow[0] ?? '');
            if ($table === '') {
                continue;
            }

            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? '';
            if ($createSql === '') {
                continue;
            }

            $lines[] = '-- Table: ' . $table;
            $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
            $lines[] = $createSql . ';';

            $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $columns = array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row));
                $values = array_map(static function ($value) use ($pdo): string {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string) $value);
                }, array_values($row));
                $lines[] = 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode("\n", $lines);
    }
}
