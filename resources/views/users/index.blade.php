<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e(__('nav.users', 'Users')) ?></h2>
        <p class="text-muted mb-0">System users, roles, and responsibility mapping.</p>
    </div>
    <a href="<?= e(route('users.create')) ?>" class="btn btn-primary"><?= e(__('actions.add', 'Add')) ?> <?= e(__('nav.users', 'Users')) ?></a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('users.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/users">
            <div class="col-md-5">
                <label class="form-label fw-semibold" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('users.search_placeholder', 'Name, email, or role')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="role"><?= e(__('common.role', 'Role')) ?></label>
                <select class="form-select" id="role" name="role">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="status"><?= e(__('users.status', 'Status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>><?= e(__('users.active', 'Active')) ?></option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>><?= e(__('users.inactive', 'Inactive')) ?></option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-fill"><?= e(__('common.search', 'Search')) ?></button>
                <a href="<?= e(route('users.index')) ?>" class="btn btn-outline-secondary"><?= e(__('common.reset_filters', 'Reset')) ?></a>
            </div>
            <div class="col-12">
                <span class="surface-chip"><i class="bi bi-people"></i> <?= e((string) count($users)) ?> <?= e(__('users.visible_results', 'visible users')) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th><?= e(__('users.status', 'Status')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users !== []): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['name']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><span class="badge text-bg-light"><?= e($user['role']) ?></span></td>
                            <td><span class="badge text-bg-<?= ($user['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= e(($user['status'] ?? 'active') === 'active' ? __('users.active', 'Active') : __('users.inactive', 'Inactive')) ?></span></td>
                            <td class="text-end">
                                <a href="<?= e(route('users.edit', ['id' => $user['id']])) ?>" class="btn btn-sm btn-outline-primary"><?= e(__('actions.edit', 'Edit')) ?></a>
                                <form method="POST" action="<?= e(route('users.destroy', ['id' => $user['id']])) ?>" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')"><?= e(__('actions.delete', 'Delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted py-5"><?= e(__('users.no_results', 'No users matched the current filters.')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
