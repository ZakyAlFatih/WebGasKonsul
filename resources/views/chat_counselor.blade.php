@extends('layouts.app')

@section('content')
<style>
    .chat-header {
        background-color: #cce4ff;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-header a {
        text-decoration: none;
        color: #007bff;
        margin: 0 1rem;
        font-weight: 500;
    }

    .chat-profile {
        background-color: white;
        padding: 0.4rem 1rem;
        border-radius: 30px;
        display: flex;
        align-items: center;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .chat-profile img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }

    .chat-empty-box {
        background-color: white;
        margin: 2rem auto;
        padding: 4rem 2rem;
        border-radius: 20px;
        max-width: 800px;
        text-align: center;
    }

    .chat-empty-box img {
        width: 80px;
        margin-bottom: 1rem;
    }
</style>

<!-- Header -->
<div class="chat-header">
    <div class="d-flex align-items-center">
        <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
        <h4 class="fw-bold m-0 text-primary">GasKonsul</h4>
        <input type="text" class="form-control ms-4" placeholder="Cari" style="width: 200px;">
    </div>
    <div class="d-flex align-items-center">
        <a href="#">Profil</a>
        <a href="#">Riwayat</a>
        <a href="#">Chat</a>
        <div class="chat-profile ms-3">
            <img src="{{ asset('images/Adi_S.png') }}" alt="User">
            <span class="fw-semibold text-primary">John Doe</span>
        </div>
    </div>
</div>

<!-- Chat Empty Section -->
<div class="chat-empty-box shadow-sm">
    <div class="text-start mb-3">
        <a href="#" class="text-dark"><i class="bi bi-arrow-left"></i> Chat</a>
    </div>
    <img src="{{ asset('images/Broken_Robot.png') }}" alt="Bot Icon"> <!-- ganti sesuai nama file -->
    <h5 class="fw-bold mt-3">MAAF</h5>
    <p class="text-muted">Belum ada user yang membooking Anda</p>
</div>
@endsection
