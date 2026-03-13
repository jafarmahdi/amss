<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('employees.title', 'Employees')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('employees.desc', 'Manage employees separately from system login users.')) ?></p>
    </div>
    <a href="<?= e(route('employees.create')) ?>" class="btn btn-primary"><?= e(__('employees.add', 'Add Employee')) ?></a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(route('employees.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/employees">
            <div class="col-md-5">
                <label class="form-label" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= e(__('employees.search_placeholder', 'Name, code, job title, branch')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status"><?= e(__('common.status', 'Status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>><?= e(__('status.active', 'Active')) ?></option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= e(__('status.inactive', 'Inactive')) ?></option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="branch"><?= e(__('common.branch', 'Branch')) ?></label>
                <select class="form-select" id="branch" name="branch">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= e($branch['name']) ?>" <?= ($filters['branch'] ?? '') === $branch['name'] ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-secondary w-100"><?= e(__('common.search', 'Search')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('common.code', 'Code')) ?></th>
                    <th><?= e(__('employees.company_email', 'Company Email')) ?></th>
                    <th><?= e(__('employees.fingerprint_id', 'Fingerprint ID')) ?></th>
                    <th><?= e(__('common.branch', 'Branch')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($employees !== []): ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><a href="<?= e(route('employees.show', ['id' => $employee['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($employee['name']) ?></a></td>
                            <td><?= e($employee['employee_code']) ?></td>
                            <td><?= e($employee['company_email']) ?></td>
                            <td><?= e($employee['fingerprint_id']) ?></td>
                            <td><?= e($employee['branch_name']) ?></td>
                            <td><span class="badge text-bg-<?= $employee['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(__('status.' . $employee['status'], $employee['status'])) ?></span></td>
                            <td class="text-end">
                                <a href="<?= e(route('employees.show', ['id' => $employee['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                                <a href="<?= e(route('employees.edit', ['id' => $employee['id']])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="<?= e(route('employees.destroy', ['id' => $employee['id']])) ?>" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this employee?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-muted"><?= e(__('employees.no_results', 'No employees match the current search.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
