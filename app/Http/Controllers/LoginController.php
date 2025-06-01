<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn as FirebaseFailedToSignIn;

class LoginController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        Log::info('LoginController: __construct called.');
        $this->firestore = app('firebase.firestore');
        $this->auth = app('firebase.auth');
        Log::info('LoginController: Firebase instances resolved in constructor.');
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
        Log::info('LoginController: login method started.');
        $idTokenString = $request->input('idToken');

        if (!$idTokenString) {
            Log::warning('LoginController: ID Token not provided.');
            return response()->json(['error' => 'ID Token diperlukan'], 400);
        }

        try {
            // Verifikasi ID Token Firebase
            Log::info('LoginController: Attempting to verify ID Token.');
            $verifiedIdToken = $this->auth->verifyIdToken($idTokenString);
            $uid = $verifiedIdToken->claims()->get('sub');

            Log::info('LoginController: ID Token verified. UID: ' . $uid);
            $userName = $verifiedIdToken->claims()->get('name');
            $userAvatar = $verifiedIdToken->claims()->get('picture');

            // Jika nama tidak ada di claims, maka ambil dari Firestore
            if (empty($userName)) {
                $userDoc = $this->firestore->database()
                    ->collection('users')
                    ->document($uid)
                    ->snapshot();

                if ($userDoc->exists()) {
                    $userName = $userDoc->data()['name'] ?? null;
                    $userAvatar = $userDoc->data()['avatar'] ?? null;
                }
            }

            // Ambil data counselor dari Firestore berdasarkan UID
            Log::info('LoginController: Attempting to get counselor document snapshot for UID: ' . $uid);
            $counselorDoc = $this->firestore->database()
                ->collection('counselors')
                ->document($uid)
                ->snapshot();
            Log::info('LoginController: Counselor document snapshot obtained. Exists: ' . ($counselorDoc->exists() ? 'true' : 'false'));

            $isCounselor = $counselorDoc->exists();

            if ($isCounselor) {
                $userName = $counselorDoc->data()['name'] ?? $userName;
                $userAvatar = $counselorDoc->data()['avatar'] ?? $userAvatar;
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
            Log::error('Login Error caught in LoginController: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'idToken_provided' => !empty($idTokenString),
                'uid_from_token' => isset($uid) ? $uid : 'N/A'
            ]);

            $errorMessage = $e->getMessage();
            $userFriendlyError = 'Login gagal. Terjadi kesalahan pada server, mohon coba lagi.'; // Default generic message

            // Cek jika error adalah stack overflow
            if (str_contains($errorMessage, 'Maximum call stack size')) {
                $userFriendlyError = 'Login gagal. Terjadi masalah internal, mohon coba kembali.';
            }

            return response()->json(['error' => $userFriendlyError], 401);
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
            Session::forget('uid');
            Session::forget('isCounselor');
            Session::forget('userName');
            Session::forget('userAvatar');

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