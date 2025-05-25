@extends('layouts.app')

@section('content')
<div class="container-fluid bg-light" style="min-height: 100vh;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0">GasKonsul</h4>
        </div>
        <div class="d-flex align-items-center">
            <input type="text" class="form-control me-3" placeholder="Cari" style="width: 200px;">
            <a href="#" class="me-3 text-decoration-none text-dark">Profil</a>
            <a href="#" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="#" class="me-3 text-decoration-none text-dark">Chat</a>
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                <img src="{{ asset('images/Adi_S.png') }}" alt="User" class="rounded-circle" width="32" height="32">
                <span class="ms-2">John Doe</span>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="bg-primary text-white rounded-4 p-4 mx-5 my-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-2">Ada masalah apa?</h5>
            <p class="mb-3">Ceritakan dan kami carikan orang yang tepat</p>
            <input type="text" class="form-control rounded-3" placeholder="Ketik sesuatu">
        </div>
        <img src="{{ asset('images/ilust.png') }}" alt="Ilustrasi" class="ms-4" style="width: 150px;">
    </div>

    <!-- Kategori -->
    <div class="bg-white mx-5 p-4 rounded-4 border border-primary">
        <h5 class="mb-3 fw-bold">Kategori</h5>
        <div class="mb-4">
            @foreach (['Psikologi', 'SDM', 'Bisnis', 'Karir', 'IT', 'Kebugaran', 'Keluarga', 'Keuangan', 'Pendidikan', 'Hukum'] as $kategori)
                <button class="btn btn-outline-secondary rounded-pill px-3 py-1 m-1">{{ $kategori }}</button>
            @endforeach
        </div>

        <!-- List Konselor -->
        @foreach (['Psikologi', 'Karir'] as $section)
            <h5 class="fw-bold mt-4">{{ $section }}</h5>
            <div class="row">
                @for ($i = 0; $i < 5; $i++)
                    <div class="col-md-2 text-center mb-4">
                        <img src="{{ asset('images/Adi_S.png') }}" alt="Konselor" class="img-fluid rounded shadow-sm mb-2" style="height: 100px; width: 100px; object-fit: cover;">
                        <p class="mb-0 fw-semibold">{{ $i % 2 == 0 ? 'Adi S.Psi' : 'Hasna S.Psi' }}</p>
                        <small class="text-muted">{{ $i % 2 == 0 ? 'Senin-Rabu' : 'Kamis' }}</small><br>
                        <a href="#" class="text-primary small">Selengkapnya</a>
                    </div>
                @endfor
            </div>
        @endforeach
    </div>
</div>
@endsection
