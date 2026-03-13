<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\DataRepository;

$path = '/Users/jafaralhassani/Library/CloudStorage/OneDrive-AlNahala/IT HQ/ASSET (1).xlsx';

if (!is_file($path)) {
    fwrite(STDERR, "Workbook not found: {$path}\n");
    exit(1);
}

$sheets = workbook_rows($path);
$branchName = 'IT';
$pdo = Database::connect();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Database connection failed in CLI importer.\n");
    exit(1);
}

$branchStatement = $pdo->prepare('SELECT id FROM branches WHERE name = :name LIMIT 1');
$branchStatement->execute(['name' => $branchName]);
if ($branchStatement->fetchColumn() === false) {
    DataRepository::createBranch([
        'name' => $branchName,
        'type' => 'HQ',
        'address' => 'Imported from workbook',
    ]);
}

$summary = [
    'employees_created' => 0,
    'categories_created' => 0,
    'assets_created' => 0,
    'sheets' => [],
];

$categoryCache = [];
$employeeCache = [];

$createCategory = static function (string $name) use (&$summary, &$categoryCache): string {
    $name = normalize_text($name);
    if ($name === '') {
        $name = 'General';
    }
    if (isset($categoryCache[$name])) {
        return $name;
    }
    if (DataRepository::categoryIdByName($name) === null) {
        DataRepository::createCategory([
            'name' => $name,
            'description' => 'Imported from ASSET (1).xlsx',
        ]);
        $summary['categories_created']++;
    }
    $categoryCache[$name] = true;
    return $name;
};

$ensureEmployee = static function (string $name, string $department = '') use (&$summary, &$employeeCache, $branchName): string {
    $name = normalize_text($name);
    if ($name === '' || strtoupper($name) === 'HQ') {
        return '';
    }
    if (isset($employeeCache[$name])) {
        return $name;
    }
    if (DataRepository::employeeIdByName($name) === null) {
        DataRepository::createEmployee([
            'name' => $name,
            'employee_code' => '',
            'department' => normalize_text($department),
            'job_title' => '',
            'phone' => '',
            'branch_id' => DataRepository::branchIdByName($branchName) ?? '',
            'status' => 'active',
        ]);
        $summary['employees_created']++;
    }
    $employeeCache[$name] = true;
    return $name;
};

$createAsset = static function (array $payload) use (&$summary): void {
    DataRepository::createAsset($payload);
    $summary['assets_created']++;
};

foreach (($sheets['COMPUTER'] ?? []) as $index => $row) {
    if ($index < 2) {
        continue;
    }
    $employeeName = normalize_text($row[0] ?? '');
    $office = normalize_text($row[1] ?? '');
    $computerName = normalize_text($row[2] ?? '');
    if ($employeeName === '' || $computerName === '') {
        continue;
    }

    $category = $createCategory('Computer');
    $assignedTo = $ensureEmployee($employeeName, $office);
    $notes = build_notes([
        'Source: COMPUTER sheet',
        $office !== '' ? 'Office: ' . $office : '',
        normalize_text($row[3] ?? '') !== '' ? '365 License: ' . normalize_text($row[3]) : '',
        normalize_text($row[4] ?? '') !== '' ? 'Kaspersky: ' . normalize_text($row[4]) : '',
        normalize_text($row[5] ?? '') !== '' ? 'UPS: ' . normalize_text($row[5]) : '',
        normalize_text($row[6] ?? '') !== '' ? 'Other: ' . normalize_text($row[6]) : '',
        normalize_text($row[7] ?? '') !== '' ? 'CPU: ' . normalize_text($row[7]) : '',
        normalize_text($row[8] ?? '') !== '' ? 'RAM: ' . normalize_text($row[8]) : '',
        'Imported from ASSET (1).xlsx',
    ]);

    $createAsset([
        'name' => $computerName,
        'category' => $category,
        'brand' => '',
        'model' => '',
        'serial_number' => '',
        'purchase_date' => '',
        'warranty_expiry' => '',
        'procurement_stage' => 'deployed',
        'vendor_name' => '',
        'invoice_number' => '',
        'status' => 'active',
        'location' => $branchName,
        'assigned_to' => $assignedTo,
        'notes' => $notes,
    ]);
}
$summary['sheets']['COMPUTER'] = count($sheets['COMPUTER'] ?? []);

