<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaliKelas;

class WaliKelasPolicy
{
    public function kelola(User $user, WaliKelas $waliKelas): bool
    {
        return $user->isGuru()
            && (int) $waliKelas->guru_id === (int) $user->id
            && $waliKelas->isAktif();
    }

    public function lihatLaporan(User $user, WaliKelas $waliKelas): bool
    {
        return $user->isKepalaSekolah() && $waliKelas->isAktif();
    }
}
