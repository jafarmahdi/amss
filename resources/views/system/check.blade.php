<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('system.title', 'System Check')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('system.desc', 'Runtime verification for routing, PHP, and database readiness.')) ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($checks as $check): ?>
        <div class="col-md-6">
            <div class="card h-100 border-<?= $check['status'] ? 'success' : 'danger' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title mb-1"><?= e($check['label']) ?></h5>
                        <span class="badge text-bg-<?= $check['status'] ? 'success' : 'danger' ?>"><?= e($check['status'] ? __('system.ok', 'OK') : __('system.fail', 'Fail')) ?></span>
                    </div>
                    <p class="text-muted mb-0"><?= e($check['detail']) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= e(__('system.db_details', 'Database Details')) ?></h5>
    </div>
    <div class="card-body">
        <p class="mb-2"><strong><?= e(__('system.database', 'Database')) ?>:</strong> <?= e($dbStatus['database']) ?></p>
        <p class="mb-2"><strong><?= e(__('system.connection', 'Connection')) ?>:</strong> <?= e($dbStatus['connected'] ? __('system.connected', 'Connected') : __('system.not_connected', 'Not connected')) ?></p>
        <p class="mb-2"><strong><?= e(__('system.visible_tables', 'Visible tables')) ?>:</strong> <?= e((string) count($dbStatus['tables'])) ?></p>
        <p class="mb-3"><strong><?= e(__('system.missing_tables', 'Missing required tables')) ?>:</strong> <?= e($dbStatus['missing_tables'] === [] ? __('system.none', 'None') : implode(', ', $dbStatus['missing_tables'])) ?></p>

        <?php if ($dbStatus['tables'] !== []): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('common.table', 'Table')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbStatus['tables'] as $table): ?>
                            <tr>
                                <td><?= e($table) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
