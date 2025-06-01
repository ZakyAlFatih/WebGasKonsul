<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
        Log::info('Attempting to update profile data for UID: ' . $uid);

        if (!$uid) {
            Log::warning('Update profile data attempt without UID in session.');
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        // Custom validation messages for updateProfileData
        $messages = [
            'name.required' => 'Nama harus diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama tidak boleh lebih dari :max karakter.',
            'phone.string' => 'Nomor telepon harus berupa teks.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari :max karakter.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka dan diawali kode telepon.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?\s*\d[\d\s]*$/'],
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $updatedData = [
                'name' => $request->name,
                'phone' => $request->phone,
            ];

            Log::info('Data sent to Firestore for update: ' . json_encode($updatedData));
            $this->firestore->database()->collection('users')->document($uid)->set($updatedData, ['merge' => true]);

            // Ambil data terbaru dari Firestore dan update session
            $userDoc = $this->firestore->database()->collection('users')->document($uid)->snapshot();
            $updatedUserData = $userDoc->exists() ? $userDoc->data() : [];

            // Update Session juga setelah berhasil mengubah nama
            Session::put('userName', $updatedUserData['name'] ?? Session::get('userName'));

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'userData' => $updatedUserData, // Kirim data terbaru ke frontend
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
     * Memperbarui URL avatar profil pengguna di Firestore.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        $uid = Session::get('uid');
        Log::info('Attempting to update avatar for UID: ' . $uid);

        if (!$uid) {
            Log::warning('Update avatar attempt without UID in session.');
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'avatar_url' => 'required|string|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $newAvatarUrl = $request->avatar_url;

            $this->firestore->database()->collection('users')->document($uid)->set([
                'avatar' => $newAvatarUrl
            ], ['merge' => true]);

            // Update session's userAvatar
            Session::put('userAvatar', $newAvatarUrl);

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
                'avatar_url' => $newAvatarUrl
            ]);

        } catch (FirebaseException $e) {
            Log::error('Error updating avatar in Firebase: ' . $e->getMessage() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui foto profil: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error updating avatar: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui foto profil: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus URL avatar profil pengguna di Firestore (mengatur ke default).
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAvatar()
    {
        $uid = Session::get('uid');
        Log::info('Attempting to remove avatar for UID: ' . $uid);

        if (!$uid) {
            Log::warning('Remove avatar attempt without UID in session.');
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        try {
            $defaultAvatarUrl = asset('images/default_profile.png');

            $this->firestore->database()->collection('users')->document($uid)->set([
                'avatar' => null
            ], ['merge' => true]);

            // Update session's userAvatar ke default
            Session::put('userAvatar', $defaultAvatarUrl);

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diatur ke default.',
                'avatar_url' => $defaultAvatarUrl
            ]);

        } catch (FirebaseException $e) {
            Log::error('Error removing avatar from Firebase: ' . $e->getMessage() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Gagal mengatur foto profil ke default: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error removing avatar: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $uid);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat mengatur foto profil ke default: ' . $e->getMessage()], 500);
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
        // Message untuk validasi
        $messages = [
            'current_password.required' => 'Mohon masukkan password saat ini.',
            'new_password.required' => 'Password baru harus diisi.',
            'new_password.min' => 'Password baru minimal :min karakter.',
            'new_password.confirmed' => 'Mohon konfirmasi password baru!',
        ];

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = Session::get('uid');
        if (!$uid) {
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        try {
            $user = $this->auth->getUser($uid);
            $userEmail = $user->email;

            if (empty($userEmail)) {
                Log::error('User does not have an email or email is not accessible for password verification for UID: ' . $uid);
                return response()->json(['success' => false, 'message' => 'Tidak dapat memverifikasi password lama karena email tidak ditemukan.'], 400);
            }

            try {
                $signInResult = $this->auth->signInWithEmailAndPassword($userEmail, $request->current_password);
                Log::info('Verifikasi password lama berhasil untuk UID: ' . $uid);

            } catch (FirebaseFailedToSignIn $e) {
                Log::warning('Verifikasi password lama gagal untuk UID: ' . $uid . ': ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Mohon masukkan password saat ini yang benar!'], 401);
            }

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