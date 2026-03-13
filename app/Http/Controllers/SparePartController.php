<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class SparePartController extends Controller
{
    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $parts = array_values(array_filter(DataRepository::spareParts(), function (array $part) use ($filters): bool {
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($part['name'] ?? ''),
                    (string) ($part['part_number'] ?? ''),
                    (string) ($part['category'] ?? ''),
                    (string) ($part['vendor_name'] ?? ''),
                    (string) ($part['location'] ?? ''),
                    (string) ($part['compatible_with'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['category'] !== '' && (string) ($part['category'] ?? '') !== $filters['category']) {
                return false;
            }

            if ($filters['location'] !== '' && (string) ($part['location'] ?? '') !== $filters['location']) {
                return false;
            }

            if ($filters['stock'] === 'low' && empty($part['low_stock'])) {
                return false;
            }

            if ($filters['stock'] === 'healthy' && !empty($part['low_stock'])) {
                return false;
            }

            return true;
        }));

        $this->render('spare-parts.index', [
            'pageTitle' => __('nav.spare_parts', 'Spare Parts'),
            'parts' => $parts,
            'summary' => DataRepository::sparePartsSummary(),
            'filters' => $filters,
            'categoryOptions' => array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['category'] ?? ''), DataRepository::spareParts())))),
            'locationOptions' => array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['location'] ?? ''), DataRepository::spareParts())))),
        ]);
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
            'stock' => trim((string) ($_GET['stock'] ?? '')),
        ];
    }

    public function create(): void
    {
        $this->render('spare-parts.form', [
            'pageTitle' => __('spare_parts.create', 'Create Spare Part'),
            'part' => null,
        ]);
    }

    public function store(): array
    {
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric'],
            'min_quantity' => ['required', 'numeric'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('spare-parts.create', $errors, $_POST);
        }

        $id = DataRepository::createSparePart($_POST);
        DataRepository::logAudit('create', 'spare_parts', $id, null, ['name' => $_POST['name'] ?? '']);
        flash('status', __('spare_parts.saved', 'Spare part stock saved successfully.'));
        return $this->redirect('spare-parts.index');
    }

    public function edit(string $id): void
    {
        $part = DataRepository::findSparePart((int) $id);
        if ($part === null) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Spare Part Not Found']);
            return;
        }

        $this->render('spare-parts.form', [
            'pageTitle' => __('spare_parts.edit', 'Edit Spare Part'),
            'part' => $part,
        ]);
    }

    public function update(string $id): array
    {
        $recordId = (int) $id;
        $errors = $this->validate($_POST, [
            'name' => ['required'],
            'quantity' => ['required', 'numeric'],
            'min_quantity' => ['required', 'numeric'],
        ]);
        if ($errors !== []) {
            return $this->validationRedirect('spare-parts.edit', $errors, $_POST, ['id' => $recordId]);
        }

        $old = DataRepository::findSparePart($recordId);
        DataRepository::updateSparePart($recordId, $_POST);
        DataRepository::logAudit('update', 'spare_parts', $recordId, $old, ['name' => $_POST['name'] ?? '']);
        flash('status', __('spare_parts.updated', 'Spare part updated successfully.'));
        return $this->redirect('spare-parts.index');
    }

    public function destroy(string $id): array
    {
        $recordId = (int) $id;
        $old = DataRepository::findSparePart($recordId);
        DataRepository::deleteSparePart($recordId);
        DataRepository::logAudit('delete', 'spare_parts', $recordId, $old, null);
        flash('status', __('spare_parts.removed', 'Spare part removed successfully.'));
        return $this->redirect('spare-parts.index');
    }

    public function show(string $id): void
    {
        $this->edit($id);
    }
}
