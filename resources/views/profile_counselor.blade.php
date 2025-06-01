@extends('layouts.app_counselor') {{-- Pastikan nama layout Anda benar --}}

@section('title', $isEditing ?? false ? 'Edit Profil Konselor' : 'Profil Konselor')

@push('styles')
{{-- Jika Anda ingin menambahkan CSS khusus untuk halaman ini --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    .profile-header {
        background-color: #0d6efd; /* Biru Bootstrap primer */
        color: white;
        border-radius: 0 0 30px 30px;
        padding: 2rem 1rem;
        margin-bottom: 2rem;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .availability-box {
        background-color: #0d6efd;
        color: white;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem; /* Tambahkan margin bawah untuk setiap kotak jadwal */
    }
    .form-label.text-primary {
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container mt-3 mb-5">

    {{-- Notifikasi Global --}}
    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(Session::has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ Session::get('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">Oops, ada kesalahan!</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $data = $counselorData ?? []; // Data dari Firestore
        // $isEditing adalah variabel yang dikirim dari controller
    @endphp

    @if($errorMessage && empty($data) && !$isEditing)
        <div class="alert alert-warning text-center">
            <p>{{ $errorMessage }}</p>
            <a href="{{ route('counselor.profile') }}?edit=true" class="btn btn-primary">Lengkapi Profil Sekarang</a>
        </div>
    @else
        {{-- Bagian Header Profil (Avatar & Nama) --}}
        <div class="text-center profile-header">
            <img src="{{ $data['avatar'] ?? asset('images/default_profile.png') }}"
                 alt="Avatar {{ $data['name'] ?? 'Konselor' }}"
                 class="img-thumbnail rounded-circle profile-avatar mb-2">
            <h2 class="h4" style="color: white;">{{ $data['name'] ?? $userName ?? 'Nama Belum Diatur' }}</h2>
        </div>

        @if($isEditing)
            {{-- =================== FORM EDIT PROFIL =================== --}}
            <form action="{{ route('counselor.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Laravel biasanya menggunakan @method('PUT') atau @method('PATCH') untuk update,
                     tapi jika rute Anda POST, maka method="POST" saja cukup.
                     Jika rute Anda 'put' atau 'patch', tambahkan @method('PUT') atau @method('PATCH') di sini.
                     Untuk saat ini, kita definisikan rute sebagai POST, jadi ini tidak perlu. --}}

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h4 class="mb-0 text-primary">Edit Detail Profil</h4>
                    </div>
                    <div class="card-body p-4">
                        {{-- NAMA --}}
                        <div class="mb-3">
                            <label for="name" class="form-label text-primary">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $data['name'] ?? '') }}" required>
                        </div>

                        {{-- AVATAR (URL) --}}
                        <div class="mb-3">
                            <label for="avatar" class="form-label text-primary">URL Foto Profil</label>
                            <input type="url" class="form-control" id="avatar" name="avatar" value="{{ old('avatar', $data['avatar'] ?? '') }}" placeholder="https://example.com/avatar.jpg">
                            <small class="form-text text-muted">Masukkan URL gambar yang valid. Kosongkan jika tidak ingin mengubah.</small>
                        </div>
                        {{-- Untuk upload file avatar, Anda perlu logika tambahan di controller dan enctype="multipart/form-data" di form. --}}

                        {{-- ABOUT --}}
                        <div class="mb-3">
                            <label for="about" class="form-label text-primary">Tentang Saya</label>
                            <textarea class="form-control" id="about" name="about" rows="4" placeholder="Ceritakan sedikit tentang diri Anda sebagai konselor...">{{ old('about', $data['about'] ?? '') }}</textarea>
                        </div>

                        {{-- PHONE --}}
                        <div class="mb-3">
                            <label for="phone" class="form-label text-primary">Nomor Telepon</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', $data['phone'] ?? '') }}" placeholder="Contoh: 081234567890">
                        </div>

                        {{-- EMAIL (Read-only) --}}
                        <div class="mb-3">
                            <label for="email" class="form-label text-primary">Email</label>
                            <input type="email" class="form-control" id="email" name="email_display" value="{{ $data['email'] ?? 'Email tidak tersedia' }}" readonly disabled>
                            <input type="hidden" name="email" value="{{ $data['email'] ?? '' }}"> {{-- Kirim email tersembunyi jika perlu di backend --}}
                            <small class="form-text text-muted">Email tidak dapat diubah.</small>
                        </div>

                        {{-- KETERSEDIAAN (JADWAL) --}}
                        <h5 class="text-primary mt-4 mb-3">Ketersediaan Jadwal</h5>
                        @for ($i = 1; $i <= 3; $i++)
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-5">
                                <label for="availability_day{{ $i }}" class="form-label">Pilihan Hari ke-{{ $i }}</label>
                                <input type="text" class="form-control" id="availability_day{{ $i }}" name="availability_day{{ $i }}" placeholder="Contoh: Senin" value="{{ old('availability_day'.$i, $data['availability_day'.$i] ?? '') }}">
                            </div>
                            <div class="col-md-5">
                                <label for="availability_time{{ $i }}" class="form-label">Pilihan Waktu ke-{{ $i }}</label>
                                <input type="text" class="form-control" id="availability_time{{ $i }}" name="availability_time{{ $i }}" placeholder="Contoh: 10:00 - 12:00" value="{{ old('availability_time'.$i, $data['availability_time'.$i] ?? '') }}">
                            </div>
                            {{-- Input tersembunyi untuk scheduleIdX --}}
                            <input type="hidden" name="scheduleId{{$i}}" value="{{ $data['scheduleId'.$i] ?? '' }}">
                        </div>
                        @endfor
                        <hr>
                        {{-- PASSWORD BARU (Opsional) --}}
                        <h5 class="text-primary mt-4 mb-3">Ubah Password (Kosongkan jika tidak ingin diubah)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('counselor.profile') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            {{-- =================== TAMPILAN PROFIL (BACA SAJA) =================== --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-primary">Informasi Profil</h4>
                    <a href="{{ route('counselor.profile') }}?edit=true" class="btn btn-primary btn-sm">
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
                        for ($i = 1; $i <= 3; $i++) {
                            if (!empty($data['availability_day'.$i]) && !empty($data['availability_time'.$i])) {
                                $hasSchedule = true;
                                break;
                            }
                        }
                    @endphp

                    @if($hasSchedule)
                        <div class="row">
                            @for ($i = 1; $i <= 3; $i++)
                                @if (!empty($data['availability_day'.$i]) && !empty($data['availability_time'.$i]))
                                    <div class="col-md-4 mb-3">
                                        <div class="availability-box text-center h-100">
                                            <h6 class="card-title mb-1 fw-bold">{{ $data['availability_day'.$i] }}</h6>
                                            <p class="card-text small mb-0">{{ $data['availability_time'.$i] }}</p>
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
            {{-- Tombol Logout sudah ada di navbar utama, jadi bisa dihilangkan dari sini --}}
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    // JavaScript sederhana untuk mengontrol input ketersediaan (opsional)
    // Ini hanya contoh dasar, Anda bisa membuatnya lebih canggih.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('form[action="{{ route("counselor.profile.update") }}"]')) { // Hanya jalan jika form edit ada
            const day1 = document.getElementById('availability_day1');
            const time1 = document.getElementById('availability_time1');
            const day2 = document.getElementById('availability_day2');
            const time2 = document.getElementById('availability_time2');
            const day3 = document.getElementById('availability_day3');
            const time3 = document.getElementById('availability_time3');

            function checkAndToggle(checkDay, checkTime, targetDay, targetTime) {
                if (targetDay && targetTime) {
                    const dayIsEmpty = !checkDay || checkDay.value.trim() === '';
                    const timeIsEmpty = !checkTime || checkTime.value.trim() === '';
                    
                    targetDay.readOnly = dayIsEmpty || timeIsEmpty;
                    targetTime.readOnly = dayIsEmpty || timeIsEmpty;

                    // Jika field prasyarat kosong, kosongkan dan disable field target
                    if (dayIsEmpty || timeIsEmpty) {
                        targetDay.value = '';
                        targetTime.value = '';
                        targetDay.style.backgroundColor = '#e9ecef'; // Warna disabled Bootstrap
                        targetTime.style.backgroundColor = '#e9ecef';
                    } else {
                        targetDay.style.backgroundColor = ''; // Kembalikan ke warna normal
                        targetTime.style.backgroundColor = '';
                    }
                }
            }

            function setupAvailabilityLogic() {
                checkAndToggle(day1, time1, day2, time2);
                checkAndToggle(day2, time2, day3, time3);
            }

            // Panggil saat halaman dimuat untuk set initial state
            setupAvailabilityLogic();

            // Tambahkan event listener
            [day1, time1, day2, time2].forEach(el => {
                if (el) el.addEventListener('input', setupAvailabilityLogic);
            });
        }
    });
</script>
@endpush