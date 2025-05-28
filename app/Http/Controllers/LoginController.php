<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Firestore;
// use Kreait\Firebase\Factory; // Tidak diperlukan jika app('firebase.auth') sudah ada
// use Kreait\Firebase\ServiceAccount; // Tidak diperlukan jika app('firebase.auth') sudah ada
// use Kreait\Firebase\Database; // Tidak diperlukan jika app('firebase.auth') sudah ada
// use Illuminate\Support\Facades\Validator; // Tidak diperlukan di sini

class LoginController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
        $this->firestore = app('firebase.firestore');
    }

    /**
     * Menampilkan halaman login.
     *
     * @return \Illuminate\View\View
     */
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $idTokenString = $request->input('idToken');

        if (!$idTokenString) {
            return response()->json(['error' => 'ID Token diperlukan'], 400);
        }

        try {
            // Verifikasi ID Token Firebase
            $verifiedIdToken = $this->auth->verifyIdToken($idTokenString);
            $uid = $verifiedIdToken->claims()->get('sub');
            $userName = $verifiedIdToken->claims()->get('name'); // Ambil nama dari ID Token claims
            $userAvatar = $verifiedIdToken->claims()->get('picture'); // Ambil avatar dari ID Token claims

            // Jika nama tidak ada di claims, coba ambil dari Firestore
            if (empty($userName)) {
                // Ambil data user dari Firestore berdasarkan UID
                $userDoc = $this->firestore->database()
                    ->collection('users') // Asumsi ada koleksi 'users' untuk user biasa
                    ->document($uid)
                    ->snapshot();

                if ($userDoc->exists()) {
                    $userName = $userDoc->data()['name'] ?? null;
                    $userAvatar = $userDoc->data()['avatar'] ?? null;
                }
            }

            // Ambil data counselor dari Firestore berdasarkan UID (untuk cek role dan nama/avatar jika user adalah counselor)
            $counselorDoc = $this->firestore->database()
                ->collection('counselors')
                ->document($uid)
                ->snapshot();

            $isCounselor = $counselorDoc->exists();

            if ($isCounselor) {
                // Jika konselor, gunakan nama dan avatar dari data konselor Firestore
                $userName = $counselorDoc->data()['name'] ?? $userName; // Prioritaskan nama dari counselorDoc
                $userAvatar = $counselorDoc->data()['avatar'] ?? $userAvatar; // Prioritaskan avatar dari counselorDoc
            }

            // Default jika nama masih kosong
            $userName = $userName ?? 'Pengguna GasKonsul';
            $userAvatar = $userAvatar ?? asset('images/default_profile.png');


            // Simpan session
            Session::put('uid', $uid);
            Session::put('isCounselor', $isCounselor);
            Session::put('userName', $userName);
            Session::put('userAvatar', $userAvatar);

            Log::info("Login berhasil. UID: $uid, Nama: $userName, Role: " . ($isCounselor ? 'Counselor' : 'User'));


            if ($isCounselor) {
                return response()->json(['message' => 'Login counselor berhasil', 'role' => 'counselor']);
            } else {
                return response()->json(['message' => 'Login user biasa berhasil', 'role' => 'user']);
            }
        } catch (\Throwable $e) {
            Log::error('Login Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Token tidak valid atau error server: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Log out the user from Firebase and clear session.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        try {
            // Opsional: Cabut token Firebase jika Anda menggunakan session token di backend
            // $this->auth->revokeRefreshTokens(Session::get('uid'));

            Session::forget('uid'); // Hapus UID dari sesi
            Session::forget('isCounselor'); // Hapus role dari sesi
            Session::forget('userName');
            Session::forget('userAvatar');

            // Hapus sesi Laravel (penting untuk security)
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('Logout berhasil.');
            return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
        } catch (\Throwable $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal logout: ' . $e->getMessage());
        }
    }
}