@extends('layouts.app')

@section('title', 'Chat Konselor - GasKonsul')

@section('content')
<div class="container-fluid bg-light py-4" style="min-height: 100vh;">
    {{-- Navbar di Bagian Atas --}}
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm mb-4">
        <div class="d-flex align-items-center">
            @if(isset($selectedReceiverId) && $selectedReceiverId)
                <button id="backToListBtn" class="btn btn-link text-primary me-2"><i class="bi bi-arrow-left"></i> Kembali</button>
            @else
                <a href="{{ route('home') }}" class="btn btn-link text-primary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            @endif
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0" id="chatTitle">
                @if(isset($selectedReceiverName) && $selectedReceiverName)
                    {{ $selectedReceiverName }}
                @else
                    Daftar Chat
                @endif
            </h4>
        </div>
        <div class="d-flex align-items-center">
            {{-- Navigasi Lain --}}
            <a href="{{ route('home') }}" class="me-3 text-decoration-none text-dark">Beranda</a>
            <a href="{{ route('profile') }}" class="me-3 text-decoration-none text-dark">Profil</a>
            <a href="{{ route('history') }}" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="{{ route('chat') }}" class="me-3 text-decoration-none text-dark">Chat</a>
            {{-- Info User --}}
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                <img src="{{ $senderAvatar }}" alt="User" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                <span class="ms-2">{{ $senderName }}</span>
            </div>
        </div>
    </div>

    {{-- Notifikasi Error/Sukses --}}
    <div class="container mt-3">
        @if(isset($errorMessage) && $errorMessage)
            <div class="alert alert-danger text-center">
                {{ $errorMessage }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger text-center">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success text-center">
                {{ session('success') }}
            </div>
        @endif
        <div id="chatAjaxAlert" class="alert mt-3 text-center" style="display: none;"></div>
    </div>

    {{-- Konten Utama Chat --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 700px; min-height: 70vh; display: flex; flex-direction: column;">

        {{-- Daftar Konselor yang Dibooking (Tampilan Awal) --}}
        <div id="chatListSection" style="display: {{ (isset($selectedReceiverId) && $selectedReceiverId) ? 'none' : 'block' }}; flex-grow: 1;">
            @if(empty($chatList))
                <div class="text-center py-5">
                    <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="150" height="150" style="object-fit: contain;">
                    <h2 class="mt-4 text-primary fw-bold">BELUM ADA CHAT</h2>
                    <p class="text-muted fs-5">Anda belum memiliki sesi chat aktif.</p>
                    <p class="text-muted fs-5">Silakan booking konselor terlebih dahulu.</p>
                </div>
            @else
                <h5 class="text-primary fw-bold mb-4">Sesi Chat Aktif</h5>
                <div class="list-group">
                    @foreach($chatList as $chat)
                        <a href="{{ route('chat.show', ['receiverId' => $chat['chatPartnerId'], 'bookingId' => $chat['bookingId'], 'scheduleId' => $chat['scheduleId']]) }}"
                           class="list-group-item list-group-item-action py-3 mb-2 rounded-3 shadow-sm chat-item">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 50%;">
                                    <i class="bi bi-person-fill" style="font-size: 1.8rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ $chat['chatPartnerName'] }}</h6>
                                    <small class="text-muted">Sesi Aktif</small>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- UI Chat Spesifik (Tersembunyi Awalnya) --}}
        <div id="chatUISession" style="display: {{ (isset($selectedReceiverId) && $selectedReceiverId) ? 'flex' : 'none' }}; flex-direction: column; flex-grow: 1;">
            @if(isset($selectedReceiverId) && $selectedReceiverId)
                <div class="chat-messages-container flex-grow-1 overflow-auto p-2" style="background-color: #f8f9fa;">
                    @forelse($messages as $message)
                        <div class="d-flex mb-2 {{ $message['senderId'] == $senderId ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="card p-2 rounded-3 shadow-sm" style="max-width: 70%; background-color: {{ $message['senderId'] == $senderId ? '#d1e7dd' : '#f0f2f5' }};">
                                <small class="text-muted text-end">{{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}</small>
                                <p class="mb-0">{{ $message['content'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">Belum ada pesan dalam sesi ini. Mulai chat Anda!</p>
                    @endforelse
                </div>

                <div class="chat-input-area d-flex p-3 border-top">
                    <input type="text" id="messageInput" class="form-control me-2" placeholder="Ketik pesan...">
                    <button id="sendMessageBtn" class="btn btn-primary"><i class="bi bi-send-fill"></i></button>
                    <button id="completeSessionBtn" class="btn btn-success ms-2"><i class="bi bi-check-circle-fill"></i> Selesaikan Sesi</button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatListSection = document.getElementById('chatListSection');
        const chatUISession = document.getElementById('chatUISession');
        const backToListBtn = document.getElementById('backToListBtn');
        const chatTitle = document.getElementById('chatTitle');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const completeSessionBtn = document.getElementById('completeSessionBtn');
        const chatAjaxAlert = document.getElementById('chatAjaxAlert');
        const chatMessagesContainer = document.querySelector('.chat-messages-container');

        const senderId = "{{ $senderId }}";
        const senderName = "{{ $senderName }}";
        const senderAvatar = "{{ $senderAvatar }}";

        let selectedReceiverId = "{{ $selectedReceiverId }}";
        let selectedReceiverName = "{{ $selectedReceiverName }}";
        let selectedBookingId = "{{ $selectedBookingId }}";
        let selectedScheduleId = "{{ $selectedScheduleId }}";

        // Fungsi Helper untuk Notifikasi
        function showAlert(message, type) {
            chatAjaxAlert.textContent = message;
            chatAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            chatAjaxAlert.style.display = 'block';
            setTimeout(() => {
                chatAjaxAlert.style.display = 'none';
            }, 5000);
        }

        // --- Event Listeners ---

        // Tombol kembali ke daftar chat
        if (backToListBtn) {
            backToListBtn.addEventListener('click', function() {
                window.location.href = "{{ route('chat') }}"; // Kembali ke route daftar chat
            });
        }

        // --- FUNGSI MENGGAMBAR (RENDER) PESAN KE DOM ---
        function renderMessages(messages) {
            if (!chatMessagesContainer) return; // Keluar jika container tidak ditemukan

            chatMessagesContainer.innerHTML = ''; // Hapus pesan yang sudah ada

            if (messages.length === 0) {
                chatMessagesContainer.innerHTML = '<p class="text-center text-muted">Belum ada pesan dalam sesi ini. Mulai chat Anda!</p>';
                return;
            }

            messages.forEach(message => {
                const isMe = message.senderId === senderId;
                const messageHtml = `
                    <div class="d-flex mb-2 ${isMe ? 'justify-content-end' : 'justify-content-start'}">
                        <div class="card p-2 rounded-3 shadow-sm" style="max-width: 70%; background-color: ${isMe ? '#d1e7dd' : '#f0f2f5'};">
                            <small class="text-muted text-end">${message.timestamp_formatted}</small>
                            <p class="mb-0">${message.content}</p>
                        </div>
                    </div>
                `;
                chatMessagesContainer.insertAdjacentHTML('beforeend', messageHtml);
            });
            scrollToBottom(); // Scroll ke bawah setelah me-render pesan
        }

        // --- FUNGSI MENGAMBIL DAN MEMPERBARUI PESAN DENGAN AJAX ---
        async function fetchAndUpdateMessages() {
            if (!selectedReceiverId) return; // Hanya ambil jika sesi chat aktif

            try {
                const response = await fetch('{{ route('chat.getMessages') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        receiverId: selectedReceiverId // Kirim receiverId untuk mengambil pesan chat ini
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    renderMessages(data.messages); // Panggil fungsi render dengan pesan yang baru diambil
                } else {
                    showAlert(data.message || 'Gagal memuat pesan baru.', 'danger');
                }
            } catch (error) {
                console.error('Error fetching new messages:', error);
                showAlert('Terjadi kesalahan jaringan saat memuat pesan baru.', 'danger');
            }
        }

        // --- Event Listener Pengiriman Pesan ---
        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', async function() {
                const content = messageInput.value.trim();
                if (content === '') {
                    showAlert('Pesan tidak boleh kosong.', 'warning');
                    return;
                }

                if (!selectedReceiverId) {
                    showAlert('Pilih konselor terlebih dahulu untuk mengirim pesan.', 'danger');
                    return;
                }

                sendMessageBtn.disabled = true; // Nonaktifkan tombol saat mengirim

                try {
                    const response = await fetch('{{ route('chat.sendMessage') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            receiverId: selectedReceiverId,
                            content: content,
                            senderName: senderName,
                            senderAvatar: senderAvatar,
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        messageInput.value = ''; // Kosongkan input
                        // PANGGIL FUNGSI UNTUK MEMPERBARUI PESAN SETELAH KIRIM
                        await fetchAndUpdateMessages();
                    } else {
                        showAlert(data.message || 'Gagal mengirim pesan.', 'danger');
                    }
                } catch (error) {
                    showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                    console.error('Error sending message:', error);
                } finally {
                    sendMessageBtn.disabled = false; // Aktifkan kembali tombol
                }
            });
        }

        // Selesaikan Sesi
        if (completeSessionBtn) {
            completeSessionBtn.addEventListener('click', async function() {
                if (!selectedBookingId || !selectedScheduleId || !selectedReceiverId) {
                    showAlert('Data sesi tidak lengkap untuk diselesaikan.', 'danger');
                    return;
                }

                if (!confirm('Apakah Anda yakin ingin menyelesaikan sesi chat ini?')) {
                    return;
                }

                completeSessionBtn.disabled = true; // Disable tombol saat memproses
                showAlert('Menyelesaikan sesi...', 'info');

                try {
                    const response = await fetch('{{ route('chat.completeSession') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            bookingId: selectedBookingId,
                            scheduleId: selectedScheduleId,
                            receiverId: selectedReceiverId,
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = "{{ route('chat') }}"; // Kembali ke daftar chat
                        }, 2000); // Tunggu sebentar sebelum redirect
                    } else {
                        showAlert(data.message || 'Gagal menyelesaikan sesi.', 'danger');
                    }
                } catch (error) {
                    showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                    console.error('Error completing session:', error);
                } finally {
                    completeSessionBtn.disabled = false; // Aktifkan kembali tombol
                }
            });
        }

        // Arahkan scroll ke bawah di chat messages container
        function scrollToBottom() {
            const chatMessagesContainer = document.querySelector('.chat-messages-container');
            if (chatMessagesContainer) {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
        }
        // Panggil saat DOMContentLoaded dan mungkin setelah pesan baru ditambahkan
        if (selectedReceiverId) {
            scrollToBottom(); // Pastikan scroll ke bawah untuk pesan terbaru
        }
    });
</script>
@endsection