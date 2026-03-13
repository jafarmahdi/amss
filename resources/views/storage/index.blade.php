<?php
$visibleRecords = count($storageItems) + count($sparePartsStock) + count($licenseStock) + count($brokenAssets) + count($repairQueue) + count($receivedAssets);
$topStorageItem = $storageItems[0] ?? null;
$storageTotalQty = array_sum(array_map(static fn (array $row): int => (int) ($row['qty'] ?? 0), $storageItems));
$storageMaxQty = max(array_map(static fn (array $row): int => (int) ($row['qty'] ?? 0), $storageItems ?: [['qty' => 0]]));
$spareMaxQty = max(array_map(static fn (array $row): int => (int) ($row['quantity'] ?? 0), $sparePartsStock ?: [['quantity' => 0]]));
$licenseMaxQty = max(array_map(static fn (array $row): int => (int) ($row['available_seats'] ?? 0), $licenseStock ?: [['available_seats' => 0]]));
$issueCount = (int) $summary['broken_count'] + (int) $summary['repair_count'];
$activeFilterCount = count(array_filter($filters, static fn (string $value): bool => $value !== ''));
$previewLimit = 6;
$activeTab = in_array($filters['section'], ['spare_parts', 'licenses', 'received', 'broken', 'repair'], true) ? $filters['section'] : 'spare_parts';
?>