foreach (($sheets['STORAGE'] ?? []) as $index => $row) {
    if ($index === 0) {
        continue;
    }
    $assetName = normalize_text($row[0] ?? '');
    $type = normalize_text($row[1] ?? '');
    $quantity = parse_quantity($row[2] ?? '');
    $description = normalize_text($row[3] ?? '');
    if ($assetName === '' || $quantity <= 0) {
        continue;
    }

    $category = $createCategory($type !== '' ? $type : 'Storage Item');
    for ($i = 1; $i <= $quantity; $i++) {
        $createAsset([
            'name' => $assetName,
            'category' => $category,
            'brand' => '',
            'model' => '',
            'serial_number' => '',
            'purchase_date' => '',
            'warranty_expiry' => '',
            'procurement_stage' => 'received',
            'vendor_name' => '',
            'invoice_number' => '',
            'status' => 'storage',
            'location' => $branchName,
            'assigned_to' => '',
            'notes' => build_notes([
                'Source: STORAGE sheet',
                $description !== '' ? 'Description: ' . $description : '',
                'Imported quantity item ' . $i . ' of ' . $quantity,
                'Imported from ASSET (1).xlsx',
            ]),
        ]);
    }
}
$summary['sheets']['STORAGE'] = count($sheets['STORAGE'] ?? []);

foreach (($sheets['CAMERA'] ?? []) as $index => $row) {
    if ($index === 0) {
        continue;
    }
    $assetName = normalize_text($row[0] ?? '');
    $quantity = parse_quantity($row[1] ?? '');
    $description = normalize_text($row[2] ?? '');
    if ($assetName === '' || $quantity <= 0) {
        continue;
    }

    $category = $createCategory('Camera');
    for ($i = 1; $i <= $quantity; $i++) {
        $createAsset([
            'name' => $assetName,
            'category' => $category,
            'brand' => '',
            'model' => '',
            'serial_number' => '',
            'purchase_date' => '',
            'warranty_expiry' => '',
            'procurement_stage' => 'received',
            'vendor_name' => '',
            'invoice_number' => '',
            'status' => 'storage',
            'location' => $branchName,
            'assigned_to' => '',
            'notes' => build_notes([
                'Source: CAMERA sheet',
                $description !== '' ? 'Description: ' . $description : '',
                'Imported quantity item ' . $i . ' of ' . $quantity,
                'Imported from ASSET (1).xlsx',
            ]),
        ]);
    }
}
$summary['sheets']['CAMERA'] = count($sheets['CAMERA'] ?? []);

foreach (($sheets['NETWORK DEVICE'] ?? []) as $row) {
    $assetName = normalize_text($row[3] ?? '');
    $description = normalize_text($row[7] ?? '');
    if ($assetName === '') {
        continue;
    }

    $category = $createCategory('Network Device');
    $createAsset([
        'name' => $assetName,
        'category' => $category,
        'brand' => '',
        'model' => '',
        'serial_number' => '',
        'purchase_date' => '',
        'warranty_expiry' => '',
        'procurement_stage' => 'deployed',
        'vendor_name' => '',
        'invoice_number' => '',
        'status' => 'active',
        'location' => $branchName,
        'assigned_to' => '',
        'notes' => build_notes([
            'Source: NETWORK DEVICE sheet',
            $description !== '' ? $description : '',
            'Imported from ASSET (1).xlsx',
        ]),
    ]);
}
$summary['sheets']['NETWORK DEVICE'] = count($sheets['NETWORK DEVICE'] ?? []);

