<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class AuthenticateFirebase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('--- AuthenticateFirebase Middleware Start ---');
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('Session UID exists: ' . (Session::has('uid') ? 'true' : 'false'));
        Log::info('Request expects JSON: ' . ($request->expectsJson() ? 'true' : 'false'));
        Log::info('Request is AJAX: ' . ($request->ajax() ? 'true' : 'false'));
        Log::info('Request Method: ' . $request->method());
        // Periksa apakah UID pengguna ada di sesi
        if (!Session::has('uid')) {
            // Log untuk debugging
            Log::warning('Pengguna tidak terotentikasi. Mencoba mengakses: ' . $request->fullUrl());

            // Jika ini adalah permintaan AJAX atau mengharapkan respons JSON,
            // kembalikan respons JSON error
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir atau Anda belum login. Mohon login kembali.'
                ], 401); // 401 Unauthorized
            }

            // Jika ini bukan permintaan AJAX, alihkan ke halaman login
            return redirect()->route('login')->with('error', 'Anda harus login untuk mengakses halaman ini.');
        }

        Log::info('Pengguna terotentikasi. Melanjutkan request.');
        // Jika UID ada di sesi, lanjutkan ke request berikutnya
        return $next($request);
    }
}