<div class="ops-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 position-relative" style="z-index:1;">
        <div>
            <div class="badge-soft mb-3"><i class="bi bi-box-seam"></i> <?= e(__('storage.title', 'Storage Inventory')) ?></div>
            <h2 class="mb-2"><?= e(__('storage.title', 'Storage Inventory')) ?></h2>
            <p class="text-muted mb-0" style="max-width:760px;"><?= e(__('storage.desc', 'Warehouse stock levels and readiness status.')) ?></p>
        </div>
        <div class="app-toolbar-actions">
            <a href="<?= e(route('assets.create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(__('dashboard.register_asset', 'Register Asset')) ?></a>
            <a id="storage-export-xls" href="<?= e(base_url() . '/index.php?' . http_build_query(['route' => '/storage/export', 'format' => 'xls'] + $filters)) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> <?= e(__('reports.export_excel', 'Export Excel')) ?></a>
            <a id="storage-export-pdf" href="<?= e(base_url() . '/index.php?' . http_build_query(['route' => '/storage/export', 'format' => 'pdf'] + $filters)) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> <?= e(__('reports.export_pdf', 'Export PDF')) ?></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4 position-relative" style="z-index:1;">
        <span class="surface-chip"><i class="bi bi-boxes"></i> <?= e((string) $summary['storage_count']) ?> <?= e(__('storage.in_stock', 'In storage')) ?></span>
        <span class="surface-chip"><i class="bi bi-tools"></i> <?= e((string) $summary['spare_parts_quantity']) ?> <?= e(__('nav.spare_parts', 'Spare Parts')) ?></span>
        <span class="surface-chip"><i class="bi bi-key"></i> <?= e((string) $summary['license_available_seats']) ?> <?= e(__('licenses.available_seats', 'Available Seats')) ?></span>
        <span class="surface-chip"><i class="bi bi-exclamation-triangle"></i> <?= e((string) $issueCount) ?> <?= e(__('storage.issues_total', 'items need attention')) ?></span>
        <span class="surface-chip"><i class="bi bi-funnel"></i> <?= e((string) $visibleRecords) ?> <?= e(__('storage.visible_records', 'visible records')) ?></span>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('storage.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/storage">
            <div class="col-md-4">
                <label for="storage_live_search" class="form-label fw-semibold"><?= e(__('reports.search', 'Search everything')) ?></label>
                <input type="text" name="q" id="storage_live_search" value="<?= e($filters['q']) ?>" class="form-control" placeholder="<?= e(__('storage.search_placeholder', 'Search asset, tag, category, branch, status...')) ?>">
            </div>
            <div class="col-md-2">
                <label for="section" class="form-label fw-semibold"><?= e(__('storage.section', 'Section')) ?></label>
                <select name="section" id="section" class="form-select">
                    <option value=""><?= e(__('storage.all_sections', 'All sections')) ?></option>
                    <option value="storage" <?= $filters['section'] === 'storage' ? 'selected' : '' ?>><?= e(__('storage.in_stock', 'In storage')) ?></option>
                    <option value="spare_parts" <?= $filters['section'] === 'spare_parts' ? 'selected' : '' ?>><?= e(__('nav.spare_parts', 'Spare Parts')) ?></option>
                    <option value="licenses" <?= $filters['section'] === 'licenses' ? 'selected' : '' ?>><?= e(__('nav.licenses', 'Licenses')) ?></option>
                    <option value="broken" <?= $filters['section'] === 'broken' ? 'selected' : '' ?>><?= e(__('storage.broken', 'Broken devices')) ?></option>
                    <option value="repair" <?= $filters['section'] === 'repair' ? 'selected' : '' ?>><?= e(__('storage.repair', 'Repair queue')) ?></option>
                    <option value="received" <?= $filters['section'] === 'received' ? 'selected' : '' ?>><?= e(__('storage.received', 'Received not deployed')) ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="branch" class="form-label fw-semibold"><?= e(__('storage.branch', 'Branch')) ?></label>
                <select name="branch" id="branch" class="form-select">
                    <option value=""><?= e(__('assets.all_branches', 'All branches')) ?></option>
                    <?php foreach ($branchOptions as $branchOption): ?>
                        <option value="<?= e($branchOption) ?>" <?= $filters['branch'] === $branchOption ? 'selected' : '' ?>><?= e($branchOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label fw-semibold"><?= e(__('storage.category', 'Category')) ?></label>
                <select name="category" id="category" class="form-select">
                    <option value=""><?= e(__('assets.all_categories', 'All categories')) ?></option>
                    <?php foreach ($categoryOptions as $categoryOption): ?>
                        <option value="<?= e($categoryOption) ?>" <?= $filters['category'] === $categoryOption ? 'selected' : '' ?>><?= e($categoryOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('storage.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <span class="surface-chip"><i class="bi bi-filter"></i> <?= e((string) $activeFilterCount) ?> <?= e(__('storage.active_filters', 'active filters')) ?></span>
                <?php if ($topStorageItem !== null): ?>
                    <span class="surface-chip"><i class="bi bi-box2"></i> <?= e(__('storage.top_item', 'Top item')) ?>: <?= e($topStorageItem['item']) ?> (<?= e((string) $topStorageItem['qty']) ?>)</span>
                <?php endif; ?>
                <span class="surface-chip"><i class="bi bi-collection"></i> <?= e((string) $summary['storage_groups']) ?> <?= e(__('storage.unique_items', 'unique storage items')) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('storage.in_stock', 'In storage')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $summary['storage_count']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $summary['storage_groups']) ?> <?= e(__('storage.unique_items', 'unique storage items')) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('nav.spare_parts', 'Spare Parts')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $summary['spare_parts_quantity']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $summary['spare_parts_count']) ?> <?= e(__('spare_parts.visible_results', 'visible spare parts')) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('nav.licenses', 'Licenses')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $summary['license_available_seats']) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $summary['license_count']) ?> <?= e(__('licenses.visible_results', 'visible licenses')) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ops-kpi-card h-100">
            <div class="ops-kpi-label"><?= e(__('storage.issues_total', 'items need attention')) ?></div>
            <div class="ops-kpi-value"><?= e((string) $issueCount) ?></div>
            <div class="ops-kpi-meta"><?= e((string) $summary['received_count']) ?> <?= e(__('storage.staging_queue', 'staging queue')) ?></div>
        </div>
    </div>
</div>

