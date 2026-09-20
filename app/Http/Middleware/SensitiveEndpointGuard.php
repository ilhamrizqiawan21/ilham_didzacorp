<?php

namespace App\Http\Middleware;

use App\Models\Siswa;
use App\Models\User;
use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents legacy endpoints from exposing shared/default passwords.
 *
 * This middleware intentionally sits before the controller action so the
 * legacy implementation cannot execute while the route remains available
 * for backwards compatibility.
 */
class SensitiveEndpointGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('admin.kelas-siswa.reset-password')) {
            return $this->secureStudentResetPassword($request, $next);
        }

        return $next($request);
    }

    private function secureStudentResetPassword(Request $request, Closure $next): Response
    {
        if ($response = $this->authorizeAdmin($request, $next)) {
            return $response;
        }

        /** @var Siswa|null $siswa */
        $siswa = $request->route('siswa');
        $siswa?->loadMissing('user');
        $user = $siswa?->user;

        abort_unless($siswa instanceof Siswa && $user instanceof User && $user->isSiswa(), 404);

        $user->forceFill([
            'password' => Hash::make(User::DEFAULT_PASSWORD),
            'is_password_default' => true,
        ])->save();

        return back()
            ->with('success', 'Password siswa berhasil direset.')
            ->with('student_password', [
                'title' => 'Password baru siswa',
                'name' => $user->nama_lengkap,
                'username' => $user->username,
                'password' => User::DEFAULT_PASSWORD,
            ]);
    }

    private function authorizeAdmin(Request $request, Closure $next): ?Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        abort_unless($user->hasRole(RoleAccess::GURU), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return null;
    }
}
