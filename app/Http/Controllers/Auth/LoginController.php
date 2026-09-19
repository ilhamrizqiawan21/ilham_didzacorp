<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogLogin;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLogin()
    {
        $publicAnnouncements = collect();

        if (Schema::hasTable('pengumuman') && Schema::hasColumn('pengumuman', 'is_public_login')) {
            $publicAnnouncements = Pengumuman::with('creator')
                ->where('is_public_login', true)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn (Pengumuman $item) => [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'isi' => $item->isi,
                    'creator_name' => $item->creator?->nama_lengkap,
                    'created_at' => $item->created_at,
                    'attachment' => $item->public_file_path ? [
                        'name' => $item->public_file_name ?: basename($item->public_file_path),
                        'size' => $item->public_file_size,
                        'url' => route('public-pengumuman.attachment', $item),
                    ] : null,
                ]);
        }

        return Inertia::render('Auth/Login', [
            'branding' => [
                'school_name' => school_setting('school_name', 'Nama Sekolah'),
                'school_short_name' => school_setting('school_short_name', 'LMS'),
                'school_motto' => school_setting('motto', 'Learning Management System'),
                'school_address' => school_setting('address', 'Alamat sekolah belum diatur'),
                'support_contact' => school_setting('whatsapp') ?: school_setting('phone'),
                'logo_url' => school_logo_url(),
            ],
            'loginUrl' => route('login.post'),
            'publicAnnouncements' => $publicAnnouncements,
            'year' => date('Y'),
        ]);
    }

    public function downloadPublicAnnouncementAttachment(Pengumuman $pengumuman)
    {
        abort_unless($pengumuman->is_public_login && $pengumuman->public_file_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($pengumuman->public_file_path), 404);

        return response()->download(
            $disk->path($pengumuman->public_file_path),
            $pengumuman->public_file_name ?: basename($pengumuman->public_file_path)
        );
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $ip = $request->ip();
        $throttleKey = Str::lower($username . '|' . $ip);
        // Keep a second, account-based bucket so a distributed attack cannot
        // bypass the per-IP limit by rotating source addresses.
        $accountThrottleKey = 'login-account:'.sha1(Str::lower(trim($username)));

        if (RateLimiter::tooManyAttempts($throttleKey, 5) || RateLimiter::tooManyAttempts($accountThrottleKey, 10)) {
            $seconds = max(
                RateLimiter::availableIn($throttleKey),
                RateLimiter::availableIn($accountThrottleKey)
            );

            return back()->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.");
        }

        $loginField = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$loginField => $username, 'password' => $request->password], $request->filled('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
            }

            $request->session()->regenerate();

            LogLogin::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'role' => $user->role?->nama_role ?? 'unknown',
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'login_time' => now(),
            ]);

            RateLimiter::clear($throttleKey);
            RateLimiter::clear($accountThrottleKey);

            $defaultUrl = $this->redirectToByRole($user);
            $intendedUrl = $request->session()->pull('url.intended', $defaultUrl);
            $intendedUrl = $this->intendedUrlIsAllowedForRole($intendedUrl, $user->role?->nama_role, $request)
                ? $intendedUrl
                : $defaultUrl;

            if ($request->header('X-Inertia')) {
                return Inertia::location($intendedUrl);
            }

            return redirect($intendedUrl);
        }

        RateLimiter::hit($throttleKey, 60);
        RateLimiter::hit($accountThrottleKey, 60);

        return back()->with('error', 'Username atau password salah.')->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    protected function redirectToByRole($user): string
    {
        return match ($user->role?->nama_role) {
            'admin' => route('admin.dashboard'),
            'guru' => route('guru.dashboard'),
            'siswa' => route('siswa.dashboard'),
            'kepala_sekolah' => route('kepsek.dashboard'),
            default => '/',
        };
    }

    private function intendedUrlIsAllowedForRole(?string $url, ?string $role, Request $request): bool
    {
        if (! $url || ! $role) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        // Absolute/protocol-relative intended URLs must remain on this application host.
        if (isset($parts['host']) && ! hash_equals((string) $request->getHost(), (string) $parts['host'])) {
            return false;
        }

        if (isset($parts['scheme']) && ! hash_equals((string) $request->getScheme(), (string) $parts['scheme'])) {
            return false;
        }

        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');

        return match ($role) {
            'admin' => str_starts_with($path, '/admin'),
            'guru' => str_starts_with($path, '/guru'),
            'siswa' => str_starts_with($path, '/siswa'),
            'kepala_sekolah' => str_starts_with($path, '/kepsek'),
            default => false,
        };
    }
}
