@extends('layouts.app_counselor')

@section('title', 'Profil Konselor')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    .profile-header {
        background-color: #0d6efd; color: white;
        border-radius: 0 0 30px 30px; padding: 2rem 1rem; margin-bottom: 2rem;
    }
    .profile-avatar {
        width: 120px; height: 120px; object-fit: cover;
        border: 4px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .availability-box {
        background-color: #0d6efd; color: white; border-radius: 0.25rem;
        padding: 0.75rem; margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container mt-3 mb-5">

    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(Session::has('error')) {{-- Untuk error umum dari controller show/edit --}}
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
     @if(Session::has('error_password')) {{-- Untuk error spesifik password dari update --}}
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ Session::get('error_password') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php $data = $counselorData ?? []; @endphp

    @if($errorMessage && empty($data))
        <div class="alert alert-warning text-center">
            <p>{{ $errorMessage }}</p>
            {{-- Tombol edit bisa langsung ke rute edit jika data tidak ada --}}
            <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary">Lengkapi Profil Sekarang</a>
        </div>
    @elseif(!empty($data))
        <div class="text-center profile-header">
            <img src="{{ $data['avatar'] ?? asset('images/default_profile.png') }}"
                 alt="Avatar {{ $data['name'] ?? $userName }}"
                 class="img-thumbnail rounded-circle profile-avatar mb-2">
            <h2 class="h4" style="color: white;">{{ $data['name'] ?? $userName ?? 'Nama Belum Diatur' }}</h2>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 text-primary">Informasi Profil</h4>
                <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-pencil-alt me-1"></i> Edit Profil
                </a>
            </div>
            <div class="card-body p-4">
                <dl class="row mb-0">
                    <dt class="col-sm-4 col-md-3 text-primary">Email</dt>
                    <dd class="col-sm-8 col-md-9">{{ $data['email'] ?? '-' }}</dd>

                    <dt class="col-sm-4 col-md-3 text-primary">Telepon</dt>
                    <dd class="col-sm-8 col-md-9">{{ $data['phone'] ?? '-' }}</dd>

                    <dt class="col-sm-4 col-md-3 text-primary">Tentang Saya</dt>
                    <dd class="col-sm-8 col-md-9" style="white-space: pre-wrap;">{{ $data['about'] ?? '-' }}</dd>

                    <dt class="col-sm-4 col-md-3 text-primary">Rating</dt>
                    <dd class="col-sm-8 col-md-9">
                        @php
                            $rating = isset($data['rate']) ? round(floatval($data['rate'])) : 0;
                            $actualRate = isset($data['rate']) ? number_format(floatval($data['rate']), 1) : 'N/A';
                        @endphp
                        @if($rating > 0)
                            @for ($i = 0; $i < $rating; $i++)
                                <i class="fas fa-star text-warning"></i>
                            @endfor
                            @for ($i = $rating; $i < 5; $i++)
                                <i class="far fa-star text-muted"></i>
                            @endfor
                            <span class="ms-1">({{ $actualRate }})</span>
                        @else
                            <span class="text-muted">Belum ada rating.</span>
                        @endif
                    </dd>
                </dl>

                <h5 class="text-primary mt-4 mb-3">Jadwal Ketersediaan</h5>
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
                                <div class="col-md-4 mb-3">
                                    <div class="availability-box text-center h-100">
                                        <h6 class="card-title mb-1 fw-bold">{{ $data['availability_day'.$idx] }}</h6>
                                        <p class="card-text small mb-0">{{ $data['availability_time'.$idx] }}</p>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    </div>
                @else
                    <p class="text-muted">Belum ada jadwal ketersediaan yang diatur. Klik "Edit Profil" untuk menambahkan.</p>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-info text-center">
            <p>Silakan lengkapi profil Anda.</p>
            <a href="{{ route('counselor.profile.edit') }}" class="btn btn-primary">Lengkapi Profil Sekarang</a>
        </div>
    @endif
</div>
@endsection