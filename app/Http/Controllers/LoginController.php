<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
        $this->firestore = app('firebase.firestore');
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

            // Ambil data counselor dari Firestore berdasarkan UID
            $counselorDoc = $this->firestore->database()
                ->collection('counselors')
                ->document($uid)
                ->snapshot();

            // Simpan session
            Session::put('uid', $uid);
            Session::put('isCounselor', $counselorDoc->exists());

            if ($counselorDoc->exists()) {
                return response()->json(['message' => 'Login counselor berhasil', 'role' => 'counselor']);
            } else {
                return response()->json(['message' => 'Login user biasa berhasil', 'role' => 'user']);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token tidak valid: ' . $e->getMessage()], 401);
        }
    }
}
