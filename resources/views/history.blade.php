@extends('layouts.app')

@section('title', 'Riwayat Konseling - GasKonsul')

@section('content')
<div class="container-fluid bg-light py-4" style="min-height: 100vh;">
    {{-- Navbar di Bagian Atas (mirip dengan counselor_detail.blade.php) --}}
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('home') }}" class="btn btn-link text-primary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0">Riwayat Konseling</h4>
        </div>
        <div class="d-flex align-items-center">
            {{-- Navigasi Lain --}}
            <a href="{{ route('home') }}" class="me-3 text-decoration-none text-dark">Beranda</a>
            <a href="{{ route('profile') }}" class="me-3 text-decoration-none text-dark">Profil</a>
            <a href="{{ route('history') }}" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="{{ route('chat') }}" class="me-3 text-decoration-none text-dark">Chat</a>
            {{-- Info User --}}
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                <img src="{{ Session::get('userAvatar') ?? asset('images/default_profile.png') }}" alt="User" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                <span class="ms-2">{{ Session::get('userName') ?? 'User' }}</span>
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
    </div>

    {{-- Konten Utama Riwayat --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 700px; min-height: 50vh;">
        @if(isset($bookingHistory)) {{-- Pastikan variabel $bookingHistory ada --}}
            @if(empty($bookingHistory))
                <div class="text-center py-5">
                    <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="150" height="150" style="object-fit: contain;">
                    <h2 class="mt-4 text-primary fw-bold">MAAF</h2>
                    <p class="text-muted fs-5">Riwayat Anda masih kosong</p>
                    <p class="text-muted fs-5">Harap pesan konselor terlebih dahulu</p>
                </div>
            @else
                <h5 class="text-primary fw-bold mb-4">Daftar Riwayat Konseling Anda</h5>
                <div class="list-group">
                    @foreach($bookingHistory as $history)
                        <div class="card mb-3 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 50%;">
                                        <i class="bi bi-person-fill" style="font-size: 1.8rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $history['counselorName'] }}</h6>
                                        <p class="mb-0 text-muted">{{ $history['counselorBidang'] }}</p>
                                        <p class="mb-0 text-dark">{{ $history['day'] }}, {{ $history['time'] }}</p>
                                        {{-- Opsional: Tampilkan status jika diperlukan --}}
                                        {{-- <span class="badge bg-{{ $history['status'] == 'completed' ? 'success' : 'info' }}">{{ ucfirst($history['status']) }}</span> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <p class="text-center text-muted">Memuat riwayat...</p>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Halaman riwayat dimuat.");
    });
</script>
@endsection