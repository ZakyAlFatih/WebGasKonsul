<?php
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ChatCounselorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/chat-counselor', [ChatCounselorController::class, 'showChatCounselor'])->name('chat-counselor');


Route::get('/home', [HomeController::class, 'showHome'])->name('home');

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