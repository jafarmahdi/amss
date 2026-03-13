<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('movements.title', 'Move / Assign Asset')) ?></h2>
        <p class="text-muted mb-0"><?= e($asset['name']) ?> (<?= e($asset['tag']) ?>)</p>
    </div>
    <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= e(route('assets.move.store', ['id' => $asset['id']])) ?>" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="branch_id"><?= e(__('movements.branch', 'Move To Branch')) ?></label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="" data-current-branch-id="<?= e((string) ($asset['branch_id'] ?? '')) ?>"><?= e(__('movements.keep_branch', 'Keep current branch')) ?></option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= e((string) $branch['id']) ?>" <?= (int) ($asset['branch_id'] ?? 0) === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status"><?= e(__('assets.status', 'Status')) ?></label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach (['active', 'repair', 'broken', 'storage'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $asset['status'] === $status ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="assigned_employee_ids"><?= e(__('movements.employees', 'Employees Using This Asset')) ?></label>
                            <select class="form-select" id="assigned_employee_ids" name="assigned_employee_ids[]" multiple size="8">
                                <?php $currentIds = array_map(static fn(array $a): int => (int) $a['id'], $assignments); ?>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= e((string) $employee['id']) ?>" data-branch-id="<?= e((string) ($employee['branch_id'] ?? '')) ?>" <?= in_array((int) $employee['id'], $currentIds, true) ? 'selected' : '' ?>>
                                        <?= e($employee['name']) ?><?= $employee['department'] !== '' ? ' (' . e($employee['department']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?= e(__('movements.employee_help', 'Only employees from the selected branch are shown. Hold Ctrl/Cmd to select more than one employee.')) ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="movement_documents"><?= e(__('movements.documents', 'Movement Documents')) ?></label>
                            <input class="form-control" type="file" id="movement_documents" name="movement_documents[]" multiple>
                            <div class="form-text"><?= e(__('movements.document_help', 'A document is required for every movement or assignment update.')) ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="movement_notes"><?= e(__('movements.notes', 'Movement / Assignment Notes')) ?></label>
                            <textarea class="form-control" id="movement_notes" name="movement_notes" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?= e(__('movements.save', 'Save Movement')) ?></button>
                        <a href="<?= e(route('assets.show', ['id' => $asset['id']])) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small"><?= e(__('movements.current_branch', 'Current Branch')) ?></div>
                <div class="fw-semibold mb-3"><?= e($asset['location']) ?></div>
                <div class="text-muted small"><?= e(__('movements.current_employees', 'Current Employees')) ?></div>
                <?php if (!empty($assignments)): ?>
                    <ul class="mt-2">
                        <?php foreach ($assignments as $assignment): ?>
                            <li><?= e($assignment['name']) ?><?= !empty($assignment['department']) ? ' - ' . e($assignment['department']) : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mt-2 mb-0"><?= e(__('movements.no_employees', 'No employees assigned.')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var branchSelect = document.getElementById('branch_id');
    var employeeSelect = document.getElementById('assigned_employee_ids');

    if (!branchSelect || !employeeSelect) {
        return;
    }

    function activeBranchId() {
        if (branchSelect.value !== '') {
            return branchSelect.value;
        }

        var selectedOption = branchSelect.options[branchSelect.selectedIndex];
        return selectedOption ? selectedOption.getAttribute('data-current-branch-id') || '' : '';
    }

    function syncEmployees() {
        var branchId = activeBranchId();
        Array.prototype.forEach.call(employeeSelect.options, function (option) {
            var matches = branchId !== '' && option.getAttribute('data-branch-id') === branchId;
            option.hidden = !matches;
            option.disabled = !matches;
            if (!matches) {
                option.selected = false;
            }
        });
    }

    branchSelect.addEventListener('change', syncEmployees);
    syncEmployees();
});
</script>