foreach (($sheets['Out Of Support'] ?? []) as $index => $row) {
    if ($index === 0) {
        continue;
    }
    $assetName = normalize_text($row[0] ?? '');
    $quantity = parse_quantity($row[1] ?? '');
    $description = normalize_text($row[2] ?? '');
    if ($assetName === '' || $quantity <= 0) {
        continue;
    }

    $category = $createCategory('Out Of Support');
    for ($i = 1; $i <= $quantity; $i++) {
        $createAsset([
            'name' => $assetName,
            'category' => $category,
            'brand' => '',
            'model' => '',
            'serial_number' => '',
            'purchase_date' => '',
            'warranty_expiry' => '',
            'procurement_stage' => 'received',
            'vendor_name' => '',
            'invoice_number' => '',
            'status' => 'broken',
            'location' => $branchName,
            'assigned_to' => '',
            'notes' => build_notes([
                'Source: Out Of Support sheet',
                $description !== '' ? 'Description: ' . $description : '',
                'Imported quantity item ' . $i . ' of ' . $quantity,
                'Imported from ASSET (1).xlsx',
            ]),
        ]);
    }
}
$summary['sheets']['Out Of Support'] = count($sheets['Out Of Support'] ?? []);

DataRepository::logAudit('import', 'assets', null, null, $summary + ['source' => basename($path)]);

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function workbook_rows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Unable to open workbook.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sharedRoot = simplexml_load_string($sharedXml);
        if ($sharedRoot instanceof SimpleXMLElement) {
            $main = $sharedRoot->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($main->si as $item) {
                $parts = [];
                foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                    $parts[] = (string) $textNode;
                }
                $sharedStrings[] = trim(implode('', $parts));
            }
        }
    }

    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($relsXml === false || $workbookXml === false) {
        throw new RuntimeException('Workbook relationships are missing.');
    }

    $relsRoot = simplexml_load_string($relsXml);
    $workbookRoot = simplexml_load_string($workbookXml);
    if (!$relsRoot instanceof SimpleXMLElement || !$workbookRoot instanceof SimpleXMLElement) {
        throw new RuntimeException('Workbook XML is invalid.');
    }

    $relsRoot->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
    $workbookRoot->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbookRoot->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    $targets = [];
    foreach ($relsRoot->xpath('//r:Relationship') ?: [] as $relationship) {
        $attributes = $relationship->attributes();
        $targets[(string) $attributes['Id']] = (string) $attributes['Target'];
    }

    $result = [];
    foreach ($workbookRoot->xpath('//a:sheets/a:sheet') ?: [] as $sheet) {
        $attrs = $sheet->attributes('r', true);
        $rid = (string) $attrs['id'];
        $name = (string) $sheet['name'];
        $target = $targets[$rid] ?? null;
        if ($target === null) {
            continue;
        }
        $sheetXml = $zip->getFromName('xl/' . ltrim($target, '/'));
        if ($sheetXml === false) {
            continue;
        }
        $sheetRoot = simplexml_load_string($sheetXml);
        if (!$sheetRoot instanceof SimpleXMLElement) {
            continue;
        }
        $rows = [];
        $sheetData = $sheetRoot->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->sheetData;
        foreach ($sheetData->row as $row) {
            $values = [];
            foreach ($row->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
                $type = (string) $cell['t'];
                $value = (string) ($cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->v ?? '');
                if ($type === 's' && $value !== '') {
                    $value = $sharedStrings[(int) $value] ?? $value;
                }
                $values[] = normalize_text($value);
            }
            if (array_filter($values, static fn (string $value): bool => $value !== '') !== []) {
                $rows[] = $values;
            }
        }
        $result[$name] = $rows;
    }

    $zip->close();
    return $result;
}

function normalize_text(mixed $value): string
{
    $text = trim(str_replace(["\xc2\xa0", "\t"], ' ', (string) $value));
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
}

function parse_quantity(mixed $value): int
{
    $text = strtolower(normalize_text($value));
    if ($text === '') {
        return 0;
    }
    if (preg_match('/(\d+)\s*x\s*(\d+)/', $text, $matches) === 1) {
        return (int) $matches[1] * (int) $matches[2];
    }
    if (preg_match('/\d+/', $text, $matches) === 1) {
        return (int) $matches[0];
    }
    return 0;
}

function build_notes(array $parts): string
{
    $parts = array_values(array_filter(array_map('normalize_text', $parts), static fn (string $value): bool => $value !== ''));
    return implode(' | ', $parts);
}
