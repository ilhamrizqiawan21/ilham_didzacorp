<?php

namespace App\Providers;

use App\Http\Controllers\Guru\BiodataSiswaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WaliKelasBiodataServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:guru'])
            ->prefix('guru')
            ->name('guru.')
            ->group(function () {
                Route::get('/wali-kelas/{waliKelas}/biodata', [BiodataSiswaController::class, 'index'])
                    ->name('wali-kelas.biodata')
                    ->middleware('can:kelola-wali-kelas,waliKelas');

                Route::put('/wali-kelas/{waliKelas}/biodata/{siswa}', [BiodataSiswaController::class, 'update'])
                    ->name('wali-kelas.biodata.update')
                    ->middleware('can:kelola-wali-kelas,waliKelas');
            });
    }
}
