@extends('layouts.app_counselor') {{-- Pastikan nama layout utama Anda benar --}}

@section('title', 'Chat dengan ' . ($partnerName ?? 'Pengguna'))

@push('styles')
{{-- Font Awesome untuk ikon (jika belum ada di layout utama) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* Container utama halaman chat */
    .chat-page-container {
        display: flex;
        flex-direction: column;
        /* Tinggi dihitung berdasarkan tinggi viewport dikurangi tinggi navbar utama Anda.
           Ganti 56px jika tinggi navbar Anda berbeda. Contoh untuk navbar Bootstrap standar. */
        height: calc(100vh - 56px); /* Sesuaikan 56px dengan tinggi navbar Anda */
        overflow: hidden; /* Mencegah container utama ini memiliki scrollbar */
    }

    /* Header chat (nama partner, tombol kembali) */
    .chat-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 0.75rem 1.25rem;
        flex-shrink: 0; /* Header tidak akan menyusut */
        display: flex;
        align-items: center;
    }

    /* Area untuk menampilkan pesan-pesan */
    .chat-messages {
        flex-grow: 1; /* Mengisi semua sisa ruang vertikal yang tersedia */
        overflow-y: auto; /* HANYA area ini yang akan scrollable jika pesan banyak */
        padding: 1rem;
        display: flex;
        flex-direction: column-reverse; /* Pesan baru muncul di bawah (DOM teratas), dan scroll otomatis ke bawah (DOM teratas) */
    }

    /* Bubble untuk setiap pesan individual */
    .message-bubble {
        max-width: 70%;
        padding: 0.65rem 1rem;
        border-radius: 1rem;
        margin-bottom: 0.6rem;
        word-wrap: break-word;
        line-height: 1.4;
    }

    .message-bubble.is-me {
        background-color: #0d6efd;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 0.25rem;
    }

    .message-bubble.is-them {
        background-color: #e9ecef;
        color: #212529;
        margin-right: auto;
        border-bottom-left-radius: 0.25rem;
    }

    .message-time {
        font-size: 0.7rem;
        color: #6c757d;
        display: block;
        margin-top: 0.25rem;
    }
    .message-bubble.is-me .message-time {
        color: rgba(255, 255, 255, 0.75);
        text-align: right;
    }
    .message-bubble.is-them .message-time {
        text-align: left;
    }

    /* Area input pesan di bagian bawah */
    .chat-input-area {
        border-top: 1px solid #dee2e6;
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        flex-shrink: 0;
    }
    .chat-input-area textarea.form-control {
        border-radius: 1.5rem 0 0 1.5rem;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }
    .chat-input-area .btn-primary {
        border-radius: 0 1.5rem 1.5rem 0;
    }
</style>
@endpush

@section('content')
<div class="chat-page-container">

    <div class="chat-header">
        <a href="{{ route('counselor.chat') }}" class="btn btn-sm btn-outline-secondary me-3" title="Kembali ke Daftar Chat">
            <i class="fas fa-arrow-left"></i>
        </a>
        <img src="{{ $partnerAvatar ?? asset('images/default_profile.png') }}" alt="Avatar {{ $partnerName ?? 'User' }}" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
        <h5 class="mb-0 fw-bold text-primary">{{ $partnerName ?? 'Percakapan' }}</h5>
    </div>

    <div class="chat-messages" id="chatMessagesAreaCounselor"> {{-- Mengubah ID agar unik jika diperlukan --}}
        @if(isset($errorMessage) && $errorMessage)
            <div class="alert alert-warning text-center m-auto">{{ $errorMessage }}</div>
        @elseif(empty($messages))
            <div class="text-center text-muted m-auto" id="noMessagesInfoCounselor">
                <p class="mb-1">Belum ada pesan dalam percakapan ini.</p>
                <p class="small">Mulai percakapan dengan mengirim pesan pertama Anda!</p>
            </div>
        @else
            @foreach($messages as $message)     
                <div class="message-bubble {{ $message['isMe'] ? 'is-me' : 'is-them' }}">
                    <div>{!! nl2br(e($message['content'])) !!}</div>
                    <div class="d-flex justify-content-end align-items-center mt-1"> {{-- Wrapper untuk waktu & centang --}}
                        <small class="message-time me-1">{{ $message['formattedTime'] ?? '' }}</small>
                        @if($message['isMe'])
                            @if($message['isRead'] ?? false) {{-- Jika isMe dan isRead true --}}
                                <i class="fas fa-check-double" style="font-size: 0.7rem; color: #b0e0e6;"></i> {{-- Centang dua, warna bisa disesuaikan --}}
                            @else {{-- Jika isMe dan isRead false (atau tidak ada) --}}
                                <i class="fas fa-check" style="font-size: 0.7rem; color: rgba(255,255,255,0.75);"></i> {{-- Centang satu --}}
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="chat-input-area">
        <form id="sendMessageFormCounselor" action="{{ route('counselor.chat.send', ['partnerUserId' => $partnerId, 'bookingId' => $bookingId]) }}" method="POST">
            @csrf
            <div class="input-group">
                <textarea class="form-control" id="message_content_counselor" name="message_content" rows="1" placeholder="Ketik pesan Anda di sini..." style="resize: none;" required aria-label="Ketik pesan"></textarea>
                <button class="btn btn-primary" type="submit" title="Kirim Pesan">
                    <i class="fas fa-paper-plane"></i> <span class="d-none d-sm-inline ms-1">Kirim</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menggunakan json_encode dengan raw echo
    const currentCounselorName = {!! json_encode($currentCounselorName ?? "N/A") !!};
    const partnerName = {!! json_encode($partnerName ?? "Pengguna") !!};
    console.log('Chat Detail Page Loaded. Counselor:', currentCounselorName, ', Partner:', partnerName);

    const messagesArea = document.getElementById('chatMessagesAreaCounselor');
    const sendMessageForm = document.getElementById('sendMessageFormCounselor');
    const messageInput = document.getElementById('message_content_counselor');
    const noMessagesInfo = document.getElementById('noMessagesInfoCounselor');

    function scrollToBottom() {
        if(messagesArea){
            messagesArea.scrollTop = 0; // Untuk flex-direction: column-reverse
            // console.log('Scrolled to visual bottom (DOM top for column-reverse). scrollTop:', messagesArea.scrollTop);
        }
    }
    setTimeout(scrollToBottom, 100);

    function appendMessage(messageData, isMe) {
        if (!messagesArea) {
            console.error('ERROR in appendMessage: messagesArea (ID: chatMessagesAreaCounselor) element not found.');
            return;
        }
        console.log('Appending message. isMe:', isMe, 'Received messageData:', messageData);

        if (noMessagesInfo) {
            noMessagesInfo.style.display = 'none';
        }

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('message-bubble');
        messageBubble.classList.add(isMe ? 'is-me' : 'is-them');
        console.log('Applied classes to bubble:', messageBubble.className);

        const contentDiv = document.createElement('div');
        const tempDiv = document.createElement('div');
        tempDiv.textContent = messageData.content || '';
        contentDiv.innerHTML = tempDiv.innerHTML.replace(/\n/g, '<br>');

        const timeSmall = document.createElement('small');
        timeSmall.classList.add('message-time');
        
        let formattedTime = 'Baru saja';
        if (messageData.formattedTime) {
            formattedTime = messageData.formattedTime;
        } else if (messageData.timestamp && typeof messageData.timestamp === 'object' && messageData.timestamp.seconds) {
            const date = new Date(messageData.timestamp.seconds * 1000);
            formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        }
        timeSmall.textContent = formattedTime;

        messageBubble.appendChild(contentDiv);
        messageBubble.appendChild(timeSmall);
        
        messagesArea.insertBefore(messageBubble, messagesArea.firstChild);
        scrollToBottom();
    }

    if (sendMessageForm && messageInput) {
        sendMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageContent = messageInput.value.trim();
            if (messageContent === '') return;

            const formData = new FormData(this);
            const actionUrl = this.action;
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonHtml = submitButton.innerHTML;

            console.log('[SENDING] Attempting to send message:', messageContent, 'to URL:', actionUrl);
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                console.log('[RESPONSE] Received response from server. Status:', response.status);
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error('[RESPONSE] SERVER RESPONSE (RAW - NOT JSON or NOT OK):', text);
                        throw new Error('Server tidak mengirim respons JSON yang valid atau respons tidak OK. Status: '.concat(response.status, ". Output mentah ada di konsol."));
                    });
                }
            })
            .then(data => {
                console.log('[DATA] Data received from server (parsed JSON):', data);
                if (data.success && data.sentMessage) {
                    console.log('[DATA] Condition (data.success && data.sentMessage) is TRUE. Calling appendMessage.');
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    appendMessage(data.sentMessage, true); 
                } else {
                    console.warn('[DATA] Condition (data.success && data.sentMessage) is FALSE.');
                    console.warn('[DATA] Details - data.success:', data.success, '| data.sentMessage (exists?):', data.hasOwnProperty('sentMessage'), '| data.sentMessage (value):', data.sentMessage);
                    alert(data.message || 'Gagal memproses pesan yang terkirim di UI.');
                    console.error('[DATA] Server logic error or missing/invalid sentMessage data:', data);
                }
            })
            .catch(error => {
                console.error('[FETCH ERROR] Error during fetch or processing response:', error);
                alert(error.message || 'Terjadi kesalahan saat mengirim pesan. Periksa konsol browser.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHtml;
            });
        });

        messageInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessageForm.dispatchEvent(new Event('submit', {'cancelable': true}));
            }
        });
    } else {
        console.warn('sendMessageFormCounselor or message_content_counselor element not found. Sending messages will not work.');
    }

    // Placeholder untuk real-time (dikomentari)
    /*
    const currentPartnerId = {!! json_encode($partnerId ?? null) !!};
    const currentBookingId = {!! json_encode($bookingId ?? null) !!};
    const currentCounselorUid = {!! json_encode($currentCounselorUid ?? null) !!};
    let lastMessageTimestamp = 0;
    // ...
    */
    console.log('Chat Counselor Detail Page JavaScript fully initialized.');
});
</script>
@endpush