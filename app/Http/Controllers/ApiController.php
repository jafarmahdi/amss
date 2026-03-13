<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class ApiController extends Controller
{
    public function dashboard(): void
    {
        $this->json([
            'stats' => DataRepository::dashboardStats(),
            'overview' => DataRepository::dashboardOverview(),
            'charts' => DataRepository::dashboardCharts(),
        ]);
    }

    public function assets(): void
    {
        $this->json(DataRepository::assets());
    }

    public function asset(string $id): void
    {
        $asset = DataRepository::findAsset((int) $id);
        if ($asset === null) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }

        $asset['assignments'] = DataRepository::assetAssignments((int) $id);
        $asset['movements'] = DataRepository::assetMovements((int) $id);
        $asset['repairs'] = DataRepository::assetRepairs((int) $id);
        $asset['handovers'] = DataRepository::assetHandovers((int) $id);
        $asset['maintenance'] = DataRepository::assetMaintenance((int) $id);
        $this->json($asset);
    }

    public function branches(): void
    {
        $this->json(DataRepository::branches());
    }

    public function branch(string $id): void
    {
        $branch = DataRepository::branchDetail((int) $id);
        if ($branch === null) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $branch['employees'] = DataRepository::branchEmployees((int) $id);
        $branch['assets'] = DataRepository::branchAssets((int) $id);
        $this->json($branch);
    }

    public function categories(): void
    {
        $this->json(DataRepository::categories());
    }

    public function employees(): void
    {
        $this->json(DataRepository::employees());
    }

    public function employee(string $id): void
    {
        $employee = DataRepository::findEmployee((int) $id);
        if ($employee === null) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $employee['assets'] = DataRepository::employeeAssetAssignments((int) $id);
        $employee['licenses'] = DataRepository::employeeLicenseAssignments((int) $id);
        $employee['offboarding'] = DataRepository::employeeOffboardingHistory((int) $id);
        $this->json($employee);
    }

    public function licenses(): void
    {
        $this->json(DataRepository::licenses());
    }

    public function license(string $id): void
    {
        $license = DataRepository::licenseDetail((int) $id);
        if ($license === null) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $license['renewals'] = DataRepository::licenseRenewals((int) $id);
        $this->json($license);
    }

    public function spareParts(): void
    {
        $this->json([
            'summary' => DataRepository::sparePartsSummary(),
            'items' => DataRepository::spareParts(),
        ]);
    }

    public function reports(): void
    {
        $this->json([
            'summary' => DataRepository::reportSummary(),
            'dashboard' => DataRepository::dashboardOverview(),
        ]);
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
