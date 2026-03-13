<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\SimplePdf;

class ReportController extends Controller
{
    public function index(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $sections = $this->sections();
        $filters = [
            'section' => trim((string) ($_GET['section'] ?? 'assets')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'branch_id' => trim((string) ($_GET['branch_id'] ?? '')),
            'category_id' => trim((string) ($_GET['category_id'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'from_date' => trim((string) ($_GET['from_date'] ?? '')),
            'to_date' => trim((string) ($_GET['to_date'] ?? '')),
        ];
        if (!isset($sections[$filters['section']])) {
            $filters['section'] = 'assets';
        }

        $columnOptions = $this->columnOptions();
        $selectedColumns = array_values(array_intersect(
            (array) ($_GET['columns'] ?? $this->defaultColumns($filters['section'])),
            array_keys($columnOptions[$filters['section']])
        ));
        if ($selectedColumns === []) {
            $selectedColumns = $this->defaultColumns($filters['section']);
        }

        $searchResults = DataRepository::globalSearch($query, $filters);

        $this->render('reports.index', [
            'pageTitle' => __('nav.reports', 'Reports'),
            'reports' => DataRepository::reports(),
            'summary' => DataRepository::reportSummary(),
            'query' => $query,
            'filters' => $filters,
            'searchResults' => $searchResults,
            'branches' => DataRepository::branches(),
            'categories' => DataRepository::categories(),
            'roles' => ['admin', 'it_manager', 'technician', 'finance', 'viewer'],
            'sections' => $sections,
            'columnOptions' => $columnOptions,
            'selectedColumns' => $selectedColumns,
            'selectedSectionRows' => $searchResults[$filters['section']] ?? [],
        ]);
    }

    public function export(): void
    {
        if (!can('reports.export')) {
            http_response_code(403);
            echo __('auth.forbidden', 'You do not have permission to view this page.');
            return;
        }

        $format = strtolower(trim((string) ($_GET['format'] ?? 'xls')));
        $query = trim((string) ($_GET['q'] ?? ''));
        $sections = $this->sections();
        $filters = [
            'section' => trim((string) ($_GET['section'] ?? 'assets')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'branch_id' => trim((string) ($_GET['branch_id'] ?? '')),
            'category_id' => trim((string) ($_GET['category_id'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'from_date' => trim((string) ($_GET['from_date'] ?? '')),
            'to_date' => trim((string) ($_GET['to_date'] ?? '')),
        ];
        if (!isset($sections[$filters['section']])) {
            $filters['section'] = 'assets';
        }

        $columnOptions = $this->columnOptions();
        $selectedColumns = array_values(array_intersect(
            (array) ($_GET['columns'] ?? $this->defaultColumns($filters['section'])),
            array_keys($columnOptions[$filters['section']])
        ));
        if ($selectedColumns === []) {
            $selectedColumns = $this->defaultColumns($filters['section']);
        }

        $summary = DataRepository::reportSummary();
        $rows = DataRepository::globalSearch($query, $filters)[$filters['section']] ?? [];

        DataRepository::recordReport('system_report_' . $format, array_merge(['q' => $query, 'columns' => $selectedColumns], $filters), '');
        DataRepository::logAudit('export', 'reports', null, null, ['format' => $format, 'query' => $query, 'columns' => $selectedColumns] + $filters);

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="alnahala-report.pdf"');
            echo SimplePdf::fromLines('Alnahala AMS Report', $this->pdfLines($summary, $rows, $query, $filters['section'], $selectedColumns, $filters));
            return;
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-report.xls"');
        echo $this->excelMarkup($summary, $rows, $query, $filters['section'], $selectedColumns, $filters);
    }

    private function pdfLines(array $summary, array $rows, string $query, string $section, array $selectedColumns, array $filters): array
    {
        $sectionLabel = $this->sections()[$section] ?? $section;
        $columnOptions = $this->columnOptions()[$section] ?? [];

        $lines = [
            'Professional export generated from Alnahala AMS',
            'Generated at: ' . date('Y-m-d H:i'),
            'Search query: ' . ($query !== '' ? $query : 'All records'),
            'Section: ' . $sectionLabel,
            'Applied filters: ' . $this->filterSummary($filters),
            '',
            'Executive Summary',
            'Total assets: ' . $summary['assets'],
            'Total employees: ' . $summary['employees'],
            'Total licenses: ' . $summary['licenses'],
            'Total branches: ' . $summary['branches'],
            'Total categories: ' . $summary['categories'],
            'Total movements: ' . $summary['movements'],
            'Assets with docs: ' . $summary['assets_with_docs'],
            '',
            'Selected Columns',
            implode(' | ', array_map(static fn (string $column): string => $columnOptions[$column] ?? $column, $selectedColumns)),
            '',
            'Result Rows: ' . count($rows),
        ];

        if ($rows === []) {
            $lines[] = 'No matching records.';
            return $lines;
        }

        foreach (array_slice($rows, 0, 30) as $index => $row) {
            $label = implode(' | ', array_map(
                fn (string $column): string => ($columnOptions[$column] ?? $column) . ': ' . $this->displayValue((string) ($row[$column] ?? ''), $column),
                $selectedColumns
            ));
            $lines[] = ($index + 1) . '. ' . $label;
        }

        return $lines;
    }

    private function excelMarkup(array $summary, array $rows, string $query, string $section, array $selectedColumns, array $filters): string
    {
        $sectionLabel = $this->sections()[$section] ?? $section;
        $columnOptions = $this->columnOptions()[$section] ?? [];

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; color: #1f2937; }
                .sheet-title { font-size: 22px; font-weight: 700; color: #0f172a; }
                .sheet-subtitle { color: #64748b; font-size: 12px; margin-bottom: 12px; }
                table { border-collapse: collapse; width: 100%; margin-top: 14px; }
                th, td { border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top; }
                th { background: #dbeafe; color: #0f172a; font-weight: 700; }
                .meta th { width: 180px; background: #eff6ff; }
                .summary th { background: #e0f2fe; }
                .section-bar { background: #0f172a; color: #ffffff; font-weight: 700; }
                .empty { color: #94a3b8; font-style: italic; }
            </style>
        </head>
        <body>
            <div class="sheet-title">Alnahala AMS Report</div>
            <div class="sheet-subtitle">Generated <?= e(date('Y-m-d H:i')) ?> | Section: <?= e($sectionLabel) ?></div>

            <table class="meta">
                <tr><th>Search query</th><td><?= e($query !== '' ? $query : 'All records') ?></td></tr>
                <tr><th>Applied filters</th><td><?= e($this->filterSummary($filters)) ?></td></tr>
                <tr><th>Selected columns</th><td><?= e(implode(', ', array_map(static fn (string $column): string => $columnOptions[$column] ?? $column, $selectedColumns))) ?></td></tr>
            </table>

            <table class="summary">
                <tr>
                    <th>Total assets</th>
                    <th>Total employees</th>
                    <th>Total licenses</th>
                    <th>Total branches</th>
                    <th>Total categories</th>
                    <th>Total movements</th>
                    <th>Assets with docs</th>
                </tr>
                <tr>
                    <td><?= e((string) $summary['assets']) ?></td>
                    <td><?= e((string) $summary['employees']) ?></td>
                    <td><?= e((string) $summary['licenses']) ?></td>
                    <td><?= e((string) $summary['branches']) ?></td>
                    <td><?= e((string) $summary['categories']) ?></td>
                    <td><?= e((string) $summary['movements']) ?></td>
                    <td><?= e((string) $summary['assets_with_docs']) ?></td>
                </tr>
            </table>

            <table>
                <tr><th class="section-bar" colspan="<?= e((string) max(count($selectedColumns), 1)) ?>"><?= e($sectionLabel) ?> Results</th></tr>
                <tr>
                    <?php foreach ($selectedColumns as $column): ?>
                        <th><?= e($columnOptions[$column] ?? $column) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= e((string) count($selectedColumns)) ?>" class="empty">No matching records.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($selectedColumns as $column): ?>
                                <td><?= e($this->displayValue((string) ($row[$column] ?? ''), $column)) ?></td>
                            <?php endforeach; ?>
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
            if ($value === '') {
                continue;
            }
            $parts[] = $key . ': ' . $value;
        }

        return $parts === [] ? 'No extra filters' : implode(' | ', $parts);
    }

    private function displayValue(string $value, string $column): string
    {
        if ($value === '') {
            return '-';
        }

        if ($column === 'status') {
            return __('status.' . $value, ucfirst($value));
        }

        return $value;
    }

    private function sections(): array
    {
        return [
            'assets' => __('nav.assets', 'Assets'),
            'employees' => __('nav.employees', 'Employees'),
            'licenses' => __('nav.licenses', 'Licenses'),
            'branches' => __('nav.branches', 'Branches'),
            'categories' => __('nav.categories', 'Categories'),
            'users' => __('nav.users', 'Users'),
            'movements' => __('reports.movements', 'Movements'),
        ];
    }

    private function columnOptions(): array
    {
        return [
            'assets' => [
                'name' => 'Name',
                'tag' => 'Tag',
                'status' => 'Status',
                'branch' => 'Branch',
                'category' => 'Category',
            ],
            'employees' => [
                'name' => 'Name',
                'employee_code' => 'Code',
                'status' => 'Status',
                'department' => 'Department',
                'branch' => 'Branch',
            ],
            'licenses' => [
                'product_name' => 'Product',
                'vendor_name' => 'Vendor',
                'license_type' => 'Type',
                'status' => 'Status',
                'seats_total' => 'Total Seats',
                'seats_used' => 'Used Seats',
                'expiry_date' => 'Expiry Date',
            ],
            'branches' => [
                'name' => 'Name',
                'type' => 'Type',
                'address' => 'Address',
            ],
            'categories' => [
                'name' => 'Name',
                'description' => 'Description',
            ],
            'users' => [
                'name' => 'Name',
                'email' => 'Email',
                'role' => 'Role',
                'status' => 'Status',
            ],
            'movements' => [
                'asset_name' => 'Asset',
                'from_branch' => 'From',
                'to_branch' => 'To',
                'moved_at' => 'Date',
                'notes' => 'Notes',
            ],
        ];
    }

    private function defaultColumns(string $section): array
    {
        return array_keys($this->columnOptions()[$section] ?? []);
    }
}
