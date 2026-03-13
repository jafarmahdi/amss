<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class ToolsController extends Controller
{
    public function index(): void
    {
        $this->render('tools.index', [
            'pageTitle' => __('nav.tools', 'Import / Export'),
            'datasets' => $this->datasets(),
        ]);
    }

    public function export(string $dataset): void
    {
        $datasets = $this->datasets();
        if (!isset($datasets[$dataset])) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Not Found']);
            return;
        }

        $rows = $this->rowsForDataset($dataset);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-' . $dataset . '-export.csv"');
        echo $this->csvFromRows($datasets[$dataset]['headers'], $rows);
        DataRepository::logAudit('export', $dataset, null, null, ['tool' => 'import_export']);
    }

    public function template(string $dataset): void
    {
        $datasets = $this->datasets();
        if (!isset($datasets[$dataset])) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Not Found']);
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-' . $dataset . '-template.csv"');
        echo $this->csvFromRows($datasets[$dataset]['headers'], [$datasets[$dataset]['sample']]);
    }

    public function import(string $dataset): array
    {
        $datasets = $this->datasets();
        if (!isset($datasets[$dataset])) {
            flash('error', __('tools.invalid_dataset', 'Invalid import dataset.'));
            return $this->redirect('tools.index');
        }

        $file = $_FILES['import_file'] ?? null;
        $tmpName = is_array($file) ? ($file['tmp_name'] ?? '') : '';
        if (!is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName)) {
            flash('error', __('tools.file_required', 'Choose a CSV file to import.'));
            return $this->redirect('tools.index');
        }

        $rows = $this->parseCsv($tmpName);
        if ($rows === []) {
            flash('error', __('tools.empty_import', 'The uploaded CSV file is empty or invalid.'));
            return $this->redirect('tools.index');
        }

        $result = match ($dataset) {
            'branches' => DataRepository::importBranches($rows),
            'categories' => DataRepository::importCategories($rows),
            'employees' => DataRepository::importEmployees($rows),
            'spare_parts' => DataRepository::importSpareParts($rows),
            'assets' => DataRepository::importAssets($rows),
            default => ['created' => 0, 'updated' => 0, 'skipped' => 0],
        };

        DataRepository::logAudit('import', $dataset, null, null, $result + ['tool' => 'import_export']);
        flash(
            'status',
            __('tools.import_done', 'Import completed.') . ' ' .
            __('tools.created', 'Created') . ': ' . $result['created'] . ', ' .
            __('tools.updated', 'Updated') . ': ' . $result['updated'] . ', ' .
            __('tools.skipped', 'Skipped') . ': ' . $result['skipped']
        );

        return $this->redirect('tools.index');
    }

    private function datasets(): array
    {
        return [
            'branches' => [
                'title' => __('nav.branches', 'Branches'),
                'headers' => ['name', 'type', 'address'],
                'sample' => ['name' => 'Baghdad Branch', 'type' => 'Branch', 'address' => 'Baghdad'],
            ],
            'categories' => [
                'title' => __('nav.categories', 'Categories'),
                'headers' => ['name', 'description'],
                'sample' => ['name' => 'Laptop', 'description' => 'Portable employee device'],
            ],
            'employees' => [
                'title' => __('nav.employees', 'Employees'),
                'headers' => ['name', 'employee_code', 'company_name', 'project_name', 'company_email', 'fingerprint_id', 'job_title', 'phone', 'branch', 'status'],
                'sample' => ['name' => 'Sara Ahmed', 'employee_code' => 'EMP-1001', 'company_name' => 'Alnahala', 'project_name' => 'HQ Rollout', 'company_email' => 'sara.ahmed', 'fingerprint_id' => 'FP-1001', 'job_title' => 'Support Engineer', 'phone' => '0770000000', 'branch' => 'Baghdad Branch', 'status' => 'active'],
            ],
            'spare_parts' => [
                'title' => __('nav.spare_parts', 'Spare Parts'),
                'headers' => ['name', 'part_number', 'category', 'vendor_name', 'location', 'quantity', 'min_quantity', 'compatible_with', 'notes'],
                'sample' => ['name' => 'Laptop Charger 65W', 'part_number' => 'CHG-65W', 'category' => 'Power', 'vendor_name' => 'Tech Source', 'location' => 'Central Storage', 'quantity' => '12', 'min_quantity' => '4', 'compatible_with' => 'Laptop', 'notes' => 'Standard replacement charger'],
            ],
            'assets' => [
                'title' => __('nav.assets', 'Assets'),
                'headers' => ['name', 'category', 'brand', 'model', 'serial_number', 'purchase_date', 'warranty_expiry', 'procurement_stage', 'vendor_name', 'invoice_number', 'status', 'location', 'assigned_to', 'notes'],
                'sample' => ['name' => 'Dell Latitude 5440', 'category' => 'Laptop', 'brand' => 'Dell', 'model' => 'Latitude 5440', 'serial_number' => 'DL-5440-001', 'purchase_date' => '2026-03-01', 'warranty_expiry' => '2029-03-01', 'procurement_stage' => 'deployed', 'vendor_name' => 'Tech Source', 'invoice_number' => 'INV-100', 'status' => 'active', 'location' => 'Baghdad Branch', 'assigned_to' => 'Sara Ahmed', 'notes' => 'Imported asset'],
            ],
        ];
    }

    private function rowsForDataset(string $dataset): array
    {
        return match ($dataset) {
            'branches' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'type' => (string) $row['type'],
                'address' => (string) $row['address'],
            ], DataRepository::branches()),
            'categories' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'description' => (string) $row['description'],
            ], DataRepository::categories()),
            'employees' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'employee_code' => (string) $row['employee_code'],
                'company_name' => (string) ($row['company_name'] ?? ''),
                'project_name' => (string) ($row['project_name'] ?? ''),
                'company_email' => (string) ($row['company_email'] ?? ''),
                'fingerprint_id' => (string) ($row['fingerprint_id'] ?? ''),
                'job_title' => (string) $row['job_title'],
                'phone' => (string) $row['phone'],
                'branch' => (string) $row['branch_name'],
                'status' => (string) $row['status'],
            ], DataRepository::employees()),
            'spare_parts' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'part_number' => (string) $row['part_number'],
                'category' => (string) $row['category'],
                'vendor_name' => (string) $row['vendor_name'],
                'location' => (string) $row['location'],
                'quantity' => (string) $row['quantity'],
                'min_quantity' => (string) $row['min_quantity'],
                'compatible_with' => (string) $row['compatible_with'],
                'notes' => (string) $row['notes'],
            ], DataRepository::spareParts()),
            'assets' => array_map(static fn (array $row): array => [
                'name' => (string) $row['name'],
                'category' => (string) $row['category'],
                'brand' => (string) $row['brand'],
                'model' => (string) $row['model'],
                'serial_number' => (string) $row['serial_number'],
                'purchase_date' => (string) $row['purchase_date'],
                'warranty_expiry' => (string) $row['warranty_expiry'],
                'procurement_stage' => (string) $row['procurement_stage'],
                'vendor_name' => (string) $row['vendor_name'],
                'invoice_number' => (string) $row['invoice_number'],
                'status' => (string) $row['status'],
                'location' => (string) $row['location'],
                'assigned_to' => (string) $row['assigned_to'],
                'notes' => (string) $row['notes'],
            ], DataRepository::assets()),
            default => [],
        };
    }

    private function csvFromRows(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = (string) ($row[$header] ?? '');
            }
            fputcsv($stream, $line);
        }
        rewind($stream);
        return (string) stream_get_contents($stream);
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            return [];
        }

        $headers = array_map(static fn ($value): string => trim((string) $value), $headers);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($data[$index] ?? ''));
            }
            if (array_filter($row, static fn (string $value): bool => $value !== '') !== []) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return $rows;
    }
}
