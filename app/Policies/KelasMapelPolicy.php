<?php

namespace App\Policies;

use App\Models\KelasMapel;
use App\Models\User;

class KelasMapelPolicy
{
    /**
     * Determine if the user (guru) mengajar kelas_mapel ini.
     */
    public function mengajar(User $user, KelasMapel $kelasMapel): bool
    {
        return $user->isGuru()
            && (int) $kelasMapel->guru_id === (int) $user->id
            && $kelasMapel->isAktif();
    }
}
