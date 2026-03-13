<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\SimplePdf;

class StorageController extends Controller
{
    public function index(): void
    {
        $overview = DataRepository::storageOverview();
        $filters = $this->filtersFromRequest();
        $groups = $this->filteredGroups($overview, $filters);

        $this->render('storage.index', [
            'pageTitle' => __('storage.page_title', 'Storage'),
            'storageItems' => $groups['storage_stock'],
            'sparePartsStock' => $groups['spare_parts_stock'],
            'licenseStock' => $groups['license_stock'],
            'brokenAssets' => $groups['broken_assets'],
            'repairQueue' => $groups['repair_queue'],
            'receivedAssets' => $groups['received_assets'],
            'summary' => $groups['summary'],
            'filters' => $filters,
            'branchOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::branches()),
            'categoryOptions' => array_map(static fn (array $row): string => (string) $row['name'], DataRepository::categories()),
        ]);
    }

    public function export(): void
    {
        $overview = DataRepository::storageOverview();
        $filters = $this->filtersFromRequest();
        $groups = $this->filteredGroups($overview, $filters);
        $rows = $this->flatRows($groups);
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xls')));
        DataRepository::logAudit('export', 'storage', null, null, ['format' => $format, 'filters' => $filters]);

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="alnahala-storage.pdf"');
            echo SimplePdf::fromLines(__('storage.title', 'Storage Inventory'), $this->pdfLines($rows, $filters));
            return;
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-storage.xls"');
        echo $this->excelMarkup($rows, $filters);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'section' => trim((string) ($_GET['section'] ?? '')),
            'branch' => trim((string) ($_GET['branch'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
        ];
    }

    private function filteredGroups(array $overview, array $filters): array
    {
        $storageStock = array_values(array_filter($overview['storage_stock'], function (array $row) use ($filters): bool {
            if ($filters['section'] !== '' && $filters['section'] !== 'storage') {
                return false;
            }

            $haystack = strtolower(implode(' ', [
                (string) $row['item'],
                (string) ($row['category'] ?? ''),
                (string) ($row['branch'] ?? ''),
                (string) $row['status'],
            ]));
            if ($filters['q'] !== '' && !str_contains($haystack, strtolower($filters['q']))) {
                return false;
            }

            if ($filters['branch'] !== '' && (string) ($row['branch'] ?? '') !== $filters['branch']) {
                return false;
            }

            if ($filters['category'] !== '' && (string) ($row['category'] ?? '') !== $filters['category']) {
                return false;
            }

            return true;
        }));

        $sparePartsStock = array_values(array_filter($overview['spare_parts_stock'] ?? [], function (array $row) use ($filters): bool {
            if ($filters['section'] !== '' && $filters['section'] !== 'spare_parts') {
                return false;
            }

            $haystack = strtolower(implode(' ', [
                (string) ($row['name'] ?? ''),
                (string) ($row['part_number'] ?? ''),
                (string) ($row['category'] ?? ''),
                (string) ($row['location'] ?? ''),
            ]));
            if ($filters['q'] !== '' && !str_contains($haystack, strtolower($filters['q']))) {
                return false;
            }

            if ($filters['branch'] !== '' && (string) ($row['location'] ?? '') !== $filters['branch']) {
                return false;
            }

            if ($filters['category'] !== '' && (string) ($row['category'] ?? '') !== $filters['category']) {
                return false;
            }

            return true;
        }));

        $licenseStock = array_values(array_filter($overview['license_stock'] ?? [], function (array $row) use ($filters): bool {
            if ($filters['section'] !== '' && $filters['section'] !== 'licenses') {
                return false;
            }

            $haystack = strtolower(implode(' ', [
                (string) ($row['product_name'] ?? ''),
                (string) ($row['vendor_name'] ?? ''),
                (string) ($row['license_type'] ?? ''),
                (string) ($row['status'] ?? ''),
            ]));
            if ($filters['q'] !== '' && !str_contains($haystack, strtolower($filters['q']))) {
                return false;
            }

            return true;
        }));

        $filterAssetRow = function (array $row, string $section) use ($filters): bool {
            if ($filters['section'] !== '' && $filters['section'] !== $section) {
                return false;
            }

            $haystack = strtolower(implode(' ', [
                (string) ($row['name'] ?? ''),
                (string) ($row['tag'] ?? ''),
                (string) ($row['category'] ?? ''),
                (string) ($row['branch'] ?? ''),
                (string) ($row['status'] ?? ''),
            ]));

            if ($filters['q'] !== '' && !str_contains($haystack, strtolower($filters['q']))) {
                return false;
            }

            if ($filters['branch'] !== '' && (string) ($row['branch'] ?? '') !== $filters['branch']) {
                return false;
            }

            if ($filters['category'] !== '' && (string) ($row['category'] ?? '') !== $filters['category']) {
                return false;
            }

            return true;
        };

        $broken = array_values(array_filter($overview['broken_assets'], fn (array $row): bool => $filterAssetRow($row, 'broken')));
        $repair = array_values(array_filter($overview['repair_queue'], fn (array $row): bool => $filterAssetRow($row, 'repair')));
        $received = array_values(array_filter($overview['received_assets'], fn (array $row): bool => $filterAssetRow($row, 'received')));

        return [
            'storage_stock' => $storageStock,
            'spare_parts_stock' => $sparePartsStock,
            'license_stock' => $licenseStock,
            'broken_assets' => $broken,
            'repair_queue' => $repair,
            'received_assets' => $received,
            'summary' => [
                'storage_count' => array_sum(array_map(static fn (array $row): int => (int) $row['qty'], $storageStock)),
                'storage_groups' => count($storageStock),
                'spare_parts_count' => count($sparePartsStock),
                'spare_parts_quantity' => array_sum(array_map(static fn (array $row): int => (int) ($row['quantity'] ?? 0), $sparePartsStock)),
                'license_count' => count($licenseStock),
                'license_available_seats' => array_sum(array_map(static fn (array $row): int => (int) ($row['available_seats'] ?? 0), $licenseStock)),
                'broken_count' => count($broken),
                'repair_count' => count($repair),
                'received_count' => count($received),
            ],
        ];
    }

    private function flatRows(array $groups): array
    {
        $rows = [];
        foreach ($groups['storage_stock'] as $row) {
            $rows[] = [
                'section' => __('storage.in_stock', 'In storage'),
                'name' => (string) $row['item'],
                'tag' => '',
                'category' => (string) ($row['category'] ?? ''),
                'branch' => (string) ($row['branch'] ?? ''),
                'status' => (string) $row['status'],
                'quantity' => (string) $row['qty'],
            ];
        }
        foreach ($groups['spare_parts_stock'] as $row) {
            $rows[] = [
                'section' => __('nav.spare_parts', 'Spare Parts'),
                'name' => (string) $row['name'],
                'tag' => (string) $row['part_number'],
                'category' => (string) $row['category'],
                'branch' => (string) $row['location'],
                'status' => !empty($row['low_stock']) ? __('spare_parts.low_stock', 'Low Stock') : __('spare_parts.healthy_stock', 'Healthy Stock'),
                'quantity' => (string) $row['quantity'],
            ];
        }
        foreach ($groups['license_stock'] as $row) {
            $rows[] = [
                'section' => __('nav.licenses', 'Licenses'),
                'name' => (string) $row['product_name'],
                'tag' => (string) $row['license_type'],
                'category' => (string) $row['vendor_name'],
                'branch' => '',
                'status' => (string) $row['status'],
                'quantity' => (string) $row['available_seats'],
            ];
        }
        foreach (['broken_assets' => __('storage.broken', 'Broken devices'), 'repair_queue' => __('storage.repair', 'Repair queue'), 'received_assets' => __('storage.received', 'Received not deployed')] as $key => $label) {
            foreach ($groups[$key] as $row) {
                $rows[] = [
                    'section' => $label,
                    'name' => (string) $row['name'],
                    'tag' => (string) $row['tag'],
                    'category' => (string) $row['category'],
                    'branch' => (string) $row['branch'],
                    'status' => (string) $row['status'],
                    'quantity' => '1',
                ];
            }
        }
        return $rows;
    }

    private function pdfLines(array $rows, array $filters): array
    {
        $lines = [
            'Alnahala AMS',
            __('storage.title', 'Storage Inventory'),
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
            $lines[] = sprintf('%d. %s | %s | %s | %s | %s', $index + 1, $row['section'], $row['name'], $row['category'], $row['branch'], $row['status']);
        }
        return $lines;
    }

    private function excelMarkup(array $rows, array $filters): string
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
            <h2><?= e(__('storage.title', 'Storage Inventory')) ?></h2>
            <p>Generated <?= e(date('Y-m-d H:i')) ?> | <?= e($this->filterSummary($filters)) ?></p>
            <table>
                <tr>
                    <th><?= e(__('storage.section', 'Section')) ?></th>
                    <th><?= e(__('storage.asset', 'Asset')) ?></th>
                    <th><?= e(__('storage.tag', 'Tag')) ?></th>
                    <th><?= e(__('storage.category', 'Category')) ?></th>
                    <th><?= e(__('storage.branch', 'Branch')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th><?= e(__('storage.quantity', 'Quantity')) ?></th>
                </tr>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7">No matching records.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['section']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['tag']) ?></td>
                            <td><?= e($row['category']) ?></td>
                            <td><?= e($row['branch']) ?></td>
                            <td><?= e(__('status.' . $row['status'], $row['status'])) ?></td>
                            <td><?= e($row['quantity']) ?></td>
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
