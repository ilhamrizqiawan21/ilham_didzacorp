<?php

namespace App\Support;

class RoleAccess
{
    public const GURU = 'guru';

    public const SISWA = 'siswa';

    public const ALL = [
        self::GURU,
        self::SISWA,
    ];
}
