<?php
$auditExportBase = route('audit.export');
$auditExportXls = $auditExportBase . '&' . http_build_query(array_merge($filters, ['format' => 'xls']));
$auditExportPdf = $auditExportBase . '&' . http_build_query(array_merge($filters, ['format' => 'pdf']));
$auditExportCsv = $auditExportBase . '&' . http_build_query(array_merge($filters, ['format' => 'csv']));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="badge-soft mb-3"><i class="bi bi-journal-text"></i> <?= e(__('audit.title', 'Audit Logs')) ?></div>
        <h2 class="mb-1"><?= e(__('audit.title', 'Audit Logs')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('audit.desc', 'Track who changed records, exports, and permission settings.')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e($auditExportXls) ?>" class="btn btn-outline-secondary"><?= e(__('common.export_excel', 'Export Excel')) ?></a>
        <a href="<?= e($auditExportPdf) ?>" class="btn btn-outline-secondary"><?= e(__('common.export_pdf', 'Export PDF')) ?></a>
        <a href="<?= e($auditExportCsv) ?>" class="btn btn-outline-secondary"><?= e(__('common.export_csv', 'Export CSV')) ?></a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('common.total', 'Total')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['total']) ?></div></div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('audit.create_count', 'Creates')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['create_count']) ?></div></div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('audit.update_count', 'Updates')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['update_count']) ?></div></div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('audit.delete_count', 'Deletes')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['delete_count']) ?></div></div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('audit.export_count', 'Exports')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['export_count']) ?></div></div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('audit.modules', 'Modules')) ?></div><div class="fs-3 fw-semibold"><?= e((string) $summary['modules_count']) ?></div></div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(route('audit.index')) ?>" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" name="q" value="<?= e($filters['q']) ?>" class="form-control" placeholder="<?= e(__('audit.search_placeholder', 'Actor, action, module, record name')) ?>">
            </div>
            <div class="col-lg-2">
                <label class="form-label"><?= e(__('audit.actor', 'Actor')) ?></label>
                <select name="actor_id" class="form-select">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($options['actors'] as $actor): ?>
                        <option value="<?= e((string) $actor['id']) ?>" <?= $filters['actor_id'] === (string) $actor['id'] ? 'selected' : '' ?>><?= e($actor['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label"><?= e(__('audit.action', 'Action')) ?></label>
                <select name="action" class="form-select">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($options['actions'] as $action): ?>
                        <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label"><?= e(__('audit.module', 'Module')) ?></label>
                <select name="table_name" class="form-select">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($options['tables'] as $table): ?>
                        <option value="<?= e($table) ?>" <?= $filters['table_name'] === $table ? 'selected' : '' ?>><?= e($table) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1">
                <label class="form-label"><?= e(__('common.from', 'From')) ?></label>
                <input type="date" name="from_date" value="<?= e($filters['from_date']) ?>" class="form-control">
            </div>
            <div class="col-lg-1">
                <label class="form-label"><?= e(__('common.to', 'To')) ?></label>
                <input type="date" name="to_date" value="<?= e($filters['to_date']) ?>" class="form-control">
            </div>
            <div class="col-lg-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><?= e(__('common.filter', 'Filter')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th><?= e(__('audit.date', 'Date')) ?></th>
                    <th><?= e(__('audit.actor', 'Actor')) ?></th>
                    <th><?= e(__('audit.action', 'Action')) ?></th>
                    <th><?= e(__('audit.module', 'Module')) ?></th>
                    <th><?= e(__('audit.record', 'Record')) ?></th>
                    <th><?= e(__('audit.details', 'Details')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs !== []): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e($log['created_at']) ?></td>
                            <td><?= e($log['actor']) ?></td>
                            <td><span class="badge text-bg-light"><?= e($log['action']) ?></span></td>
                            <td><?= e($log['table_name']) ?></td>
                            <td><?= e($log['record_name'] !== '' ? $log['record_name'] : (string) ($log['record_id'] ?? '')) ?></td>
                            <td style="min-width: 280px;">
                                <details>
                                    <summary><?= e(__('audit.view_payload', 'View payload')) ?></summary>
                                    <div class="small text-muted mt-2"><?= e(__('audit.old_values', 'Old values')) ?></div>
                                    <pre class="small bg-body-tertiary rounded-3 p-2"><?= e($log['old_values'] !== '' ? $log['old_values'] : '{}') ?></pre>
                                    <div class="small text-muted"><?= e(__('audit.new_values', 'New values')) ?></div>
                                    <pre class="small bg-body-tertiary rounded-3 p-2"><?= e($log['new_values'] !== '' ? $log['new_values'] : '{}') ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-muted"><?= e(__('audit.empty', 'No audit entries yet.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
