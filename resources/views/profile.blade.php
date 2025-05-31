@extends('layouts.app')

@section('title', 'Profil Pengguna - GasKonsul')

@section('content')
<div class="container-fluid bg-light py-4" style="min-height: 100vh;">
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('home') }}" class="btn btn-link text-primary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0">Profil Saya</h4>
        </div>
        <div class="d-flex align-items-center">
            <a href="{{ route('home') }}" class="me-3 text-decoration-none text-dark">Beranda</a>
            <a href="{{ route('history') }}" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="{{ route('chat') }}" class="me-3 text-decoration-none text-dark">Chat</a>
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                <img src="{{ asset('images/default_profile.png') }}" alt="User" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                <span class="ms-2">{{ $userData['name'] ?? 'User' }}</span>
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
        {{-- Alert untuk pesan sukses/error dari AJAX --}}
        <div id="profileAjaxAlert" class="alert mt-3 text-center" style="display: none;"></div>
    </div>


    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 700px;">
        @if($userData)
            <div id="viewProfile" style="display: block;">
                <div class="text-center mb-4 pb-4 bg-primary text-white rounded-bottom-4" style="margin-top: -30px; border-top-left-radius: 45px !important; border-top-right-radius: 45px !important; border-bottom-left-radius: 45px !important; border-bottom-right-radius: 45px !important;">
                    <img src="{{ $userData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-white border-3 mb-3" style="width: 120px; height: 120px; object-fit: cover; margin-top: 30px;">
                    <h4 class="fw-bold">{{ $userData['name'] ?? 'Nama Tidak Tersedia' }}</h4>
                </div>

                <div class="px-4 pt-4">
                    <div class="mb-4">
                        <label class="form-label text-primary fw-bold">Email</label>
                        <input type="text" class="form-control" value="{{ $userData['email'] ?? 'Email Tidak Tersedia' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-primary fw-bold">Phone</label>
                        <input type="text" class="form-control" value="{{ $userData['phone'] ?? '+62 XXXXXXXXXX' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                    </div>

                    <div class="text-center mt-5 mb-3">
                        <button id="editProfileBtn" class="btn btn-primary btn-lg rounded-pill px-5">Edit Profil</button>
                    </div>
                    <div class="text-center mt-3">
                        <form method="POST" action="{{ route('logout') }}" id="user-profile-logout-form">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg rounded-pill px-5">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="editProfileForm" style="display: none;">
                <form id="updateProfileForm">
                    @csrf
                    <div class="text-center mb-4 pb-4 bg-primary text-white rounded-bottom-4" style="margin-top: -30px; border-top-left-radius: 45px !important; border-top-right-radius: 45px !important; border-bottom-left-radius: 45px !important; border-bottom-right-radius: 45px !important;">
                        <div class="position-relative d-inline-block" style="margin-top: 30px;">
                            <img src="{{ $userData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-white border-3 mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                            <button type="button" class="btn btn-info btn-sm rounded-circle position-absolute bottom-0 end-0" style="width: 40px; height: 40px;"><i class="bi bi-camera"></i></button>
                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                        </div>
                        <h4 class="fw-bold">{{ $userData['name'] ?? 'Nama Tidak Tersedia' }}</h4>
                    </div>

                    <div class="px-4 pt-4">
                        <div class="mb-3">
                            <label for="name" class="form-label text-primary fw-bold">Nama</label>
                            <input type="text" class="form-control" id="edit_name" name="name" value="{{ $userData['name'] ?? '' }}" required style="background-color: #e8f1ff; border-radius: 10px;">
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label text-primary fw-bold">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" value="{{ $userData['email'] ?? '' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                            <small class="form-text text-muted">Email tidak dapat diubah di sini.</small>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label text-primary fw-bold">Phone</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone" value="{{ $userData['phone'] ?? '' }}" style="background-color: #e8f1ff; border-radius: 10px;">
                            <div class="invalid-feedback" id="phoneError"></div>
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold text-primary mb-3">Ubah Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label text-primary fw-bold">Password Saat Ini</label>
                            <input type="password" class="form-control" id="edit_current_password" name="current_password" style="background-color: #e8f1ff; border-radius: 10px;">
                            <div class="invalid-feedback" id="currentPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label text-primary fw-bold">Password Baru</label>
                            <input type="password" class="form-control" id="edit_new_password" name="new_password" style="background-color: #e8f1ff; border-radius: 10px;">
                            <div class="invalid-feedback" id="newPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label text-primary fw-bold">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="edit_new_password_confirmation" name="new_password_confirmation" style="background-color: #e8f1ff; border-radius: 10px;">
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary btn-lg rounded-pill px-5">Batal</button>
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <p class="text-center text-muted">Gagal memuat data profil. Silakan coba lagi nanti.</p>
        @endif
    </div>
</div>
@endsection

