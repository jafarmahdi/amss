<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\SimplePdf;

class AuditController extends Controller
{
    public function index(): void
    {
        if (!can('audit.view')) {
            flash('error', __('auth.forbidden', 'You do not have permission to view this page.'));
            header('Location: ' . route('dashboard'));
            exit;
        }

        $filters = $this->filtersFromRequest();

        $this->render('audit.index', [
            'pageTitle' => __('audit.title', 'Audit Logs'),
            'logs' => DataRepository::auditLogs($filters, 300),
            'summary' => DataRepository::auditSummary($filters),
            'filters' => $filters,
            'options' => DataRepository::auditFilterOptions(),
        ]);
    }

    public function export(): void
    {
        if (!can('audit.view')) {
            http_response_code(403);
            echo __('auth.forbidden', 'You do not have permission to view this page.');
            return;
        }

        $filters = $this->filtersFromRequest();
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xls')));
        $logs = DataRepository::auditLogs($filters, 1000);
        $summary = DataRepository::auditSummary($filters);
        DataRepository::logAudit('export', 'audit_logs', null, null, ['format' => $format, 'filters' => $filters]);

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="alnahala-audit-log.pdf"');
            echo SimplePdf::fromLines(__('audit.title', 'Audit Logs'), $this->pdfLines($logs, $summary, $filters));
            return;
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="alnahala-audit-log.csv"');
            echo $this->csvMarkup($logs);
            return;
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="alnahala-audit-log.xls"');
        echo $this->excelMarkup($logs, $summary, $filters);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'actor_id' => trim((string) ($_GET['actor_id'] ?? '')),
            'action' => trim((string) ($_GET['action'] ?? '')),
            'table_name' => trim((string) ($_GET['table_name'] ?? '')),
            'from_date' => trim((string) ($_GET['from_date'] ?? '')),
            'to_date' => trim((string) ($_GET['to_date'] ?? '')),
        ];
    }

    private function pdfLines(array $logs, array $summary, array $filters): array
    {
        $lines = [
            'Alnahala AMS',
            __('audit.title', 'Audit Logs'),
            'Generated at: ' . date('Y-m-d H:i'),
            'Filters: ' . $this->filterSummary($filters),
            'Rows: ' . count($logs),
            'Summary: total=' . $summary['total'] . ', create=' . $summary['create_count'] . ', update=' . $summary['update_count'] . ', delete=' . $summary['delete_count'] . ', export=' . $summary['export_count'],
            '',
        ];

        if ($logs === []) {
            $lines[] = __('audit.empty', 'No audit entries yet.');
            return $lines;
        }

        foreach ($logs as $index => $log) {
            $lines[] = sprintf(
                '%d. %s | %s | %s | %s | %s',
                $index + 1,
                (string) $log['created_at'],
                (string) $log['actor'],
                (string) $log['action'],
                (string) $log['table_name'],
                (string) ($log['record_name'] !== '' ? $log['record_name'] : (string) ($log['record_id'] ?? '-'))
            );
        }

        return $lines;
    }

    private function csvMarkup(array $logs): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        fputcsv($stream, ['Date', 'Actor', 'Action', 'Module', 'Record', 'Old Values', 'New Values']);
        foreach ($logs as $log) {
            fputcsv($stream, [
                (string) $log['created_at'],
                (string) $log['actor'],
                (string) $log['action'],
                (string) $log['table_name'],
                (string) ($log['record_name'] !== '' ? $log['record_name'] : (string) ($log['record_id'] ?? '-')),
                (string) $log['old_values'],
                (string) $log['new_values'],
            ]);
        }
        rewind($stream);
        return (string) stream_get_contents($stream);
    }

    private function excelMarkup(array $logs, array $summary, array $filters): string
    {
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; color: #172033; }
                table { border-collapse: collapse; width: 100%; margin-top: 14px; }
                th, td { border: 1px solid #d5dceb; padding: 8px 10px; vertical-align: top; }
                th { background: #dfeeff; text-align: left; }
                .meta th { width: 180px; background: #f4f8ff; }
                .summary td { background: #f8fbff; font-weight: 700; }
            </style>
        </head>
        <body>
            <h2>Alnahala AMS Audit Center</h2>
            <table class="meta">
                <tr><th>Generated at</th><td><?= e(date('Y-m-d H:i')) ?></td></tr>
                <tr><th>Applied filters</th><td><?= e($this->filterSummary($filters)) ?></td></tr>
            </table>
            <table class="summary">
                <tr>
                    <th>Total</th>
                    <th>Create</th>
                    <th>Update</th>
                    <th>Delete</th>
                    <th>Export</th>
                    <th>Actors</th>
                    <th>Modules</th>
                </tr>
                <tr>
                    <td><?= e((string) $summary['total']) ?></td>
                    <td><?= e((string) $summary['create_count']) ?></td>
                    <td><?= e((string) $summary['update_count']) ?></td>
                    <td><?= e((string) $summary['delete_count']) ?></td>
                    <td><?= e((string) $summary['export_count']) ?></td>
                    <td><?= e((string) $summary['actors_count']) ?></td>
                    <td><?= e((string) $summary['modules_count']) ?></td>
                </tr>
            </table>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Record</th>
                    <th>Old Values</th>
                    <th>New Values</th>
                </tr>
                <?php if ($logs === []): ?>
                    <tr><td colspan="7"><?= e(__('audit.empty', 'No audit entries yet.')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e((string) $log['created_at']) ?></td>
                            <td><?= e((string) $log['actor']) ?></td>
                            <td><?= e((string) $log['action']) ?></td>
                            <td><?= e((string) $log['table_name']) ?></td>
                            <td><?= e((string) ($log['record_name'] !== '' ? $log['record_name'] : (string) ($log['record_id'] ?? '-'))) ?></td>
                            <td><?= e((string) $log['old_values']) ?></td>
                            <td><?= e((string) $log['new_values']) ?></td>
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

        return $parts === [] ? __('common.all_records', 'All records') : implode(' | ', $parts);
    }
}
