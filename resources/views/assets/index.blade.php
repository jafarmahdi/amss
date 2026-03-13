<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="badge-soft mb-3"><i class="bi bi-box-seam"></i> <?= e($archivedMode ? __('assets.archived_badge', 'Archive') : __('assets.registry_badge', 'Asset registry')) ?></div>
        <h2 class="mb-1"><?= e($archivedMode ? __('assets.archived_page', 'Archived Assets') : __('assets.title', 'Assets')) ?></h2>
        <p class="text-muted mb-0"><?= e($archivedMode ? __('assets.archived_desc', 'Retired assets that are no longer available for assignment or movement.') : __('assets.desc', 'Track purchased, received, stored, and deployed inventory with supporting documents.')) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$archivedMode): ?>
            <a href="<?= e(route('assets.archived')) ?>" class="btn btn-outline-secondary"><i class="bi bi-archive"></i> <?= e(__('assets.archived_page', 'Archived Assets')) ?></a>
            <a href="<?= e(route('assets.create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(__('assets.new', 'New Asset')) ?></a>
        <?php else: ?>
            <a href="<?= e(route('assets.index')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= e(__('assets.back_to_active', 'Back to active assets')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <?php $currentRoutePath = $archivedMode ? '/assets/archived' : '/assets'; ?>
        <form method="GET" action="<?= e($archivedMode ? route('assets.archived') : route('assets.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="<?= e($currentRoutePath) ?>">
            <div class="col-md-3">
                <label for="asset_live_search" class="form-label fw-semibold"><?= e(__('reports.search', 'Search everything')) ?></label>
                <input type="text" name="q" id="asset_live_search" value="<?= e($filters['q']) ?>" class="form-control" placeholder="<?= e(__('assets.search_placeholder', 'Search by name, tag, serial, brand, branch, employee...')) ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label fw-semibold"><?= e(__('assets.filter_status', 'Filter by status')) ?></label>
                <select name="status" id="status" class="form-select">
                    <option value=""><?= e(__('assets.all_statuses', 'All statuses')) ?></option>
                    <?php foreach ($statusOptions as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>><?= e(__('status.' . $statusOption, ucfirst($statusOption))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label fw-semibold"><?= e(__('assets.category', 'Category')) ?></label>
                <select name="category" id="category" class="form-select">
                    <option value=""><?= e(__('assets.all_categories', 'All categories')) ?></option>
                    <?php foreach ($categoryOptions as $categoryOption): ?>
                        <option value="<?= e($categoryOption) ?>" <?= $filters['category'] === $categoryOption ? 'selected' : '' ?>><?= e($categoryOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="branch" class="form-label fw-semibold"><?= e(__('common.branch', 'Branch')) ?></label>
                <select name="branch" id="branch" class="form-select">
                    <option value=""><?= e(__('assets.all_branches', 'All branches')) ?></option>
                    <?php foreach ($branchOptions as $branchOption): ?>
                        <option value="<?= e($branchOption) ?>" <?= $filters['branch'] === $branchOption ? 'selected' : '' ?>><?= e($branchOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="stage" class="form-label fw-semibold"><?= e(__('assets.stage', 'Procurement Stage')) ?></label>
                <select name="stage" id="stage" class="form-select">
                    <option value=""><?= e(__('assets.all_stages', 'All stages')) ?></option>
                    <?php foreach ($stageOptions as $stageOption): ?>
                        <option value="<?= e($stageOption) ?>" <?= $filters['stage'] === $stageOption ? 'selected' : '' ?>><?= e(__('stage.' . $stageOption, ucfirst($stageOption))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-secondary w-100"><?= e(__('common.search', 'Search')) ?></button>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="surface-chip"><i class="bi bi-list-check"></i> <span id="assets-visible-count"><?= e((string) count($assets)) ?></span> <?= e(__('assets.visible', 'visible assets')) ?></span>
                <div class="d-flex gap-2 flex-wrap">
                    <?php $xlsParams = ['route' => '/assets/export', 'format' => 'xls', 'archived' => $archivedMode ? '1' : '0'] + $filters; ?>
                    <?php $pdfParams = ['route' => '/assets/export', 'format' => 'pdf', 'archived' => $archivedMode ? '1' : '0'] + $filters; ?>
                    <a id="assets-export-xls" href="<?= e(base_url() . '/index.php?' . http_build_query($xlsParams)) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('reports.export_excel', 'Export Excel')) ?></a>
                    <a id="assets-export-pdf" href="<?= e(base_url() . '/index.php?' . http_build_query($pdfParams)) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('reports.export_pdf', 'Export PDF')) ?></a>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= e(route('assets.bulk')) ?>">
<div class="card mb-3">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold"><?= e(__('assets.bulk_action', 'Bulk Action')) ?></label>
            <select name="bulk_action" class="form-select">
                <option value=""><?= e(__('common.select', 'Select')) ?></option>
                <option value="set_status"><?= e(__('assets.bulk_set_status', 'Set status')) ?></option>
                <option value="move_branch"><?= e(__('assets.bulk_move_branch', 'Move to branch')) ?></option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold"><?= e(__('common.status', 'Status')) ?></label>
            <select name="bulk_status" class="form-select">
                <option value=""><?= e(__('common.select', 'Select')) ?></option>
                <?php foreach (['active','repair','broken','storage'] as $bulkStatus): ?>
                    <option value="<?= e($bulkStatus) ?>"><?= e(__('status.' . $bulkStatus, ucfirst($bulkStatus))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold"><?= e(__('common.branch', 'Branch')) ?></label>
            <select name="bulk_branch_id" class="form-select">
                <option value=""><?= e(__('common.select', 'Select')) ?></option>
                <?php foreach (\App\Support\DataRepository::branches() as $branchRow): ?>
                    <option value="<?= e((string) $branchRow['id']) ?>"><?= e($branchRow['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100"><?= e(__('assets.apply_bulk', 'Apply')) ?></button>
        </div>
    </div>
</div>
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table align-middle" id="assets-live-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="assets-select-all"></th>
                    <th><?= e(__('assets.title', 'Assets')) ?></th>
                    <th><?= e(__('assets.category', 'Category')) ?></th>
                    <th><?= e(__('assets.stage', 'Stage')) ?></th>
                    <th><?= e(__('common.location', 'Location')) ?></th>
                    <th><?= e(__('assets.primary_employee', 'Assigned')) ?></th>
                    <th><?= e(__('common.documents', 'Documents')) ?></th>
                    <th>QR</th>
                    <th class="text-end"><?= e(__('common.actions', 'Actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assets !== []): ?>
                    <?php foreach ($assets as $asset): ?>
                        <tr
                            data-search="<?= e(strtolower(implode(' ', [$asset['name'], $asset['tag'], $asset['category'], $asset['location'], $asset['assigned_to'], $asset['serial_number'] ?? '', $asset['brand'] ?? '', $asset['model'] ?? '']))) ?>"
                            data-status="<?= e((string) $asset['status']) ?>"
                            data-category="<?= e((string) $asset['category']) ?>"
                            data-branch="<?= e((string) $asset['location']) ?>"
                            data-stage="<?= e((string) $asset['procurement_stage']) ?>"
                        >
                            <td><input type="checkbox" name="asset_ids[]" value="<?= e((string) $asset['id']) ?>" class="asset-select-row"></td>
                            <td>
                                <div class="fw-semibold"><?= e($asset['name']) ?></div>
                                <div class="small text-muted"><?= e($asset['tag']) ?></div>
                            </td>
                            <td><?= e($asset['category']) ?></td>
                            <td><span class="badge-soft"><?= e(__('stage.' . $asset['procurement_stage'], ucfirst($asset['procurement_stage']))) ?></span></td>
                            <td><?= e($asset['location']) ?></td>
                            <td><?= e($asset['assigned_to']) ?></td>
                            <td><?= e((string) ($asset['documents_count'] ?? 0)) ?></td>
                            <td>
                                <?php $qrReady = in_array((string) $asset['status'], ['storage', 'active', 'broken', 'archived'], true) || (string) $asset['procurement_stage'] === 'deployed'; ?>
                                <?php if ($qrReady): ?>
                                    <?php $historyUrl = app_url(ltrim(route('assets.show', ['id' => $asset['id']]), '/')); ?>
                                    <div class="asset-qr-mini" data-history-url="<?= e($historyUrl) ?>"></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="<?= e(route('assets.edit', ['id' => $asset['id']])) ?>" class="btn btn-sm btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="assets-live-empty" class="d-none">
                        <td colspan="9" class="text-center text-muted py-5"><?= e(__('assets.no_results', 'No assets matched the selected filters.')) ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5"><?= e(__('assets.no_results', 'No assets matched the selected filters.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</form>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof QRCode !== 'undefined') {
        document.querySelectorAll('.asset-qr-mini').forEach(function (node) {
            var url = node.getAttribute('data-history-url');
            if (!url) {
                return;
            }
            new QRCode(node, {
                text: url,
                width: 56,
                height: 56
            });
        });
    }

    var searchInput = document.getElementById('asset_live_search');
    var statusInput = document.getElementById('status');
    var categoryInput = document.getElementById('category');
    var branchInput = document.getElementById('branch');
    var stageInput = document.getElementById('stage');
    var exportXls = document.getElementById('assets-export-xls');
    var exportPdf = document.getElementById('assets-export-pdf');
    var rows = document.querySelectorAll('#assets-live-table tbody tr[data-search]');
    var selectAll = document.getElementById('assets-select-all');
    var visibleCount = document.getElementById('assets-visible-count');
    var emptyRow = document.getElementById('assets-live-empty');

    function applyLiveAssetFilter() {
        var term = (searchInput && searchInput.value || '').toLowerCase().trim();
        var status = statusInput ? statusInput.value : '';
        var category = categoryInput ? categoryInput.value : '';
        var branch = branchInput ? branchInput.value : '';
        var stage = stageInput ? stageInput.value : '';
        var matchedRows = 0;

        rows.forEach(function (row) {
            var show = (!term || row.dataset.search.indexOf(term) !== -1)
                && (!status || row.dataset.status === status)
                && (!category || row.dataset.category === category)
                && (!branch || row.dataset.branch === branch)
                && (!stage || row.dataset.stage === stage);
            row.style.display = show ? '' : 'none';

            var checkbox = row.querySelector('.asset-select-row');
            if (checkbox && !show) {
                checkbox.checked = false;
            }

            if (show) {
                matchedRows += 1;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = String(matchedRows);
        }

        if (emptyRow && rows.length > 0) {
            emptyRow.classList.toggle('d-none', matchedRows !== 0);
        }

        if (selectAll) {
            selectAll.checked = false;
        }

        [exportXls, exportPdf].forEach(function (link) {
            if (!link) {
                return;
            }
            var url = new URL(link.href, window.location.origin);
            url.searchParams.set('q', searchInput ? searchInput.value : '');
            url.searchParams.set('status', status);
            url.searchParams.set('category', category);
            url.searchParams.set('branch', branch);
            url.searchParams.set('stage', stage);
            link.href = url.toString();
        });
    }

    [searchInput, statusInput, categoryInput, branchInput, stageInput].forEach(function (node) {
        if (!node) {
            return;
        }
        node.addEventListener('input', applyLiveAssetFilter);
        node.addEventListener('change', applyLiveAssetFilter);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rows.forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }

                var checkbox = row.querySelector('.asset-select-row');
                if (checkbox) {
                    checkbox.checked = selectAll.checked;
                }
            });
        });
    }

    applyLiveAssetFilter();
});
</script>
