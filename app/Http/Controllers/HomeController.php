<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    protected $firestore;
    protected $auth;
    protected $groqApiKey;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
        $this->auth = app('firebase.auth');
        $this->groqApiKey = env('GROQ_API_KEY');
    }

    /**
     * Menampilkan halaman home. Untuk saat ini, akan selalu merender home_user.
     * Nantinya akan ada logic untuk memeriksa role dan merender view yang sesuai.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showHome()
    {
        // Panggil langsung metode untuk user home
        return $this->showUserHome();
    }

    /**
     * Menampilkan halaman home untuk user biasa.
     * Mengambil data konselor dari Firestore dan mengelompokkannya.
     * @return \Illuminate\View\View
     */
    public function showUserHome()
    {
        $categories = [];
        $userName = 'User';
        $errorMessage = null;

        try {
            // Ambil nama user dari Firestore berdasarkan UID di sesi
            if (Session::has('uid')) {
                $uid = Session::get('uid');
                $userDoc = $this->firestore->database()->collection('users')->document($uid)->snapshot();
                if ($userDoc->exists()) {
                    $userName = $userDoc->data()['name'] ?? 'User';
                }
            }

            // Mengambil semua dokumen dari koleksi 'counselors'
            $querySnapshot = $this->firestore->database()->collection('counselors')->documents();
            $groupedByBidang = [];

            foreach ($querySnapshot as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $bidang = $data['bidang'] ?? 'Unknown Bidang'; // Fallback
                    $uid = $document->id();

                    // Ekstrak ketersediaan konselor
                    $availability = [
                        "day1" => $data['availability_day1'] ?? 'Unknown',
                        "time1" => $data['availability_time1'] ?? 'Unknown Time',
                        "day2" => $data['availability_day2'] ?? 'Unknown',
                        "time2" => $data['availability_time2'] ?? 'Unknown Time',
                        "day3" => $data['availability_day3'] ?? 'Unknown',
                        "time3" => $data['availability_time3'] ?? 'Unknown Time',
                    ];

                    // Kelompokkan konselor berdasarkan bidang
                    if (!empty($bidang)) {
                        if (!isset($groupedByBidang[$bidang])) {
                            $groupedByBidang[$bidang] = [];
                        }
                        $groupedByBidang[$bidang][] = [
                            "uid" => $uid,
                            "name" => $data['name'] ?? 'Unknown',
                            "availability" => $availability,
                            "image" => $data['avatar'] ?? asset('images/default_profile.png'), // Default placeholder gambar
                            "email" => $data['email'] ?? 'No Email',
                        ];
                    }
                }
            }

            // Transformasi map menjadi struktur categories
            foreach ($groupedByBidang as $bidangName => $counselorsInBidang) {
                $categories[] = [
                    "category" => $bidangName,
                    "counselors" => $counselorsInBidang,
                ];
            }

        } catch (FirebaseException $e) {
            Log::error('Failed to fetch counselors for user home: ' . $e->getMessage());
            $errorMessage = 'Gagal memuat data konselor: ' . $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('An unexpected error occurred in user home: ' . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan saat memuat halaman: ' . $e->getMessage();
        }

        return view('home_user', [
            'categories' => $categories,
            'userName' => $userName,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Memproses permintaan rekomendasi bidang konseling dari Groq API.
     * Ini akan dipanggil via AJAX dari frontend.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recommendCounselorBidang(Request $request)
    {
        $request->validate([
            'cerita' => 'required|string|max:1000',
        ]);

        $cerita = $request->input('cerita');
        $apiKey = $this->groqApiKey;

        if (empty($apiKey)) {
            Log::error('GROQ_API_KEY is not set in .env');
            return response()->json(['error' => 'API Key Groq tidak diatur.'], 500);
        }

        $client = new Client();

        try {
            $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    "model" => "llama3-70b-8192",
                    "messages" => [
                        [
                            "role" => "system",
                            "content" => "Kamu adalah asisten yang membantu merekomendasikan bidang konseling berdasarkan masalah pengguna. jawab dengan 2 kalimat saja"
                        ],
                        [
                            "role" => "user",
                            "content" => "Masalah saya adalah: $cerita. Berdasarkan masalah ini, sebutkan satu bidang konseling yang cocok.pilih diantara pilihan berikut (Kesehatan, psikologi, game, IT, karir, keuangan)"
                        ]
                    ],
                    "temperature" => 0.7,
                    "max_tokens" => 100,
                    "stream" => false,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $content = $body["choices"][0]["message"]["content"] ?? 'Tidak dapat merekomendasikan bidang.';

            return response()->json(['rekomendasiBidang' => $content]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Groq API Request Failed: ' . $e->getMessage());
            if ($e->hasResponse()) {
                Log::error('Groq API Error Response: ' . $e->getResponse()->getBody()->getContents());
            }
            return response()->json(['error' => 'Gagal mendapatkan rekomendasi dari AI.'], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in recommendCounselorBidang: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Memfilter konselor berdasarkan kategori yang dipilih.
     * Ini akan dipanggil via AJAX dari frontend.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterCounselorsByBidang(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string|max:255',
        ]);

        $selectedCategory = $request->input('category');
        $filteredCounselors = [];

        try {
            $query = $this->firestore->database()->collection('counselors');

            if (!empty($selectedCategory)) {
                $query = $query->where('bidang', '=', $selectedCategory);
            }

            $querySnapshot = $query->documents();

            foreach ($querySnapshot as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $uid = $document->id();

                    $availability = [
                        "day1" => $data['availability_day1'] ?? 'Unknown',
                        "time1" => $data['availability_time1'] ?? 'Unknown Time',
                        "day2" => $data['availability_day2'] ?? 'Unknown',
                        "time2" => $data['availability_time2'] ?? 'Unknown Time',
                        "day3" => $data['availability_day3'] ?? 'Unknown',
                        "time3" => $data['availability_time3'] ?? 'Unknown Time',
                    ];

                    $filteredCounselors[] = [
                        "uid" => $uid,
                        "name" => $data['name'] ?? 'Unknown',
                        "availability" => $availability,
                        "image" => $data['avatar'] ?? asset('images/default_profile.png'),
                        "email" => $data['email'] ?? 'No Email',
                    ];
                }
            }

            return response()->json(['counselors' => $filteredCounselors]);

        } catch (FirebaseException $e) {
            Log::error('Failed to filter counselors from Firestore: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memfilter konselor.'], 500);
        } catch (\Throwable $e) {
            Log::error('An unexpected error occurred during filtering: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan.']);
        }
    }

    // Metode placeholder untuk home page counselor (tidak aktif karena fokus user home)
    // public function showCounselorHome()
    // {
    //     return view('home_counselor', [
    //         'userName' => 'Counselor',
    //         'counselorData' => [],
    //         'pendingChats' => [],
    //         'upcomingSchedules' => [],
    //     ]);
    // }
}