<div class="card mb-4 ops-table-card">
    <div class="card-header">
        <div class="ops-panel-title">
            <h5><?= e(__('storage.in_stock', 'In storage')) ?></h5>
            <span class="small text-muted"><?= e((string) $storageTotalQty) ?> <?= e(__('storage.quantity', 'Quantity')) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 storage-table">
            <thead>
                <tr>
                    <th><?= e(__('storage.item', 'Item')) ?></th>
                    <th><?= e(__('storage.category', 'Category')) ?></th>
                    <th><?= e(__('storage.branch', 'Branch')) ?></th>
                    <th><?= e(__('storage.stock_level', 'Stock level')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($storageItems !== []): ?>
                    <?php foreach ($storageItems as $index => $item): ?>
                        <?php
                        $isLow = (int) $item['qty'] < 5;
                        $meterWidth = $storageMaxQty > 0 ? max(14, min(100, round(((int) $item['qty'] / $storageMaxQty) * 100, 2))) : 14;
                        ?>
                        <tr class="<?= $index >= $previewLimit ? 'd-none storage-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$item['item'], $item['category'], $item['branch'], $item['status'], $item['barcode_preview'] ?? '']))) ?>" data-section="storage" data-branch="<?= e($item['branch']) ?>" data-category="<?= e($item['category']) ?>">
                            <td>
                                <a href="<?= e(route('assets.show', ['id' => $item['sample_asset_id']])) ?>" class="fw-semibold"><?= e($item['item']) ?></a>
                                <?php if (($item['barcode_preview'] ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= e(__('assets.barcode', 'Barcode')) ?>: <?= e($item['barcode_preview']) ?><?= (int) $item['qty'] > 3 ? '…' : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['category_id'])): ?>
                                    <a href="<?= e(route('categories.show', ['id' => $item['category_id']])) ?>"><?= e($item['category']) ?></a>
                                <?php else: ?>
                                    <?= e($item['category']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['branch_id'])): ?>
                                    <a href="<?= e(route('branches.show', ['id' => $item['branch_id']])) ?>"><?= e($item['branch']) ?></a>
                                <?php else: ?>
                                    <?= e($item['branch']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="ops-stock-meter<?= $isLow ? ' is-low' : '' ?>">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?= e((string) $meterWidth) ?>%;"><?= e((string) $item['qty']) ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-muted py-5"><?= e(__('storage.empty_stock', 'No assets are currently stored.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($storageItems) > $previewLimit): ?>
        <div class="card-body pt-0">
            <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".storage-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
        </div>
    <?php endif; ?>
</div>

<div class="card ops-table-card">
    <div class="card-header">
        <div class="ops-panel-title">
            <h5><?= e(__('storage.more_sections', 'Detailed sections')) ?></h5>
            <span class="small text-muted"><?= e(__('storage.sections_as_tabs', 'Organized as tabs for faster review')) ?></span>
        </div>
        <ul class="nav nav-pills ops-tab-nav mt-3" id="storage-detail-tabs" role="tablist">
            <?php foreach ([
                'spare_parts' => __('nav.spare_parts', 'Spare Parts'),
                'licenses' => __('nav.licenses', 'Licenses'),
                'received' => __('storage.received', 'Received not deployed'),
                'broken' => __('storage.broken', 'Broken devices'),
                'repair' => __('storage.repair', 'Repair queue'),
            ] as $tabKey => $tabLabel): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link<?= $activeTab === $tabKey ? ' active' : '' ?>" id="tab-<?= e($tabKey) ?>" data-bs-toggle="pill" data-bs-target="#pane-<?= e($tabKey) ?>" type="button" role="tab" aria-controls="pane-<?= e($tabKey) ?>" aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                        <?= e($tabLabel) ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade<?= $activeTab === 'spare_parts' ? ' show active' : '' ?>" id="pane-spare_parts" role="tabpanel" aria-labelledby="tab-spare_parts">
                <div class="table-responsive">
                    <table class="table mb-0 storage-table">
                        <thead>
                            <tr>
                                <th><?= e(__('common.name', 'Name')) ?></th>
                                <th><?= e(__('assets.category', 'Category')) ?></th>
                                <th><?= e(__('common.location', 'Location')) ?></th>
                                <th><?= e(__('storage.stock_level', 'Stock level')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sparePartsStock !== []): ?>
                                <?php foreach ($sparePartsStock as $index => $part): ?>
                                    <?php
                                    $isLow = (int) $part['quantity'] < 5;
                                    $meterWidth = $spareMaxQty > 0 ? max(14, min(100, round(((int) $part['quantity'] / $spareMaxQty) * 100, 2))) : 14;
                                    ?>
                                    <tr class="<?= $index >= $previewLimit ? 'd-none spare-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$part['name'], $part['part_number'], $part['category'], $part['location']]))) ?>" data-section="spare_parts" data-branch="<?= e($part['location']) ?>" data-category="<?= e($part['category']) ?>">
                                        <td>
                                            <a href="<?= e(route('spare-parts.show', ['id' => $part['id']])) ?>" class="fw-semibold"><?= e($part['name']) ?></a>
                                            <div class="small text-muted"><?= e($part['part_number']) ?></div>
                                        </td>
                                        <td><?= e($part['category']) ?></td>
                                        <td><?= e($part['location']) ?></td>
                                        <td>
                                            <div class="ops-stock-meter<?= $isLow ? ' is-low' : '' ?>">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= e((string) $meterWidth) ?>%;"><?= e((string) $part['quantity']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-muted py-5"><?= e(__('spare_parts.empty', 'No spare parts added yet.')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($sparePartsStock) > $previewLimit): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".spare-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade<?= $activeTab === 'licenses' ? ' show active' : '' ?>" id="pane-licenses" role="tabpanel" aria-labelledby="tab-licenses">
                <div class="table-responsive">
                    <table class="table mb-0 storage-table">
                        <thead>
                            <tr>
                                <th><?= e(__('licenses.product', 'Product')) ?></th>
                                <th><?= e(__('licenses.available_seats', 'Available Seats')) ?></th>
                                <th><?= e(__('common.status', 'Status')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($licenseStock !== []): ?>
                                <?php foreach ($licenseStock as $index => $license): ?>
                                    <?php $meterWidth = $licenseMaxQty > 0 ? max(14, min(100, round(((int) $license['available_seats'] / $licenseMaxQty) * 100, 2))) : 14; ?>
                                    <tr class="<?= $index >= $previewLimit ? 'd-none license-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$license['product_name'], $license['vendor_name'], $license['license_type'], $license['status']]))) ?>" data-section="licenses" data-branch="" data-category="">
                                        <td>
                                            <a href="<?= e(route('licenses.show', ['id' => $license['id']])) ?>" class="fw-semibold"><?= e($license['product_name']) ?></a>
                                            <div class="small text-muted"><?= e($license['vendor_name']) ?> • <?= e(__('licenses.type_' . $license['license_type'], ucfirst($license['license_type']))) ?></div>
                                        </td>
                                        <td>
                                            <div class="ops-stock-meter">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= e((string) $meterWidth) ?>%;"><?= e((string) $license['available_seats']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e(__('licenses.status_' . $license['status'], ucfirst($license['status']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted py-5"><?= e(__('licenses.empty', 'No licenses added yet.')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($licenseStock) > $previewLimit): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".license-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade<?= $activeTab === 'received' ? ' show active' : '' ?>" id="pane-received" role="tabpanel" aria-labelledby="tab-received">
                <div class="table-responsive">
                    <table class="table mb-0 storage-table">
                        <thead>
                            <tr>
                                <th><?= e(__('storage.asset', 'Asset')) ?></th>
                                <th><?= e(__('storage.branch', 'Branch')) ?></th>
                                <th><?= e(__('storage.purchase_date', 'Purchase date')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($receivedAssets !== []): ?>
                                <?php foreach ($receivedAssets as $index => $asset): ?>
                                    <tr class="<?= $index >= $previewLimit ? 'd-none received-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$asset['name'], $asset['tag'], $asset['category'], $asset['branch'], $asset['status']]))) ?>" data-section="received" data-branch="<?= e($asset['branch']) ?>" data-category="<?= e($asset['category']) ?>">
                                        <td>
                                            <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="fw-semibold"><?= e($asset['name']) ?></a>
                                            <div class="small text-muted"><?= e($asset['tag']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($asset['branch_id'])): ?>
                                                <a href="<?= e(route('branches.show', ['id' => $asset['branch_id']])) ?>"><?= e($asset['branch']) ?></a>
                                            <?php else: ?>
                                                <?= e($asset['branch']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($asset['purchase_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted py-5"><?= e(__('storage.empty_received', 'No received assets waiting for deployment.')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($receivedAssets) > $previewLimit): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".received-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade<?= $activeTab === 'broken' ? ' show active' : '' ?>" id="pane-broken" role="tabpanel" aria-labelledby="tab-broken">
                <div class="table-responsive">
                    <table class="table mb-0 storage-table">
                        <thead>
                            <tr>
                                <th><?= e(__('storage.asset', 'Asset')) ?></th>
                                <th><?= e(__('storage.category', 'Category')) ?></th>
                                <th><?= e(__('storage.branch', 'Branch')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($brokenAssets !== []): ?>
                                <?php foreach ($brokenAssets as $index => $asset): ?>
                                    <tr class="<?= $index >= $previewLimit ? 'd-none broken-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$asset['name'], $asset['tag'], $asset['category'], $asset['branch'], $asset['status']]))) ?>" data-section="broken" data-branch="<?= e($asset['branch']) ?>" data-category="<?= e($asset['category']) ?>">
                                        <td>
                                            <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="fw-semibold"><?= e($asset['name']) ?></a>
                                            <div class="small text-muted"><?= e($asset['tag']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($asset['category_id'])): ?>
                                                <a href="<?= e(route('categories.show', ['id' => $asset['category_id']])) ?>"><?= e($asset['category']) ?></a>
                                            <?php else: ?>
                                                <?= e($asset['category']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($asset['branch_id'])): ?>
                                                <a href="<?= e(route('branches.show', ['id' => $asset['branch_id']])) ?>"><?= e($asset['branch']) ?></a>
                                            <?php else: ?>
                                                <?= e($asset['branch']) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted py-5"><?= e(__('storage.empty_broken', 'No broken devices recorded.')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($brokenAssets) > $previewLimit): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".broken-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade<?= $activeTab === 'repair' ? ' show active' : '' ?>" id="pane-repair" role="tabpanel" aria-labelledby="tab-repair">
                <div class="table-responsive">
                    <table class="table mb-0 storage-table">
                        <thead>
                            <tr>
                                <th><?= e(__('storage.asset', 'Asset')) ?></th>
                                <th><?= e(__('storage.tag', 'Tag')) ?></th>
                                <th><?= e(__('storage.category', 'Category')) ?></th>
                                <th><?= e(__('storage.branch', 'Branch')) ?></th>
                                <th><?= e(__('storage.purchase_date', 'Purchase date')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($repairQueue !== []): ?>
                                <?php foreach ($repairQueue as $index => $asset): ?>
                                    <tr class="<?= $index >= $previewLimit ? 'd-none repair-extra-row' : '' ?>" data-search="<?= e(strtolower(implode(' ', [$asset['name'], $asset['tag'], $asset['category'], $asset['branch'], $asset['status']]))) ?>" data-section="repair" data-branch="<?= e($asset['branch']) ?>" data-category="<?= e($asset['category']) ?>">
                                        <td><a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="fw-semibold"><?= e($asset['name']) ?></a></td>
                                        <td><?= e($asset['tag']) ?></td>
                                        <td>
                                            <?php if (!empty($asset['category_id'])): ?>
                                                <a href="<?= e(route('categories.show', ['id' => $asset['category_id']])) ?>"><?= e($asset['category']) ?></a>
                                            <?php else: ?>
                                                <?= e($asset['category']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($asset['branch_id'])): ?>
                                                <a href="<?= e(route('branches.show', ['id' => $asset['branch_id']])) ?>"><?= e($asset['branch']) ?></a>
                                            <?php else: ?>
                                                <?= e($asset['branch']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($asset['purchase_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted py-5"><?= e(__('storage.empty_repair', 'No devices in repair.')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($repairQueue) > $previewLimit): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-expand-rows" data-target=".repair-extra-row"><?= e(__('common.view_more', 'View more')) ?></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('storage_live_search');
    var sectionInput = document.getElementById('section');
    var branchInput = document.getElementById('branch');
    var categoryInput = document.getElementById('category');
    var exportXls = document.getElementById('storage-export-xls');
    var exportPdf = document.getElementById('storage-export-pdf');
    var rows = document.querySelectorAll('.storage-table tbody tr[data-search]');

    function applyStorageFilter() {
        var term = (searchInput && searchInput.value || '').toLowerCase().trim();
        var section = sectionInput ? sectionInput.value : '';
        var branch = branchInput ? branchInput.value : '';
        var category = categoryInput ? categoryInput.value : '';

        rows.forEach(function (row) {
            var show = (!term || row.dataset.search.indexOf(term) !== -1)
                && (!section || row.dataset.section === section)
                && (!branch || row.dataset.branch === branch)
                && (!category || row.dataset.category === category);
            row.style.display = show ? '' : 'none';
        });

        [exportXls, exportPdf].forEach(function (link) {
            if (!link) {
                return;
            }
            var url = new URL(link.href, window.location.origin);
            url.searchParams.set('q', searchInput ? searchInput.value : '');
            url.searchParams.set('section', section);
            url.searchParams.set('branch', branch);
            url.searchParams.set('category', category);
            link.href = url.toString();
        });
    }

    document.querySelectorAll('.js-expand-rows').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-target');
            if (!target) {
                return;
            }
            document.querySelectorAll(target).forEach(function (row) {
                row.classList.remove('d-none');
            });
            button.remove();
        });
    });

    [searchInput, sectionInput, branchInput, categoryInput].forEach(function (node) {
        if (!node) {
            return;
        }
        node.addEventListener('input', applyStorageFilter);
        node.addEventListener('change', applyStorageFilter);
    });

    applyStorageFilter();
});
</script>
