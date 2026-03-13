<?php
$monthlyMax = max(array_map(static fn (array $row): int => (int) $row['total'], $charts['monthly_purchases'] ?: [['total' => 0]]));
$monthlyPoints = [];
$monthlyCount = count($charts['monthly_purchases']);
foreach ($charts['monthly_purchases'] as $index => $row) {
    $x = $monthlyCount > 1 ? (18 + (($index / ($monthlyCount - 1)) * 264)) : 150;
    $ratio = $monthlyMax > 0 ? ((int) $row['total'] / $monthlyMax) : 0;
    $y = 116 - ($ratio * 86);
    $monthlyPoints[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
}

$statusMax = max(array_map(static fn (array $row): int => (int) $row['total'], $charts['status_mix'] ?: [['total' => 0]]));
$branchMax = max(array_map(static fn (array $row): int => (int) $row['total'], $charts['branch_distribution'] ?: [['total' => 0]]));
$assignmentRate = $stats['total_assets'] > 0 ? round(($overview['kpis']['assigned_assets'] / $stats['total_assets']) * 100, 1) : 0;
$storageRate = $stats['total_assets'] > 0 ? round(($stats['in_storage'] / $stats['total_assets']) * 100, 1) : 0;
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-speedometer2"></i> <?= e(__('dashboard.command_center', 'Operations Command Center')) ?></div>
            <h1 class="mb-2"><?= e(__('nav.dashboard', 'Dashboard')) ?></h1>
            <p class="text-muted mb-0" style="max-width: 760px;"><?= e(__('dashboard.desc', 'Live operational summary for assets, assignments, branches, and procurement.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('assets.create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(__('dashboard.register_asset', 'Register Asset')) ?></a>
            <a href="<?= e(route('storage.index')) ?>" class="btn btn-outline-secondary"><i class="bi bi-box-seam"></i> <?= e(__('dashboard.open_storage', 'Open Storage')) ?></a>
            <a href="<?= e(route('requests.index')) ?>" class="btn btn-outline-secondary"><i class="bi bi-diagram-3"></i> <?= e(__('dashboard.review_requests', 'Review Requests')) ?></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4 position-relative" style="z-index:1;">
        <span class="surface-chip"><i class="bi bi-buildings"></i> <?= e((string) $overview['kpis']['total_branches']) ?> <?= e(__('nav.branches', 'Branches')) ?></span>
        <span class="surface-chip"><i class="bi bi-people"></i> <?= e((string) $overview['kpis']['total_employees']) ?> <?= e(__('dashboard.active_employees', 'Active employees')) ?></span>
        <span class="surface-chip"><i class="bi bi-box-arrow-in-down"></i> <?= e((string) $overview['kpis']['purchased_this_month']) ?> <?= e(__('dashboard.purchased_this_month', 'Purchased this month')) ?></span>
        <span class="surface-chip"><i class="bi bi-shield-exclamation"></i> <?= e((string) $overview['kpis']['expiring_warranties']) ?> <?= e(__('dashboard.warranties_expiring', 'warranties expiring in 30 days')) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="ops-kpi-card">
            <div class="ops-kpi-label"><?= e(__('dashboard.total_assets', 'Total assets')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $stats['total_assets']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $overview['kpis']['assigned_assets']) ?> <?= e(__('dashboard.assigned', 'assigned')) ?> / <?= e((string) $overview['kpis']['unassigned_assets']) ?> <?= e(__('dashboard.unassigned', 'unassigned')) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="ops-kpi-card">
            <div class="ops-kpi-label"><?= e(__('dashboard.asset_coverage', 'Assignment coverage')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $assignmentRate) ?>%</div>
            <div class="ops-kpi-meta"><?= e((string) $overview['kpis']['deployed_assets']) ?> <?= e(__('dashboard.deployed', 'deployed')) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="ops-kpi-card">
            <div class="ops-kpi-label"><?= e(__('dashboard.storage_readiness', 'Storage readiness')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $storageRate) ?>%</div>
            <div class="ops-kpi-meta"><?= e((string) $stats['in_storage']) ?> <?= e(__('storage.in_stock', 'In storage')) ?> / <?= e((string) $overview['kpis']['received_assets']) ?> <?= e(__('dashboard.received_waiting', 'received and waiting')) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="ops-kpi-card">
            <div class="ops-kpi-label"><?= e(__('dashboard.need_attention', 'Need attention')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $stats['attention_needed']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $overview['kpis']['ordered_assets']) ?> <?= e(__('stage.ordered', 'Ordered')) ?> / <?= e((string) $overview['kpis']['received_assets']) ?> <?= e(__('stage.received', 'Received')) ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <div>
                        <h5><?= e(__('dashboard.purchase_trend', 'Purchase Trend')) ?></h5>
                        <div class="small text-muted"><?= e(__('dashboard.last_six_months', 'Last 6 months')) ?></div>
                    </div>
                    <span class="surface-chip"><i class="bi bi-graph-up-arrow"></i> <?= e(__('dashboard.operational_snapshot', 'Operational snapshot')) ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($charts['monthly_purchases'] !== []): ?>
                    <svg viewBox="0 0 300 130" class="w-100" role="img" aria-label="<?= e(__('dashboard.purchase_trend', 'Purchase Trend')) ?>">
                        <polyline points="<?= e(implode(' ', $monthlyPoints)) ?>" fill="none" stroke="#0f62fe" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <?php foreach ($charts['monthly_purchases'] as $index => $row): ?>
                            <?php
                            $x = $monthlyCount > 1 ? (18 + (($index / ($monthlyCount - 1)) * 264)) : 150;
                            $ratio = $monthlyMax > 0 ? ((int) $row['total'] / $monthlyMax) : 0;
                            $y = 116 - ($ratio * 86);
                            ?>
                            <circle cx="<?= e(number_format($x, 2, '.', '')) ?>" cy="<?= e(number_format($y, 2, '.', '')) ?>" r="4.5" fill="#0f62fe"></circle>
                        <?php endforeach; ?>
                    </svg>
                    <div class="row g-2 mt-3">
                        <?php foreach ($charts['monthly_purchases'] as $row): ?>
                            <div class="col-6 col-md">
                                <div class="ops-subtle-item">
                                    <span><?= e($row['label']) ?></span>
                                    <strong><?= e((string) $row['total']) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('dashboard.no_chart_data', 'No chart data available yet.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="row g-3 h-100">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="ops-panel-title">
                            <h5><?= e(__('dashboard.procurement_pipeline', 'Procurement Pipeline')) ?></h5>
                            <span class="small text-muted"><?= e(__('dashboard.asset_register_health', 'Asset register health')) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="ops-subtle-list">
                            <div class="ops-subtle-item"><span><?= e(__('stage.ordered', 'Ordered')) ?></span><strong><?= e((string) $overview['kpis']['ordered_assets']) ?></strong></div>
                            <div class="ops-subtle-item"><span><?= e(__('stage.received', 'Received')) ?></span><strong><?= e((string) $overview['kpis']['received_assets']) ?></strong></div>
                            <div class="ops-subtle-item"><span><?= e(__('stage.deployed', 'Deployed')) ?></span><strong><?= e((string) $overview['kpis']['deployed_assets']) ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0"><?= e(__('dashboard.status_mix', 'Status Mix')) ?></h6></div>
                    <div class="card-body">
                        <?php if ($charts['status_mix'] !== []): ?>
                            <?php foreach ($charts['status_mix'] as $row): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= e(__('status.' . $row['label'], ucfirst($row['label']))) ?></span>
                                        <strong><?= e((string) $row['total']) ?></strong>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= e((string) ($statusMax > 0 ? round(((int) $row['total'] / $statusMax) * 100, 2) : 0)) ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0"><?= e(__('dashboard.no_chart_data', 'No chart data available yet.')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0"><?= e(__('dashboard.branch_distribution', 'Branch Distribution')) ?></h6></div>
                    <div class="card-body">
                        <?php if ($charts['branch_distribution'] !== []): ?>
                            <?php foreach ($charts['branch_distribution'] as $row): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= e($row['label']) ?></span>
                                        <strong><?= e((string) $row['total']) ?></strong>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= e((string) ($branchMax > 0 ? round(((int) $row['total'] / $branchMax) * 100, 2) : 0)) ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0"><?= e(__('dashboard.no_chart_data', 'No chart data available yet.')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('dashboard.branch_capacity', 'Branch Capacity')) ?></h5>
                    <span class="small text-muted"><?= e(__('dashboard.operational_snapshot', 'Operational snapshot')) ?></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('common.branch', 'Branch')) ?></th>
                            <th><?= e(__('nav.assets', 'Assets')) ?></th>
                            <th><?= e(__('nav.employees', 'Employees')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($overview['branch_load'] !== []): ?>
                            <?php foreach ($overview['branch_load'] as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($row['branch']) ?></td>
                                    <td><?= e((string) $row['asset_total']) ?></td>
                                    <td><?= e((string) $row['employee_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-muted py-5"><?= e(__('dashboard.no_branch_data', 'No branch data available.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('dashboard.warranty_alerts', 'Warranty Alerts')) ?></h5>
                    <span class="small text-muted"><?= e(__('dashboard.need_attention', 'Need attention')) ?></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('nav.assets', 'Assets')) ?></th>
                            <th><?= e(__('common.branch', 'Branch')) ?></th>
                            <th><?= e(__('dashboard.expiry', 'Expiry')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($overview['warranty_alerts'] !== []): ?>
                            <?php foreach ($overview['warranty_alerts'] as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?= e(route('assets.show', ['id' => $row['id']])) ?>" class="fw-semibold"><?= e($row['name']) ?></a>
                                        <div class="small text-muted"><?= e($row['tag']) ?></div>
                                    </td>
                                    <td><?= e($row['branch']) ?></td>
                                    <td>
                                        <?= e($row['warranty_expiry']) ?>
                                        <div class="small text-muted"><?= e((string) $row['days_left']) ?> <?= e(__('notifications.days_left', 'days left')) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-muted py-5"><?= e(__('dashboard.no_warranty_data', 'No warranty dates available.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('dashboard.recent_assets', 'Recent Assets')) ?></h5>
                    <span class="small text-muted"><?= e(__('dashboard.asset_register_health', 'Asset register health')) ?></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('nav.assets', 'Assets')) ?></th>
                            <th><?= e(__('common.branch', 'Branch')) ?></th>
                            <th><?= e(__('common.status', 'Status')) ?></th>
                            <th><?= e(__('assets.purchase_date', 'Purchase Date')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($overview['recent_assets'] !== []): ?>
                            <?php foreach ($overview['recent_assets'] as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?= e(route('assets.show', ['id' => $row['id']])) ?>" class="fw-semibold"><?= e($row['name']) ?></a>
                                        <div class="small text-muted"><?= e($row['tag']) ?> • <?= e(__('stage.' . $row['procurement_stage'], ucfirst($row['procurement_stage']))) ?></div>
                                    </td>
                                    <td><?= e($row['branch']) ?></td>
                                    <td><?= e(__('status.' . $row['status'], ucfirst($row['status']))) ?></td>
                                    <td><?= e($row['purchase_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted py-5"><?= e(__('dashboard.no_assets', 'No assets available.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100 ops-table-card">
            <div class="card-header">
                <div class="ops-panel-title">
                    <h5><?= e(__('dashboard.recent_movements', 'Recent Movements')) ?></h5>
                    <span class="small text-muted"><?= e(__('dashboard.recent_activity', 'Recent activity')) ?></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= e(__('nav.assets', 'Assets')) ?></th>
                            <th><?= e(__('dashboard.from', 'From')) ?></th>
                            <th><?= e(__('dashboard.to', 'To')) ?></th>
                            <th><?= e(__('dashboard.date', 'Date')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentMovements !== []): ?>
                            <?php foreach ($recentMovements as $movement): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($movement['asset']) ?></td>
                                    <td><?= e($movement['from']) ?></td>
                                    <td><?= e($movement['to']) ?></td>
                                    <td><?= e($movement['date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted py-5"><?= e(__('dashboard.no_movements', 'No movement history available.')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
