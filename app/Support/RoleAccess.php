<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;

class RoleAccess
{
    public const ADMIN = 'admin';

    public const GURU = 'guru';

    public const SISWA = 'siswa';

    public const KEPALA_SEKOLAH = 'kepala_sekolah';

    public const ALL = [
        self::ADMIN,
        self::GURU,
        self::SISWA,
        self::KEPALA_SEKOLAH,
    ];

    public const STAFF_MANAGED_BY_ADMIN = [
        self::ADMIN,
        self::GURU,
        self::KEPALA_SEKOLAH,
    ];

    public static function staffRoles(): Collection
    {
        return Role::whereIn('nama_role', self::STAFF_MANAGED_BY_ADMIN)
            ->orderBy('nama_role')
            ->get();
    }

    public static function staffRoleIds(): array
    {
        return self::staffRoles()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function isStaffRole(Role $role): bool
    {
        return in_array($role->nama_role, self::STAFF_MANAGED_BY_ADMIN, true);
    }
}
