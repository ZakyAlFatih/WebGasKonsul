@extends('layouts.app')

@section('title', 'Chat Konselor - GasKonsul')

@section('content')
    {{-- Notifikasi Error/Sukses --}}
    <div class="container mt-3 mx-auto" style="max-width: 700px;">
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
    <div class="container bg-white rounded-4 p-4 shadow-sm mx-auto" style="max-width: 700px; min-height: 70vh; display: flex; flex-direction: column;">

        {{-- Daftar Konselor yang Dibooking --}}
        <div id="chatListSection" style="display: {{ (isset($selectedReceiverId) && $selectedReceiverId) ? 'none' : 'block' }}; flex-grow: 1;">
            <h5 class="text-primary fw-bold mb-4">Sesi Chat Aktif</h5>
            @if(empty($chatList))
                <div class="text-center py-5">
                    <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="150" height="150" style="object-fit: contain;">
                    <h2 class="mt-4 text-primary fw-bold">BELUM ADA CHAT</h2>
                    <p class="text-muted fs-5">Anda belum memiliki sesi chat aktif.</p>
                    <p class="text-muted fs-5">Silakan booking konselor terlebih dahulu.</p>
                </div>
            @else
                <div class="list-group">
                    @foreach($chatList as $chat)
                        <a href="{{ route('chat.show', ['receiverId' => $chat['chatPartnerId'], 'bookingId' => $chat['bookingId'], 'scheduleId' => $chat['scheduleId']]) }}"
                           class="list-group-item list-group-item-action py-3 mb-2 rounded-3 shadow-sm chat-item">
                            <div class="d-flex align-items-center">
                                <img src="{{ $chat['chatPartnerAvatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
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

        {{-- UI Chat Spesifik --}}
        <div id="chatUISession" style="display: {{ (isset($selectedReceiverId) && $selectedReceiverId) ? 'flex' : 'none' }}; flex-direction: column; flex-grow: 1;">
            @if(isset($selectedReceiverId) && $selectedReceiverId)
                {{-- HEADER CHAT DENGAN TOMBOL BACK --}}
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <button id="backToChatListBtn" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </button>
                    <img src="{{ $selectedReceiverAvatar ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                    <h5 class="mb-0 fw-bold">{{ $selectedReceiverName ?? 'Partner Chat' }}</h5>
                </div>

                <div class="chat-messages-container flex-grow-1 overflow-auto p-2" style="background-color: #f8f9fa; max-height: calc(70vh - 120px - 60px);"> {{-- Adjusted max-height --}}
                    @forelse($messages as $message)
                        <div class="d-flex mb-2 {{ $message['senderId'] == $senderId ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="card p-2 rounded-3 shadow-sm" style="max-width: 70%; background-color: {{ $message['senderId'] == $senderId ? '#d1e7dd' : '#f0f2f5' }};">
                                <small class="text-muted text-end">{{ $message['timestamp_formatted'] }}</small>
                                <p class="mb-0">{{ $message['content'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">Belum ada pesan dalam sesi ini. Mulai chat Anda!</p>
                    @endforelse
                </div>

                <div class="chat-input-area d-flex p-3 border-top">
                    <input type="text" id="messageInput" class="form-control me-2" placeholder="Ketik pesan..."
                        @if($bookingStatus !== 'booked') disabled @endif>
                    <button id="sendMessageBtn" class="btn btn-primary"
                        @if($bookingStatus !== 'booked') disabled @endif><i class="bi bi-send-fill"></i></button>
                    <button id="completeSessionBtn" class="btn btn-success ms-2"
                        @if($bookingStatus !== 'booked') disabled @endif><i class="bi bi-check-circle-fill"></i> Selesaikan Sesi</button>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatListSection = document.getElementById('chatListSection');
        const chatUISession = document.getElementById('chatUISession');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const completeSessionBtn = document.getElementById('completeSessionBtn');
        const chatAjaxAlert = document.getElementById('chatAjaxAlert');
        const chatMessagesContainer = document.querySelector('.chat-messages-container');
        const backToChatListBtn = document.getElementById('backToChatListBtn');

        const senderId = "{{ $senderId }}";
        const senderName = "{{ $senderName }}";
        const senderAvatar = "{{ Session::get('userAvatar') ?? asset('images/default_profile.png') }}";

        let selectedReceiverId = "{{ $selectedReceiverId }}";
        let selectedReceiverName = "{{ $selectedReceiverName }}";
        let selectedReceiverAvatar = "{{ $selectedReceiverAvatar ?? asset('images/default_profile.png') }}";
        let selectedBookingId = "{{ $selectedBookingId }}";
        let selectedScheduleId = "{{ $selectedScheduleId }}";
        let bookingStatus = "{{ $bookingStatus ?? 'unknown' }}";

        function showAlert(message, type) {
            chatAjaxAlert.textContent = message;
            chatAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            chatAjaxAlert.style.display = 'block';
            setTimeout(() => {
                chatAjaxAlert.style.display = 'none';
            }, 5000);
        }

        function renderMessages(messages) {
            if (!chatMessagesContainer) return;

            chatMessagesContainer.innerHTML = '';

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
            scrollToBottom();
        }

        async function fetchAndUpdateMessages() {
            if (!selectedReceiverId || !selectedBookingId || selectedReceiverId === '' || selectedBookingId === '') {
                console.warn('Cannot fetch messages: Missing receiverId or bookingId.');
                return;
            }
            // Only fetch if booking status is still 'booked'
            if (bookingStatus !== 'booked') {
                console.log('Booking is not active, not fetching new messages.');
                return;
            }

            try {
                const response = await fetch('{{ route('chat.getMessages') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        receiverId: selectedReceiverId,
                        bookingId: selectedBookingId
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    renderMessages(data.messages);
                } else {
                    messageInput.disabled = true;
                    sendMessageBtn.disabled = true;
                    completeSessionBtn.disabled = true;
                    showAlert(data.message || 'Gagal memuat pesan baru. Sesi mungkin sudah berakhir.', 'danger');
                }
            } catch (error) {
                console.error('Error fetching new messages:', error);
                showAlert('Terjadi kesalahan jaringan saat memuat pesan baru.', 'danger');
                messageInput.disabled = true;
                sendMessageBtn.disabled = true;
                completeSessionBtn.disabled = true;
            }
        }

        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', async function() {
                const content = messageInput.value.trim();
                if (content === '') {
                    showAlert('Pesan tidak boleh kosong.', 'warning');
                    return;
                }

                if (bookingStatus !== 'booked') {
                    showAlert('Sesi chat ini tidak aktif atau sudah selesai, tidak bisa mengirim pesan.', 'danger');
                    return;
                }

                if (!selectedReceiverId || selectedReceiverId === '' || !selectedBookingId || selectedBookingId === '') {
                    showAlert('Pilih konselor atau sesi chat terlebih dahulu untuk mengirim pesan.', 'danger');
                    return;
                }

                sendMessageBtn.disabled = true;

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
                            bookingId: selectedBookingId,
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        messageInput.value = '';
                        await fetchAndUpdateMessages();
                    } else {
                        showAlert(data.message || 'Gagal mengirim pesan.', 'danger');
                    }
                } catch (error) {
                    showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                    console.error('Error sending message:', error);
                } finally {
                    sendMessageBtn.disabled = false;
                }
            });
        }

        if (completeSessionBtn) {
            completeSessionBtn.addEventListener('click', async function() {
                if (bookingStatus !== 'booked') {
                    showAlert('Sesi ini sudah tidak aktif atau sudah selesai.', 'danger');
                    return;
                }

                if (!selectedBookingId || selectedBookingId === '' || !selectedScheduleId || selectedScheduleId === '' || !selectedReceiverId || selectedReceiverId === '') {
                    showAlert('Data sesi tidak lengkap untuk diselesaikan.', 'danger');
                    return;
                }

                if (!confirm('Apakah Anda yakin ingin menyelesaikan sesi chat ini? Sesi akan ditandai sebagai selesai dan Anda bisa memberikan rating di riwayat.')) {
                    return;
                }

                completeSessionBtn.disabled = true;
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
                        // After successfully completing the session, redirect to history or chat list
                        setTimeout(() => {
                            window.location.href = "{{ route('history') }}";
                        }, 2000);
                    } else {
                        showAlert(data.message || 'Gagal menyelesaikan sesi.', 'danger');
                    }
                } catch (error) {
                    showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                    console.error('Error completing session:', error);
                } finally {
                    completeSessionBtn.disabled = false;
                }
            });
        }

        function scrollToBottom() {
            const chatMessagesContainer = document.querySelector('.chat-messages-container');
            if (chatMessagesContainer) {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
        }

        // --- Handle Back Button Click ---
        if (backToChatListBtn) {
            backToChatListBtn.addEventListener('click', function() {
                window.location.href = "{{ route('chat') }}";
            });
        }

        // Initial load logic
        if (selectedReceiverId && selectedReceiverId !== '' && selectedBookingId && selectedBookingId !== '') {
            // If viewing a specific chat, fetch messages and scroll to bottom
            scrollToBottom();
            fetchAndUpdateMessages();

            // Disable input/button if booking is not 'booked'
            if (bookingStatus !== 'booked') {
                messageInput.disabled = true;
                sendMessageBtn.disabled = true;
                completeSessionBtn.disabled = true;
                showAlert('Sesi chat ini sudah selesai. Silakan lihat riwayat Anda untuk memberikan rating.', 'info');
            }

            // Set up interval for refreshing messages
            // Only if the booking is still active ('booked')
            if (bookingStatus === 'booked') {
                setInterval(fetchAndUpdateMessages, 5000); // Fetch new messages every 5 seconds
            }

        } else {
            // If no specific chat selected, show chat list
            chatListSection.style.display = 'block';
            chatUISession.style.display = 'none';
        }
    });
</script>
@endsection