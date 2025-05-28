<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FieldValue;
use Carbon\Carbon;
use Google\Cloud\Firestore\Transaction;

class ChatController extends Controller
{
    protected $firestore;
    protected $auth;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
        $this->auth = app('firebase.auth');
    }

    public function showChatList()
    {
        $userId = Session::get('uid');
        $userName = Session::get('userName');
        $userAvatar = Session::get('userAvatar') ?? asset('images/default_profile.png');

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk melihat chat.');
        }

        $chatList = [];
        $errorMessage = null;

        try {
            Log::info("Mulai mengambil daftar chat aktif untuk userId: $userId");

            $bookingSnapshot = $this->firestore->database()->collection('bookings')
                ->where('userId', '=', $userId)
                ->where('status', '=', 'booked')
                ->documents();

            foreach ($bookingSnapshot as $bookingDoc) {
                if ($bookingDoc->exists()) {
                    $bookingData = $bookingDoc->data();
                    $chatList[] = [
                        'chatPartnerId' => $bookingData['counselorId'] ?? '',
                        'chatPartnerName' => $bookingData['counselorName'] ?? 'Konselor Tidak Dikenal',
                        'status' => $bookingData['status'] ?? 'booked',
                        'bookingId' => $bookingDoc->id(),
                        'scheduleId' => $bookingData['scheduleId'] ?? '',
                    ];
                }
            }
            Log::info("Total chat aktif ditemukan: " . count($chatList));

        } catch (\Throwable $e) {
            Log::error('Error mengambil daftar chat: ' . $e->getMessage() . ' - UID: ' . $userId, ['trace' => $e->getTraceAsString()]);
            $errorMessage = 'Terjadi kesalahan saat memuat daftar chat: ' . $e->getMessage();
        }

        return view('chat', [
            'chatList' => $chatList,
            'errorMessage' => $errorMessage,
            'senderId' => $userId,
            'senderName' => $userName,
            'senderAvatar' => $userAvatar,
            // --- VARIABEL INI SELALU DISEDIAKAN ---
            'selectedReceiverId' => null,
            'selectedReceiverName' => null,
            'selectedBookingId' => null,
            'selectedScheduleId' => null,
            'messages' => [], // Untuk memastikan 'messages' selalu ada
        ]);
    }

    public function showChat(string $receiverId, string $bookingId, string $scheduleId)
    {
        $userId = Session::get('uid');
        $userName = Session::get('userName');
        $userAvatar = Session::get('userAvatar') ?? asset('images/default_profile.png');

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk chat.');
        }

        $messages = [];
        $errorMessage = null;
        $receiverName = 'Partner Chat'; // Default

        try {
            $counselorDoc = $this->firestore->database()->collection('counselors')->document($receiverId)->snapshot();
            if ($counselorDoc->exists()) {
                $receiverName = $counselorDoc->data()['name'] ?? 'Konselor';
            }

            Log::info("Memuat riwayat pesan antara $userId dan $receiverId untuk booking $bookingId");

            $chatSnapshot = $this->firestore->database()->collection('chats')
                ->where('senderId', 'in', [$userId, $receiverId])
                ->where('receiverId', 'in', [$userId, $receiverId])
                ->orderBy('timestamp')
                ->documents();

            foreach ($chatSnapshot as $messageDoc) {
                if ($messageDoc->exists()) {
                    $messages[] = $messageDoc->data();
                }
            }
            usort($messages, function($a, $b) {
                $tsA = $a['timestamp'] instanceof \Google\Cloud\Core\Timestamp ? $a['timestamp']->get()->getTimestamp() : strtotime($a['timestamp']);
                $tsB = $b['timestamp'] instanceof \Google\Cloud\Core\Timestamp ? $b['timestamp']->get()->getTimestamp() : strtotime($b['timestamp']);
                return $tsA <=> $tsB;
            });
            Log::info("Total pesan chat ditemukan: " . count($messages));

        } catch (\Throwable $e) {
            Log::error('Error mengambil pesan chat: ' . $e->getMessage() . ' - UID: ' . $userId, ['trace' => $e->getTraceAsString()]);
            $errorMessage = 'Terjadi kesalahan saat memuat pesan chat: ' . $e->getMessage();
        }

        return view('chat', [
            'chatList' => [],
            'errorMessage' => $errorMessage,
            'senderId' => $userId,
            'senderName' => $userName,
            'senderAvatar' => $userAvatar,
            // --- VARIABEL INI SELALU DISEDIAKAN ---
            'selectedReceiverId' => $receiverId,
            'selectedReceiverName' => $receiverName,
            'selectedBookingId' => $bookingId,
            'selectedScheduleId' => $scheduleId,
            'messages' => $messages,
        ]);
    }

    /**
     * Mengirim pesan chat.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiverId' => 'required|string',
            'content' => 'required|string|max:500',
            'senderName' => 'required|string',
            'senderAvatar' => 'nullable|string|url',
        ]);

        $senderId = Session::get('uid');
        if (!$senderId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk mengirim pesan.'], 401);
        }

        try {
            $message = [
                'senderId' => $senderId,
                'receiverId' => $request->receiverId,
                'content' => $request->content,
                'timestamp' => FieldValue::serverTimestamp(),
                'senderName' => $request->senderName,
                'senderAvatar' => $request->senderAvatar,
                'isRead' => false,
            ];

            $this->firestore->database()->collection('chats')->add($message);
            Log::info("Pesan berhasil dikirim dari $senderId ke {$request->receiverId}");

            return response()->json(['success' => true, 'message' => 'Pesan terkirim.']);

        } catch (\Throwable $e) {
            Log::error('Error mengirim pesan chat: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menyelesaikan sesi booking, mengembalikan jadwal, dan menghapus chat.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'bookingId' => 'required|string',
            'scheduleId' => 'required|string',
            'receiverId' => 'required|string', // counselorId
        ]);

        $userId = Session::get('uid');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk menyelesaikan sesi.'], 401);
        }

        $bookingId = $request->bookingId;
        $scheduleId = $request->scheduleId;
        $counselorId = $request->receiverId; // Nama receiverId di frontend adalah counselorId di backend

        try {
            $this->firestore->database()->runTransaction(function (Transaction $transaction) use ($bookingId, $scheduleId) {
                $db = $this->firestore->database();

                // 1. Hapus dokumen booking
                $bookingRef = $db->collection('bookings')->document($bookingId);
                $transaction->delete($bookingRef);
                Log::info("Dokumen booking dihapus: $bookingId");

                // 2. Set isBooked = false pada schedule
                $scheduleRef = $db->collection('schedules')->document($scheduleId);
                $transaction->update($scheduleRef, [
                    ['path' => 'isBooked', 'value' => false]
                ]);
                Log::info("Status isBooked pada schedule $scheduleId diupdate ke false");
            });

            return response()->json(['success' => true, 'message' => 'Sesi berhasil diselesaikan. Jadwal sudah tersedia kembali.']);

        } catch (\Throwable $e) {
            Log::error('Error menyelesaikan sesi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal menyelesaikan sesi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengambil pesan chat untuk sesi spesifik melalui AJAX.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionMessages(Request $request)
    {
        $request->validate([
            'receiverId' => 'required|string', // Ini adalah UID Konselor
        ]);

        $userId = Session::get('uid');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk melihat pesan.'], 401);
        }

        $receiverId = $request->receiverId;
        $messages = [];

        try {
            Log::info("Memuat pesan chat untuk sesi AJAX antara $userId dan $receiverId.");

            $chatSnapshot = $this->firestore->database()->collection('chats')
                ->where('senderId', 'in', [$userId, $receiverId])
                ->where('receiverId', 'in', [$userId, $receiverId])
                ->orderBy('timestamp')
                ->documents();

            foreach ($chatSnapshot as $messageDoc) {
                if ($messageDoc->exists()) {
                    $messageData = $messageDoc->data();
                    // Format timestamp agar mudah digunakan di JavaScript
                    $messageData['timestamp_formatted'] = \Carbon\Carbon::parse($messageData['timestamp'])->format('H:i');
                    $messages[] = $messageData;
                }
            }

            // Urutkan pesan lagi untuk memastikan konsistensi
            usort($messages, function($a, $b) {
                $tsA = $a['timestamp'] instanceof \Google\Cloud\Core\Timestamp ? $a['timestamp']->get()->getTimestamp() : strtotime($a['timestamp']);
                $tsB = $b['timestamp'] instanceof \Google\Cloud\Core\Timestamp ? $b['timestamp']->get()->getTimestamp() : strtotime($b['timestamp']);
                return $tsA <=> $tsB;
            });

            Log::info("Total pesan chat untuk AJAX ditemukan: " . count($messages));
            return response()->json(['success' => true, 'messages' => $messages]);

        } catch (\Throwable $e) {
            Log::error('Error mengambil pesan chat via AJAX: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal memuat pesan chat: ' . $e->getMessage()], 500);
        }
    }
}