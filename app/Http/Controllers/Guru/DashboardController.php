<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly TeacherDashboardService $dashboardService)
    {
    }

    public function index()
    {
        return Inertia::render(
            'Guru/Dashboard',
            $this->dashboardService->forGuru((int) Auth::id())
        );
    }
}
