<?php

namespace App\Policies;

use App\Models\Tugas;
use App\Models\User;

class TugasPolicy
{
    /**
     * Determine whether a guru owns the class subject that contains the task.
     *
     * This is intentionally based on the task's parent KelasMapel rather than
     * trusting the task id alone, preventing same-role IDOR across teachers.
     */
    public function mengajar(User $user, Tugas $tugas): bool
    {
        $kelasMapel = $tugas->kelasMapel;

        return $user->isGuru()
            && $kelasMapel !== null
            && (int) $kelasMapel->guru_id === (int) $user->id
            && $kelasMapel->isAktif();
    }
}
