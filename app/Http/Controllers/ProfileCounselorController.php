<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Validation\Rule; // Jika diperlukan

class ProfileCounselorController extends Controller
{
    protected $firestoreDb;
    protected $firebaseAuth;

    public function __construct(Firestore $firestore, FirebaseAuth $firebaseAuth)
    {
        $this->firestoreDb = $firestore->database();
        $this->firebaseAuth = $firebaseAuth;
    }

    // Method show() dan _getCounselorData() Anda tetap sama seperti sebelumnya...
    // Saya akan langsung ke method update()

    public function show(Request $request) // Kode show dari sebelumnya untuk kelengkapan
    {
        $uid = Session::get('uid');
        if (!$uid) {
            Log::warning('UID not found in session for ProfileCounselorController@show. Redirecting to login.');
            return redirect()->route('login')->with('error', 'Sesi tidak valid, silakan login kembali.');
        }
        list($counselorData, $errorMessage) = $this->_getCounselorData($uid);
        return view('profile_counselor_show', [
            'counselorData' => $counselorData,
            'userName' => Session::get('userName', 'Konselor'),
            'userAvatar' => Session::get('userAvatar'),
            'errorMessage' => $errorMessage,
        ]);
    }

    public function edit() // Kode edit dari sebelumnya untuk kelengkapan
    {
        $uid = Session::get('uid');
        if (!$uid) {
            Log::warning('UID not found in session for ProfileCounselorController@edit. Redirecting to login.');
            return redirect()->route('login')->with('error', 'Sesi tidak valid, silakan login kembali.');
        }
        list($counselorData, $errorMessage) = $this->_getCounselorData($uid);
        if (empty($counselorData) && $errorMessage) {
             Session::flash('error_load', $errorMessage);
        }
        return view('profile_counselor_edit', [
            'counselorData' => $counselorData,
            'userName' => Session::get('userName', 'Konselor'),
            'userAvatar' => Session::get('userAvatar'),
            // 'errorMessage' => $errorMessage, // Error bisa ditampilkan via Session::flash
        ]);
    }

    private function _getCounselorData(string $uid): array // Kode _getCounselorData dari sebelumnya
    {
        $counselorData = [];
        $errorMessage = null;
        try {
            Log::info("Fetching counselor data for UID: $uid in _getCounselorData");
            $counselorDoc = $this->firestoreDb->collection('counselors')->document($uid)->snapshot();
            if ($counselorDoc->exists()) {
                $counselorData = $counselorDoc->data();
                Log::info("Counselor data found for UID: $uid");
            } else {
                Log::warning("Counselor document with UID: $uid not found in Firestore.");
                $errorMessage = "Data profil konselor tidak ditemukan.";
            }
        } catch (\Throwable $e) {
            Log::error('Error fetching counselor data from Firestore: ' . $e->getMessage(), ['exception' => $e]);
            $errorMessage = "Terjadi kesalahan internal saat mengambil data profil.";
        }
        return [$counselorData, $errorMessage];
    }


