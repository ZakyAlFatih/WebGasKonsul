@extends('layouts.app')

@section('title', 'Riwayat Konseling - GasKonsul')

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
    </div>

    {{-- Konten Utama Riwayat --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm mx-auto" style="max-width: 700px; min-height: 50vh;">
        @if(isset($bookingHistory))
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
                                        <img src="{{ $history['counselorAvatar'] ?? asset('images/default_profile.png') }}" alt="Avatar Konselor" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $history['counselorName'] }}</h6>
                                        <p class="mb-0 text-muted">{{ $history['counselorBidang'] }}</p>
                                        <p class="mb-0 text-dark">{{ $history['day'] }}, {{ $history['time'] }}</p>
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
@endsection

@section('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Halaman riwayat dimuat.");
    });
</script>
@endsection