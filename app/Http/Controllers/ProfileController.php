<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Untuk logging
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn as FirebaseFailedToSignIn;

class ProfileController extends Controller
{
    protected $firestore;
    protected $auth;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
        $this->auth = app('firebase.auth');
    }

    /**
     * Menampilkan halaman profil pengguna dan mengambil datanya.
     * @return \Illuminate\View\View
     */
    public function showProfile()
    {
        $userData = [];
        $errorMessage = null;
        $uid = Session::get('uid');

        if (!$uid) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk melihat profil.');
        }

        try {
            // Ambil data user dari Firestore
            $userDoc = $this->firestore->database()->collection('users')->document($uid)->snapshot();

            if ($userDoc->exists()) {
                $userData = $userDoc->data();
                // Pastikan ada nilai default jika field tidak ada
                $userData['avatar'] = $userData['avatar'] ?? asset('images/default_profile.png');
                $userData['name'] = $userData['name'] ?? 'Nama Tidak Tersedia';
                $userData['email'] = $userData['email'] ?? 'Email Tidak Tersedia';
                $userData['phone'] = $userData['phone'] ?? '+62 XXXXXXXXXX';
            } else {
                $errorMessage = 'Data profil tidak ditemukan.';
            }

        } catch (FirebaseException $e) {
            Log::error('Error fetching user profile data: ' . $e->getMessage());
            $errorMessage = 'Gagal memuat data profil: ' . $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('An unexpected error occurred fetching profile: ' . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan saat memuat profil.';
        }

        return view('profile', [
            'userData' => $userData,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Memperbarui data profil pengguna di Firestore (Nama, Avatar, Phone).
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileData(Request $request)
    {
        $uid = Session::get('uid');
        Log::info('Attempting to update profile for UID: ' . $uid);

        if (!$uid) {
            Log::warning('Update profile attempt without UID in session.');
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20', // Sesuaikan validasi phone (Tidak dipakai)
            // 'avatar' => 'nullable|string|url', // Jika ingin mengupload URL avatar dari client
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $uid = Session::get('uid');
        if (!$uid) {
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        try {
            $updatedData = [
                'name' => $request->name,
                'phone' => $request->phone,
                // 'avatar' => $request->avatar, // Menangani upload avatar via API terpisah atau dari client (Tidak dipakai sekarang)
            ];

            // Jika ingin mengupdate email di Firebase Auth juga
            // karena butuh re-authentication. Untuk saat ini, asumsikan email tidak diubah lewat sini.
            Log::info('Data sent to Firestore for update: ' . json_encode($updatedData));
            $this->firestore->database()->collection('users')->document($uid)->set($updatedData, ['merge' => true]);

            // Fetch updated data to return to client
            $userDoc = $this->firestore->database()->collection('users')->document($uid)->snapshot();
            $updatedUserData = $userDoc->exists() ? $userDoc->data() : [];

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'userData' => $updatedUserData,
            ]);

        } catch (FirebaseException $e) {
            Log::error('Error updating profile data to Firebase: ' . $e->getMessage() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui profil: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error updating profile data: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui profil: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui password pengguna di Firebase Authentication.
     * Memverifikasi password lama sebelum mengizinkan perubahan.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        // Custom validation messages
        $messages = [
            'current_password.required' => 'Mohon masukkan password saat ini.',
            'new_password.required' => 'Password baru harus diisi.',
            'new_password.min' => 'Password baru minimal :min karakter.',
            'new_password.confirmed' => 'Mohon konfirmasi password baru!',
        ];

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = Session::get('uid');
        if (!$uid) {
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        try {
            // 1. Dapatkan user Firebase berdasarkan UID
            $user = $this->auth->getUser($uid);
            $userEmail = $user->email;

            if (empty($userEmail)) {
                Log::error('User does not have an email or email is not accessible for password verification for UID: ' . $uid);
                return response()->json(['success' => false, 'message' => 'Tidak dapat memverifikasi password lama karena email tidak ditemukan.'], 400);
            }

            // 2. Verifikasi password lama dengan melakukan sign-in programatik
            try {
                $signInResult = $this->auth->signInWithEmailAndPassword($userEmail, $request->current_password);
                Log::info('Verifikasi password lama berhasil untuk UID: ' . $uid);

            } catch (FirebaseFailedToSignIn $e) {
                Log::warning('Verifikasi password lama gagal untuk UID: ' . $uid . ': ' . $e->getMessage());
                // Mengubah pesan error agar sesuai dengan permintaan pengguna
                return response()->json(['success' => false, 'message' => 'Mohon masukkan password saat ini yang benar!'], 401); // <-- Pesan kustom untuk password lama salah
            }

            // 3. Jika password lama benar, lanjutkan dengan mengganti password
            $this->auth->changeUserPassword($uid, $request->new_password);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diperbarui.',
            ]);

        } catch (AuthException $e) {
            Log::error('Firebase Auth error updating password (after old password check): ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'requires-recent-login')) {
                return response()->json(['success' => false, 'message' => 'Untuk keamanan, silakan login kembali dan coba ubah password.'], 403);
            }
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui password: ' . $e->getMessage()], 500);
        } catch (FirebaseException $e) {
            Log::error('Firebase error updating password (general): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui password: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error updating password: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui password: ' . $e->getMessage()], 500);
        }
    }
}