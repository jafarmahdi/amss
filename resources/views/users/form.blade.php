<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($user ? __('form.edit_user', 'Edit User') : __('form.create_user', 'Create User')) ?></h2>
        <p class="text-muted mb-0">Manage system access and assigned roles.</p>
    </div>
    <a href="<?= e(route('users.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($user ? route('users.update', ['id' => $user['id']]) : route('users.store')) ?>">
            <?php if ($user): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name"><?= e(__('form.name', 'Name')) ?></label>
                    <input class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) old('name', $user['name'] ?? '')) ?>" required>
                    <?php if (has_error('name')): ?><div class="invalid-feedback"><?= e((string) field_error('name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email"><?= e(__('form.email', 'Email')) ?></label>
                    <input class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e((string) old('email', $user['email'] ?? '')) ?>" required>
                    <?php if (has_error('email')): ?><div class="invalid-feedback"><?= e((string) field_error('email')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="role"><?= e(__('common.role', 'Role')) ?></label>
                    <select class="form-select <?= has_error('role') ? 'is-invalid' : '' ?>" id="role" name="role" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= e($role) ?>" <?= ((string) old('role', $user['role'] ?? '') === $role) ? 'selected' : '' ?>><?= e($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('role')): ?><div class="invalid-feedback"><?= e((string) field_error('role')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status"><?= e(__('users.status', 'Status')) ?></label>
                    <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                        <option value="active" <?= ((string) old('status', $user['status'] ?? 'active') === 'active') ? 'selected' : '' ?>><?= e(__('users.active', 'Active')) ?></option>
                        <option value="inactive" <?= ((string) old('status', $user['status'] ?? 'active') === 'inactive') ? 'selected' : '' ?>><?= e(__('users.inactive', 'Inactive')) ?></option>
                    </select>
                    <?php if (has_error('status')): ?><div class="invalid-feedback"><?= e((string) field_error('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="password"><?= e(__('auth.password', 'Password')) ?></label>
                    <input class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" id="password" name="password" type="password" <?= $user ? '' : 'required' ?>>
                    <?php if (has_error('password')): ?><div class="invalid-feedback"><?= e((string) field_error('password')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e($user ? __('form.password_help_keep', 'Leave blank to keep current password.') : __('form.password_help_set', 'Set the initial login password.')) ?></div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($user ? __('actions.save', 'Save Changes') : __('form.create_user', 'Create User')) ?></button>
                <a href="<?= e(route('users.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
