@extends('layouts.app')

@section('title', 'Profil Pengguna - GasKonsul')

@section('content')
<div class="container-fluid py-4" style="min-height: 100vh;">
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

    {{-- Main Profile Content Container --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 900px;">
        @if($userData)
            {{-- View Profile Section --}}
            <div id="viewProfile" style="display: block;">
                <div class="row">
                    <div class="col-md-7">
                        <h5 class="fw-bold text-primary mb-4">Profil Pengguna</h5>
                        <div class="mb-4">
                            <label class="form-label text-primary fw-bold">Nama</label>
                            <input type="text" class="form-control" id="display_name_view" value="{{ $userData['name'] ?? 'Nama Tidak Tersedia' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-primary fw-bold">Email</label>
                            <input type="email" class="form-control" id="display_email" value="{{ $userData['email'] ?? 'Email Tidak Tersedia' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-primary fw-bold">Phone</label>
                            <input type="text" class="form-control" id="display_phone" value="{{ $userData['phone'] ?? '+62 XXXXXXXXXX' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                        </div>
                    </div>
                    <div class="col-md-5 d-flex flex-column align-items-center justify-content-center">
                        <div class="text-center">
                            <img id="display_avatar" src="{{ $userData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-primary border-3 mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <h4 class="fw-bold text-primary" id="display_name_avatar">{{ $userData['name'] ?? 'Nama Tidak Tersedia' }}</h4>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 mb-3">
                    <button id="editProfileBtn" class="btn btn-primary btn-lg rounded-pill px-5">Edit Profil</button>
                </div>
                <div class="text-center mt-3">
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill px-5">Log Out</button>
                    </form>
                </div>
            </div>

            {{-- Edit Profile Form Section --}}
            <div id="editProfileForm" style="display: none;">
                <form id="updateProfileForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-7">
                            <h5 class="fw-bold text-primary mb-3">Edit Profil</h5>
                            <div class="mb-3">
                                <label for="edit_name" class="form-label text-primary fw-bold">Nama</label>
                                <input type="text" class="form-control" id="edit_name" name="name" value="{{ $userData['name'] ?? '' }}" required style="background-color: #e8f1ff; border-radius: 10px;">
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label text-primary fw-bold">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="{{ $userData['email'] ?? '' }}" readonly style="background-color: #e8f1ff; border-radius: 10px;">
                                <small class="form-text text-muted">Email tidak dapat diubah di sini.</small>
                            </div>
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label text-primary fw-bold">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" value="{{ $userData['phone'] ?? '' }}" style="background-color: #e8f1ff; border-radius: 10px;">
                                <div class="invalid-feedback" id="phoneError"></div>
                            </div>

                            <hr class="my-4">

                            <h5 class="fw-bold text-primary mb-3">Ubah Password</h5>
                            <div class="mb-3">
                                <label for="edit_current_password" class="form-label text-primary fw-bold">Password Saat Ini</label>
                                <input type="password" class="form-control" id="edit_current_password" name="current_password" style="background-color: #e8f1ff; border-radius: 10px;">
                                <div class="invalid-feedback" id="current_passwordError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_new_password" class="form-label text-primary fw-bold">Password Baru</label>
                                <input type="password" class="form-control" id="edit_new_password" name="new_password" style="background-color: #e8f1ff; border-radius: 10px;">
                                <div class="invalid-feedback" id="new_passwordError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_new_password_confirmation" class="form-label text-primary fw-bold">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="edit_new_password_confirmation" name="new_password_confirmation" style="background-color: #e8f1ff; border-radius: 10px;">
                                <div class="invalid-feedback" id="new_password_confirmationError"></div>
                            </div>
                        </div>
                        <div class="col-md-5 d-flex flex-column align-items-center justify-content-center">
                            <div class="text-center">
                                <div class="position-relative d-inline-block">
                                    <img id="editProfileAvatar" src="{{ $userData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-primary border-3 mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <button type="button" id="uploadAvatarBtn" class="btn btn-info btn-sm rounded-circle position-absolute bottom-0 end-0" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-camera"></i></button>
                                </div>
                                <h4 class="fw-bold text-primary" id="editProfileNameDisplay">{{ $userData['name'] ?? 'Nama Tidak Tersedia' }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary btn-lg rounded-pill px-5">Batal</button>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5" id="saveProfileBtn">Simpan</button>
                    </div>
                </form>
            </div>
        @else
            <p class="text-center text-muted">Gagal memuat data profil. Silakan coba lagi nanti.</p>
        @endif
    </div>
</div>

{{-- Modal untuk Upload Avatar --}}
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarModalLabel">Ubah Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="avatarUploadForm">
                    @csrf
                    <div class="mb-3">
                        <label for="avatar_url" class="form-label">Link Gambar Profil (URL)</label>
                        <input type="url" class="form-control" id="avatar_url" name="avatar_url" placeholder="https://example.com/your-image.jpg" required>
                        <div class="invalid-feedback" id="avatar_urlError"></div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveAvatarBtn">Simpan Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewProfileSection = document.getElementById('viewProfile');
        const editProfileFormSection = document.getElementById('editProfileForm');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const updateProfileForm = document.getElementById('updateProfileForm');
        const profileAjaxAlert = document.getElementById('profileAjaxAlert');
        const saveProfileBtn = document.getElementById('saveProfileBtn');

        const currentPasswordInput = document.getElementById('edit_current_password');
        const newPasswordInput = document.getElementById('edit_new_password');
        const newPasswordConfirmationInput = document.getElementById('edit_new_password_confirmation');

        const uploadAvatarBtn = document.getElementById('uploadAvatarBtn');
        const avatarModal = new bootstrap.Modal(document.getElementById('avatarModal'));
        const avatarUploadForm = document.getElementById('avatarUploadForm');
        const avatarUrlInput = document.getElementById('avatar_url');
        const editProfileAvatarImg = document.getElementById('editProfileAvatar');
        const viewProfileAvatarImg = document.getElementById('display_avatar');
        // Selektor untuk elemen di navbar utama (layouts.app)
        const navbarUserAvatarImg = document.querySelector('.navbar-user-custom .dropdown-toggle img');
        const navbarUserNameDisplay = document.querySelector('.navbar-user-custom .dropdown-toggle span');

        const editProfileNameDisplay = document.getElementById('editProfileNameDisplay');
        const viewProfileNameDisplay = document.getElementById('display_name_view');
        const viewProfileNameAvatarDisplay = document.getElementById('display_name_avatar');

        function showAlert(message, type) {
            profileAjaxAlert.textContent = message;
            profileAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            profileAjaxAlert.style.display = 'block';
            if (type !== 'info') {
                setTimeout(() => {
                    profileAjaxAlert.style.display = 'none';
                }, 5000);
            }
        }

        function clearValidationErrors() {
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        }

        function showValidationErrors(errors) {
            for (const field in errors) {
                if (field === 'message') {
                    showAlert(errors[field][0], 'danger');
                    continue;
                }

                let inputField = document.getElementById(`edit_${field}`);
                if (!inputField && field === 'new_password_confirmation') {
                    inputField = document.getElementById('edit_new_password_confirmation');
                } else if (!inputField && field === 'current_password') {
                    inputField = document.getElementById('edit_current_password');
                } else if (!inputField && field === 'new_password') {
                    inputField = document.getElementById('edit_new_password');
                } else if (!inputField && field === 'avatar_url') {
                    inputField = document.getElementById('avatar_url');
                }

                if (inputField) {
                    inputField.classList.add('is-invalid');
                    const errorDiv = document.getElementById(`${field}Error`);
                    if (errorDiv) {
                        errorDiv.textContent = errors[field][0];
                    } else {
                        showAlert(errors[field][0], 'danger');
                    }
                } else {
                    showAlert(errors[field][0], 'danger');
                }
            }
        }

        // --- Logic Mode View/Edit ---
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', function() {
                viewProfileSection.style.display = 'none';
                editProfileFormSection.style.display = 'block';
                clearValidationErrors();
                showAlert('', '');

                document.getElementById('edit_name').value = viewProfileNameDisplay.value.trim();
                document.getElementById('edit_email').value = document.getElementById('display_email').value;
                document.getElementById('edit_phone').value = document.getElementById('display_phone').value;
                document.getElementById('editProfileAvatar').src = viewProfileAvatarImg.src;
                editProfileNameDisplay.textContent = viewProfileNameDisplay.value.trim();

                currentPasswordInput.value = '';
                newPasswordInput.value = '';
                newPasswordConfirmationInput.value = '';
            });
        }

        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function() {
                editProfileFormSection.style.display = 'none';
                viewProfileSection.style.display = 'block';
                clearValidationErrors();
                showAlert('', '');
            });
        }

        // --- Logic Submit Form Update Profil (Data & Password) ---
        if (updateProfileForm) {
            updateProfileForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                clearValidationErrors();
                showAlert('', '');

                saveProfileBtn.disabled = true;
                showAlert('Sedang memperbarui profil...', 'info');

                const name = document.getElementById('edit_name').value;
                const phone = document.getElementById('edit_phone').value;
                const currentPassword = currentPasswordInput.value;
                const newPassword = newPasswordInput.value;
                const newPasswordConfirmation = newPasswordConfirmationInput.value;

                let updateProfileDataSuccess = true;
                let updatePasswordProcess = false;
                let updatePasswordSuccess = true;
                let finalMessage = 'Profil berhasil diperbarui.';
                let finalType = 'success';
                let updatedUserData = {};

                // --- Bagian 1: Update Data Profil (Nama, Phone) ---
                try {
                    const profileUpdatePayload = { name, phone };

                    const responseData = await fetch('{{ route('profile.updateData') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(profileUpdatePayload)
                    });

                    const data = await responseData.json();

                    if (!responseData.ok) {
                        updateProfileDataSuccess = false;
                        showValidationErrors(data.errors || { general: [data.message || 'Gagal memperbarui data profil.'] });
                        finalMessage = data.message || 'Gagal memperbarui data profil.';
                        finalType = 'danger';
                    } else {
                        updatedUserData = data.userData;
                        viewProfileNameDisplay.value = updatedUserData.name ?? 'Nama Tidak Tersedia';
                        viewProfileNameAvatarDisplay.textContent = updatedUserData.name ?? 'Nama Tidak Tersedia';
                        editProfileNameDisplay.textContent = updatedUserData.name ?? 'Nama Tidak Tersedia';
                        // Memperbarui nama di navbar utama dari layouts.app
                        navbarUserNameDisplay.textContent = updatedUserData.name ?? 'User';
                        document.getElementById('display_phone').value = updatedUserData.phone ?? '+62 XXXXXXXXXX';
                    }
                } catch (error) {
                    updateProfileDataSuccess = false;
                    finalMessage = 'Terjadi kesalahan jaringan saat memperbarui data profil.';
                    finalType = 'danger';
                    console.error('Error updating profile data:', error);
                }

                // --- Bagian 2: Update Password (Hanya jika ada perubahan password yang diminta) ---
                let hasPasswordFieldsFilled = currentPassword.length > 0 || newPassword.length > 0 || newPasswordConfirmation.length > 0;

                if (hasPasswordFieldsFilled) {
                    updatePasswordProcess = true;
                    if (newPassword !== newPasswordConfirmation) {
                        showValidationErrors({new_password_confirmation: ['Konfirmasi password baru tidak cocok!']});
                        updatePasswordSuccess = false;
                    } else if (newPassword.length < 8) {
                        showValidationErrors({new_password: ['Password baru minimal 8 karakter.']});
                        updatePasswordSuccess = false;
                    } else if (currentPassword.length === 0) {
                        showValidationErrors({current_password: ['Password saat ini harus diisi untuk mengubah password.']});
                        updatePasswordSuccess = false;
                    }

                    if (updatePasswordSuccess) {
                        try {
                            const responsePassword = await fetch('{{ route('profile.updatePassword') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    current_password: currentPassword,
                                    new_password: newPassword,
                                    new_password_confirmation: newPasswordConfirmation
                                })
                            });

                            const dataPassword = await responsePassword.json();

                            if (!responsePassword.ok) {
                                updatePasswordSuccess = false;
                                showValidationErrors(dataPassword.errors || { general: [dataPassword.message || 'Gagal memperbarui password.'] });
                                if (updateProfileDataSuccess) {
                                    finalMessage = (dataPassword.message || 'Gagal memperbarui password.') + ' Namun, data profil berhasil diperbarui.';
                                    finalType = 'warning';
                                } else {
                                    finalMessage = (finalMessage === 'Profil berhasil diperbarui.' ? '' : finalMessage + ' | ') + (dataPassword.message || 'Gagal memperbarui password.');
                                    finalType = 'danger';
                                }
                            } else {
                                currentPasswordInput.value = '';
                                newPasswordInput.value = '';
                                newPasswordConfirmationInput.value = '';
                                if (updateProfileDataSuccess) {
                                    finalMessage = 'Profil dan password berhasil diperbarui.';
                                } else {
                                    finalMessage = dataPassword.message || 'Password berhasil diperbarui.';
                                }
                            }
                        } catch (error) {
                            updatePasswordSuccess = false;
                            console.error('Error updating password:', error);
                            if (updateProfileDataSuccess) {
                                finalMessage = 'Data profil berhasil diperbarui, namun terjadi kesalahan jaringan saat memperbarui password.';
                                finalType = 'warning';
                            } else {
                                finalMessage = 'Terjadi kesalahan jaringan saat memperbarui password. ' + (finalMessage === 'Profil berhasil diperbarui.' ? '' : finalMessage);
                                finalType = 'danger';
                            }
                        }
                    } else {
                        if (updateProfileDataSuccess) {
                            finalMessage = 'Data profil berhasil diperbarui, namun password gagal diperbarui karena kesalahan input.';
                            finalType = 'warning';
                        }
                    }
                }

                // --- Final Feedback dan Aksi ---
                if (updateProfileDataSuccess || updatePasswordProcess) {
                    showAlert(finalMessage, finalType);
                }

                if (updateProfileDataSuccess && (!hasPasswordFieldsFilled || (hasPasswordFieldsFilled && updatePasswordSuccess))) {
                    setTimeout(() => {
                        cancelEditBtn.click();
                    }, 1000);
                }
                saveProfileBtn.disabled = false;
            });
        }

        // --- Logic untuk Modal Upload Avatar ---
        if (uploadAvatarBtn) {
            uploadAvatarBtn.addEventListener('click', function() {
                avatarModal.show();
                avatarUrlInput.value = editProfileAvatarImg.src;
                clearValidationErrors();
            });
        }

        if (avatarUploadForm) {
            avatarUploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearValidationErrors();
                showAlert('', '');

                const newAvatarUrl = avatarUrlInput.value.trim();
                if (!newAvatarUrl) {
                    showValidationErrors({avatar_url: ['Link gambar tidak boleh kosong.']});
                    return;
                }

                document.getElementById('saveAvatarBtn').disabled = true;
                showAlert('Mengunggah foto profil...', 'info');

                try {
                    const response = await fetch('{{ route('profile.updateAvatar') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ avatar_url: newAvatarUrl })
                    });

                    const data = await response.json();
                    showAlert('', '');

                    if (response.ok && data.success) {
                        showAlert(data.message, 'success');
                        const updatedAvatarUrl = data.avatar_url;
                        viewProfileAvatarImg.src = updatedAvatarUrl;
                        editProfileAvatarImg.src = updatedAvatarUrl;
                        // Memperbarui avatar di navbar utama dari layouts.app
                        navbarUserAvatarImg.src = updatedAvatarUrl;

                        avatarModal.hide();
                    } else {
                        showValidationErrors(data.errors || { general: [data.message || 'Gagal memperbarui foto profil.'] });
                        showAlert(data.message || 'Gagal memperbarui foto profil.', 'danger');
                    }
                } catch (error) {
                    console.error('Error updating avatar:', error);
                    showAlert('Terjadi kesalahan jaringan atau server saat memperbarui foto profil.', 'danger');
                } finally {
                    document.getElementById('saveAvatarBtn').disabled = false;
                }
            });
        }
    });
</script>
@endsection