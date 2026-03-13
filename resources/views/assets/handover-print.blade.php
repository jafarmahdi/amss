<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="text-uppercase text-muted small"><?= e(setting('app_name', __('app.name', 'Alnahala AMS')) ?? __('app.name', 'Alnahala AMS')) ?></div>
                <h2 class="mb-1"><?= e(__('assets.handover_title', 'Asset Handover')) ?></h2>
                <div class="text-muted"><?= e(__('handover.' . $handover['handover_type'], ucfirst($handover['handover_type']))) ?> · <?= e($handover['handover_date']) ?></div>
            </div>
            <div class="d-flex gap-2 print-hidden">
                <a href="<?= e(route('assets.handover.print', ['id' => $handover['id']]) . '&format=pdf') ?>" class="btn btn-outline-dark"><?= e(__('actions.export_pdf', 'PDF')) ?></a>
                <button type="button" class="btn btn-primary" onclick="window.print()"><?= e(__('actions.print', 'Print')) ?></button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small"><?= e(__('nav.assets', 'Assets')) ?></div>
                    <div class="fw-semibold"><?= e($handover['asset_name']) ?></div>
                    <div><?= e($handover['tag']) ?> · <?= e($handover['category_name']) ?></div>
                    <div class="text-muted small mt-2"><?= e(__('common.branch', 'Branch')) ?></div>
                    <div><?= e($handover['branch_name']) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small"><?= e(__('nav.employees', 'Employees')) ?></div>
                    <div class="fw-semibold"><?= e($handover['employee_name']) ?></div>
                    <div><?= e($handover['employee_code']) ?><?= $handover['job_title'] !== '' ? ' · ' . e($handover['job_title']) : '' ?></div>
                    <div><?= e($handover['company_email'] ?: '-') ?></div>
                </div>
            </div>
        </div>

        <div class="border rounded-4 p-3 mb-4">
            <div class="text-muted small"><?= e(__('assets.notes', 'Notes')) ?></div>
            <div><?= nl2br(e($handover['notes'] !== '' ? $handover['notes'] : '-')) ?></div>
        </div>

        <div class="row g-4 mt-5">
            <div class="col-md-4">
                <div class="border-top pt-2 text-center"><?= e(__('assets.prepared_by', 'Prepared By')) ?><br><strong><?= e($handover['created_by']) ?></strong></div>
            </div>
            <div class="col-md-4">
                <div class="border-top pt-2 text-center"><?= e(__('assets.employee_signature', 'Employee Signature')) ?></div>
            </div>
            <div class="col-md-4">
                <div class="border-top pt-2 text-center"><?= e(__('assets.manager_signature', 'Manager Signature')) ?></div>
            </div>
        </div>
    </div>
</div>