    /**
     * Method untuk menangani update profil konselor.
     */
    public function update(Request $request)
    {
        $uid = Session::get('uid');
        if (!$uid) {
            return redirect()->route('login')->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
        }

        Log::info("ProfileCounselorController@update: Attempting update for UID: $uid", $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|url|max:1024',
            'about' => 'nullable|string|max:5000',
            'phone' => ['nullable', 'string', 'regex:/^(\+62|0)8[1-9][0-9]{6,11}$/'], // Tetap
            
            // Validasi untuk ketersediaan waktu
            'availability_day1' => 'nullable|string|max:50',
            'availability_time1' => ['nullable', 'string', 'regex:/^\d{2}\:\d{2} - \d{2}\:\d{2}$/'], // Format HH.MM - HH.MM
            'availability_day2' => 'nullable|string|max:50',
            'availability_time2' => ['nullable', 'string', 'regex:/^\d{2}\:\d{2} - \d{2}\:\d{2}$/'],
            'availability_day3' => 'nullable|string|max:50',
            'availability_time3' => ['nullable', 'string', 'regex:/^\d{2}\:\d{2} - \d{2}\:\d{2}$/'],
            
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::warning("Profile update validation failed for UID: $uid", $validator->errors()->toArray());
            return redirect()->route('counselor.profile.edit') // Kembali ke form edit
                        ->withErrors($validator)
                        ->withInput();
        }

        $validatedData = $validator->validated();
        $firestoreUpdates = []; // Data yang akan diupdate ke dokumen 'counselors'

        // 1. Siapkan data profil dasar
        $firestoreUpdates['name'] = $validatedData['name'];
        $firestoreUpdates['about'] = $validatedData['about'] ?? '';
        $firestoreUpdates['phone'] = $validatedData['phone'] ?? '';

        // --- PENANGANAN AVATAR ---
        if ($request->has('avatar')) { // Cek apakah field avatar dikirim (bisa jadi string kosong)
            $avatarUrl = $validatedData['avatar'] ?? null; // Ambil dari data yang sudah divalidasi (nullable|url)
            if (is_null($avatarUrl) || trim($avatarUrl) === '') {
                // Jika URL avatar adalah null atau string kosong setelah validasi,
                // kita set sebagai null di Firestore (atau string kosong jika preferensi Anda).
                // Menggunakan null lebih bersih untuk "tidak ada gambar".
                $firestoreUpdates['avatar'] = null; 
                Log::info("Avatar for UID: $uid will be set to null/removed.");
            } else {
                // Jika ada URL valid, gunakan itu.
                $firestoreUpdates['avatar'] = $avatarUrl;
                Log::info("Avatar for UID: $uid will be updated to: $avatarUrl");
            }
        }
        // 2. Proses Ketersediaan Jadwal (3 slot statis)
        for ($i = 1; $i <= 3; $i++) {
            $dayValue = $validatedData["availability_day{$i}"] ?? null;
            $timeValue = $validatedData["availability_time{$i}"] ?? null;
            // Ambil scheduleId yang sudah ada dari hidden input di form
            $existingScheduleId = $request->input("scheduleId{$i}");

            $firestoreUpdates["availability_day{$i}"] = $dayValue ?? '';
            $firestoreUpdates["availability_time{$i}"] = $timeValue ?? '';

            if (!empty($dayValue) && !empty($timeValue)) {
                // Jika hari dan waktu diisi untuk slot ini
                if (empty($existingScheduleId)) {
                    // Belum ada scheduleId, berarti ini slot baru yang diisi -> Buat schedule baru
                    try {
                        $newScheduleRef = $this->firestoreDb->collection('schedules')->newDocument();
                        $newScheduleId = $newScheduleRef->id();
                        $newScheduleRef->set([
                            'scheduleId' => $newScheduleId, // Simpan ID uniknya sendiri
                            'counselorId' => $uid,
                            'day' => $dayValue,
                            'time' => $timeValue,
                            'isBooked' => false,
                            'createdAt' => new \DateTimeImmutable(),
                        ]);
                        $firestoreUpdates["scheduleId{$i}"] = $newScheduleId; // Link ke counselor doc
                        Log::info("New schedule created for UID: $uid, Slot: $i, ScheduleID: $newScheduleId");
                    } catch (\Throwable $e) {
                        Log::error("Failed to create schedule for UID: $uid, Slot: $i. Error: " . $e->getMessage());
                        // Lanjutkan proses update profil meskipun pembuatan schedule gagal, tapi beri notifikasi
                        Session::flash('warning_schedule', "Gagal membuat entri jadwal untuk slot {$i}. Silakan coba lagi.");
                    }
                } else {
                    // Sudah ada scheduleId, berarti update schedule yang ada
                    try {
                        $this->firestoreDb->collection('schedules')->document($existingScheduleId)->update([
                            ['path' => 'day', 'value' => $dayValue],
                            ['path' => 'time', 'value' => $timeValue],
                            // 'isBooked' tidak diubah dari sini, biarkan apa adanya
                        ]);
                        $firestoreUpdates["scheduleId{$i}"] = $existingScheduleId; // Pertahankan ID yang ada
                        Log::info("Updated existing schedule for UID: $uid, Slot: $i, ScheduleID: $existingScheduleId");
                    } catch (\Throwable $e) {
                        Log::error("Failed to update existing schedule (ID: $existingScheduleId) for UID: $uid, Slot: $i. Error: " . $e->getMessage());
                        Session::flash('warning_schedule', "Gagal memperbarui entri jadwal untuk slot {$i}.");
                    }
                }
            } else {
                // Jika hari atau waktu dikosongkan, berarti slot ini tidak aktif/dihapus
                // Kosongkan scheduleId di counselor doc.
                $firestoreUpdates["scheduleId{$i}"] = ''; // Atau null
                // Pertimbangkan: Apakah dokumen schedule di koleksi 'schedules' yang terkait dengan $existingScheduleId (jika ada) perlu dihapus?
                // Untuk saat ini: kita hanya menghapus linknya dari counselor.
                if (!empty($existingScheduleId)) {
                    Log::info("Availability slot $i cleared for UID: $uid. Associated ScheduleID $existingScheduleId is now orphaned or needs manual cleanup/marking.");
                    // Opsional: Hapus atau tandai dokumen di 'schedules'
                    // try {
                    //     $this->firestoreDb->collection('schedules')->document($existingScheduleId)->delete();
                    //     Log::info("Deleted orphaned schedule document: $existingScheduleId");
                    // } catch (\Throwable $e) {
                    //     Log::error("Failed to delete orphaned schedule (ID: $existingScheduleId). Error: " . $e->getMessage());
                    // }
                }
            }
        }

        // 3. Lakukan Update ke Dokumen 'counselors' di Firestore
        try {
            $counselorDocumentRef = $this->firestoreDb->collection('counselors')->document($uid);
            $updatePayload = [];
            foreach($firestoreUpdates as $key => $value){
                // Pastikan kita hanya mengirim field yang relevan dan tidak mengirim value null jika fieldnya tidak boleh null di Firestore
                // Untuk field yang boleh kosong (seperti scheduleId, availability_day/time), string kosong adalah representasi yang baik.
                $updatePayload[] = ['path' => $key, 'value' => $value];
            }

            if(!empty($updatePayload)){
                 $counselorDocumentRef->update($updatePayload);
                 Log::info("Counselor profile data successfully updated in Firestore for UID: $uid", $firestoreUpdates);
            } else {
                 Log::info("No actual data changes to update for counselor profile UID: $uid");
            }

            // Update data session jika nama atau avatar berubah
            if(isset($firestoreUpdates['name'])) Session::put('userName', $firestoreUpdates['name']);
            if(array_key_exists('avatar', $firestoreUpdates)) Session::put('userAvatar', $firestoreUpdates['avatar']); // array_key_exists untuk handle string kosong

        } catch (\Throwable $e) {
            Log::error("Failed to update counselor profile in Firestore for UID: $uid. Error: " . $e->getMessage(), ['payload' => $firestoreUpdates]);
            return redirect()->route('counselor.profile.edit')
                        ->with('error', 'Gagal menyimpan perubahan profil ke database: ' . $e->getMessage())
                        ->withInput();
        }

        // 4. Update Password di Firebase Authentication (jika diisi)
        $successMessage = 'Profil berhasil diperbarui!';
        if (!empty($validatedData['password'])) {
            try {
                $this->firebaseAuth->changeUserPassword($uid, $validatedData['password']);
                Log::info("Password updated successfully in Firebase Authentication for UID: $uid");
                $successMessage = 'Profil dan password berhasil diperbarui!'; // Ubah pesan sukses
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                Log::error("Firebase Auth: User not found when trying to update password for UID: $uid - " . $e->getMessage());
                Session::flash('error_password', 'Gagal memperbarui password: Pengguna tidak ditemukan di sistem otentikasi.');
            } catch (\Throwable $e) { // Tangkap error umum lainnya dari Firebase Auth
                Log::error("Failed to update password in Firebase Authentication for UID: $uid. Error: " . $e->getMessage());
                // Pesan error dari Firebase mungkin terlalu teknis, beri pesan umum
                Session::flash('error_password', 'Password gagal diperbarui. Terjadi kesalahan pada sistem otentikasi.');
            }
        }
        
        return redirect()->route('counselor.profile.show')->with('success', $successMessage);
    }
}