@extends('layouts.app_counselor') {{-- Pastikan nama layout utama Anda benar --}}

@section('title', 'Edit Profil Konselor')

@push('styles')
{{-- Jika Anda ingin menambahkan CSS khusus untuk halaman ini --}}
{{-- Contoh: <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" /> --}}
<style>
    .profile-header-edit {
        background-color: #0d6efd; /* Biru Bootstrap primer */
        color: white;
        border-radius: 0 0 30px 30px; /* Disesuaikan agar tidak terlalu besar jika hanya header kecil */
        padding: 1.5rem 1rem; /* Sedikit padding */
        margin-bottom: 2rem;
    }
    .profile-avatar-edit {
        width: 100px; /* Ukuran avatar di form edit */
        height: 100px;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 0 8px rgba(0,0,0,0.2);
    }
    .form-label.text-primary {
        font-weight: 600; /* Membuat label sedikit tebal */
    }
    /* Style untuk input yang readonly agar terlihat sedikit berbeda */
    input[readonly].form-control, textarea[readonly].form-control {
        background-color: #e9ecef; /* Warna abu-abu Bootstrap untuk disabled/readonly */
        opacity: 1; /* Pastikan teks tetap terbaca */
    }
</style>
@endpush

@section('content')
<div class="container mt-3 mb-5">

    {{-- Notifikasi Global dari Session atau Error Validasi --}}
    @if(Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">Oops, ada beberapa hal yang perlu diperbaiki:</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        // Ambil data konselor dari controller, default ke array kosong jika tidak ada
        $data = $counselorData ?? [];
        // Ambil nama dan avatar dari session untuk fallback jika $data['name'] atau $data['avatar'] kosong
        $sessionUserName = $userName ?? 'Konselor';
        $sessionUserAvatar = $userAvatar ?? asset('images/default_profile.png');
    @endphp

    {{-- Header Kecil untuk Halaman Edit --}}
    <div class="text-center profile-header-edit">
        <img src="{{ $data['avatar'] ?? $sessionUserAvatar }}"
             alt="Avatar {{ $data['name'] ?? $sessionUserName }}"
             class="img-thumbnail rounded-circle profile-avatar-edit mb-2">
        <h2 class="h5" style="color: white;">Edit Profil: {{ $data['name'] ?? $sessionUserName }}</h2>
    </div>

    <form action="{{ route('counselor.profile.update') }}" method="POST" enctype="multipart/form-data"> {{-- enctype jika Anda akan implementasi upload file avatar --}}
        @csrf
        {{-- Laravel biasanya menggunakan @method('PUT') atau @method('PATCH') untuk update.
             Namun, karena rute kita definisikan sebagai POST, ini tidak wajib.
             Jika Anda mengubah rute menjadi PUT/PATCH, tambahkan @method('PUT') atau @method('PATCH') di sini.
        --}}

        {{-- Kartu untuk Informasi Pribadi --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0 text-primary">Informasi Pribadi</h4>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="name" class="form-label text-primary">Nama Lengkap</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $data['name'] ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="avatar" class="form-label text-primary">URL Foto Profil</label>
                    <input type="url" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" value="{{ old('avatar', $data['avatar'] ?? '') }}" placeholder="https://example.com/avatar.jpg">
                    <small class="form-text text-muted">Masukkan URL gambar. Kosongkan jika tidak ingin mengubah dari yang sudah ada.</small>
                    @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                {{-- Catatan: Jika Anda ingin implementasi upload file untuk avatar, field inputnya akan berbeda (type="file")
                     dan controller perlu logika tambahan untuk menangani upload. --}}

                <div class="mb-3">
                    <label for="about" class="form-label text-primary">Tentang Saya</label>
                    <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="4" placeholder="Ceritakan sedikit tentang diri Anda sebagai konselor...">{{ old('about', $data['about'] ?? '') }}</textarea>
                    @error('about') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label text-primary">Nomor Telepon</label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $data['phone'] ?? '') }}" placeholder="Contoh: 081234567890">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="email_display" class="form-label text-primary">Email</label>
                    <input type="email" class="form-control" id="email_display" name="email_display" value="{{ $data['email'] ?? 'Email tidak tersedia' }}" readonly>
                    {{-- Email biasanya tidak diubah dari form profil standar untuk menjaga integritas akun --}}
                </div>
            </div>
        </div>

        {{-- Kartu untuk Ketersediaan Jadwal --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0 text-primary">Ketersediaan Jadwal</h4>
            </div>
            <div class="card-body p-4">
                @for ($i = 1; $i <= 3; $i++)
                <div class="row mb-3 align-items-end">
                    {{-- Input dropdown baru untuk Hari --}}
                    <div class="col-md-5">
                        <label for="availability_day{{ $i }}" class="form-label">Pilihan Hari ke-{{ $i }}</label>
                        @php
                            $selectedDay = old('availability_day'.$i, $data['availability_day'.$i] ?? '');
                            $daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                        @endphp
                        <select class="form-select @error('availability_day'.$i) is-invalid @enderror" id="availability_day{{ $i }}" name="availability_day{{ $i }}">
                            <option value="">-- Pilih Hari --</option> {{-- Opsi untuk pilihan kosong/null --}}
                            @foreach($daysOfWeek as $day)
                                <option value="{{ $day }}" {{ $selectedDay == $day ? 'selected' : '' }}>
                                    {{ $day }}
                                </option>
                            @endforeach
                        </select>
                        @error('availability_day'.$i) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label for="availability_time{{ $i }}" class="form-label">Pilihan Waktu ke-{{ $i }}</label>
                        <input type="text" class="form-control @error('availability_time'.$i) is-invalid @enderror" id="availability_time{{ $i }}" name="availability_time{{ $i }}" placeholder="Contoh: 10:00 - 12:00" value="{{ old('availability_time'.$i, $data['availability_time'.$i] ?? '') }}">
                        @error('availability_time'.$i) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- Input tersembunyi untuk menyimpan scheduleId yang sudah ada --}}
                    <input type="hidden" name="scheduleId{{$i}}" value="{{ $data['scheduleId'.$i] ?? '' }}">
                </div>
                @endfor
            </div>
        </div>

        {{-- Kartu untuk Ubah Password --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0 text-primary">Ubah Password (Opsional)</h4>
            </div>
            <div class="card-body p-4">
                <small class="form-text text-muted d-block mb-3">Isi bagian ini hanya jika Anda ingin mengubah password Anda.</small>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        {{-- Error untuk password_confirmation biasanya ditangani oleh rule 'confirmed' pada field 'password' --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="d-flex justify-content-end mt-4 mb-5">
            <a href="{{ route('counselor.profile.show') }}" class="btn btn-outline-secondary me-2 px-4">Batal</a>
            <button type="submit" class="btn btn-primary px-5">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Hanya jalankan jika form edit ada di halaman
        const editForm = document.querySelector('form[action="{{ route("counselor.profile.update") }}"]');
        if (editForm) {
            const day1 = document.getElementById('availability_day1');
            const time1 = document.getElementById('availability_time1');
            const day2 = document.getElementById('availability_day2');
            const time2 = document.getElementById('availability_time2');
            const day3 = document.getElementById('availability_day3');
            const time3 = document.getElementById('availability_time3');

            // console.log('Initial elements:', { day1, time1, day2, time2, day3, time3 });

            function setElementState(element, isDisabled) {
                if (!element) return;
                
                // console.log(`Setting state for ${element.id}: isDisabled = ${isDisabled}`);

                if (element.tagName.toLowerCase() === 'select') {
                    element.disabled = isDisabled;
                } else { // Untuk text inputs
                    element.readOnly = isDisabled; // readOnly agar nilainya tetap bisa dikirim jika ada
                                                 // Jika ingin seperti select (value tidak dikirim saat disabled), gunakan element.disabled = isDisabled;
                }
                
                element.style.backgroundColor = isDisabled ? '#e9ecef' : ''; // Warna input disabled Bootstrap
                if (isDisabled) {
                    if (element.tagName.toLowerCase() === 'select') {
                        element.value = ''; // Reset select ke opsi default (yang value-nya kosong)
                        // console.log(`${element.id} value reset to empty`);
                    } else {
                        element.value = ''; // Kosongkan field text
                        // console.log(`${element.id} value cleared`);
                    }
                } else {
                    // console.log(`${element.id} enabled`);
                }
            }

            function evaluateAndSetTargets(checkDay, checkTime, targetDay, targetTime) {
                // console.log(`Evaluating: checkDay=${checkDay?.id}, checkTime=${checkTime?.id} for targetDay=${targetDay?.id}, targetTime=${targetTime?.id}`);
                if (!targetDay || !targetTime) {
                    // console.log('One or more target elements do not exist for this pair.');
                    return;
                }

                // Sebuah field dianggap "kosong" jika elemennya tidak ada, atau value-nya kosong setelah di-trim
                const dayIsEmpty = !checkDay || checkDay.value.trim() === '';
                const timeIsEmpty = !checkTime || checkTime.value.trim() === '';
                const shouldDisableTargets = dayIsEmpty || timeIsEmpty;

                // console.log(`checkDay (${checkDay?.id}) empty: ${dayIsEmpty} (value: "${checkDay?.value}")`);
                // console.log(`checkTime (${checkTime?.id}) empty: ${timeIsEmpty} (value: "${checkTime?.value}")`);
                // console.log(`Should disable targets (${targetDay.id}, ${targetTime.id}): ${shouldDisableTargets}`);

                setElementState(targetDay, shouldDisableTargets);
                setElementState(targetTime, shouldDisableTargets);
            }

            function setupAvailabilityLogic() {
                // console.log('Running setupAvailabilityLogic...');
                evaluateAndSetTargets(day1, time1, day2, time2); // Day1/Time1 mempengaruhi Day2/Time2
                evaluateAndSetTargets(day2, time2, day3, time3); // Day2/Time2 mempengaruhi Day3/Time3
                // console.log('Finished setupAvailabilityLogic.');
            }

            // Pastikan semua elemen utama ada sebelum menambahkan listener dan menjalankan logika awal
            if (day1 && time1 && day2 && time2 && day3 && time3) {
                setupAvailabilityLogic(); // Panggil saat load untuk set initial state

                // Tambahkan event listener ke field yang mempengaruhi field lain
                // Gunakan 'change' untuk select, dan 'input' untuk text field
                day1.addEventListener('change', setupAvailabilityLogic); // Jika day1 adalah select
                time1.addEventListener('input', setupAvailabilityLogic);
                day2.addEventListener('change', setupAvailabilityLogic); // Jika day2 adalah select
                time2.addEventListener('input', setupAvailabilityLogic);
                
                // console.log('Event listeners attached.');
            } else {
                console.warn('One or more availability fields are missing. JS logic for cascading might not work correctly.');
            }
        }
    });
</script>
@endpush