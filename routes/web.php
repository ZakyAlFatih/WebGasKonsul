<?php
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CounselorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

// Route untuk halaman utama yang mengarahkan ke login
Route::get('/', function () {
    return view('login');
});

// --- Authentication Routes ---
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// --- Register POST Routes (Hanya POST karena form ada di modal) ---
// Route POST untuk memproses pendaftaran Counselor
Route::post('/register/counselor', [RegisterController::class, 'storeCounselor'])->name('register.counselor.store');

// Route POST untuk memproses pendaftaran User
Route::post('/register/user', [RegisterController::class, 'storeUser'])->name('register.user.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// --- Protected Routes (Perlu Login) ---
Route::middleware(['auth_firebase'])->group(function () {
    // Beranda User
    Route::get('/home', [HomeController::class, 'showHome'])->name('home'); // Ini akan menentukan home_user atau home_counselor
    Route::post('/home/recommend-counselor', [HomeController::class, 'recommendCounselorBidang'])->name('home.recommend');
    Route::get('/home/filter-counselors', [HomeController::class, 'filterCounselorsByBidang'])->name('home.filter');

    // --- Profil Routes ---
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'showProfile'])->name('profile');
    Route::post('/profile/update-data', [App\Http\Controllers\ProfileController::class, 'updateProfileData'])->name('profile.updateData');
    Route::post('/profile/update-password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::post('/profile/update-avatar', [App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('profile.updateAvatar');

    // --- Detail Konselor ---
    // Route untuk menampilkan halaman detail konselor
    Route::get('/counselor/{uid}', [CounselorController::class, 'showCounselorDetail'])->name('counselor.detail');
    // Route POST untuk booking jadwal konselor
    Route::post('/counselor/book-schedule', [CounselorController::class, 'bookSchedule'])->name('counselor.bookSchedule');
    // Route POST untuk menyimpan rating konselor
    Route::post('/counselor/save-rating', [CounselorController::class, 'saveRating'])->name('counselor.saveRating');

    // Route untuk halaman riwayat
    Route::get('/history', [HistoryController::class, 'showHistory'])->name('history');

    // Route untuk halaman chat
    // Route untuk halaman daftar chat aktif
    Route::get('/chat', [ChatController::class, 'showChatList'])->name('chat');

    // Route untuk menampilkan UI chat spesifik (ketika user klik konselor di daftar chat)
    Route::get('/chat/{receiverId}/{bookingId}/{scheduleId}', [ChatController::class, 'showChat'])->name('chat.show');

    // Route untuk mengirim pesan
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');

    // Route untuk menyelesaikan sesi chat
    Route::post('/chat/complete-session', [ChatController::class, 'completeSession'])->name('chat.completeSession');

    // Route baru untuk mengambil pesan chat spesifik melalui AJAX
    Route::post('/chat/get-session-messages', [App\Http\Controllers\ChatController::class, 'getSessionMessages'])->name('chat.getMessages');

    // Chat Counselor (placeholder, ini untuk role counselor)
    Route::get('/chat-counselor', [ChatController::class, 'showChatCounselor'])->name('chat-counselor');

});

// --- Debug Route ---
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