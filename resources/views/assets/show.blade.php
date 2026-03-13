<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="mb-1"><?= e($asset['name']) ?></h2>
        <p class="text-muted mb-0">
            <?= e(__('assets.detail_desc', 'Asset detail, procurement context, and supporting documents.')) ?>
            <span class="badge text-bg-<?= ($asset['status'] ?? '') === 'broken' ? 'danger' : (($asset['status'] ?? '') === 'archived' ? 'dark' : 'light') ?> ms-2">
                <?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?>
            </span>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(route('assets.edit', ['id' => $asset['id']])) ?>" class="btn btn-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
        <?php if ($asset['status'] !== 'archived'): ?>
            <a href="<?= e(route('assets.move', ['id' => $asset['id']])) ?>" class="btn btn-outline-primary"><?= e(__('movements.title', 'Move / Assign Asset')) ?></a>
            <a href="<?= e(route('assets.handover', ['id' => $asset['id']])) ?>" class="btn btn-outline-dark"><?= e(__('assets.handover_button', 'Handover Form')) ?></a>
            <?php if (!empty($assignments)): ?>
                <a href="<?= e(route('assets.return', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('assets.return_button', 'Return Asset')) ?></a>
            <?php endif; ?>
            <a href="<?= e(route('assets.repair', ['id' => $asset['id']])) ?>" class="btn btn-outline-warning"><?= e(__('assets.repair_button', 'Repair Workflow')) ?></a>
            <a href="<?= e(route('assets.maintenance', ['id' => $asset['id']])) ?>" class="btn btn-outline-info"><?= e(__('assets.maintenance_button', 'Maintenance')) ?></a>
            <a href="<?= e(route('assets.archive', ['id' => $asset['id']])) ?>" class="btn btn-outline-danger"><?= e(__('assets.archive_button', 'Archive')) ?></a>
        <?php endif; ?>
        <form method="POST" action="<?= e(route('assets.destroy', ['id' => $asset['id']])) ?>">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this asset?')"><?= e(__('actions.delete', 'Delete')) ?></button>
        </form>
        <a href="<?= e(route('assets.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
    </div>
</div>

