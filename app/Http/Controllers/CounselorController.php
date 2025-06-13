<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Carbon\Carbon;

class CounselorController extends Controller
{
    protected $firestore;
    protected $auth;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
        $this->auth = app('firebase.auth');
    }

    /**
     * Menampilkan halaman detail konselor dan mengambil datanya.
     * @param string $counselorUid UID konselor dari URL.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showCounselorDetail(string $counselorUid)
    {
        $counselorData = [];
        $schedules = [];
        $errorMessage = null;

        try {
            $counselorDoc = $this->firestore->database()->collection('counselors')->document($counselorUid)->snapshot();

            if ($counselorDoc->exists()) {
                $counselorData = $counselorDoc->data();
                $counselorData['uid'] = $counselorUid; // Memastikan UID ada di data

                // Default values for counselor data
                $counselorData['avatar'] = $counselorData['avatar'] ?? asset('images/default_profile.png');
                $counselorData['name'] = $counselorData['name'] ?? 'Nama Tidak Tersedia';
                $counselorData['bidang'] = $counselorData['bidang'] ?? 'Bidang Tidak Tersedia';
                $counselorData['rate'] = $counselorData['rate'] ?? 0.0;
                $counselorData['about'] = $counselorData['about'] ?? 'Tidak ada deskripsi tersedia.';

                $scheduleIds = [];
                for ($i = 1; $i <= 3; $i++) {
                    $scheduleIdKey = 'scheduleId' . $i;
                    if (isset($counselorData[$scheduleIdKey]) && !empty($counselorData[$scheduleIdKey])) {
                        $scheduleIds[] = $counselorData[$scheduleIdKey];
                    }
                }

                if (!empty($scheduleIds)) {
                    $schedulesSnapshot = $this->firestore->database()->collection('schedules')
                        ->where('scheduleId', 'in', $scheduleIds)
                        ->documents();

                    foreach ($schedulesSnapshot as $doc) {
                        if ($doc->exists()) {
                            $data = $doc->data();
                            $scheduleId = $data['scheduleId'];
                            $isBooked = $data['isBooked'] ?? false;

                            // Temukan data ketersediaan yang cocok dari counselorData
                            $adjustedDay = 'Unknown';
                            $adjustedTime = 'Unknown Time';

                            if ($scheduleId == ($counselorData['scheduleId1'] ?? null)) {
                                $adjustedDay = $counselorData['availability_day1'] ?? 'Unknown';
                                $adjustedTime = $counselorData['availability_time1'] ?? 'Unknown Time';
                            } else if ($scheduleId == ($counselorData['scheduleId2'] ?? null)) {
                                $adjustedDay = $counselorData['availability_day2'] ?? 'Unknown';
                                $adjustedTime = $counselorData['availability_time2'] ?? 'Unknown Time';
                            } else if ($scheduleId == ($counselorData['scheduleId3'] ?? null)) {
                                $adjustedDay = $counselorData['availability_day3'] ?? 'Unknown';
                                $adjustedTime = $counselorData['availability_time3'] ?? 'Unknown Time';
                            }

                            $schedules[] = [
                                'id' => $scheduleId,
                                'counselorId' => $counselorUid,
                                'day' => $adjustedDay,
                                'time' => $adjustedTime,
                                'isBooked' => $isBooked,
                            ];
                        }
                    }
                }
            } else {
                $errorMessage = 'Konselor tidak ditemukan.';
            }
        } catch (FirebaseException $e) {
            Log::error('Error fetching counselor detail: ' . $e->getMessage() . ' - UID: ' . $counselorUid);
            $errorMessage = 'Gagal memuat detail konselor: ' . $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching counselor detail: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $counselorUid);
            $errorMessage = 'Terjadi kesalahan saat memuat detail konselor: ' . $e->getMessage();
        }

        return view('counselor_detail', [
            'counselorUid' => $counselorUid,
            'counselorData' => $counselorData,
            'schedules' => $schedules,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Memproses booking jadwal oleh user.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bookSchedule(Request $request)
    {
        Log::info('Memulai proses booking jadwal.');
        Log::info('Data request booking:', $request->all());

        $validator = Validator::make($request->all(), [
            'scheduleId' => 'required|string',
            'counselorId' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi booking gagal.', ['errors' => $validator->errors()->all()]);
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $validator->errors()->all()], 422);
        }

        $scheduleId = $request->input('scheduleId');
        $counselorUid = $request->input('counselorId');
        $userUid = Session::get('uid');

        Log::info('Data yang didapat:', ['scheduleId' => $scheduleId, 'counselorUid' => $counselorUid, 'userUid' => $userUid]);

        if (!$userUid) {
            Log::warning('Pengguna tidak login saat mencoba booking.');
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk booking jadwal.'], 401);
        }

        try {
            $existingBookingSnapshot = $this->firestore->database()->collection('bookings')
                ->where('userId', '=', $userUid)
                ->where('counselorId', '=', $counselorUid)
                ->where('status', '=', 'booked')
                ->documents();

            Log::info('Hasil pengecekan booking sebelumnya:', ['jumlah' => $existingBookingSnapshot->size()]);

            if ($existingBookingSnapshot->size() > 0) {
                return response()->json(['success' => false, 'message' => 'Anda sudah memiliki sesi aktif dengan konselor ini.'], 400);
            }

            $counselorDoc = $this->firestore->database()->collection('counselors')->document($counselorUid)->snapshot();
            $scheduleDoc = $this->firestore->database()->collection('schedules')->document($scheduleId)->snapshot();

            Log::info('Status dokumen konselor dan jadwal (sebelum update):', [
                'counselorExists' => $counselorDoc->exists(),
                'scheduleExists' => $scheduleDoc->exists(),
                'scheduleIsBooked_before' => ($scheduleDoc->exists() ? ($scheduleDoc->data()['isBooked'] ?? 'N/A (tidak ada field)') : 'N/A (dokumen tidak ada)')
            ]);


            if (!$counselorDoc->exists()) {
                Log::warning('Konselor tidak ditemukan untuk UID: ' . $counselorUid);
                return response()->json(['success' => false, 'message' => 'Data konselor tidak ditemukan.'], 404);
            }
            if (!$scheduleDoc->exists()) {
                Log::warning('Jadwal tidak ditemukan untuk Schedule ID: ' . $scheduleId);
                return response()->json(['success' => false, 'message' => 'Jadwal tidak tersedia.'], 400);
            }
            if (($scheduleDoc->data()['isBooked'] ?? false) == true) {
                Log::warning('Jadwal sudah dibooking. Schedule ID: ' . $scheduleId);
                return response()->json(['success' => false, 'message' => 'Jadwal sudah dibooking.'], 400);
            }


            $counselorData = $counselorDoc->data();
            $scheduleData = $scheduleDoc->data();

            // Tentukan day dan time
            $day = 'Unknown Day';
            $time = 'Unknown Time';

            if ($scheduleId == ($counselorData['scheduleId1'] ?? null)) {
                $day = $counselorData['availability_day1'] ?? 'Unknown Day';
                $time = $counselorData['availability_time1'] ?? 'Unknown Time';
            } else if ($scheduleId == ($counselorData['scheduleId2'] ?? null)) {
                $day = $counselorData['availability_day2'] ?? 'Unknown Day';
                $time = $counselorData['availability_time2'] ?? 'Unknown Time';
            } else if ($scheduleId == ($counselorData['scheduleId3'] ?? null)) {
                $day = $counselorData['availability_day3'] ?? 'Unknown Day';
                $time = $counselorData['availability_time3'] ?? 'Unknown Time';
            }
            Log::info('Hari dan waktu jadwal yang ditentukan:', ['day' => $day, 'time' => $time]);

            // Ambil nama user
            $userDoc = $this->firestore->database()->collection('users')->document($userUid)->snapshot();
            $userName = $userDoc->exists() ? ($userDoc->data()['name'] ?? 'User') : 'User';
            Log::info('Nama user yang booking:', ['userName' => $userName]);

            $newBookingData = [
                'scheduleId' => $scheduleId,
                'counselorId' => $counselorUid,
                'counselorName' => $counselorData['name'] ?? 'Unknown Counselor',
                'userId' => $userUid,
                'userName' => $userName,
                'day' => $day,
                'time' => $time,
                'status' => 'booked', // Initial status
                'hasBeenRated' => false, // Default: belum dirating
                'createdAt' => Carbon::now()->toDateTimeString(),
            ];
            Log::info('Data untuk booking baru:', $newBookingData);
            $newBookingRef = $this->firestore->database()->collection('bookings')->add($newBookingData);
            $bookingId = $newBookingRef->id();
            Log::info('Booking baru berhasil dibuat dengan ID:', ['bookingId' => $bookingId]);

            $historyCollection = $this->firestore->database()->collection('history');
            $newHistoryDocRef = $historyCollection->newDocument();
            $historyId = $newHistoryDocRef->id();

            // Tambahkan historyId ke data sebelum disimpan
            $newHistoryData = [
                'userId' => $userUid,
                'counselorId' => $counselorUid,
                'bookingId' => $bookingId, // Sertakan bookingId ke history
                'day' => $day,
                'time' => $time,
                'createdAt' => Carbon::now()->toDateTimeString(),
                'historyId' => $historyId, // historyId diisi langsung di sini
            ];

            Log::info('Data untuk history booking baru (dengan ID):', $newHistoryData);

            // Langsung set dokumen dengan ID yang sudah didapat
            $newHistoryDocRef->set($newHistoryData);

            Log::info('History booking berhasil dibuat dengan ID:', ['historyId' => $historyId]);


            // 5. Tandai jadwal sebagai sudah dibooking di koleksi 'schedules'
            Log::info('Mencoba memperbarui status isBooked pada jadwal dengan ID:', ['scheduleId' => $scheduleId]);
            $this->firestore->database()->collection('schedules')->document($scheduleId)->update([
                ['path' => 'isBooked', 'value' => true]
            ]);
            Log::info('Status jadwal berhasil diperbarui di Firestore (semoga!).');


            return response()->json(['success' => true, 'message' => 'Jadwal berhasil dibooking!']);

        } catch (FirebaseException $e) {
            Log::error('Firebase Error saat booking: ' . $e->getMessage() . ' - UID: ' . $userUid, ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal booking jadwal: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) { // Tangkap Throwable untuk error non-Firebase
            Log::error('Unexpected Error saat booking: ' . $e->getMessage() . ' - UID: ' . $userUid, ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menyimpan rating konselor.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingId' => 'required|string', // Perubahan: Menerima bookingId
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data rating tidak valid.', 'errors' => $validator->errors()->all()], 422);
        }

        $bookingId = $request->input('bookingId'); // Ambil bookingId
        $newRating = (double)$request->input('rating');
        $userUid = Session::get('uid');

        if (!$userUid) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk memberikan rating.'], 401);
        }

        try {
            // 1. Ambil data booking untuk validasi
            $bookingRef = $this->firestore->database()->collection('bookings')->document($bookingId);
            $bookingSnapshot = $bookingRef->snapshot();

            if (!$bookingSnapshot->exists()) {
                Log::warning('Booking tidak ditemukan untuk ID: ' . $bookingId);
                return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
            }

            $bookingData = $bookingSnapshot->data();
            $counselorUid = $bookingData['counselorId'] ?? null; // Ambil counselorUid dari booking

            // Validasi kepemilikan booking
            if (($bookingData['userId'] ?? null) !== $userUid) {
                Log::warning('User mencoba merating booking yang bukan miliknya.', ['userId' => $userUid, 'bookingId' => $bookingId]);
                return response()->json(['success' => false, 'message' => 'Anda tidak berhak memberikan rating untuk sesi ini.'], 403);
            }

            // Validasi status booking (hanya yang 'completed')
            if (($bookingData['status'] ?? 'pending') !== 'completed') {
                Log::warning('User mencoba merating booking yang belum selesai.', ['bookingId' => $bookingId, 'status' => $bookingData['status']]);
                return response()->json(['success' => false, 'message' => 'Anda hanya bisa memberikan rating setelah sesi selesai.'], 400);
            }

            // Validasi apakah booking sudah dirating sebelumnya
            if (($bookingData['hasBeenRated'] ?? false) === true) {
                Log::warning('Booking ini sudah dirating sebelumnya.', ['bookingId' => $bookingId]);
                return response()->json(['success' => false, 'message' => 'Anda sudah memberikan rating untuk sesi ini.'], 400);
            }

            $counselorRef = $this->firestore->database()->collection('counselors')->document($counselorUid);
            $docSnapshot = $counselorRef->snapshot();

            if (!$docSnapshot->exists()) {
                return response()->json(['success' => false, 'message' => 'Konselor tidak ditemukan.'], 404);
            }

            $ratingList = $docSnapshot->data()['rating'] ?? [];
            if (!is_array($ratingList)) {
                $ratingList = [];
            }

            // Tambahkan rating baru
            $ratingList[] = $newRating;

            // Hitung rata-rata rating baru
            $totalRating = array_sum($ratingList);
            $averageRating = count($ratingList) > 0 ? $totalRating / count($ratingList) : 0;

            // Simpan kembali array rating yang diperbarui dan rata-rata rate ke Firestore
            $counselorRef->update([
                ['path' => 'rating', 'value' => $ratingList],
                ['path' => 'rate', 'value' => round($averageRating, 1)],
            ]);
            Log::info('Rating konselor berhasil diperbarui di Firestore.', ['counselorId' => $counselorUid, 'newRating' => $newRating, 'averageRating' => $averageRating]);

            $bookingRef->update([
                ['path' => 'hasBeenRated', 'value' => true]
            ]);
            Log::info('Status booking diperbarui: hasBeenRated = true.', ['bookingId' => $bookingId]);

            return response()->json(['success' => true, 'message' => 'Rating berhasil disimpan.']);

        } catch (FirebaseException $e) {
            Log::error('Firebase error saving rating: ' . $e->getMessage() . ' - UID: ' . $userUid, ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan rating: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error saving rating: ' . $e->getMessage() . ' - Stack Trace: ' . $e->getTraceAsString() . ' - UID: ' . $userUid);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan rating: ' . $e->getMessage()], 500);
        }
    }
}