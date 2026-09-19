<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use App\Models\Notifikasi;
use App\Models\WaliKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing('role');
        $role = $user?->role?->nama_role;
        try {
            $theme = Pengaturan::getValue('warna_tema', 'hijau');
        } catch (\Throwable) {
            $theme = 'hijau';
        }
        $themeColors = [
            'hijau' => [
                'primary' => '#198754',
                'secondary' => '#0d6efd',
                'sidebar' => '#166534',
                'navbar' => '#198754',
            ],
            'biru-azure' => [
                'primary' => '#0d6efd',
                'secondary' => '#22c55e',
                'sidebar' => '#1d4ed8',
                'navbar' => '#0d6efd',
            ],
            'biru-aqua' => [
                'primary' => '#0891b2',
                'secondary' => '#14b8a6',
                'sidebar' => '#0e7490',
                'navbar' => '#0891b2',
            ],
            'indigo' => [
                'primary' => '#4f46e5',
                'secondary' => '#06b6d4',
                'sidebar' => '#3730a3',
                'navbar' => '#4338ca',
            ],
            'marun' => [
                'primary' => '#be123c',
                'secondary' => '#f59e0b',
                'sidebar' => '#881337',
                'navbar' => '#be123c',
            ],
        ];
        $activeTheme = $themeColors[$theme] ?? $themeColors['hijau'];

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email' => $user->email,
                    'foto_url' => $user->foto ? Storage::disk('public')->url($user->foto) : null,
                    'role' => $role,
                    'role_label' => match ($role) {
                        'admin' => 'Admin',
                        'guru' => 'Guru',
                        'siswa' => 'Siswa',
                        'kepala_sekolah' => 'Kepala Sekolah',
                        default => $role,
                    },
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'school' => [
                'name' => school_setting('school_name', 'Nama Sekolah'),
                'short_name' => school_setting('school_short_name', 'LMS'),
                'app_name' => 'LMS Sekolah',
                'logo_url' => school_logo_url(),
                'favicon_url' => school_favicon_url(),
            ],
            'theme' => [
                'name' => $theme,
                'colors' => $activeTheme,
            ],
            'capabilities' => [
                'has_wali_kelas' => fn () => $this->hasWaliKelasAktif($user, $role),
            ],
            'notifications' => [
                'route' => $this->notificationRoute($role),
                'mark_all_route' => $this->notificationMarkAllRoute($role),
                'unread_count' => fn () => $this->unreadNotificationCount($user, $role),
                'latest' => fn () => $this->latestNotifications($user, $role),
            ],
        ]);
    }

    private function hasWaliKelasAktif($user, ?string $role): bool
    {
        if (! $user || $role !== 'guru') {
            return false;
        }

        try {
            return WaliKelas::where('guru_id', $user->id)->aktif()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function notificationRoute(?string $role): ?string
    {
        return match ($role) {
            'admin' => route('admin.notifikasi.index'),
            'guru' => route('guru.notifikasi.index'),
            'siswa' => route('siswa.notifikasi.index'),
            'kepala_sekolah' => route('kepsek.notifikasi.index'),
            default => null,
        };
    }

    private function notificationMarkAllRoute(?string $role): ?string
    {
        return match ($role) {
            'admin' => route('admin.notifikasi.mark-all-read'),
            'guru' => route('guru.notifikasi.mark-all-read'),
            'siswa' => route('siswa.notifikasi.mark-all-read'),
            'kepala_sekolah' => route('kepsek.notifikasi.mark-all-read'),
            default => null,
        };
    }

    private function unreadNotificationCount($user, ?string $role): int
    {
        if (! $user || ! in_array($role, ['admin', 'guru', 'siswa', 'kepala_sekolah'], true)) {
            return 0;
        }

        try {
            return Notifikasi::where('user_id', $user->id)->where('is_read', false)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function latestNotifications($user, ?string $role): array
    {
        if (! $user || ! in_array($role, ['admin', 'guru', 'siswa', 'kepala_sekolah'], true)) {
            return [];
        }

        try {
            return Notifikasi::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(fn (Notifikasi $notification) => [
                    'id' => $notification->id,
                    'judul' => $notification->judul,
                    'pesan' => $notification->pesan,
                    'is_read' => (bool) $notification->is_read,
                    'created_at' => optional($notification->created_at)->diffForHumans(),
                    'mark_read_route' => match ($role) {
                        'admin' => route('admin.notifikasi.mark-read', $notification),
                        'guru' => route('guru.notifikasi.mark-read', $notification),
                        'siswa' => route('siswa.notifikasi.mark-read', $notification),
                        'kepala_sekolah' => route('kepsek.notifikasi.mark-read', $notification),
                        default => null,
                    },
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
