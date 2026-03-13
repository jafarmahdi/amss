<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="badge-soft mb-3"><i class="bi bi-bar-chart-line"></i> <?= e(__('nav.reports', 'Reports')) ?></div>
        <h2 class="mb-1"><?= e(__('nav.reports', 'Reports')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('reports.description', 'Operational summaries plus a global search across the whole system.')) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (can('reports.export')): ?>
            <?php $exportParams = ['route' => '/reports/export', 'format' => 'xls', 'q' => $query] + $filters + ['columns' => $selectedColumns]; ?>
            <a href="<?= e(base_url() . '/index.php?' . http_build_query($exportParams)) ?>" class="btn btn-outline-secondary"><?= e(__('reports.export_excel', 'Export Excel')) ?></a>
            <?php $pdfParams = ['route' => '/reports/export', 'format' => 'pdf', 'q' => $query] + $filters + ['columns' => $selectedColumns]; ?>
            <a href="<?= e(base_url() . '/index.php?' . http_build_query($pdfParams)) ?>" class="btn btn-outline-secondary"><?= e(__('reports.export_pdf', 'Export PDF')) ?></a>
        <?php endif; ?>
        <a href="<?= e(route('api.docs')) ?>" class="btn btn-outline-secondary"><?= e(__('api.docs', 'API Documentation')) ?></a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.assets', 'Assets')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['assets']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.employees', 'Employees')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['employees']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.licenses', 'Licenses')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['licenses']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.branches', 'Branches')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['branches']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('nav.categories', 'Categories')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['categories']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('reports.movements', 'Movements')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['movements']) ?></div></div></div></div>
    <div class="col-md-2"><div class="card h-100"><div class="card-body"><div class="text-muted small"><?= e(__('reports.assets_with_docs', 'Assets with docs')) ?></div><div class="fs-4 fw-semibold"><?= e((string) $summary['assets_with_docs']) ?></div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('reports.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/reports">
            <div class="col-md-4">
                <label for="q" class="form-label fw-semibold"><?= e(__('reports.search', 'Search everything')) ?></label>
                <input type="text" id="q" name="q" class="form-control" value="<?= e($query) ?>" placeholder="<?= e(__('reports.search_placeholder', 'Asset tag, employee, branch, category, user, movement notes...')) ?>">
            </div>
            <div class="col-md-2">
                <label for="section" class="form-label fw-semibold">Section</label>
                <select id="section" name="section" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($sections as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $filters['section'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label fw-semibold"><?= e(__('common.status', 'Status')) ?></label>
                <select id="status" name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach (['active', 'repair', 'broken', 'storage', 'archived', 'inactive', 'renewal_due', 'expired'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="branch_id" class="form-label fw-semibold"><?= e(__('common.branch', 'Branch')) ?></label>
                <select id="branch_id" name="branch_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= e((string) $branch['id']) ?>" <?= $filters['branch_id'] === (string) $branch['id'] ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category_id" class="form-label fw-semibold"><?= e(__('nav.categories', 'Categories')) ?></label>
                <select id="category_id" name="category_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="role" class="form-label fw-semibold"><?= e(__('common.role', 'Role')) ?></label>
                <select id="role" name="role" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="from_date" class="form-label fw-semibold">From</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="<?= e($filters['from_date']) ?>">
            </div>
            <div class="col-md-2">
                <label for="to_date" class="form-label fw-semibold">To</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="<?= e($filters['to_date']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Columns</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($columnOptions[$filters['section']] as $columnKey => $columnLabel): ?>
                        <label class="surface-chip">
                            <input type="checkbox" name="columns[]" value="<?= e($columnKey) ?>" <?= in_array($columnKey, $selectedColumns, true) ? 'checked' : '' ?>>
                            <span><?= e($columnLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><?= e(__('common.search', 'Search')) ?></button>
            </div>
            <div class="col-md-2">
                <a href="<?= e(route('reports.index')) ?>" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($query !== '' || array_filter($filters) !== []): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e($sections[$filters['section']]) ?> <span class="text-muted">(<?= e((string) count($selectedSectionRows)) ?>)</span></h5>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <?php foreach ($selectedColumns as $column): ?>
                            <th><?= e($columnOptions[$filters['section']][$column] ?? $column) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($selectedSectionRows !== []): ?>
                        <?php foreach ($selectedSectionRows as $row): ?>
                            <tr>
                                <?php foreach ($selectedColumns as $column): ?>
                                    <td>
                                        <?php if ($filters['section'] === 'assets' && $column === 'name'): ?>
                                            <a href="<?= e(route('assets.show', ['id' => $row['id']])) ?>"><?= e((string) ($row[$column] ?? '')) ?></a>
                                        <?php elseif (in_array($column, ['status'], true)): ?>
                                            <?= e(__('status.' . ((string) ($row[$column] ?? '')), ucfirst((string) ($row[$column] ?? '')))) ?>
                                        <?php elseif ($filters['section'] === 'licenses' && $column === 'product_name'): ?>
                                            <a href="<?= e(route('licenses.edit', ['id' => $row['id']])) ?>"><?= e((string) ($row[$column] ?? '')) ?></a>
                                        <?php elseif ($filters['section'] === 'licenses' && $column === 'license_type'): ?>
                                            <?= e(__('licenses.type_' . ((string) ($row[$column] ?? '')), ucfirst((string) ($row[$column] ?? '')))) ?>
                                        <?php else: ?>
                                            <?= e((string) ($row[$column] ?? '')) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= e((string) count($selectedColumns)) ?>" class="text-muted"><?= e(__('reports.no_results', 'No results in this section.')) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= e(__('reports.generated', 'Generated reports')) ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('reports.report', 'Report')) ?></th>
                    <th><?= e(__('reports.last_generated', 'Last Generated')) ?></th>
                    <th><?= e(__('reports.format', 'Format')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reports !== []): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= e($report['name']) ?></td>
                            <td><?= e($report['updated_at']) ?></td>
                            <td><?= e($report['format']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-muted"><?= e(__('reports.empty', 'No generated reports yet.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
