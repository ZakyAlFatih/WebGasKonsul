<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
        $this->firestore = app('firebase.firestore');
    }

    public function storeCounselor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed', // Min 6 characters
            'bidang' => 'required|string|max:255',
            'license' => 'nullable|string|max:255',
            'terms' => 'accepted',
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal :min karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Cek apakah email sudah terdaftar di Firebase Auth
            try {
                $this->auth->getUserByEmail($request->email);
                // Jika tidak ada exception, berarti email SUDAH TERDAFTAR
                return response()->json(['success' => false, 'errors' => ['email' => ['Email ini sudah terdaftar.']]], 422);
            } catch (AuthException $e) {
                // Periksa pesan error untuk menentukan apakah ini adalah 'user not found'
                $errorMessage = $e->getMessage();
                // Ada beberapa varian pesan error 'user not found' tergantung Firebase/Kreait.
                // Kita mencakup yang umum dan yang terlihat di log Anda.
                if (str_contains($errorMessage, 'No user with email') || str_contains($errorMessage, 'user-not-found') || str_contains($errorMessage, 'There is no user record')) {
                    // Ini adalah kasus 'user not found', artinya email belum terdaftar. Lanjutkan registrasi.
                } else {
                    // Ini adalah AuthException lain yang BUKAN 'user not found' (misal: masalah kredensial API, koneksi, dll.).
                    // Kita harus melemparkannya kembali agar ditangani oleh blok catch AuthException di luar.
                    throw $e;
                }
            }

            // Jika sampai di sini, artinya email belum terdaftar di Firebase Auth.
            // Lanjutkan membuat user baru di Firebase Authentication
            $userRecord = $this->auth->createUserWithEmailAndPassword(
                $request->email,
                $request->password
            );

            $uid = $userRecord->uid;

            // Simpan data counselor ke Firestore
            $firestoreRef = $this->firestore->database()->collection('counselors')->document($uid);
            $firestoreRef->set([
                'uid' => $uid,
                'name' => $request->name,
                'email' => $request->email,
                'bidang' => $request->bidang,
                'license' => $request->license,
                'role' => 'counselor',
                'createdAt' => Carbon::now()->toDateTimeString(),
                'rate' => 0,
                'rating' => [],
                'avatar' => null,
                'phone' => null,
            ]);

            Log::info("Pendaftaran counselor berhasil. UID: $uid, Email: {$request->email}");
            return response()->json(['success' => true, 'message' => 'Pendaftaran counselor berhasil! Silakan login.']);

        } catch (AuthException $e) {
            // Tangani AuthException yang mungkin berasal dari createUserWithEmailAndPassword atau yang dilempar ulang
            $errorMessage = $e->getMessage();
            // Coba dapatkan kode error spesifik jika tersedia (untuk 'email-already-in-use')
            $firebaseErrorCode = $e->errorInfo['code'] ?? null; 

            if (str_contains($errorMessage, 'email-already-in-use') || $firebaseErrorCode === 'auth/email-already-in-use') {
                return response()->json(['success' => false, 'errors' => ['email' => ['Email ini sudah terdaftar.']]], 422);
            }
            Log::error('Firebase Auth Error during counselor registration: ' . $e->getMessage(), ['firebase_code' => $firebaseErrorCode, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Pendaftaran gagal: ' . $e->getMessage()], 500);
        } catch (FirebaseException $e) {
            // Tangani FirebaseException (misal masalah koneksi Firestore)
            Log::error('Firebase Error during counselor registration: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Pendaftaran gagal: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            // Tangani error umum lainnya
            Log::error('Unexpected error during counselor registration: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat pendaftaran: ' . $e->getMessage()], 500);
        }
    }

    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed', // Min 6 characters
            'terms' => 'accepted',
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal :min karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Cek apakah email sudah terdaftar di Firebase Auth
            try {
                $this->auth->getUserByEmail($request->email);
                // Jika tidak ada exception, berarti email SUDAH TERDAFTAR
                return response()->json(['success' => false, 'errors' => ['email' => ['Email ini sudah terdaftar.']]], 422);
            } catch (AuthException $e) {
                // Periksa pesan error untuk menentukan apakah ini adalah 'user not found'
                $errorMessage = $e->getMessage();
                if (str_contains($errorMessage, 'No user with email') || str_contains($errorMessage, 'user-not-found') || str_contains($errorMessage, 'There is no user record')) {
                    // Ini adalah kasus 'user not found', artinya email belum terdaftar. Lanjutkan registrasi.
                } else {
                    throw $e;
                }
            }

            $userRecord = $this->auth->createUserWithEmailAndPassword(
                $request->email,
                $request->password
            );

            $uid = $userRecord->uid;

            // Simpan data user ke Firestore
            $firestoreRef = $this->firestore->database()->collection('users')->document($uid);
            $firestoreRef->set([
                'uid' => $uid,
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'user',
                'avatar' => null,
                'phone' => null,
                'createdAt' => Carbon::now()->toDateTimeString(),
            ]);

            Log::info("Pendaftaran user berhasil. UID: $uid, Email: {$request->email}");
            return response()->json(['success' => true, 'message' => 'Pendaftaran user berhasil! Silakan login.']);

        } catch (AuthException $e) {
            // Tangani AuthException yang mungkin berasal dari createUserWithEmailAndPassword
            $errorMessage = $e->getMessage();
            $firebaseErrorCode = $e->errorInfo['code'] ?? null;

            if (str_contains($errorMessage, 'email-already-in-use') || $firebaseErrorCode === 'auth/email-already-in-use') {
                return response()->json(['success' => false, 'errors' => ['email' => ['Email ini sudah terdaftar.']]], 422);
            }
            Log::error('Firebase Auth Error during user registration: ' . $e->getMessage(), ['firebase_code' => $firebaseErrorCode, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Pendaftaran gagal: ' . $e->getMessage()], 500);
        } catch (FirebaseException $e) {
            // Tangani FirebaseException (misal masalah koneksi Firestore)
            Log::error('Firebase Error during user registration: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Pendaftaran gagal: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            // Tangani error umum lainnya
            Log::error('Unexpected error during user registration: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat pendaftaran: ' . $e->getMessage()], 500);
        }
    }
}