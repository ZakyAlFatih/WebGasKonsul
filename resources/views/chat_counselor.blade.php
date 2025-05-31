@extends('layouts.app_counselor') {{-- Menggunakan layout utama konselor Anda --}}

@section('title', 'Daftar Chat - Konselor GasKonsul')

@push('styles')
<style>
    .chat-list-item {
        border-bottom: 1px solid #e9ecef; /* Garis pemisah antar item */
        transition: background-color 0.2s ease-in-out;
    }
    .chat-list-item:last-child {
        border-bottom: none;
    }
    .chat-list-item:hover, .chat-list-item.active { /* Tambahkan class 'active' jika ingin menandai chat yang sedang dibuka (perlu JS/logika tambahan) */
        background-color: #f8f9fa; /* Warna hover lembut */
    }
    .chat-avatar {
        width: 50px;
        height: 50px;
        object-fit: cover; /* Agar gambar avatar tidak gepeng */
        border: 1px solid #ddd; /* Border tipis di avatar */
    }
    .unread-indicator { /* Contoh untuk indikator pesan belum dibaca */
        width: 10px;
        height: 10px;
        background-color: #0d6efd; /* Biru primer Bootstrap */
        border-radius: 50%;
    }
    .message-preview {
        color: #6c757d; /* Warna abu-abu Bootstrap untuk teks sekunder */
        font-size: 0.9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 300px; /* Sesuaikan agar tidak terlalu panjang */
    }
    .timestamp {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<div class="container mt-4 mb-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Daftar Percakapan Anda</h4>
        </div>

        @if(isset($errorMessage) && $errorMessage)
            <div class="alert alert-danger m-3 text-center">{{ $errorMessage }}</div>
        @elseif(empty($chatPartners))
            <div class="card-body">
                <p class="text-center text-muted py-5">Anda belum memiliki percakapan aktif.</p>
                <p class="text-center text-muted">Sesi chat akan muncul di sini setelah ada pengguna yang melakukan booking dengan Anda dan sesi dikonfirmasi.</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($chatPartners as $partner)
                    <a href="{{ route('counselor.chat.show', ['partnerUserId' => $partner['partnerId'], 'bookingId' => $partner['bookingId']]) }}"
                       class="list-group-item list-group-item-action chat-list-item p-3">
                        <div class="d-flex w-100 align-items-center">
                            <img src="{{ $partner['partnerAvatar'] ?? asset('images/default_profile.png') }}"
                                 alt="Avatar {{ $partner['partnerName'] }}"
                                 class="rounded-circle chat-avatar me-3">

                            <div class="flex-grow-1 overflow-hidden"> {{-- Tambahkan overflow-hidden untuk text-truncate --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1 fw-bold text-primary">{{ $partner['partnerName'] ?? 'Nama Pengguna' }}</h6>
                                    @if($partner['lastMessageTime'])
                                        <small class="text-muted timestamp">{{ $partner['lastMessageTime'] }}</small>
                                    @endif
                                </div>
                                <p class="mb-0 message-preview">
                                    {{ $partner['lastMessage'] ?? 'Belum ada pesan.' }}
                                </p>
                            </div>

                            @if(!($partner['isRead'] ?? true)) {{-- Jika isRead false, tampilkan indikator --}}
                                <div class="ms-2 d-flex align-items-center" title="Pesan baru">
                                    <div class="unread-indicator"></div>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Halaman daftar chat konselor berhasil dimuat.');
        // Anda bisa menambahkan JavaScript di sini jika diperlukan interaksi pada daftar ini,
        // misalnya, menandai chat yang aktif jika halaman ini juga menampilkan detail chat di sisi lain.
    });
</script>
@endpush