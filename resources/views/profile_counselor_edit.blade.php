@extends('layouts.app_counselor')

@section('title', 'Edit Profil Saya - GasKonsul')

@push('styles')
{{-- Font Awesome & Bootstrap Icons (pastikan salah satu atau keduanya ter-link di layout utama atau di sini) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .edit-profile-card {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.10) !important;
        overflow: hidden;
    }
    .edit-profile-header {
        padding: 1rem 1.5rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
    }
    .edit-profile-header .btn-back {
        font-size: 1.25rem;
        color: #0d6efd;
        text-decoration: none;
    }
    .edit-profile-header .form-title {
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 0;
        margin-left: 0.75rem;
    }
    .form-section {
        padding: 2rem;
    }
    .form-section .form-label {
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 0.35rem;
    }
    .form-section .form-control,
    .form-section .form-select {
        border-radius: 0.3rem;
        background-color: #fdfdff; /* Latar sedikit off-white untuk input */
        border: 1px solid #ced4da;
    }
    .form-section .form-control:focus,
    .form-section .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .form-section .form-control[readonly],
    .form-section .form-select[disabled] {
        background-color: #e9ecef; /* Warna standar untuk readonly/disabled */
        opacity: 1;
    }
    .avatar-edit-section {
        padding: 2rem;
        display: flex;
        flex-direction: column;
        align-items: center; /* Avatar dan input URL di tengah kolom ini */
        justify-content: flex-start;
    }
    .avatar-edit-container {
        position: relative;
        display: inline-block;
        margin-bottom: 1.5rem;
    }
    .profile-avatar-edit {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #dee2e6;
    }
    .avatar-edit-button {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .avatar-input-group { /* Untuk membungkus label dan input URL avatar */
        width: 100%;
        text-align: center; /* Membuat label dan input di tengah relatif terhadap grup ini */
    }
    .avatar-input-group .form-label {
        display: block; /* Agar mengambil lebar penuh dan text-align center bekerja */
    }
    .avatar-input-group .form-control {
        text-align: center; /* Teks di dalam input URL avatar juga di tengah */
    }
    .form-actions-column {
        width: 100%;
        margin-top: 1.5rem; /* Jarak dari input URL avatar ke tombol Simpan */
    }
    .form-actions-bottom {
        padding: 1.5rem 2rem;
        border-top: 1px solid #eee;
        background-color: #f8f9fa;
        text-align: right;
    }
</style>
@endpush

@section('content')
<div class="container my-lg-5 my-md-4 my-3">

    {{-- Notifikasi Error Validasi atau Error Lain --}}
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
    @if(Session::has('error_load'))
        <div class="alert alert-danger text-center">{{ Session::get('error_load') }}</div>
    @endif
     @if(Session::has('error'))
         <div class="alert alert-danger text-center">{{ Session::get('error') }}</div>
    @endif
    @if(Session::has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ Session::get('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(Session::has('success')) {{-- Tambahkan ini untuk notifikasi sukses --}}
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif


    @php $data = $counselorData ?? []; @endphp

    <form action="{{ route('counselor.profile.update') }}" method="POST" enctype="multipart/form-data" id="counselorEditProfileForm">
        @csrf
        <div class="edit-profile-card">
            <div class="edit-profile-header">
                <a href="{{ route('counselor.profile.show') }}" class="btn-back" title="Kembali ke Profil">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h3 class="form-title">Edit Profil</h3>
            </div>

            <div class="row g-0">
                {{-- Kolom Kiri: Form Input --}}
                <div class="col-lg-7 col-md-6 order-md-1 form-section border-end">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $data['name'] ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="about" class="form-label">Tentang Saya</label>
                        <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="3">{{ old('about', $data['about'] ?? '') }}</textarea>
                        @error('about') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_display" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email_display" value="{{ $data['email'] ?? 'Tidak tersedia' }}" readonly>
                        <small class="form-text text-muted">Email tidak dapat diubah.</small> {{-- TEKS INI DIMUNCULKAN KEMBALI --}}
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor Telepon</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $data['phone'] ?? '') }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    @if(isset($data['bidang']))
                    <div class="mb-3">
                        <label for="bidang_display" class="form-label">Spesialisasi</label>
                        <input type="text" class="form-control" id="bidang_display" value="{{ $data['bidang'] ?? '' }}" readonly>
                         <small class="form-text text-muted">Spesialisasi diatur saat registrasi.</small>
                    </div>
                    @endif

                    <h5 class="schedule-title mt-4">Ketersediaan Jadwal</h5>
                    @php $daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']; @endphp
                    @for ($i = 1; $i <= 3; $i++)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="availability_day{{ $i }}" class="form-label">Pilihan Hari ke-{{ $i }}</label>
                            @php $selectedDay = old('availability_day'.$i, $data['availability_day'.$i] ?? ''); @endphp
                            <select class="form-select @error('availability_day'.$i) is-invalid @enderror" id="availability_day{{ $i }}" name="availability_day{{ $i }}">
                                <option value="">-- Pilih Hari --</option>
                                @foreach($daysOfWeek as $day)
                                    <option value="{{ $day }}" {{ $selectedDay == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('availability_day'.$i) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="availability_time{{ $i }}" class="form-label">Pilihan Waktu ke-{{ $i }}</label>
                            <input type="text" class="form-control @error('availability_time'.$i) is-invalid @enderror" id="availability_time{{ $i }}" name="availability_time{{ $i }}" placeholder="Contoh: 10:00 - 12:00" value="{{ old('availability_time'.$i, $data['availability_time'.$i] ?? '') }}">
                            @error('availability_time'.$i) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <input type="hidden" name="scheduleId{{$i}}" value="{{ $data['scheduleId'.$i] ?? '' }}">
                    </div>
                    @endfor
                    
                    <h5 class="schedule-title mt-4">Ubah Password (Opsional)</h5>
                     <small class="form-text text-muted d-block mb-2">Isi bagian ini hanya jika Anda ingin mengubah password.</small>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Avatar & Input URL Avatar --}}
                <div class="col-lg-5 col-md-6 order-md-2 avatar-edit-section">
                    <div class="avatar-edit-container">
                        <img src="{{ $data['avatar'] ?? asset('images/default_profile.png') }}"
                             alt="Avatar {{ $data['name'] ?? ($userName ?? 'Konselor') }}"
                             class="profile-avatar-edit img-fluid" id="avatarPreviewCounselor">
                        <button type="button" class="btn btn-info avatar-edit-button" id="changeAvatarBtnCounselor" title="Ubah Foto Profil">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    {{-- Grup untuk input URL Avatar agar bisa di-center dan label di bold --}}
                    <div class="mb-3 w-100 avatar-input-group">
                        <label for="avatar" class="form-label text-primary fw-bold">URL Foto Profil</label> {{-- Label dibuat tebal --}}
                        <input type="url" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" value="{{ old('avatar', $data['avatar'] ?? '') }}" placeholder="https://example.com/avatar.jpg" style="text-align: center;"> {{-- Input teks di tengah --}}
                        @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi di Bawah Kartu --}}
            <div class="form-actions-bottom">
                <a href="{{ route('counselor.profile.show') }}" class="btn btn-outline-secondary btn-lg rounded-pill px-5 me-2">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Modal untuk Opsi Avatar --}}
<div class="modal fade" id="avatarOptionsModalCounselor" tabindex="-1" aria-labelledby="avatarOptionsModalLabelCounselor" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarOptionsModalLabelCounselor">Opsi Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="changeAvatarUrlBtnCounselor">
                        <i class="fas fa-edit me-2"></i>Ganti URL Gambar
                    </button>
                    <button type="button" class="btn btn-danger" id="removeAvatarBtnCounselor">
                        <i class="fas fa-trash-alt me-2"></i>Hapus Gambar Saat Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pastikan hanya berjalan jika elemen form utama ada
    const mainFormCounselor = document.getElementById('counselorEditProfileForm'); 
    if (mainFormCounselor) {
        const day1 = document.getElementById('availability_day1');
        const time1 = document.getElementById('availability_time1');
        const day2 = document.getElementById('availability_day2');
        const time2 = document.getElementById('availability_time2');
        const day3 = document.getElementById('availability_day3');
        const time3 = document.getElementById('availability_time3');

        function applyFieldState(element, isDisabled) {
            if (!element) return;
            if (element.tagName.toLowerCase() === 'select') {
                element.disabled = isDisabled;
            } else { // Untuk text inputs
                element.readOnly = isDisabled;
            }
            element.style.backgroundColor = isDisabled ? '#e9ecef' : ''; // Warna abu-abu Bootstrap
            if (isDisabled) {
                if (element.tagName.toLowerCase() === 'select') {
                    element.value = ''; // Reset select ke opsi "-- Pilih Hari --"
                } else {
                    element.value = ''; // Kosongkan field text
                }
            }
        }

        function evaluateAndSetTargetStates(checkDayEl, checkTimeEl, targetDayEl, targetTimeEl) {
            if (!targetDayEl || !targetTimeEl) { // Jika elemen target tidak ada, jangan lakukan apa-apa
                // console.warn('Satu atau lebih elemen target ketersediaan tidak ditemukan.');
                return;
            }
            // Sebuah field prasyarat dianggap kosong jika elemennya tidak ada ATAU value-nya kosong setelah di-trim
            const dayPrasyaratKosong = !checkDayEl || checkDayEl.value.trim() === '';
            const timePrasyaratKosong = !checkTimeEl || checkTimeEl.value.trim() === '';
            
            const shouldDisableTargets = dayPrasyaratKosong || timePrasyaratKosong;

            applyFieldState(targetDayEl, shouldDisableTargets);
            applyFieldState(targetTimeEl, shouldDisableTargets);
        }

        function setupAvailabilityLogic() {
            // Day1/Time1 mempengaruhi Day2/Time2
            evaluateAndSetTargetStates(day1, time1, day2, time2);
            // Day2/Time2 mempengaruhi Day3/Time3
            // Logika ini akan berjalan setelah day2/time2 di-update oleh baris di atas
            evaluateAndSetTargetStates(day2, time2, day3, time3);
        }

        // Hanya setup listener jika semua field utama ada
        const allAvailabilityFieldsPresent = [day1, time1, day2, time2, day3, time3].every(el => el);
        if (allAvailabilityFieldsPresent) {
            setupAvailabilityLogic(); // Panggil saat load untuk set initial state

            [day1, time1, day2, time2].forEach(el => { // Hanya field prasyarat yang perlu listener
                const eventType = el.tagName.toLowerCase() === 'select' ? 'change' : 'input';
                el.addEventListener(eventType, setupAvailabilityLogic);
            });
            // console.log('Event listeners untuk ketersediaan jadwal sudah terpasang.');
        } else {
            console.warn('Satu atau lebih field input ketersediaan tidak ditemukan oleh ID. Logika JS ketersediaan mungkin tidak berjalan sempurna.');
        }

        // Logika Avatar Modal (Counselor)
        const changeAvatarBtnCounselor = document.getElementById('changeAvatarBtnCounselor');
        const avatarInputHtml = document.getElementById('avatar');
        const avatarPreviewCounselor = document.getElementById('avatarPreviewCounselor');
        const avatarOptionsModalEl = document.getElementById('avatarOptionsModalCounselor');
        let avatarOptionsModalInstanceCounselor = null;
        if(avatarOptionsModalEl) {
            avatarOptionsModalInstanceCounselor = new bootstrap.Modal(avatarOptionsModalEl);
        } else {
            console.warn('Elemen modal #avatarOptionsModalCounselor tidak ditemukan.');
        }

        const changeAvatarUrlBtnModal = document.getElementById('changeAvatarUrlBtnCounselor'); // Tombol di dalam modal
        const removeAvatarBtnModal = document.getElementById('removeAvatarBtnCounselor'); // Tombol di dalam modal

        if (changeAvatarBtnCounselor && avatarOptionsModalInstanceCounselor) {
            changeAvatarBtnCounselor.addEventListener('click', function() {
                avatarOptionsModalInstanceCounselor.show();
            });
        }

        if (changeAvatarUrlBtnModal && avatarInputHtml && avatarPreviewCounselor && avatarOptionsModalInstanceCounselor) {
            changeAvatarUrlBtnModal.addEventListener('click', function() {
                const currentAvatarUrl = avatarInputHtml.value;
                const newAvatarUrl = prompt("Masukkan URL foto profil baru:", currentAvatarUrl);
                if (newAvatarUrl !== null) {
                    avatarInputHtml.value = newAvatarUrl;
                    avatarPreviewCounselor.src = newAvatarUrl.trim() !== '' ? newAvatarUrl : "{{ asset('images/default_profile.png') }}";
                }
                avatarOptionsModalInstanceCounselor.hide();
            });
        }

        if (removeAvatarBtnModal && avatarInputHtml && avatarPreviewCounselor && avatarOptionsModalInstanceCounselor) {
            removeAvatarBtnModal.addEventListener('click', function() {
                avatarInputHtml.value = '';
                avatarPreviewCounselor.src = "{{ asset('images/default_profile.png') }}";
                avatarOptionsModalInstanceCounselor.hide();
            });
        }
        
        if (avatarInputHtml && avatarPreviewCounselor) {
            avatarInputHtml.addEventListener('input', function() {
                 avatarPreviewCounselor.src = this.value.trim() !== '' ? this.value : "{{ asset('images/default_profile.png') }}";
            });
        }
    } else {
        // console.warn('Form utama edit profil konselor (#counselorEditProfileForm) tidak ditemukan.');
    }
});
</script>
@endpush