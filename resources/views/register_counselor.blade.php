@extends('layouts.app')

@section('title', 'Daftar sebagai Counselor - GasKonsul')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8"> <h2 class="mb-4 text-center fw-bold">Daftar sebagai Counselor</h2>
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.counselor.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                        name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Buat Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" class="form-control"
                        name="password_confirmation" required>
                </div>

                <div class="mb-3">
                    <label for="bidang" class="form-label">Spesialisasi Konseling</label>
                    <input id="bidang" type="text" class="form-control @error('bidang') is-invalid @enderror"
                        name="bidang" value="{{ old('bidang') }}" placeholder="Contoh: Karier, Kesehatan Mental" required>
                    @error('bidang')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="license" class="form-label">Nomor Lisensi (Opsional)</label>
                    <input id="license" type="text" class="form-control @error('license') is-invalid @enderror"
                        name="license" value="{{ old('license') }}">
                    @error('license')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input @error('terms') is-invalid @enderror"
                        id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                    <label class="form-check-label" for="terms">
                        Saya telah membaca dan menyetujui <a href="#">Syarat dan Ketentuan</a> serta <a href="#">Kebijakan Privasi</a>.
                    </label>
                    @error('terms')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Daftar Counselor
                    </button>
                </div>
            </form>

            <div class="mt-4 position-relative" style="height: 200px; overflow: hidden; border-radius: 12px;">
                <img src="{{ asset('images/rec1.png') }}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; z-index: 1;">
                <img src="{{ asset('images/rec2.png') }}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; z-index: 2;">
                <div class="position-absolute bottom-0 start-0 end-0 text-center z-3 mb-3" style="z-index: 3;">
                    <span class="text-white">Sudah punya akun?</span>
                    <a href="{{ route('login') }}" class="btn btn-link text-white">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection