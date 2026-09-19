<?php

namespace Tests\Unit;

use App\Models\NilaiAkhir;
use App\Services\NilaiService;
use Tests\TestCase;

class NilaiServiceTest extends TestCase
{
    public function test_rata_akhir_menghitung_semua_komponen_termasuk_nilai_harian(): void
    {
        $nilai = new NilaiAkhir([
            'sum1' => 80,
            'nilai_harian' => 100,
            'sts' => 60,
        ]);

        $this->assertSame(80.0, (new NilaiService())->hitungRataAkhir($nilai));
    }
}
