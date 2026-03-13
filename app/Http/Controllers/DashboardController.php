<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->render('dashboard.index', [
            'pageTitle' => __('nav.dashboard', 'Dashboard'),
            'stats' => DataRepository::dashboardStats(),
            'overview' => DataRepository::dashboardOverview(),
            'charts' => DataRepository::dashboardCharts(),
            'recentMovements' => DataRepository::recentMovements(),
        ]);
    }
}
