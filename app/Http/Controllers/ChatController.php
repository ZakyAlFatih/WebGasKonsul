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
        $counselorUids = [];
        $rawBookingData = [];

        try {
            Log::info("Mulai mengambil daftar chat aktif untuk userId: $userId");

            // Fetch bookings with status 'booked' to show in active chat list
            $bookingSnapshot = $this->firestore->database()->collection('bookings')
                ->where('userId', '=', $userId)
                ->where('status', '=', 'booked') // Only show active/booked sessions here
                ->documents();

            foreach ($bookingSnapshot as $bookingDoc) {
                if ($bookingDoc->exists()) {
                    $bookingData = $bookingDoc->data();
                    $bookingData['bookingId'] = $bookingDoc->id();
                    $rawBookingData[] = $bookingData;
                    if (isset($bookingData['counselorId']) && !empty($bookingData['counselorId'])) {
                        $counselorUids[$bookingData['counselorId']] = true;
                    }
                }
            }

            $counselorDataMap = [];
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

            foreach ($rawBookingData as $bookingData) {
                $counselorId = $bookingData['counselorId'] ?? null;
                $counselorDetail = $counselorDataMap[$counselorId] ?? [];

                $chatList[] = [
                    'chatPartnerId' => $counselorId,
                    'chatPartnerName' => $bookingData['counselorName'] ?? ($counselorDetail['name'] ?? 'Konselor Tidak Dikenal'),
                    'chatPartnerAvatar' => $counselorDetail['avatar'] ?? asset('images/default_profile.png'),
                    'status' => $bookingData['status'] ?? 'booked',
                    'bookingId' => $bookingData['bookingId'],
                    'scheduleId' => $bookingData['scheduleId'] ?? '',
                ];
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
            'selectedReceiverId' => null,
            'selectedReceiverName' => null,
            'selectedBookingId' => null,
            'selectedScheduleId' => null,
            'messages' => [],
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
        $receiverName = 'Partner Chat';
        $receiverAvatar = asset('images/default_profile.png');
        $bookingStatus = 'unknown'; // Tambahkan status booking di sini

        try {
            // Get booking status
            $bookingDoc = $this->firestore->database()->collection('bookings')->document($bookingId)->snapshot();
            if ($bookingDoc->exists()) {
                $bookingData = $bookingDoc->data();
                $bookingStatus = $bookingData['status'] ?? 'unknown';
            } else {
                return redirect()->route('chat')->with('error', 'Sesi chat tidak ditemukan atau sudah berakhir.');
            }


            $counselorDoc = $this->firestore->database()->collection('counselors')->document($receiverId)->snapshot();
            if ($counselorDoc->exists()) {
                $receiverName = $counselorDoc->data()['name'] ?? 'Konselor';
                $receiverAvatar = $counselorDoc->data()['avatar'] ?? asset('images/default_profile.png');
            }

            Log::info("Memuat riwayat pesan antara $userId dan $receiverId untuk booking $bookingId");

            // Query chat messages
            $chatSnapshot = $this->firestore->database()->collection('chats')
                ->where('bookingId', '=', $bookingId)
                ->where('senderId', 'in', [$userId, $receiverId])
                ->where('receiverId', 'in', [$userId, $receiverId])
                ->orderBy('timestamp')
                ->documents();

            foreach ($chatSnapshot as $messageDoc) {
                if ($messageDoc->exists()) {
                    $messageData = $messageDoc->data();

                    $timestampObject = $messageData['timestamp'];
                    $carbonTimestamp = null;
                    if ($timestampObject instanceof \Google\Cloud\Core\Timestamp) {
                        $carbonTimestamp = \Carbon\Carbon::parse($timestampObject->get());
                    } else {
                        $carbonTimestamp = \Carbon\Carbon::parse($timestampObject);
                    }

                    if ($carbonTimestamp) {
                        $messageData['timestamp_formatted'] = $carbonTimestamp->timezone(config('app.timezone'))->format('H:i');
                    } else {
                        $messageData['timestamp_formatted'] = 'Invalid Time';
                    }
                    $messages[] = $messageData;
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
            'chatList' => [], // Keep empty since we're viewing a specific chat
            'errorMessage' => $errorMessage,
            'senderId' => $userId,
            'senderName' => $userName,
            'senderAvatar' => $userAvatar,
            'selectedReceiverId' => $receiverId,
            'selectedReceiverName' => $receiverName,
            'selectedReceiverAvatar' => $receiverAvatar,
            'selectedBookingId' => $bookingId,
            'selectedScheduleId' => $scheduleId,
            'messages' => $messages,
            'bookingStatus' => $bookingStatus, // Pass booking status to the view
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
            'bookingId' => 'required|string',
        ]);

        $senderId = Session::get('uid');
        if (!$senderId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk mengirim pesan.'], 401);
        }

        try {
            // Check booking status first
            $bookingRef = $this->firestore->database()->collection('bookings')->document($request->bookingId);
            $bookingSnapshot = $bookingRef->snapshot();
            if (!$bookingSnapshot->exists() || ($bookingSnapshot->data()['status'] ?? 'unknown') !== 'booked') {
                return response()->json(['success' => false, 'message' => 'Sesi chat ini tidak aktif atau sudah selesai.'], 400);
            }

            $message = [
                'senderId' => $senderId,
                'receiverId' => $request->receiverId,
                'content' => $request->content,
                'timestamp' => FieldValue::serverTimestamp(),
                'senderName' => $request->senderName,
                'senderAvatar' => $request->senderAvatar,
                'isRead' => false,
                'bookingId' => $request->bookingId,
                'participants' => [$senderId, $request->receiverId],
            ];

            $this->firestore->database()->collection('chats')->add($message);
            Log::info("Pesan berhasil dikirim dari $senderId ke {$request->receiverId} untuk booking {$request->bookingId}");

            return response()->json(['success' => true, 'message' => 'Pesan terkirim.']);

        } catch (\Throwable $e) {
            Log::error('Error mengirim pesan chat: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menyelesaikan sesi booking.
     * Mengubah status booking menjadi 'completed' dan mengembalikan jadwal.
     * Riwayat chat tidak dihapus, tetapi booking tidak lagi 'booked'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'bookingId' => 'required|string',
            'scheduleId' => 'required|string',
            'receiverId' => 'required|string', // Counselor ID, useful for logging
        ]);

        $userId = Session::get('uid');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk menyelesaikan sesi.'], 401);
        }

        $bookingId = $request->bookingId;
        $scheduleId = $request->scheduleId;
        $counselorId = $request->receiverId;

        try {
            $this->firestore->database()->runTransaction(function (Transaction $transaction) use ($bookingId, $scheduleId, $counselorId, $userId) {
                $db = $this->firestore->database();

                // 1. Ambil dokumen booking
                $bookingRef = $db->collection('bookings')->document($bookingId);
                $bookingDoc = $transaction->snapshot($bookingRef);

                if (!$bookingDoc->exists()) {
                    throw new \Exception('Booking tidak ditemukan.');
                }

                $bookingData = $bookingDoc->data();

                // Pastikan user yang menyelesaikan sesi adalah pemilik booking
                if (($bookingData['userId'] ?? null) !== $userId) {
                    throw new \Exception('Anda tidak berhak menyelesaikan sesi ini.');
                }

                // Pastikan status booking saat ini adalah 'booked'
                if (($bookingData['status'] ?? 'unknown') !== 'booked') {
                    throw new \Exception('Sesi ini tidak dalam status "booked" atau sudah selesai.');
                }

                // 2. Ubah status booking menjadi 'completed' dan set hasBeenRated ke false
                $transaction->update($bookingRef, [
                    ['path' => 'status', 'value' => 'completed'],
                    ['path' => 'hasBeenRated', 'value' => false],
                    ['path' => 'completedAt', 'value' => FieldValue::serverTimestamp()]
                ]);
                Log::info("Dokumen booking {$bookingId} diupdate status ke 'completed'.");

                // 3. Set isBooked = false pada schedule
                $scheduleRef = $db->collection('schedules')->document($scheduleId);
                $transaction->update($scheduleRef, [
                    ['path' => 'isBooked', 'value' => false]
                ]);
                Log::info("Status isBooked pada schedule $scheduleId diupdate ke false.");
            });

            return response()->json(['success' => true, 'message' => 'Sesi berhasil diselesaikan. Anda sekarang bisa memberi rating di halaman riwayat.']);

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
            'receiverId' => 'required|string',
            'bookingId' => 'required|string',
        ]);

        $userId = Session::get('uid');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk melihat pesan.'], 401);
        }

        $receiverId = $request->receiverId;
        $bookingId = $request->bookingId;
        $messages = [];

        try {
            $bookingRef = $this->firestore->database()->collection('bookings')->document($bookingId);
            $bookingSnapshot = $bookingRef->snapshot();
            if (!$bookingSnapshot->exists() || ($bookingSnapshot->data()['status'] ?? 'unknown') !== 'booked') {
                return response()->json(['success' => false, 'message' => 'Sesi chat ini tidak aktif atau sudah selesai.'], 400);
            }


            Log::info("Memuat pesan chat untuk sesi AJAX antara $userId dan $receiverId untuk booking $bookingId.");

            $chatSnapshot = $this->firestore->database()->collection('chats')
                ->where('bookingId', '=', $bookingId)
                ->where('senderId', 'in', [$userId, $receiverId])
                ->where('receiverId', 'in', [$userId, $receiverId])
                ->orderBy('timestamp')
                ->documents();

            foreach ($chatSnapshot as $messageDoc) {
                if ($messageDoc->exists()) {
                    $messageData = $messageDoc->data();

                    $timestampObject = $messageData['timestamp'];
                    $carbonTimestamp = null;
                    if ($timestampObject instanceof \Google\Cloud\Core\Timestamp) {
                        $carbonTimestamp = \Carbon\Carbon::parse($timestampObject->get());
                    } else {
                        $carbonTimestamp = \Carbon\Carbon::parse($timestampObject);
                    }

                    if ($carbonTimestamp) {
                        $messageData['timestamp_formatted'] = $carbonTimestamp->timezone(config('app.timezone'))->format('H:i');
                    } else {
                        $messageData['timestamp_formatted'] = 'Invalid Time';
                    }
                    $messages[] = $messageData;
                }
            }

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