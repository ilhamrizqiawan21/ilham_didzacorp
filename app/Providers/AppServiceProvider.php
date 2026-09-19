<?php

namespace App\Providers;

use App\Models\KelasMapel;
use App\Policies\KelasMapelPolicy;
use App\Policies\TugasPolicy;
use App\Policies\WaliKelasPolicy;
use App\Http\Controllers\Guru\NilaiController;
use App\Http\Controllers\Guru\NilaiRekapController;
use App\Http\Controllers\Guru\SikapController;
use App\Http\Controllers\Guru\SikapRekapController;
use App\Services\CalendarTimelineService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The existing route names are kept stable while the recap endpoints are migrated to Inertia.
        $this->app->bind(NilaiController::class, fn ($app) => $app->make(NilaiRekapController::class));
        $this->app->bind(SikapController::class, fn ($app) => $app->make(SikapRekapController::class));
    }

    public function boot(): void
    {
        Gate::define('mengajar', [KelasMapelPolicy::class, 'mengajar']);
        Gate::define('mengajar-tugas', [TugasPolicy::class, 'mengajar']);
        Gate::define('kelola-wali-kelas', [WaliKelasPolicy::class, 'kelola']);
        Gate::define('lihat-laporan-wali-kelas', [WaliKelasPolicy::class, 'lihatLaporan']);

        Inertia::share('timelineEvents', function () {
            $routeName = request()->route()?->getName();
            if (!request()->user() || !in_array($routeName, [
                'admin.kalender',
                'guru.kalender',
                'siswa.kalender',
                'kepsek.kalender',
            ], true)) {
                return [];
            }

            $year = (int) request()->query('year', now()->year);
            $month = (int) request()->query('month', now()->month);

            return app(CalendarTimelineService::class)->forUser(request()->user(), $year, $month)->all();
        });
    }
}
