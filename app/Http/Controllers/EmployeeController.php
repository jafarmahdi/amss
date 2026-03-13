<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\UploadStore;

class EmployeeController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'branch' => trim((string) ($_GET['branch'] ?? '')),
        ];

        $employees = array_values(array_filter(DataRepository::employees(), static function (array $employee) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($employee['name'] ?? ''),
                    (string) ($employee['employee_code'] ?? ''),
                    (string) ($employee['company_email'] ?? ''),
                    (string) ($employee['fingerprint_id'] ?? ''),
                    (string) ($employee['department'] ?? ''),
                    (string) ($employee['job_title'] ?? ''),
                    (string) ($employee['branch_name'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['status'] !== '' && (string) ($employee['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['branch'] !== '' && (string) ($employee['branch_name'] ?? '') !== $filters['branch']) {
                return false;
            }

            return true;
        }));

        $this->render('employees.index', [
            'pageTitle' => __('nav.employees', 'Employees'),
            'employees' => $employees,
            'filters' => $filters,
            'branches' => DataRepository::branches(),
        ]);
    }

    public function create(): void
    {
        $this->render('employees.form', [
            'pageTitle' => __('form.create_employee', 'Create Employee'),
            'employee' => null,
            'statuses' => ['active', 'inactive'],
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'employee_code' => ['required'],
            'company_email' => ['email'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('employees.create', $errors, $_POST);
        }
        $payload = $_POST;
        $payload['appointment_order_name'] = '';
        $payload['appointment_order_path'] = '';
        $id = DataRepository::createEmployee($payload);
        $document = UploadStore::saveEmployeeAppointmentDocument($id, $_FILES['appointment_order'] ?? []);
        if ($document !== null) {
            $payload['appointment_order_name'] = $document['name'];
            $payload['appointment_order_path'] = $document['path'];
            DataRepository::updateEmployee($id, $payload);
        }
        DataRepository::logAudit('create', 'employees', $id, null, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.employee_created', 'Employee created successfully.'));
        return $this->redirect('employees.index');
    }

    public function edit(string $id): void
    {
        $employee = DataRepository::findEmployee((int) $id);
        if ($employee === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Employee Not Found']);
            return;
        }

        $this->render('employees.form', [
            'pageTitle' => __('form.edit_employee', 'Edit Employee'),
            'employee' => $employee,
            'statuses' => ['active', 'inactive'],
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'employee_code' => ['required'],
            'company_email' => ['email'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('employees.edit', $errors, $_POST, ['id' => $recordId]);
        }
        $old = DataRepository::findEmployee($recordId);
        $payload = $_POST;
        $payload['appointment_order_name'] = $old['appointment_order_name'] ?? '';
        $payload['appointment_order_path'] = $old['appointment_order_path'] ?? '';
        $document = UploadStore::saveEmployeeAppointmentDocument($recordId, $_FILES['appointment_order'] ?? []);
        if ($document !== null) {
            UploadStore::deleteFile((string) ($old['appointment_order_path'] ?? ''));
            $payload['appointment_order_name'] = $document['name'];
            $payload['appointment_order_path'] = $document['path'];
        }
        DataRepository::updateEmployee($recordId, $payload);
        DataRepository::logAudit('update', 'employees', $recordId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('flash.employee_updated', 'Employee updated successfully.'));
        return $this->redirect('employees.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findEmployee($recordId);
        DataRepository::deleteEmployee($recordId);
        DataRepository::logAudit('delete', 'employees', $recordId, $old, null);
        flash('status', __('flash.employee_removed', 'Employee removed successfully.'));
        return $this->redirect('employees.index');
    }

    public function offboardingForm(string $id): void
    {
        $employee = DataRepository::findEmployee((int) $id);
        if ($employee === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Employee Not Found']);
            return;
        }

        $this->render('employees.offboarding', [
            'pageTitle' => __('employees.offboarding_title', 'Employee Offboarding'),
            'employee' => $employee,
            'summary' => DataRepository::employeeOffboardingSummary((int) $id),
            'history' => DataRepository::employeeOffboardingHistory((int) $id),
        ]);
    }

    public function offboardingStore(string $id): array
    {
        $recordId = (int) $id;
        $employee = DataRepository::findEmployee($recordId);
        if ($employee === null) {
            return $this->redirect('employees.index');
        }

        $errors = $this->validate($_POST, [
            'reason' => ['required'],
            'offboarded_at' => ['required'],
            'confirm_offboarding' => ['required', 'in:yes'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('employees.offboarding', $errors, $_POST, ['id' => $recordId]);
        }

        $summary = DataRepository::employeeOffboardingSummary($recordId);
        if (!$summary['can_complete']) {
            flash('error', __('employees.offboarding_blocked', 'This employee still has assigned assets or licenses. Clear them before offboarding.'));
            return $this->redirect('employees.offboarding', ['id' => $recordId]);
        }

        $offboardingId = DataRepository::completeEmployeeOffboarding($recordId, $_POST);
        DataRepository::logAudit('offboard', 'employees', $recordId, $employee, [
            'offboarding_id' => $offboardingId,
            'status' => 'inactive',
            'reason' => $_POST['reason'] ?? '',
        ]);
        flash('status', __('employees.offboarding_completed', 'Employee offboarding completed successfully.'));
        return $this->redirect('employees.show', ['id' => $recordId]);
    }

    public function show(string $id): void
    {
        $employee = DataRepository::findEmployee((int) $id);
        if ($employee === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Employee Not Found']);
            return;
        }

        $this->render('employees.show', [
            'pageTitle' => $employee['name'],
            'employee' => $employee,
            'assignments' => DataRepository::employeeAssetAssignments((int) $id),
            'licenses' => DataRepository::employeeLicenseAssignments((int) $id),
            'handovers' => DataRepository::employeeHandovers((int) $id),
            'offboardingHistory' => DataRepository::employeeOffboardingHistory((int) $id),
        ]);
    }
}
