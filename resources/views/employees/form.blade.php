<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1"><?= e($employee ? __('form.edit_employee', 'Edit Employee') : __('form.create_employee', 'Create Employee')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('employees.desc', 'Manage employees separately from system login users.')) ?></p>
    </div>
    <a href="<?= e(route('employees.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.back', 'Back')) ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($employee ? route('employees.update', ['id' => $employee['id']]) : route('employees.store')) ?>" enctype="multipart/form-data">
            <?php if ($employee): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name"><?= e(__('form.name', 'Name')) ?></label>
                    <input class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) old('name', $employee['name'] ?? '')) ?>" required>
                    <?php if (has_error('name')): ?><div class="invalid-feedback"><?= e((string) field_error('name')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="employee_code"><?= e(__('common.code', 'Code')) ?></label>
                    <input class="form-control <?= has_error('employee_code') ? 'is-invalid' : '' ?>" id="employee_code" name="employee_code" value="<?= e((string) old('employee_code', $employee['employee_code'] ?? '')) ?>" required>
                    <?php if (has_error('employee_code')): ?><div class="invalid-feedback"><?= e((string) field_error('employee_code')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fingerprint_id"><?= e(__('employees.fingerprint_id', 'Fingerprint ID')) ?></label>
                    <input class="form-control" id="fingerprint_id" name="fingerprint_id" value="<?= e((string) old('fingerprint_id', $employee['fingerprint_id'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status"><?= e(__('common.status', 'Status')) ?></label>
                    <select class="form-select <?= has_error('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= ((string) old('status', $employee['status'] ?? 'active') === $status) ? 'selected' : '' ?>><?= e(__('status.' . $status, ucfirst($status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (has_error('status')): ?><div class="invalid-feedback"><?= e((string) field_error('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="company_name"><?= e(__('employees.company_name', 'Company')) ?></label>
                    <input class="form-control" id="company_name" name="company_name" value="<?= e((string) old('company_name', $employee['company_name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="project_name"><?= e(__('employees.project_name', 'Project')) ?></label>
                    <input class="form-control" id="project_name" name="project_name" value="<?= e((string) old('project_name', $employee['project_name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="company_email"><?= e(__('employees.company_email', 'Company Email')) ?></label>
                    <input class="form-control <?= has_error('company_email') ? 'is-invalid' : '' ?>" id="company_email" name="company_email" value="<?= e((string) old('company_email', $employee['company_email'] ?? '')) ?>" placeholder="it@alnahala.com">
                    <?php if (has_error('company_email')): ?><div class="invalid-feedback"><?= e((string) field_error('company_email')) ?></div><?php endif; ?>
                    <div class="form-text"><?= e(__('employees.email_help', 'If you enter only the username, the system will append @alnahala.com automatically.')) ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="job_title"><?= e(__('common.job_title', 'Job Title')) ?></label>
                    <input class="form-control" id="job_title" name="job_title" value="<?= e((string) old('job_title', $employee['job_title'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="branch_id"><?= e(__('common.branch', 'Branch')) ?></label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value=""><?= e(__('form.no_branch', 'No Branch')) ?></option>
                        <?php foreach (\App\Support\DataRepository::branches() as $branch): ?>
                            <option value="<?= e((string) $branch['id']) ?>" <?= ((string) old('branch_id', $employee['branch_id'] ?? '') === (string) $branch['id']) ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone"><?= e(__('form.phone', 'Phone')) ?></label>
                    <input class="form-control" id="phone" name="phone" value="<?= e((string) old('phone', $employee['phone'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="appointment_order"><?= e(__('employees.appointment_order', 'Appointment Order Document')) ?></label>
                    <input class="form-control" id="appointment_order" name="appointment_order" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <div class="form-text"><?= e(__('employees.appointment_order_help', 'Upload the امر التعيين document for this employee.')) ?></div>
                    <?php if (!empty($employee['appointment_order_path'] ?? '')): ?>
                        <div class="mt-2">
                            <a href="<?= e(base_url() . '/' . ltrim((string) $employee['appointment_order_path'], '/')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><?= e($employee['appointment_order_name'] ?: __('employees.view_appointment_order', 'View Current Document')) ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= e($employee ? __('actions.save', 'Save Changes') : __('form.create_employee', 'Create Employee')) ?></button>
                <a href="<?= e(route('employees.index')) ?>" class="btn btn-outline-secondary"><?= e(__('actions.cancel', 'Cancel')) ?></a>
            </div>
        </form>
    </div>
</div>
