<?php

// --- USE STATEMENTS UTAMA ---
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session; // Jika memang digunakan langsung di file rute
use Illuminate\Support\Facades\Auth;   // Ditambahkan untuk cek login di rute akar

// Controller untuk Otentikasi & Halaman Umum
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\HomeController; // Untuk beranda pengguna biasa

// Controller spesifik untuk Dashboard Konselor
use App\Http\Controllers\ChatCounselorController;    // Pastikan path ini benar
use App\Http\Controllers\ProfileCounselorController; // Pastikan path ini benar

// Middleware Kustom Konselor
use App\Http\Middleware\EnsureUserIsCounselor; // Pastikan path ini benar

// Controller lain untuk fungsionalitas pengguna biasa (sesuaikan jika perlu)
use App\Http\Controllers\CounselorController; // Untuk menampilkan detail konselor ke user biasa
use App\Http\Controllers\ChatController;      // Untuk chat pengguna biasa
use App\Http\Controllers\ProfileController;  // Untuk profil pengguna biasa
use App\Http\Controllers\HistoryController;  // Untuk riwayat pengguna biasa

// ======================================================================
// --- RUTE HALAMAN UTAMA / LANDING PAGE ---
// ======================================================================
Route::get('/', function () {
    // Logika untuk mengarahkan pengguna yang sudah login
    // Berdasarkan sesi kustom yang Anda set di LoginController
    if (Session::has('uid')) {
        if (Session::get('isCounselor')) {
            return redirect()->route('counselor.dashboard'); // Arahkan konselor ke dashboard mereka
        }
        // Jika bukan konselor tapi memiliki UID (dianggap user biasa yang login)
        return redirect()->route('home'); // Arahkan ke beranda pengguna biasa
    }
    // Jika tidak ada 'uid' di session, tampilkan halaman login
    return view('login');
})->name('landing');


// ======================================================================
// --- RUTE OTENTIKASI (LOGIN, REGISTER, LOGOUT) ---
// ======================================================================
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout'); // Menggunakan POST untuk logout

Route::get('/register/counselor', [RegisterController::class, 'showCounselorRegisterForm'])->name('register.counselor');
Route::post('/register/counselor', [RegisterController::class, 'storeCounselor'])->name('register.counselor.store');
Route::get('/register/user', [RegisterController::class, 'showUserRegisterForm'])->name('register.user');
Route::post('/register/user', [RegisterController::class, 'storeUser'])->name('register.user.store');


// ======================================================================
// --- RUTE UNTUK DASHBOARD KONSELOR ---
// ======================================================================
Route::middleware([EnsureUserIsCounselor::class]) // Hanya middleware kustom Anda
     ->prefix('counselor')
     ->name('counselor.')
     ->group(function () {
        Route::get('/', [ChatCounselorController::class, 'index'])->name('dashboard');
        Route::get('/chat', [ChatCounselorController::class, 'index'])->name('chat');

        // Rute untuk Profil Konselor yang sudah dipisah
        Route::get('/profile', [ProfileCounselorController::class, 'show'])->name('profile.show');     // Menampilkan profil
        Route::get('/profile/edit', [ProfileCounselorController::class, 'edit'])->name('profile.edit');   // Menampilkan form edit
        Route::post('/profile/update', [ProfileCounselorController::class, 'update'])->name('profile.update'); // Memproses update profil

        // Rute untuk menampilkan percakapan spesifik dengan seorang user (berdasarkan bookingId)
        Route::get('/chat/with/{partnerUserId}/booking/{bookingId}', [ChatCounselorController::class, 'showSpecificChat'])->name('chat.show');

        // Rute untuk mengirim pesan dalam percakapan spesifik (akan dipanggil via AJAX)
        Route::post('/chat/with/{partnerUserId}/booking/{bookingId}/send', [ChatCounselorController::class, 'sendMessage'])->name('chat.send');
     });


// ======================================================================
// --- RUTE TERPROTEKSI UNTUK PENGGUNA BIASA ---
// ======================================================================
// Pastikan middleware 'auth_firebase' Anda sesuai dan berfungsi untuk pengguna biasa
Route::middleware(['auth_firebase'])->group(function () {
    Route::get('/home', [HomeController::class, 'showHome'])->name('home');
    Route::post('/home/recommend-counselor', [HomeController::class, 'recommendCounselorBidang'])->name('home.recommend');
    Route::get('/home/filter-counselors', [HomeController::class, 'filterCounselorsByBidang'])->name('home.filter');

    // Profil untuk Pengguna Biasa
    // Pastikan nama rute 'profile' ini tidak bentrok dengan 'counselor.profile.show' jika diakses tanpa prefix grup
    // Karena ini di luar grup 'counselor.', maka namanya hanya 'profile'
    Route::get('/profile', [ProfileController::class, 'showProfile'])->name('profile');
    Route::post('/profile/update-data', [ProfileController::class, 'updateProfileData'])->name('profile.updateData');
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // Detail Konselor (dilihat oleh Pengguna Biasa)
    Route::get('/counselor/{uid}', [CounselorController::class, 'showCounselorDetail'])->name('counselor.detail');
    Route::post('/counselor/book-schedule', [CounselorController::class, 'bookSchedule'])->name('counselor.bookSchedule');
    Route::post('/counselor/save-rating', [CounselorController::class, 'saveRating'])->name('counselor.saveRating');

    // Riwayat untuk Pengguna Biasa
    Route::get('/history', [HistoryController::class, 'showHistory'])->name('history');

    // Chat untuk Pengguna Biasa
    Route::get('/chat', [ChatController::class, 'showChatList'])->name('chat');
    Route::get('/chat/{receiverId}/{bookingId}/{scheduleId}', [ChatController::class, 'showChat'])->name('chat.show');
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');
    Route::post('/chat/complete-session', [ChatController::class, 'completeSession'])->name('chat.completeSession');
    Route::post('/chat/get-session-messages', [ChatController::class, 'getSessionMessages'])->name('chat.getMessages');

    // Route::get('/chat-counselor', [ChatController::class, 'showChatCounselor'])->name('chat-counselor'); // Evaluasi ulang jika perlu
});


// ======================================================================
// --- DEBUG ROUTE ---
// ======================================================================
Route::get('/debug-firebase', function () {
    $credentialsFile = config('firebase.credentials');
    $projectId = config('firebase.project_id');
    $serviceAccountPath = storage_path('app/firebase/' . $credentialsFile);

    return response()->json([
        'credentialsFile' => $credentialsFile,
        'projectId' => $projectId,
        'serviceAccountPath' => $serviceAccountPath,
        'fileExists' => file_exists($serviceAccountPath),
        'fileReadable' => is_readable($serviceAccountPath),
    ]);
});