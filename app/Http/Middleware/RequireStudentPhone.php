<?php

namespace App\Http\Middleware;

use App\Support\WhatsAppPhone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStudentPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.require_student_phone', false)) {
            return $next($request);
        }

        if ($request->routeIs('siswa.pengaturan', 'siswa.pengaturan.*', 'siswa.profil', 'siswa.profil.update')) {
            return $next($request);
        }

        $student = $request->user()?->siswa;
        if (WhatsAppPhone::isValid($student?->nomor_whatsapp) && $student?->whatsapp_opt_in) {
            return $next($request);
        }

        $message = 'Lengkapi nomor telepon dan persetujuan WhatsApp di Pengaturan Akun sebelum melanjutkan.';
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'code' => 'student_phone_required',
                'redirect_url' => route('siswa.pengaturan'),
            ], 403);
        }

        return redirect()->route('siswa.pengaturan')->with('warning', $message);
    }
}
