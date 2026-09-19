<?php

use App\Http\Middleware\CheckBlockedIp;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SecurityRateLimit;
use App\Http\Middleware\SensitiveEndpointGuard;
use App\Models\SystemError;
use App\Support\SensitiveDataRedactor;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CheckBlockedIp::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
            SecurityRateLimit::class,
            SensitiveEndpointGuard::class,
            RequirePasswordChange::class,
        ]);
        $middleware->alias([
            'role' => CheckRole::class,
            'security.rate' => SecurityRateLimit::class,
            'password.change' => RequirePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            if ($e instanceof AuthenticationException || $e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException || $e instanceof NotFoundHttpException || $e instanceof TokenMismatchException || ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500)) {
                return;
            }
            try {
                $request = request();
                SystemError::create([
                    'error_level' => 'error', 'error_code' => get_class($e),
                    'message' => mb_substr(SensitiveDataRedactor::text($e->getMessage() ?: get_class($e)) ?? get_class($e), 0, 5000),
                    'file' => mb_substr($e->getFile(), 0, 255), 'line' => $e->getLine(),
                    'trace' => mb_substr(SensitiveDataRedactor::text($e->getTraceAsString()) ?? '', 0, 10000),
                    'url' => mb_substr(SensitiveDataRedactor::url($request->fullUrl()) ?? '', 0, 255),
                    'ip_address' => $request->ip(), 'user_agent' => mb_substr(SensitiveDataRedactor::text((string) $request->userAgent()) ?? '', 0, 255),
                    'user_id' => Auth::id(), 'created_at' => now(),
                ]);
            } catch (Throwable) {
            }
        });

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->expectsJson() || $request->is('api/*'));
        $errorPageResponse = function (Request $request, int $status = 500, ?string $detail = null) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }
            $view = view()->exists("errors.{$status}") ? "errors.{$status}" : 'errors.500';

            return response()->view($view, compact('detail'), $status);
        };

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        });
        $exceptions->render(function (AuthorizationException $e, Request $request) use ($errorPageResponse) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            if (! Auth::check()) {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }

            return $errorPageResponse($request, 403);
        });
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($errorPageResponse) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            if (! Auth::check()) {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }

            return $errorPageResponse($request, 403);
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Not Found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });
        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($errorPageResponse) {
            return $errorPageResponse($request, 419);
        });
        $exceptions->render(function (UniqueConstraintViolationException $e, Request $request) {
            $message = $e->getMessage();
            if (preg_match("/for key '(.+?)\\.(.+?)'/", $message, $m)) {
                $errorMsg = "Data gagal disimpan: duplikasi pada {$m[2]} di tabel {$m[1]}.";
            } elseif (preg_match("/for key '(.+?)'/", $message, $m)) {
                $errorMsg = "Data gagal disimpan: data duplikat terdeteksi ({$m[1]}).";
            } else {
                $errorMsg = 'Data gagal disimpan: data duplikat terdeteksi.';
            }
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(422, $errorMsg);
            }

            return back()->withInput()->with('error', $errorMsg);
        });
        $exceptions->render(function (Throwable $e, Request $request) use ($errorPageResponse) {
            if ($e instanceof AuthenticationException || $e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException || $e instanceof ValidationException || $e instanceof UniqueConstraintViolationException || $e instanceof TokenMismatchException) {
                return null;
            }
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $status >= 500 ? 'Server Error.' : Response::$statusTexts[$status] ?? 'Error.'], $status);
            }

            $detail = match (true) {
                $request->routeIs('admin.kelas-siswa.import.template') => 'Template import siswa gagal dibuat. Silakan coba lagi atau hubungi administrator.',
                $status >= 500 => 'Permintaan tidak dapat diproses saat ini.',
                default => null,
            };

            return $errorPageResponse($request, $status, $detail);
        });
    })->create();