@section('scripts')
{{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewProfileSection = document.getElementById('viewProfile');
        const editProfileFormSection = document.getElementById('editProfileForm');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const updateProfileForm = document.getElementById('updateProfileForm');
        const profileAjaxAlert = document.getElementById('profileAjaxAlert');

        // Fungsi untuk menampilkan alert
        function showAlert(message, type) {
            profileAjaxAlert.textContent = message;
            profileAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            profileAjaxAlert.style.display = 'block';
            setTimeout(() => {
                profileAjaxAlert.style.display = 'none';
            }, 5000); // Sembunyikan setelah 5 detik
        }

        // Fungsi untuk membersihkan error dari form
        function clearErrors() {
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        }

        // --- Logic Mode View/Edit ---
        editProfileBtn.addEventListener('click', function() {
            viewProfileSection.style.display = 'none';
            editProfileFormSection.style.display = 'block';
            clearErrors(); // Bersihkan error jika ada dari percobaan sebelumnya
            // Isi kembali field edit_email dan edit_phone jika ada perubahan yang tidak disubmit
            document.getElementById('edit_name').value = document.querySelector('#viewProfile h4').textContent.trim();
            document.getElementById('edit_email').value = document.querySelector('#viewProfile input[type="text"][value*="@"]').value;
            document.getElementById('edit_phone').value = document.querySelector('#viewProfile input[type="text"][value*="+62"]').value;
            // Kosongkan field password saat masuk mode edit
            document.getElementById('edit_current_password').value = '';
            document.getElementById('edit_new_password').value = '';
            document.getElementById('edit_new_password_confirmation').value = '';
        });

        cancelEditBtn.addEventListener('click', function() {
            editProfileFormSection.style.display = 'none';
            viewProfileSection.style.display = 'block';
            clearErrors(); // Bersihkan error saat keluar mode edit
        });

        // --- Logic Submit Form Update Profil ---
        updateProfileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            clearErrors(); // Bersihkan error dari validasi sebelumnya
            showAlert('Memperbarui profil...', 'info'); // Tampilkan pesan loading

            const name = document.getElementById('edit_name').value;
            const phone = document.getElementById('edit_phone').value;
            const currentPassword = document.getElementById('edit_current_password').value;
            const newPassword = document.getElementById('edit_new_password').value;
            const newPasswordConfirmation = document.getElementById('edit_new_password_confirmation').value;

            let hasPasswordChanged = newPassword.length > 0 || newPasswordConfirmation.length > 0 || currentPassword.length > 0;

            // Update Data Profil (Nama, Phone)
            try {
                const responseData = await fetch('{{ route('profile.updateData') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name, phone })
                });

                const data = await responseData.json();

                if (!responseData.ok) {
                    if (responseData.status === 422 && data.errors) {
                        for (const field in data.errors) {
                            const inputField = document.getElementById(`edit_${field}`);
                            if (inputField) {
                                inputField.classList.add('is-invalid');
                                const errorDiv = document.getElementById(`${field}Error`);
                                if (errorDiv) errorDiv.textContent = data.errors[field][0];
                            }
                        }
                    }
                    showAlert(data.message || 'Gagal memperbarui data profil.', 'danger');
                    return; // Hentikan jika ada error pada data profil
                }
                showAlert(data.message, 'success');

                // Jika data profil berhasil diupdate, update tampilan viewProfile
                if (data.userData) {
                    document.querySelector('#viewProfile h4').textContent = data.userData.name;
                    document.querySelector('#viewProfile input[type="text"][value*="@"]').value = data.userData.email;
                    document.querySelector('#viewProfile input[type="text"][value*="+62"]').value = data.userData.phone;
                    // Update avatar jika ada perubahan
                    if (data.userData.avatar) {
                        document.querySelector('#viewProfile img').src = data.userData.avatar;
                        document.querySelector('#editProfileForm img').src = data.userData.avatar;
                    }
                }

            } catch (error) {
                showAlert('Terjadi kesalahan jaringan saat memperbarui data profil.', 'danger');
                console.error('Error updating profile data:', error);
                return;
            }

            // Update Password (Jika ada perubahan password yang diminta)
            if (hasPasswordChanged) {
                if (newPassword !== newPasswordConfirmation) {
                    showAlert('Konfirmasi password baru tidak cocok!', 'danger');
                    document.getElementById('edit_new_password_confirmation').classList.add('is-invalid');
                    return;
                }
                if (newPassword.length < 8) {
                    showAlert('Password baru minimal 8 karakter.', 'danger');
                    document.getElementById('edit_new_password').classList.add('is-invalid');
                    return;
                }

                try {
                    const responsePassword = await fetch('{{ route('profile.updatePassword') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            current_password: currentPassword,
                            new_password: newPassword,
                            new_password_confirmation: newPasswordConfirmation
                        })
                    });

                    const dataPassword = await responsePassword.json();

                    if (!responsePassword.ok) {
                        if (responsePassword.status === 422 && dataPassword.errors) {
                            for (const field in dataPassword.errors) {
                                const inputField = document.getElementById(`edit_new_password`); // Atau sesuaikan ID field
                                if (inputField) {
                                    inputField.classList.add('is-invalid');
                                    const errorDiv = document.getElementById(`newPasswordError`); // Atau sesuaikan ID error div
                                    if (errorDiv) errorDiv.textContent = dataPassword.errors[field][0];
                                }
                            }
                        }
                        showAlert(dataPassword.message || 'Gagal memperbarui password.', 'danger');
                        return;
                    }
                    showAlert(dataPassword.message, 'success');

                    // Kosongkan field password setelah berhasil
                    document.getElementById('edit_current_password').value = '';
                    document.getElementById('edit_new_password').value = '';
                    document.getElementById('edit_new_password_confirmation').value = '';

                } catch (error) {
                    showAlert('Terjadi kesalahan jaringan saat memperbarui password.', 'danger');
                    console.error('Error updating password:', error);
                    return;
                }
            }

            // Keluar dari mode edit setelah semua update berhasil
            cancelEditBtn.click(); // Tombol batal
        });
    });
</script>
@endsection