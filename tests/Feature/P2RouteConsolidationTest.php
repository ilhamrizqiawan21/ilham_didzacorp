<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class P2RouteConsolidationTest extends TestCase
{
    public function test_consolidated_guru_routes_keep_expected_names_and_paths(): void
    {
        $routes = [
            ['guru.tugas.index', [], '/guru/tugas'],
            ['guru.nilai.index', [], '/guru/nilai'],
            ['guru.sikap.index', [], '/guru/sikap'],
            ['guru.wali-kelas.index', [], '/guru/wali-kelas'],
            ['guru.chat.index', [], '/guru/chat'],
            ['guru.notifikasi.index', [], '/guru/notifikasi'],
            ['guru.materi.download', [10, 20], '/guru/materi/10/20/download'],
            ['guru.tugas.pengumpulan', [10, 20], '/guru/tugas/10/20/pengumpulan'],
            ['guru.tugas.file.download', [10, 20, 30], '/guru/tugas/10/20/pengumpulan/30/download'],
        ];

        foreach ($routes as [$name, $parameters, $path]) {
            $this->assertTrue(Route::has($name), "Route [{$name}] should exist.");
            $this->assertSame($path, parse_url(route($name, $parameters), PHP_URL_PATH));
        }
    }

    public function test_removed_duplicate_guru_route_name_stays_removed(): void
    {
        $this->assertFalse(Route::has('guru.guru.materi.download'));
    }
}
