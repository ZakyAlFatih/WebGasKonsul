<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Firestore;

class HistoryController extends Controller
{
    protected $firestore;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
    }

    /**
     * Menampilkan halaman riwayat booking pengguna.
     * Mengambil data dari koleksi 'history' dan 'counselors'.
     *
     * @return \Illuminate\View\View
     */
    public function showHistory()
    {
        $userId = Session::get('uid');
        $bookingHistory = [];
        $errorMessage = null;

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk melihat riwayat.');
        }

        try {
            Log::info("Mulai mengambil riwayat booking untuk userId: $userId");

            // Ambil riwayat dari koleksi 'history' berdasarkan userId
            $historySnapshot = $this->firestore->database()->collection('history')
                ->where('userId', '=', $userId)
                ->documents();

            $counselorUids = [];
            $rawHistoryData = [];
            $bookingIds = [];

            // Kumpulkan UID konselor dan ID booking dari riwayat
            foreach ($historySnapshot as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    $rawHistoryData[] = $data;
                    if (isset($data['counselorId']) && !empty($data['counselorId'])) {
                        $counselorUids[$data['counselorId']] = true;
                    }
                    if (isset($data['bookingId']) && !empty($data['bookingId'])) {
                        $bookingIds[] = $data['bookingId'];
                    }
                }
            }

            $counselorDataMap = [];
            // Ambil data detail konselor secara batch jika ada UID konselor
            if (!empty($counselorUids)) {
                $counselorRefs = array_map(function($uid) {
                    return $this->firestore->database()->collection('counselors')->document($uid);
                }, array_keys($counselorUids));

                $counselorSnapshots = $this->firestore->database()->documents($counselorRefs);

                foreach ($counselorSnapshots as $snapshot) {
                    if ($snapshot->exists()) {
                        $counselorDataMap[$snapshot->id()] = $snapshot->data();
                    }
                }
            }

            $bookingDataMap = [];
            // Ambil detail booking secara batch jika ada ID booking
            if (!empty($bookingIds)) {
                $bookingRefs = array_map(function($id) {
                    return $this->firestore->database()->collection('bookings')->document($id);
                }, $bookingIds);

                $bookingSnapshots = $this->firestore->database()->documents($bookingRefs);

                foreach ($bookingSnapshots as $snapshot) {
                    if ($snapshot->exists()) {
                        $bookingDataMap[$snapshot->id()] = $snapshot->data();
                    }
                }
            }

            // Gabungkan data riwayat dengan data konselor dan booking
            foreach ($rawHistoryData as $historyData) {
                $counselorId = $historyData['counselorId'] ?? null;
                $counselorData = $counselorDataMap[$counselorId] ?? null;
                $bookingId = $historyData['bookingId'] ?? null;
                $bookingDetail = $bookingDataMap[$bookingId] ?? null;

                $day = 'Tidak tersedia';
                $time = 'Tidak tersedia';

                if (isset($bookingDetail['day']) && isset($bookingDetail['time'])) {
                    $day = $bookingDetail['day'];
                    $time = $bookingDetail['time'];
                } else if (isset($historyData['day']) && isset($historyData['time'])) {
                    $day = $historyData['day'];
                    $time = $historyData['time'];
                } else if (isset($historyData['scheduleId'])) {
                     $bookedScheduleId = $historyData['scheduleId'];
                     if ($bookedScheduleId == ($counselorData['scheduleId1'] ?? null)) {
                         $day = $counselorData['availability_day1'] ?? 'Tidak tersedia';
                         $time = $counselorData['availability_time1'] ?? 'Tidak tersedia';
                     } else if ($bookedScheduleId == ($counselorData['scheduleId2'] ?? null)) {
                         $day = $counselorData['availability_day2'] ?? 'Tidak tersedia';
                         $time = $counselorData['availability_time2'] ?? 'Tidak tersedia';
                     } else if ($bookedScheduleId == ($counselorData['scheduleId3'] ?? null)) {
                         $day = $counselorData['availability_day3'] ?? 'Tidak tersedia';
                         $time = $counselorData['availability_time3'] ?? 'Tidak tersedia';
                     }
                }


                $bookingHistory[] = [
                    'userId' => $historyData['userId'] ?? '',
                    'counselorId' => $historyData['counselorId'] ?? '',
                    'bookingId' => $bookingId,
                    'counselorName' => $counselorData['name'] ?? 'Tidak diketahui',
                    'counselorBidang' => $counselorData['bidang'] ?? 'Tidak diketahui',
                    'scheduleId' => $historyData['scheduleId'] ?? '',
                    'day' => $day,
                    'time' => $time,
                    'status' => $bookingDetail['status'] ?? 'unknown',
                    'hasBeenRated' => $bookingDetail['hasBeenRated'] ?? false,
                    'createdAt' => $historyData['createdAt'] ?? 'Tidak tersedia',
                    'counselorAvatar' => $counselorData['avatar'] ?? asset('images/default_profile.png'),
                ];
            }

            // Sort history by creation date
            usort($bookingHistory, function($a, $b) {
                // Konversi ke DateTimeImmutable
                $dateA = (isset($a['createdAt']) && $a['createdAt'] !== 'Tidak tersedia') ? new \DateTimeImmutable($a['createdAt']) : new \DateTimeImmutable('1970-01-01');
                $dateB = (isset($b['createdAt']) && $b['createdAt'] !== 'Tidak tersedia') ? new \DateTimeImmutable($b['createdAt']) : new \DateTimeImmutable('1970-01-01');

                return $dateB <=> $dateA;
            });


            Log::info("Total riwayat booking ditemukan: " . count($bookingHistory));

        } catch (\Throwable $e) {
            Log::error('Error mengambil riwayat booking: ' . $e->getMessage() . ' - UID: ' . $userId, ['trace' => $e->getTraceAsString()]);
            $errorMessage = 'Terjadi kesalahan saat memuat riwayat: ' . $e->getMessage();
        }

        return view('history', [
            'bookingHistory' => $bookingHistory,
            'errorMessage' => $errorMessage,
        ]);
    }
}