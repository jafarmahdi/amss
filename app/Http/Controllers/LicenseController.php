<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class LicenseController extends Controller
{
    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $licenses = array_values(array_filter(DataRepository::licenses(), function (array $license) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($license['product_name'] ?? ''),
                    (string) ($license['vendor_name'] ?? ''),
                    (string) ($license['license_key'] ?? ''),
                    (string) ($license['employee_name'] ?? ''),
                    (string) ($license['asset_name'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['status'] !== '' && (string) ($license['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['type'] !== '' && (string) ($license['license_type'] ?? '') !== $filters['type']) {
                return false;
            }

            if ($filters['availability'] === 'available' && (int) ($license['available_seats'] ?? 0) <= 0) {
                return false;
            }

            if ($filters['availability'] === 'full' && (int) ($license['available_seats'] ?? 0) > 0) {
                return false;
            }

            return true;
        }));

        $this->render('licenses.index', [
            'pageTitle' => __('nav.licenses', 'Licenses'),
            'licenses' => $licenses,
            'summary' => DataRepository::licenseSummary(),
            'filters' => $filters,
            'typeOptions' => ['subscription', 'perpetual', 'trial', 'oem'],
            'statusOptions' => ['active', 'renewal_due', 'expired', 'inactive'],
        ]);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'availability' => trim((string) ($_GET['availability'] ?? '')),
        ];
    }

    public function create(): void
    {
        $this->render('licenses.form', [
            'pageTitle' => __('licenses.create', 'Create License'),
            'license' => null,
            'licenseTypes' => ['subscription', 'perpetual', 'trial', 'oem'],
            'statusOptions' => ['active', 'renewal_due', 'expired', 'inactive'],
            'assets' => DataRepository::assets(),
            'employees' => DataRepository::activeEmployees(),
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'product_name' => ['required'],
            'license_type' => ['required', 'in:subscription,perpetual,trial,oem'],
            'seats_total' => ['required', 'numeric'],
            'seats_used' => ['required', 'numeric'],
            'status' => ['required', 'in:active,renewal_due,expired,inactive'],
        ]);
        if ((int) ($_POST['seats_used'] ?? 0) > (int) ($_POST['seats_total'] ?? 0)) {
            $errors['seats_used'] = __('licenses.seats_limit', 'Used seats cannot exceed total seats.');
        }
        if ($errors !== []) {
            return $this->validationRedirect('licenses.create', $errors, $_POST);
        }

        $id = DataRepository::createLicense($_POST);
        DataRepository::logAudit('create', 'licenses', $id, null, ['product_name' => $_POST['product_name'] ?? '']);
        flash('status', __('licenses.saved', 'License stock saved successfully.'));
        return $this->redirect('licenses.index');
    }

    public function edit(string $id): void
    {
        $license = DataRepository::findLicense((int) $id);
        if ($license === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'License Not Found']);
            return;
        }

        $this->render('licenses.form', [
            'pageTitle' => __('licenses.edit', 'Edit License'),
            'license' => $license,
            'licenseTypes' => ['subscription', 'perpetual', 'trial', 'oem'],
            'statusOptions' => ['active', 'renewal_due', 'expired', 'inactive'],
            'assets' => DataRepository::assets(),
            'employees' => DataRepository::activeEmployees(),
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'product_name' => ['required'],
            'license_type' => ['required', 'in:subscription,perpetual,trial,oem'],
            'seats_total' => ['required', 'numeric'],
            'seats_used' => ['required', 'numeric'],
            'status' => ['required', 'in:active,renewal_due,expired,inactive'],
        ]);
        if ((int) ($_POST['seats_used'] ?? 0) > (int) ($_POST['seats_total'] ?? 0)) {
            $errors['seats_used'] = __('licenses.seats_limit', 'Used seats cannot exceed total seats.');
        }
        if ($errors !== []) {
            return $this->validationRedirect('licenses.edit', $errors, $_POST, ['id' => $recordId]);
        }

        $old = DataRepository::findLicense($recordId);
        DataRepository::updateLicense($recordId, $_POST);
        DataRepository::logAudit('update', 'licenses', $recordId, $old, ['product_name' => $_POST['product_name'] ?? '']);
        flash('status', __('flash.license_updated', 'License updated successfully.'));
        return $this->redirect('licenses.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findLicense($recordId);
        DataRepository::deleteLicense($recordId);
        DataRepository::logAudit('delete', 'licenses', $recordId, $old, null);
        flash('status', __('flash.license_removed', 'License removed successfully.'));
        return $this->redirect('licenses.index');
    }

    public function show(string $id): void
    {
        $license = DataRepository::licenseDetail((int) $id);
        if ($license === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'License Not Found']);
            return;
        }

        $this->render('licenses.show', [
            'pageTitle' => $license['product_name'],
            'license' => $license,
            'renewals' => DataRepository::licenseRenewals((int) $id),
            'allocations' => DataRepository::licenseAllocations((int) $id),
        ]);
    }

    public function renew(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'new_expiry_date' => ['required'],
            'renewed_at' => ['required'],
            'new_seats_total' => ['required', 'numeric'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('licenses.show', $errors, $_POST, ['id' => $recordId]);
        }

        $old = DataRepository::findLicense($recordId);
        DataRepository::renewLicense($recordId, $_POST);
        DataRepository::logAudit('renew', 'licenses', $recordId, $old, [
            'new_expiry_date' => $_POST['new_expiry_date'] ?? '',
            'new_seats_total' => $_POST['new_seats_total'] ?? '',
        ]);
        flash('status', __('flash.license_updated', 'License updated successfully.'));

        return $this->redirect('licenses.show', ['id' => $recordId]);
    }
}
