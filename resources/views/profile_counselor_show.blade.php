@extends('layouts.app_counselor')

@section('title', 'Profil Saya - GasKonsul')

@push('styles')
{{-- Font Awesome sudah ada di layout utama, jadi tidak perlu di-push lagi jika sudah --}}
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" /> --}}
<style>
    .profile-page-card {
        background-color: #ffffff; /* Kartu utama berwarna putih */
        border-radius: 12px;     /* Sudut membulat untuk kartu */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.10) !important; /* Bayangan lebih lembut */
        padding: 2rem;           /* Padding dalam kartu */
    }
    .profile-avatar-display {
        width: 200px; /* Ukuran avatar besar */
        height: 200px;
        object-fit: cover;
        border-radius: 50%;      /* Membuatnya benar-benar bulat */
        border: 4px solid #dee2e6; /* Border abu-abu muda di sekitar avatar */
        margin-bottom: 1.5rem;
    }
    .profile-details dt {
        font-weight: 600;       /* Label tebal */
        color: #0d6efd;         /* Warna biru primer untuk label */
        margin-bottom: 0.25rem;
    }
    .profile-details dd {
        margin-bottom: 1rem;    /* Jarak antar item detail */
        color: #495057;         /* Warna teks abu-abu gelap untuk nilai */
        background-color: #f8f9fa; /* Latar belakang lembut untuk nilai agar terlihat seperti field */
        padding: 0.5rem 0.75rem;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
    }
    .profile-details .rating-stars i {
        font-size: 1.25rem; /* Ukuran bintang */
        margin-right: 0.1rem;
    }
    .schedule-title {
        font-weight: 600;
        color: #0d6efd;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .schedule-item-box {
        background-color: #e7f5ff; /* Latar biru muda untuk jadwal */
        border: 1px solid #cfe2ff;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        text-align: center;
        margin-bottom: 0.75rem;
    }
    .schedule-item-box h6 {
        color: #0d6efd;
        margin-bottom: 0.25rem;
    }
    .profile-actions {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
</style>
@endpush

@section('content')
<div class="container my-lg-5 my-md-4 my-3"> {{-- Margin atas-bawah untuk container --}}

    {{-- Notifikasi --}}
    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    {{-- Tambahkan notifikasi error lain jika perlu --}}


    @php $data = $counselorData ?? []; @endphp

    @if($errorMessage && empty($data))
        <div class="alert alert-warning text-center profile-page-card">
            <p>{{ $errorMessage }}</p>
            <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary mt-2">Lengkapi Profil Sekarang</a>
        </div>
    @elseif(!empty($data))
        <div class="profile-page-card">
            <h3 class="text-center text-primary mb-4">Profil Pengguna</h3>
            <div class="row align-items-center">
                {{-- Kolom Kiri: Detail Info --}}
                <div class="col-lg-7 col-md-6 order-md-1">
                    <dl class="profile-details">
                        <dt>Rating</dt>
                            <dd class="rating-stars">
                                @php
                                    // Ambil nilai rating, bulatkan, dan pastikan antara 0 dan 5
                                    $ratingValue = isset($data['rate']) ? floatval($data['rate']) : 0;
                                    $roundedRating = round($ratingValue);
                                    // Pastikan $roundedRating selalu dalam rentang 0-5 untuk loop bintang
                                    $roundedRating = max(0, min(5, $roundedRating));

                                    // Siapkan teks untuk angka rating, tampilkan "0.0" jika tidak ada rating
                                    $actualRateDisplay = $ratingValue > 0 ? number_format($ratingValue, 1) : '0.0';
                                @endphp

                                {{-- Selalu tampilkan 5 bintang --}}
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $roundedRating)
                                        <i class="fas fa-star text-warning"></i> {{-- Bintang terisi (warna kuning) --}}
                                    @else
                                        <i class="far fa-star text-muted"></i> {{-- Bintang kosong (outline, warna abu-abu) --}}
                                    @endif
                                @endfor

                                {{-- Tampilkan angka rating di samping bintang --}}
                                <span class="ms-1 small text-muted">
                                    @if ($ratingValue > 0)
                                        ({{ $actualRateDisplay }})
                                    @else
                                        (Belum ada rating) {{-- Teks ini muncul jika ratingValue adalah 0 --}}
                                    @endif
                                </span>
                            </dd>

                        <dt>Nama</dt>
                        <dd>{{ $data['name'] ?? $userName ?? 'Belum diatur' }}</dd>

                        <dt>Email</dt>
                        <dd>{{ $data['email'] ?? '-' }}</dd>

                        <dt>Telepon</dt>
                        <dd>{{ $data['phone'] ?? '-' }}</dd>
                        
                        @if(!empty($data['bidang']))
                        <dt>Spesialisasi</dt>
                        <dd>{{ $data['bidang'] }}</dd>
                        @endif

                        @if(!empty($data['about']))
                        <dt>Tentang Saya</dt>
                        <dd style="white-space: pre-wrap; background-color: transparent; border: none; padding-left:0;">{{ $data['about'] }}</dd> {{-- Untuk 'Tentang Saya' mungkin tidak perlu background field --}}
                        @endif
                    </dl>
                </div>

                {{-- Kolom Kanan: Avatar --}}
                <div class="col-lg-5 col-md-6 order-md-2 text-center text-md-end">
                    <img src="{{ $data['avatar'] ?? asset('images/default_profile.png') }}"
                         alt="Avatar {{ $data['name'] ?? $userName }}"
                         class="profile-avatar-display img-fluid">
                </div>
            </div>

            {{-- Jadwal Ketersediaan --}}
            <div class="mt-4">
                <h5 class="schedule-title">Jadwal Ketersediaan</h5>
                @php
                    $hasSchedule = false;
                    for ($idx = 1; $idx <= 3; $idx++) {
                        if (!empty($data['availability_day'.$idx]) && !empty($data['availability_time'.$idx])) {
                            $hasSchedule = true;
                            break;
                        }
                    }
                @endphp

                @if($hasSchedule)
                    <div class="row">
                        @for ($idx = 1; $idx <= 3; $idx++)
                            @if (!empty($data['availability_day'.$idx]) && !empty($data['availability_time'.$idx]))
                                <div class="col-sm-6 col-md-4">
                                    <div class="schedule-item-box">
                                        <h6 class="fw-bold">{{ $data['availability_day'.$idx] }}</h6>
                                        <p class="small mb-0">{{ $data['availability_time'.$idx] }}</p>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    </div>
                @else
                    <p class="text-muted">Belum ada jadwal ketersediaan yang diatur. Silakan klik "Edit Profil" untuk menambahkan.</p>
                @endif
            </div>

            {{-- Tombol Aksi --}}
            <div class="profile-actions text-center">
            <div class="d-inline-flex flex-wrap justify-content-center"> {{-- Wrapper untuk tombol --}}
                <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary btn-lg rounded-pill px-4 me-2 mb-2 mb-lg-0">
                    <i class="fas fa-pencil-alt me-2"></i>Edit Profil
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;" id="profile-show-logout-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-lg rounded-pill px-4 mb-2 mb-lg-0">
                        <i class="fas fa-sign-out-alt me-2"></i>Log Out
                    </button>
                </form>
            </div>
        </div>
        </div>
    @else
        <div class="alert alert-info text-center profile-page-card">
            <p>Data profil tidak dapat dimuat atau belum lengkap.</p>
            <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary mt-2">Lengkapi Profil Sekarang</a>
        </div>
    @endif
</div>
@endsection