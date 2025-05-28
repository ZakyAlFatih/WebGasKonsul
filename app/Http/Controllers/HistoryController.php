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
        $userId = Session::get('uid'); // Mengambil UID pengguna dari session
        $bookingHistory = [];
        $errorMessage = null;

        if (!$userId) {
            // Jika pengguna tidak login, redirect atau tampilkan pesan error
            return redirect()->route('login')->with('error', 'Anda harus login untuk melihat riwayat.');
        }

        try {
            Log::info("Mulai mengambil riwayat booking untuk userId: $userId");

            // 1. Ambil riwayat dari koleksi 'history' berdasarkan userId
            $historySnapshot = $this->firestore->database()->collection('history')
                ->where('userId', '=', $userId)
                ->documents();

            $counselorUids = [];
            $rawHistoryData = [];

            // Kumpulkan UID konselor dari riwayat untuk query batch
            foreach ($historySnapshot as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    $rawHistoryData[] = $data;
                    if (isset($data['counselorId']) && !empty($data['counselorId'])) {
                        $counselorUids[$data['counselorId']] = true; // Gunakan array asosiatif untuk menghindari duplikasi
                    }
                }
            }

            $counselorDataMap = [];
            // 2. Ambil data detail konselor secara batch jika ada UID konselor
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

            // 3. Gabungkan data riwayat dengan data konselor
            foreach ($rawHistoryData as $historyData) {
                $counselorId = $historyData['counselorId'] ?? null;
                $counselorData = $counselorDataMap[$counselorId] ?? null;

                $day = 'Tidak tersedia';
                $time = 'Tidak tersedia';

                // Logika untuk menentukan day dan time dari data konselor
                if (isset($historyData['scheduleId'])) {
                    $bookedScheduleId = $historyData['scheduleId']; // Ini adalah scheduleId yang dibooking

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
                $day = $historyData['day'] ?? $day;
                $time = $historyData['time'] ?? $time;


                $bookingHistory[] = [
                    'userId' => $historyData['userId'] ?? '',
                    'counselorId' => $historyData['counselorId'] ?? '',
                    'counselorName' => $counselorData['name'] ?? 'Tidak diketahui',
                    'counselorBidang' => $counselorData['bidang'] ?? 'Tidak diketahui',
                    'scheduleId' => $historyData['scheduleId'] ?? '', // Menggunakan 'scheduleId' dari historyData
                    'day' => $day,
                    'time' => $time,
                    'status' => $historyData['status'] ?? 'completed', // Ambil status dari historyData, default 'completed'
                    'createdAt' => $historyData['createdAt'] ?? 'Tidak tersedia',
                ];
            }

            // Sort history by creation date (most recent first)
            usort($bookingHistory, function($a, $b) {
                return strtotime($b['createdAt']) - strtotime($a['createdAt']);
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