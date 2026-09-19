<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_web_403_uses_forbidden_page(): void
    {
        Route::get('/__test-error-403', fn () => abort(403));

        $response = $this->get('/__test-error-403');

        $response->assertStatus(403);
        $response->assertSee('Akses Ditolak');
        $response->assertDontSee('Sistem sedang dalam pemeliharaan');
    }

    public function test_web_404_uses_not_found_page_instead_of_maintenance_page(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada');
        $response->assertStatus(404);
        $response->assertSee('Halaman tidak ditemukan');
        $response->assertDontSee('sedang dalam perbaikan/pengembangan');
    }

    public function test_web_419_uses_session_expired_page(): void
    {
        Route::get('/__test-error-419', fn () => throw new TokenMismatchException);
        $response = $this->get('/__test-error-419');
        $response->assertStatus(419);
        $response->assertSee('Sesi Anda sudah berakhir');
        $response->assertDontSee('Sistem sedang dalam pemeliharaan');
    }

    public function test_web_429_uses_too_many_requests_page(): void
    {
        Route::get('/__test-error-429', fn () => abort(429));
        $response = $this->get('/__test-error-429');
        $response->assertStatus(429);
        $response->assertSee('Terlalu banyak permintaan');
        $response->assertDontSee('Sistem sedang dalam pemeliharaan');
    }

    public function test_web_500_uses_system_error_page(): void
    {
        Route::get('/__test-error-500', fn () => abort(500));
        $response = $this->get('/__test-error-500');
        $response->assertStatus(500);
        $response->assertSee('Terjadi kesalahan sistem');
        $response->assertDontSee('Sistem sedang dalam pemeliharaan');
    }

    public function test_web_503_uses_maintenance_page(): void
    {
        Route::get('/__test-error-503', fn () => abort(503));
        $response = $this->get('/__test-error-503');
        $response->assertStatus(503);
        $response->assertSee('Sistem sedang dalam pemeliharaan');
        $response->assertDontSee('Terjadi kesalahan sistem');
    }

    public function test_json_errors_use_safe_messages(): void
    {
        Route::get('/__test-json-error-403', fn () => abort(403));
        Route::get('/__test-json-error-404', fn () => abort(404));
        Route::get('/__test-json-error-500', fn () => abort(500, 'database password=super-secret'));

        $this->getJson('/__test-json-error-403')
            ->assertForbidden()
            ->assertJson(['message' => 'Forbidden']);

        $this->getJson('/__test-json-error-404')
            ->assertNotFound()
            ->assertJson(['message' => 'Not Found.']);

        $this->getJson('/__test-json-error-500')
            ->assertStatus(500)
            ->assertJson(['message' => 'Server Error.'])
            ->assertDontSee('super-secret');
    }
}
