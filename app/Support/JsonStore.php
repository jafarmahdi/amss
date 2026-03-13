<?php

declare(strict_types=1);

namespace App\Support;

class JsonStore
{
    public static function all(string $name, array $defaults): array
    {
        self::ensureDirectory();

        $file = self::path($name);
        if (!is_file($file)) {
            self::write($name, $defaults);
            return $defaults;
        }

        $contents = file_get_contents($file);
        $decoded = json_decode($contents ?: '[]', true);

        return is_array($decoded) ? $decoded : $defaults;
    }

    public static function write(string $name, array $records): void
    {
        self::ensureDirectory();
        $file = self::path($name);
        $written = file_put_contents($file, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($written === false) {
            throw new \RuntimeException('Unable to write data file: ' . $file);
        }

        @chmod($file, 0666);
    }

    private static function ensureDirectory(): void
    {
        $directory = base_path('storage/data');
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create storage directory: ' . $directory);
        }

        @chmod(base_path('storage'), 0777);
        @chmod($directory, 0777);
    }

    private static function path(string $name): string
    {
        return base_path('storage/data/' . $name . '.json');
    }
}
