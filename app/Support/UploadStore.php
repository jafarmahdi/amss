<?php

declare(strict_types=1);

namespace App\Support;

class UploadStore
{
    public static function saveAssetDocuments(int $assetId, array $files): array
    {
        return self::saveDocuments(base_path('storage/uploads/assets/' . $assetId), 'storage/uploads/assets/' . $assetId, $files);
    }

    public static function saveMovementDocuments(int $movementId, array $files): array
    {
        return self::saveDocuments(base_path('storage/uploads/movements/' . $movementId), 'storage/uploads/movements/' . $movementId, $files);
    }

    public static function saveEmployeeAppointmentDocument(int $employeeId, array $files): ?array
    {
        $documents = self::saveDocuments(
            base_path('storage/uploads/employees/' . $employeeId),
            'storage/uploads/employees/' . $employeeId,
            $files
        );

        return $documents[0] ?? null;
    }

    public static function saveAdministrativeLibraryFiles(string $entryId, array $files): array
    {
        return self::saveDocuments(
            base_path('storage/app/administrative-forms/custom/' . $entryId),
            'storage/app/administrative-forms/custom/' . $entryId,
            $files
        );
    }

    private static function saveDocuments(string $directory, string $relativeDirectory, array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create upload directory: ' . $directory);
        }

        @chmod(base_path('storage/uploads'), 0777);
        @chmod(dirname($directory), 0777);
        @chmod($directory, 0777);

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

        $saved = [];

        foreach ($names as $index => $originalName) {
            $originalName = trim((string) $originalName);
            $tmpName = (string) ($tmpNames[$index] ?? '');
            $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);

            if ($error !== UPLOAD_ERR_OK || $originalName === '' || $tmpName === '') {
                continue;
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'document';
            $targetName = time() . '_' . $index . '_' . $safeName;
            $targetPath = $directory . '/' . $targetName;

            $moved = is_uploaded_file($tmpName)
                ? move_uploaded_file($tmpName, $targetPath)
                : @rename($tmpName, $targetPath);

            if (!$moved) {
                app_log('error', 'Failed to move uploaded file', [
                    'directory' => $directory,
                    'relative_directory' => $relativeDirectory,
                    'original_name' => $originalName,
                    'tmp_name' => $tmpName,
                    'target_path' => $targetPath,
                ]);
                continue;
            }

            @chmod($targetPath, 0666);

            $saved[] = [
                'name' => $originalName,
                'path' => $relativeDirectory . '/' . $targetName,
            ];
        }

        return $saved;
    }

    public static function deleteAssetDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $path = base_path((string) ($document['path'] ?? ''));
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function deleteFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $fullPath = base_path($path);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
