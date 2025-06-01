<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Firestore as FirebaseFirestore;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Core\Timestamp;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ChatCounselorController extends Controller
{
    protected $firestoreDb;

    public function __construct(FirebaseFirestore $firestore)
    {
        $this->firestoreDb = $firestore->database();
    }

    public function index()
    {
        $currentCounselorUid = Session::get('uid');
        $currentCounselorName = Session::get('userName', 'Konselor');

        if (!$currentCounselorUid) {
            return redirect()->route('login')->with('error', 'Sesi tidak valid.');
        }

        $chatPartners = [];
        $errorMessage = null;

        try {
            Log::info("ChatCounselorController@index: Fetching bookings for counselor UID: {$currentCounselorUid}");

            $bookingsQuery = $this->firestoreDb->collection('bookings')
                                        ->where('counselorId', '=', $currentCounselorUid)
                                        ->orderBy('createdAt', 'desc'); 

            $bookingsSnapshot = $bookingsQuery->documents();
            $processedUserIdsForChatList = [];

            foreach ($bookingsSnapshot as $bookingDoc) {
                if ($bookingDoc->exists()) {
                    $bookingData = $bookingDoc->data();
                    $userId = $bookingData['userId'] ?? null;

                    if ($userId && !in_array($userId, $processedUserIdsForChatList)) {
                        $userName = $bookingData['userName'] ?? 'Nama Pengguna Tidak Diketahui';
                        
                        // --- MULAI LOGIKA PENGAMBILAN AVATAR ---
                        $userAvatar = $bookingData['userAvatar'] ?? null; // Cek dulu dari data booking

                        if (empty($userAvatar)) { // Jika tidak ada di data booking, coba ambil dari koleksi 'users'
                            try {
                                $userDoc = $this->firestoreDb->collection('users')->document($userId)->snapshot();
                                if ($userDoc->exists() && !empty($userDoc->data()['avatar'])) {
                                    $userAvatar = $userDoc->data()['avatar'];
                                    Log::info("Avatar found for user {$userId} from users collection.");
                                } else {
                                    // Jika tidak ada di 'users' atau field avatar kosong, gunakan UI-Avatars
                                    $userAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random&color=fff&rounded=true';
                                    Log::info("Avatar not found for user {$userId} in users collection, using UI-Avatar.");
                                }
                            } catch (\Throwable $userFetchError) {
                                Log::error("Error fetching user {$userId} for avatar: " . $userFetchError->getMessage());
                                // Fallback jika query ke users gagal
                                $userAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random&color=fff&rounded=true';
                            }
                        }
                        // --- AKHIR LOGIKA PENGAMBILAN AVATAR ---
                        
                        $lastMessage = "Klik untuk mulai chat";
                        $lastMessageTime = "";
                        $isRead = true; // Placeholder

                        $chatPartners[] = [
                            'bookingId' => $bookingDoc->id(),
                            'partnerId' => $userId,
                            'partnerName' => $userName,
                            'partnerAvatar' => $userAvatar, // Sekarang ini bisa jadi URL asli atau UI-Avatar
                            'lastMessage' => $lastMessage,
                            'lastMessageTime' => $lastMessageTime,
                            'isRead' => $isRead,
                        ];
                        $processedUserIdsForChatList[] = $userId;
                    }
                }
            }
            Log::info("ChatCounselorController@index: Found " . count($chatPartners) . " unique chat partners for counselor UID: {$currentCounselorUid}");

        } catch (\Throwable $e) {
            Log::error("ChatCounselorController@index: Error fetching chat list. UID: {$currentCounselorUid}. Error: " . $e->getMessage(), ['exception' => $e]);
            $errorMessage = "Terjadi kesalahan internal saat mengambil daftar chat Anda.";
            // Jika error karena index Firestore, pesan error $e->getMessage() akan berisi link untuk membuat index
            if (str_contains($e->getMessage(), 'query requires an index')) {
                $errorMessage .= " Mungkin ada index database yang perlu dibuat. Periksa log server untuk detail.";
            }
        }

        return view('chat_counselor', [
            'chatPartners' => $chatPartners,
            'errorMessage' => $errorMessage,
            'currentUserName' => $currentCounselorName,
        ]);
    }

    /**
     * Menampilkan pesan-pesan dalam satu sesi chat spesifik antara konselor dan user.
     */
    public function showSpecificChat(Request $request, string $partnerUserId, string $bookingId)
    {
        $currentCounselorUid = Session::get('uid');
        $currentCounselorName = Session::get('userName', 'Konselor');
        $currentCounselorAvatar = Session::get('userAvatar', 'https://ui-avatars.com/api/?name=' . urlencode($currentCounselorName) . '&background=0D6EFD&color=fff');

        if (!$currentCounselorUid) {
            return redirect()->route('login')->with('error', 'Sesi tidak valid.');
        }

        $messages = [];
        $partnerName = 'Pengguna';
        $partnerAvatar = 'https://ui-avatars.com/api/?name=User&background=random&color=fff';
        $errorMessage = null;

        try {
            $bookingDoc = $this->firestoreDb->collection('bookings')->document($bookingId)->snapshot();
            if ($bookingDoc->exists() &&
                ($bookingDoc->data()['counselorId'] ?? null) === $currentCounselorUid &&
                ($bookingDoc->data()['userId'] ?? null) === $partnerUserId) {
                
                $bookingData = $bookingDoc->data(); // Ambil data booking
                $partnerName = $bookingData['userName'] ?? $partnerName; // Ambil nama dari booking

                // COBA AMBIL userAvatar DARI bookingData DULU (jika ada)
                if (!empty($bookingData['userAvatar'])) {
                    $partnerAvatar = $bookingData['userAvatar'];
                    Log::info("Avatar for partner {$partnerUserId} found in bookingData for specific chat: {$partnerAvatar}");
                } else {
                    // JIKA TIDAK ADA DI bookingData, BARU AMBIL DARI USERS COLLECTION
                    Log::info("userAvatar not in bookingData for partner {$partnerUserId}. Attempting to fetch from 'users' collection.");
                    $userDoc = $this->firestoreDb->collection('users')->document($partnerUserId)->snapshot();
                    if ($userDoc->exists()) {
                        $userDataFromUsers = $userDoc->data();
                        if (!empty($userDataFromUsers['avatar'])) { // Pastikan field 'avatar' ada dan tidak kosong
                            $partnerAvatar = $userDataFromUsers['avatar'];
                            Log::info("Avatar FOUND for partner {$partnerUserId} from users collection for specific chat: {$partnerAvatar}");
                        } else {
                            Log::info("Field 'avatar' is empty or not present for partner {$partnerUserId} in users collection. Using UI-Avatar with name: {$partnerName}");
                            $partnerAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($partnerName) . '&background=random&color=fff&rounded=true';
                        }
                    } else {
                        Log::info("User document not found in 'users' collection for partner {$partnerUserId}. Using UI-Avatar with name: {$partnerName}");
                        $partnerAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($partnerName) . '&background=random&color=fff&rounded=true';
                    }
                }
            } else {
                Log::warning("ChatCounselorController@showSpecificChat: Booking context not found/mismatched. Counselor: $currentCounselorUid, TargetUser: $partnerUserId, Booking: $bookingId. Attempting to fetch partner info directly from 'users'.");
                // Jika booking tidak valid, tetap coba ambil info partner dari 'users' collection
                $userDoc = $this->firestoreDb->collection('users')->document($partnerUserId)->snapshot();
                if($userDoc->exists()){
                    $partnerName = $userDoc->data()['name'] ?? $partnerName;
                    if (!empty($userDoc->data()['avatar'])) {
                        $partnerAvatar = $userDoc->data()['avatar'];
                    } else {
                        $partnerAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($partnerName) . '&background=random&color=fff&rounded=true';
                    }
                } else {
                    Log::warning("User doc also not found for partner {$partnerUserId} when booking context failed.");
                }
            }

            Log::info("ChatCounselorController@showSpecificChat: Fetching messages between Counselor UID: {$currentCounselorUid} and User UID: {$partnerUserId}");

            $sentMessagesQuery = $this->firestoreDb->collection('chats')
                                        ->where('senderId', '=', $currentCounselorUid)
                                        ->where('receiverId', '=', $partnerUserId)
                                        ->orderBy('timestamp', 'asc');
            $receivedMessagesQuery = $this->firestoreDb->collection('chats')
                                        ->where('senderId', '=', $partnerUserId)
                                        ->where('receiverId', '=', $currentCounselorUid)
                                        ->orderBy('timestamp', 'asc');

            $allMessageDocsSnapshots = array_merge(
                iterator_to_array($sentMessagesQuery->documents()),
                iterator_to_array($receivedMessagesQuery->documents())
            );

            $messageDataToSort = [];
            foreach($allMessageDocsSnapshots as $docSnapshot){
                if($docSnapshot->exists()){
                    $data = $docSnapshot->data();
                    $data['id'] = $docSnapshot->id();
                    $messageDataToSort[] = $data;
                }
            }

            usort($messageDataToSort, function($a, $b) {
                $timestampA = $a['timestamp'] ?? null;
                $timestampB = $b['timestamp'] ?? null;
                if (!$timestampA && !$timestampB) return 0;
                if (!$timestampA) return 1; // Diubah agar null di akhir saat descending
                if (!$timestampB) return -1; // Diubah agar null di akhir saat descending

                // Pastikan kita mendapatkan nilai numerik untuk perbandingan timestamp
                $tsAValue = ($timestampA instanceof Timestamp) ? $timestampA->get()->getTimestamp() : (is_numeric($timestampA) ? (int)$timestampA : strtotime($timestampA->formatAsString() ?? 'now'));
                $tsBValue = ($timestampB instanceof Timestamp) ? $timestampB->get()->getTimestamp() : (is_numeric($timestampB) ? (int)$timestampB : strtotime($timestampB->formatAsString() ?? 'now'));

                return $tsBValue <=> $tsAValue; // <-- DIUBAH MENJADI DESCENDING (B ke A)
            });

            foreach ($messageDataToSort as $msgData) {
                $timestamp = $msgData['timestamp'] ?? null;
                Log::info('Inspecting timestamp for chat message:', [ 
                    'message_id' => $msgData['id'] ?? 'unknown_id',
                    'raw_timestamp_value' => $timestamp,
                    'timestamp_type' => gettype($timestamp),
                    'is_core_timestamp_instance' => ($timestamp instanceof \Google\Cloud\Core\Timestamp)
                ]);
                $formattedTime = 'N/A';
                if ($timestamp instanceof Timestamp) { // Menggunakan \Google\Cloud\Core\Timestamp dari 'use' statement
                    try {
                        $formattedTime = Carbon::instance($timestamp->get())->setTimezone('Asia/Jakarta')->format('H:i');
                    } catch (\Throwable $carbonError) { /* Log error */ }
                } elseif (is_string($timestamp) && !empty($timestamp)) {
                    try { $formattedTime = Carbon::parse($timestamp)->setTimezone('Asia/Jakarta')->format('H:i'); } catch (\Throwable $ex) { /* Log error */ }
                }

                $messages[] = [
                    'id' => $msgData['id'],
                    'content' => $msgData['content'] ?? '',
                    'senderId' => $msgData['senderId'] ?? '',
                    'formattedTime' => $formattedTime,
                    'isMe' => ($msgData['senderId'] == $currentCounselorUid),
                    'senderName' => $msgData['senderName'] ?? ($msgData['senderId'] == $currentCounselorUid ? $currentCounselorName : $partnerName),
                    'senderAvatar' => $msgData['senderAvatar'] ?? ($msgData['senderId'] == $currentCounselorUid ? $currentCounselorAvatar : $partnerAvatar),
                    'isRead' => $msgData['isRead'] ?? false,
                ];
            }
            Log::info("ChatCounselorController@showSpecificChat: Found " . count($messages) . " total messages after merge and sort.");

        } catch (\Throwable $e) {
            Log::error("ChatCounselorController@showSpecificChat: Error fetching messages. Error: " . $e->getMessage(), ['exception' => $e]);
            $errorMessage = "Terjadi kesalahan saat mengambil pesan chat: " . $e->getMessage();
        }

        return view('chat_counselor_detail', [
            'messages' => $messages,
            'errorMessage' => $errorMessage,
            'currentCounselorUid' => $currentCounselorUid,
            'currentCounselorName' => $currentCounselorName,
            'partnerId' => $partnerUserId,
            'partnerName' => $partnerName,
            'partnerAvatar' => $partnerAvatar,
            'bookingId' => $bookingId,
        ]);
    }

    /**
     * Mengirim pesan baru dari konselor ke pengguna.
     */
    public function sendMessage(Request $request, string $partnerUserId, string $bookingId)
    {
        // dd() untuk memeriksa keberadaan kelas
        // dd(
        //     'Pengecekan dari sendMessage:', // Pesan agar kita tahu dd() ini dari mana
        //     'Apakah \Kreait\Firebase\Contract\Firestore ada?', class_exists(\Kreait\Firebase\Contract\Firestore::class),
        //     'Apakah \Kreait\Firestore\FieldValue (FQN) ada?', class_exists(\Kreait\Firestore\FieldValue::class),
        //     'Apakah FieldValue (shortname, via use) ada?', class_exists('FieldValue')
        // );
        $currentCounselorUid = Session::get('uid');
        $currentCounselorName = Session::get('userName', 'Konselor');
        $currentCounselorAvatar = Session::get('userAvatar', 'https://ui-avatars.com/api/?name=' . urlencode($currentCounselorName) . '&background=0D6EFD&color=fff');

        if (!$currentCounselorUid) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak valid.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'message_content' => 'required|string|max:5000',
            // 'bookingId' => 'required|string', // Tidak perlu validasi di sini jika sudah dari route parameter
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $messageContent = $request->input('message_content');

        try {
            $bookingDoc = $this->firestoreDb->collection('bookings')->document($bookingId)->snapshot();
            if (!$bookingDoc->exists() ||
                ($bookingDoc->data()['counselorId'] ?? null) !== $currentCounselorUid ||
                ($bookingDoc->data()['userId'] ?? null) !== $partnerUserId) {
                Log::warning("ChatCounselorController@sendMessage: Unauthorized attempt or invalid booking context. Counselor: $currentCounselorUid, TargetUser: $partnerUserId, Booking: $bookingId");
                return response()->json(['success' => false, 'message' => 'Konteks sesi chat tidak valid untuk mengirim pesan.'], 403);
            }

            $newMessageData = [
                'senderId' => $currentCounselorUid,
                'receiverId' => $partnerUserId,
                'senderName' => $currentCounselorName,
                'senderAvatar' => $currentCounselorAvatar,
                'content' => $messageContent,
                'timestamp' => FieldValue::serverTimestamp(),
                'isRead' => false,
                'bookingId' => $bookingId, // <--- TAMBAHKAN BARIS INI
                'participants' => [$currentCounselorUid, $partnerUserId], // <--- TAMBAHKAN BARIS INI (opsional tapi disarankan)
            ];

            $newMessageRef = $this->firestoreDb->collection('chats')->add($newMessageData); // Gunakan add() jika ingin ID otomatis
            // Jika sebelumnya menggunakan newDocument()->set(), sekarang langsung add()
            // $newMessageRef->set($newMessageData);

            Log::info("ChatCounselorController@sendMessage: Message sent by Counselor: {$currentCounselorUid} to User: {$partnerUserId}. Booking ID: {$bookingId}.");

            // Siapkan data untuk respons AJAX
            $sentMessageForResponse = $newMessageData;
            $sentMessageForResponse['id'] = $newMessageRef->id();
            $now = Carbon::now(new \DateTimeZone('Asia/Jakarta'));
            $sentMessageForResponse['formattedTime'] = $now->format('H:i');

            return response()->json([
                'success' => true,
                'message' => 'Pesan terkirim!',
                'sentMessage' => $sentMessageForResponse
            ]);

        } catch (\Throwable $e) {
            Log::error("ChatCounselorController@sendMessage: Error sending message. Counselor: {$currentCounselorUid}. Error: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan: Terjadi kesalahan server.'], 500);
        }
    }
}