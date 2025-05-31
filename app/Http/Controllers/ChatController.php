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

            $bookingSnapshot = $this->firestore->database()->collection('bookings')
                ->where('userId', '=', $userId)
                ->where('status', '=', 'booked')
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

        try {
            $counselorDoc = $this->firestore->database()->collection('counselors')->document($receiverId)->snapshot();
            if ($counselorDoc->exists()) {
                $receiverName = $counselorDoc->data()['name'] ?? 'Konselor';
                $receiverAvatar = $counselorDoc->data()['avatar'] ?? asset('images/default_profile.png');
            }

            Log::info("Memuat riwayat pesan antara $userId dan $receiverId untuk booking $bookingId");

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
            'chatList' => [],
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
            'receiverId' => 'required|string',
        ]);

        $userId = Session::get('uid');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk menyelesaikan sesi.'], 401);
        }

        $bookingId = $request->bookingId;
        $scheduleId = $request->scheduleId;
        // $counselorId = $request->receiverId; // Tidak dipakai untuk hapus chat

        try {
            $this->firestore->database()->runTransaction(function (Transaction $transaction) use ($bookingId, $scheduleId) {
                $db = $this->firestore->database();

                // Hapus dokumen booking
                $bookingRef = $db->collection('bookings')->document($bookingId);
                $transaction->delete($bookingRef);
                Log::info("Dokumen booking dihapus: $bookingId");

                // Set isBooked = false pada schedule
                $scheduleRef = $db->collection('schedules')->document($scheduleId);
                $transaction->update($scheduleRef, [
                    ['path' => 'isBooked', 'value' => false]
                ]);
                Log::info("Status isBooked pada schedule $scheduleId diupdate ke false");

                // Hapus SEMUA chat yang berhubungan dengan bookingId ini
                $chatCollectionRef = $db->collection('chats');
                $chatQuery = $chatCollectionRef->where('bookingId', '=', $bookingId);

                $chatDocs = $chatQuery->documents();
                $deletedChatCount = 0;

                foreach ($chatDocs as $chatDoc) {
                    if ($chatDoc->exists()) {
                        $transaction->delete($chatDoc->reference());
                        $deletedChatCount++;
                    }
                }
                Log::info("Total $deletedChatCount pesan chat terkait booking $bookingId dihapus.");
            });

            return response()->json(['success' => true, 'message' => 'Sesi berhasil diselesaikan. Jadwal sudah tersedia kembali dan riwayat chat dihapus.']);

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
        $bookingId = $request->bookingId; // Ambil bookingId dari request
        $messages = [];

        try {
            Log::info("Memuat pesan chat untuk sesi AJAX antara $userId dan $receiverId untuk booking $bookingId.");

            // Menggunakan kombinasi senderId, receiverId, dan bookingId untuk query
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