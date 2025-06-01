<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class EnsureUserIsCounselor
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('EnsureUserIsCounselor Middleware Triggered (Alternative approach).');
        Log::info('All Session Data in Middleware: ', Session::all());

        // Langkah 1: Periksa apakah ada indikasi login dasar (misalnya, UID Firebase ada di session)
        if (!Session::has('uid')) {
            Log::warning('No UID in session. User not considered logged in by custom check. Redirecting to login.');
            // Jika tidak ada UID, anggap belum login, arahkan ke halaman login
            return redirect()->route('login')->with('error', 'Sesi Anda tidak valid atau Anda belum login. Silakan login kembali.');
        }

        // Langkah 2: Jika UID ada, periksa apakah pengguna adalah konselor
        if (!Session::get('isCounselor')) {
            Log::warning('User has UID, but is NOT a counselor or session "isCounselor" is not true. Aborting with 403.');
            // Jika bukan konselor (atau session 'isCounselor' tidak bernilai true),
            // hentikan dengan error 403 (Forbidden).
            abort(403, 'Akses Ditolak. Halaman ini hanya untuk Konselor.');
        }

        Log::info('User has UID and is verified as a counselor. Allowing access to route: ' . $request->path());
        // Jika lolos kedua pemeriksaan, izinkan akses
        return $next($request);
    }
}