<?php $historyUrl = app_url(ltrim(route('assets.show', ['id' => $asset['id']]), '/')); ?>
<?php $qrEligible = in_array((string) $asset['status'], ['storage', 'active', 'broken', 'archived'], true) || (string) $asset['procurement_stage'] === 'deployed'; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.tag', 'Asset Tag')) ?></div><div class="fw-semibold"><?= e($asset['tag']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.barcode', 'Barcode')) ?></div><div class="fw-semibold"><?= e($asset['barcode'] !== '' ? $asset['barcode'] : '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.status', 'Operational Status')) ?></div><div class="fw-semibold"><?= e(__('status.' . $asset['status'], ucfirst($asset['status']))) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.stage', 'Procurement Stage')) ?></div><div class="fw-semibold"><?= e(__('stage.' . $asset['procurement_stage'], ucfirst($asset['procurement_stage']))) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.category', 'Category')) ?></div><div class="fw-semibold"><?= e($asset['category']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.stock_group', 'Storage Group')) ?></div><div class="fw-semibold"><?= e($asset['stock_group'] !== '' ? $asset['stock_group'] : '-') ?></div></div>
                    <div class="col-md-6">
                        <div class="text-muted small"><?= e(__('assets.request_id', 'Request ID')) ?></div>
                        <div class="fw-semibold">
                            <?php if (!empty($asset['request_no'])): ?>
                                <a href="<?= e(route('requests.show', ['id' => $asset['request_id']])) ?>" class="text-decoration-none"><?= e($asset['request_no']) ?></a>
                            <?php else: ?>
                                <?= e('-') ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($asset['request_title'])): ?>
                            <div class="small text-muted"><?= e($asset['request_title']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.primary_employee', 'Primary Employee')) ?></div><div class="fw-semibold"><?= e($asset['assigned_to']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.vendor', 'Vendor')) ?></div><div class="fw-semibold"><?= e($asset['vendor_name']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.invoice', 'Invoice Number')) ?></div><div class="fw-semibold"><?= e($asset['invoice_number']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.serial', 'Serial Number')) ?></div><div class="fw-semibold"><?= e($asset['serial_number']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.brand_model', 'Brand / Model')) ?></div><div class="fw-semibold"><?= e(trim($asset['brand'] . ' ' . $asset['model'])) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.purchase_date', 'Purchase Date')) ?></div><div class="fw-semibold"><?= e($asset['purchase_date']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.warranty', 'Warranty Expiry')) ?></div><div class="fw-semibold"><?= e($asset['warranty_expiry']) ?></div></div>
                    <?php if (!empty($asset['archived_at'])): ?>
                        <div class="col-md-6"><div class="text-muted small"><?= e(__('assets.archived_at', 'Archived At')) ?></div><div class="fw-semibold"><?= e($asset['archived_at']) ?></div></div>
                    <?php endif; ?>
                    <div class="col-12"><div class="text-muted small"><?= e(__('assets.notes', 'Notes')) ?></div><div class="fw-semibold"><?= e($asset['notes']) ?></div></div>
                    <?php if (!empty($asset['archive_reason'])): ?>
                        <div class="col-12"><div class="text-muted small"><?= e(__('assets.archive_reason', 'Archive Reason')) ?></div><div class="fw-semibold text-danger"><?= e($asset['archive_reason']) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="text-muted small"><?= e(__('assets.location', 'Current Location')) ?></div>
                <div class="fs-5 fw-semibold mb-3"><?= e($asset['location']) ?></div>
                <div class="text-muted small"><?= e(__('assets.documents', 'Documents')) ?></div>
                <?php if (!empty($asset['documents'])): ?>
                    <ul class="mt-2">
                        <?php foreach ($asset['documents'] as $document): ?>
                            <li><a href="<?= e(base_url() . '/' . ltrim($document['path'], '/')) ?>" target="_blank"><?= e($document['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mt-2 mb-0"><?= e(__('assets.no_docs', 'No documents uploaded yet.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($qrEligible): ?>
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-2"><?= e(__('assets.qr_history', 'QR history')) ?></div>
                    <div id="asset-qr-code" class="d-inline-flex justify-content-center p-3 rounded-4" style="background: white;"></div>
                    <p class="text-muted mt-3 mb-2"><?= e(__('assets.scan_help', 'Scan this QR code to open the full asset history page.')) ?></p>
                    <a href="<?= e($historyUrl) ?>" class="btn btn-outline-secondary" target="_blank"><?= e(__('assets.history_link', 'Open history page')) ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e(__('assets.employee_usage', 'Current Employee Usage')) ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($assignments)): ?>
                    <ul class="mb-0">
                        <?php foreach ($assignments as $assignment): ?>
                            <li><?= e($assignment['name']) ?><?= !empty($assignment['department']) ? ' (' . e($assignment['department']) . ')' : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('assets.no_assignments', 'No employees currently assigned.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e(__('movements.history', 'Movement History')) ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($movements)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($movements as $movement): ?>
                            <div class="border rounded-4 p-3">
                                <div class="fw-semibold"><?= e($movement['date']) ?>: <?= e($movement['from']) ?> → <?= e($movement['to']) ?></div>
                                <?php if (($movement['movement_type'] ?? 'manual') === 'request' && !empty($movement['request_no'])): ?>
                                    <div class="small mt-1">
                                        <span class="badge text-bg-info-subtle border border-info-subtle text-info-emphasis"><?= e(__('requests.title', 'Requests')) ?></span>
                                        <a href="<?= e(route('requests.show', ['id' => $movement['request_id']])) ?>" class="text-decoration-none ms-1"><?= e($movement['request_no']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <?php if ($movement['notes'] !== ''): ?>
                                    <div class="small text-muted mt-1"><?= e($movement['notes']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($movement['documents'])): ?>
                                    <div class="small mt-2"><?= e(__('movements.documents', 'Movement Documents')) ?>:</div>
                                    <ul class="mb-0 mt-1">
                                        <?php foreach ($movement['documents'] as $document): ?>
                                            <li><a href="<?= e(base_url() . '/' . ltrim($document['path'], '/')) ?>" target="_blank"><?= e($document['name']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= e(__('movements.empty', 'No movement history yet.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($repairs)): ?>
    <div class="card mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('assets.repair_history', 'Repair History')) ?></h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3">
                <?php foreach ($repairs as $repair): ?>
                    <div class="border rounded-4 p-3">
                        <div class="fw-semibold"><?= e($repair['vendor_name']) ?><?= $repair['reference_number'] !== '' ? ' · ' . e($repair['reference_number']) : '' ?></div>
                        <div class="small text-muted"><?= e($repair['sent_at']) ?><?= $repair['completed_at'] !== '' ? ' → ' . e($repair['completed_at']) : '' ?></div>
                        <div class="small mt-1"><?= e(__('assets.repair_outcome', 'Repair Outcome')) ?>: <?= e(__('repair.' . $repair['outcome'], $repair['outcome'])) ?></div>
                        <?php if ($repair['return_status'] !== ''): ?><div class="small mt-1"><?= e(__('assets.repair_return_status', 'Return Status After Repair')) ?>: <?= e(__('status.' . $repair['return_status'], $repair['return_status'])) ?></div><?php endif; ?>
                        <?php if ($repair['notes'] !== ''): ?><div class="small mt-1"><?= e($repair['notes']) ?></div><?php endif; ?>
                        <?php if ($repair['completion_notes'] !== ''): ?><div class="small mt-1"><?= e($repair['completion_notes']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($handovers)): ?>
    <div class="card mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('assets.handover_history', 'Handover History')) ?></h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3">
                <?php foreach ($handovers as $handover): ?>
                    <div class="border rounded-4 p-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-semibold"><?= e($handover['employee_name']) ?> · <?= e(__('handover.' . $handover['handover_type'], ucfirst($handover['handover_type']))) ?></div>
                            <div class="small text-muted"><?= e($handover['handover_date']) ?> · <?= e($handover['employee_code']) ?></div>
                            <?php if ($handover['notes'] !== ''): ?><div class="small mt-1"><?= e($handover['notes']) ?></div><?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= e(route('assets.handover.print', ['id' => $handover['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                            <a href="<?= e(route('assets.handover.print', ['id' => $handover['id']]) . '&format=pdf') ?>" class="btn btn-sm btn-outline-dark"><?= e(__('actions.export_pdf', 'PDF')) ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($maintenance)): ?>
    <div class="card mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= e(__('assets.maintenance_history', 'Maintenance History')) ?></h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3">
                <?php foreach ($maintenance as $record): ?>
                    <div class="border rounded-4 p-3">
                        <div class="fw-semibold"><?= e($record['maintenance_type']) ?> <span class="badge text-bg-light ms-2"><?= e(__('status.' . $record['status'], ucfirst($record['status']))) ?></span></div>
                        <div class="small text-muted"><?= e($record['scheduled_date']) ?><?= $record['completed_date'] !== '' ? ' → ' . e($record['completed_date']) : '' ?></div>
                        <?php if ($record['technician_name'] !== '' || $record['vendor_name'] !== ''): ?>
                            <div class="small mt-1"><?= e(trim($record['technician_name'] . ' ' . $record['vendor_name'])) ?></div>
                        <?php endif; ?>
                        <?php if ($record['result_summary'] !== ''): ?><div class="small mt-1"><?= e($record['result_summary']) ?></div><?php endif; ?>
                        <?php if ($record['next_service_date'] !== ''): ?><div class="small mt-1"><?= e(__('assets.next_service_date', 'Next service date')) ?>: <?= e($record['next_service_date']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($qrEligible): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('asset-qr-code');
        if (!el || typeof QRCode === 'undefined') {
            return;
        }
        new QRCode(el, {
            text: <?= json_encode($historyUrl) ?>,
            width: 180,
            height: 180
        });
    });
    </script>
<?php endif; ?>
