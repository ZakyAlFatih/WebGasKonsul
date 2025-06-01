<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - GasKonsul</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #e0f0ff;
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .main-content {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            padding: 60px;
            background-color: #2196f3;
            color: white;
        }
        .main-content h2 {
            font-weight: bold;
        }
        .promo-section {
            background-color: #b3d9ff;
            padding: 50px;
            text-align: center;
            color: #333;
        }
        .promo-section h3 {
            font-weight: bold;
        }
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        /* Style untuk invalid-feedback di modal register */
        .modal .invalid-feedback {
            display: block; /* Pastikan pesan error Bootstrap terlihat */
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light px-4">
    <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" />
        <span class="fw-bold text-primary">GasKonsul</span>
    </a>
    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Register</button>
</nav>

<div class="main-content">
    <div>
        <h2>Empowering<br />Your Journey</h2>
        <p class="mt-3">
            Terhubung dengan konselor profesional untuk mendapatkan dukungan akademik, karier, dan kesehatan mental. Konseling waktu nyata dan rekomendasi yang dipersonalisasi di ujung jari Anda.
        </p>
    </div>
    <img src="{{ asset('images/ilust.png') }}" alt="Illustration" style="max-width: 45%;" />
</div>

<div class="promo-section">
    <h3>Try Our New Mobile App</h3>
    <p class="mt-3">
        Jangan ketinggalan! Mulai langkah pertama menuju masa depan yang lebih cerah dengan GasKonsul hari ini!
    </p>
    <img src="{{ asset('images/frame.png') }}" alt="Mobile App Preview" style="max-width: 200px;" class="mt-4" />
</div>

<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="loginModalLabel">Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="firebaseLoginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" class="form-control" required autofocus />
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" class="form-control" required />
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary" id="loginSubmitBtn">Login</button>
                    </div>
                    <div class="text-center">
                        <span class="small">No Account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Sign Up</a></span>
                    </div>
                </form>
                <div id="loginResponse" class="text-center mt-2 text-danger small"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerRoleModal" tabindex="-1" aria-labelledby="registerRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4 rounded-4 text-center">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold w-100" id="registerRoleModalLabel">Daftar Sebagai Apa?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <p>Pilih peran Anda untuk melanjutkan pendaftaran:</p>
                <div class="d-grid gap-2 mt-4">
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerCounselorModal">Counselor</button>
                    <button type="button" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerUserModal">User</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerUserModal" tabindex="-1" aria-labelledby="registerUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="registerUserModalLabel">Daftar sebagai User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="registerUserForm">
                    @csrf
                    <div class="mb-3">
                        <label for="register_user_name" class="form-label">Nama Lengkap</label>
                        <input id="register_user_name" type="text" class="form-control" name="name" required autofocus />
                        <div class="invalid-feedback" id="register_user_nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_user_email" class="form-label">Email Address</label>
                        <input id="register_user_email" type="email" class="form-control" name="email" required />
                        <div class="invalid-feedback" id="register_user_emailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_user_password" class="form-label">Buat Password</label>
                        <input id="register_user_password" type="password" class="form-control" name="password" required />
                        <div class="invalid-feedback" id="register_user_passwordError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_user_password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input id="register_user_password_confirmation" type="password" class="form-control" name="password_confirmation" required />
                        <div class="invalid-feedback" id="register_user_password_confirmationError"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="register_user_terms" name="terms" value="1" required />
                        <label class="form-check-label" for="register_user_terms">
                            Saya telah membaca dan menyetujui <a href="#">Syarat dan Ketentuan</a> serta <a href="#">Kebijakan Privasi</a>.
                        </label>
                        <div class="invalid-feedback" id="register_user_termsError"></div>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary" id="registerUserSubmitBtn">Daftar User</button>
                    </div>
                </form>
                <div id="registerUserResponse" class="text-center mt-2 small"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerCounselorModal" tabindex="-1" aria-labelledby="registerCounselorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="registerCounselorModalLabel">Daftar sebagai Counselor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="registerCounselorForm">
                    @csrf
                    <div class="mb-3">
                        <label for="register_counselor_name" class="form-label">Nama Lengkap</label>
                        <input id="register_counselor_name" type="text" class="form-control" name="name" required autofocus />
                        <div class="invalid-feedback" id="register_counselor_nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_counselor_email" class="form-label">Email Address</label>
                        <input id="register_counselor_email" type="email" class="form-control" name="email" required />
                        <div class="invalid-feedback" id="register_counselor_emailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_counselor_password" class="form-label">Buat Password</label>
                        <input id="register_counselor_password" type="password" class="form-control" name="password" required />
                        <div class="invalid-feedback" id="register_counselor_passwordError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_counselor_password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input id="register_counselor_password_confirmation" type="password" class="form-control" name="password_confirmation" required />
                        <div class="invalid-feedback" id="register_counselor_password_confirmationError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_counselor_bidang" class="form-label">Spesialisasi Konseling</label>
                        <input id="register_counselor_bidang" type="text" class="form-control" name="bidang" placeholder="Contoh: Karier, Kesehatan Mental" required />
                        <div class="invalid-feedback" id="register_counselor_bidangError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="register_counselor_license" class="form-label">Nomor Lisensi (Opsional)</label>
                        <input id="register_counselor_license" type="text" class="form-control" name="license" />
                        <div class="invalid-feedback" id="register_counselor_licenseError"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="register_counselor_terms" name="terms" value="1" required />
                        <label class="form-check-label" for="register_counselor_terms">
                            Saya telah membaca dan menyetujui <a href="#">Syarat dan Ketentuan</a> serta <a href="#">Kebijakan Privasi</a>.
                        </label>
                        <div class="invalid-feedback" id="register_counselor_termsError"></div>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary" id="registerCounselorSubmitBtn">Daftar Counselor</button>
                    </div>
                </form>
                <div id="registerCounselorResponse" class="text-center mt-2 small"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-auth-compat.js"></script>


<script>
    // Konfigurasi Firebase
    const firebaseConfig = {
        apiKey: "AIzaSyAWFHOHpnWHn6LQ8hcfo8qJr1Ug5Rscs7E",
        authDomain: "PROJECT_ID.firebaseapp.com",
        projectId: "PROJECT_ID",
    };
    // Inisialisasi Firebase
    firebase.initializeApp(firebaseConfig);

    // Dapatkan instance auth
    const auth = firebase.auth();

    // Fungsi untuk membersihkan error validasi di modal
    function clearModalValidationErrors(formId) {
      document.querySelectorAll(`#${formId} .is-invalid`).forEach(el => {
          el.classList.remove('is-invalid');
      });
      document.querySelectorAll(`#${formId} .invalid-feedback`).forEach(el => {
          el.textContent = '';
      });

      const responseBoxId = formId.replace('Form', '') + 'Response'; // Akan menjadi 'registerUserResponse' atau 'registerCounselorResponse'
      const responseBox = document.getElementById(responseBoxId); // Gunakan getElementById karena lebih efisien untuk ID
      if (responseBox) { // Tambahkan pengecekan null safety
          responseBox.innerText = '';
          responseBox.classList.remove('text-success', 'text-danger', 'text-info'); // Tambahkan 'text-info'
      } else {
          console.warn(`Elemen response box dengan ID ${responseBoxId} tidak ditemukan.`);
      }
    }

    // Fungsi untuk menampilkan error validasi dari Laravel di modal
    function showModalValidationErrors(formId, errors) {
      const responseBoxId = formId.replace('Form', '') + 'Response';
      const responseBox = document.getElementById(responseBoxId);
      if (!responseBox) {
          console.warn(`Elemen response box dengan ID ${responseBoxId} tidak ditemukan untuk menampilkan error.`);
          return; // Hentikan fungsi jika elemen tidak ditemukan
      }

      responseBox.classList.add('text-danger');

      let generalErrorMessage = [];

      for (const field in errors) {
          // Selektor inputField ini juga perlu penyesuaian jika formId.replace('Form', '') tidak diterapkan
          const inputIdPrefix = formId.replace('Form', ''); // misal: 'registerUser'
          const inputField = document.querySelector(`#${formId} #${inputIdPrefix}_${field}`);

          if (inputField) {
              inputField.classList.add('is-invalid');
              const errorDiv = document.querySelector(`#${formId} #${inputIdPrefix}_${field}Error`);
              if (errorDiv) {
                  errorDiv.textContent = errors[field][0];
              } else {
                  generalErrorMessage.push(errors[field][0]);
              }
          } else {
              generalErrorMessage.push(errors[field][0]);
          }
      }
      if (generalErrorMessage.length > 0) {
          responseBox.innerText = generalErrorMessage.join('; ');
      } else {
          responseBox.innerText = 'Terdapat kesalahan input.';
      }
    }


    // Event handler form login Firebase
    document.getElementById('firebaseLoginForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const responseBox = document.getElementById('loginResponse');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');

        responseBox.innerText = '';
        responseBox.classList.remove('text-success', 'text-danger');
        responseBox.classList.add('text-danger');

        loginSubmitBtn.disabled = true;
        responseBox.innerText = 'Sedang memproses login...';

        try {
            const userCredential = await auth.signInWithEmailAndPassword(email, password);
            const idToken = await userCredential.user.getIdToken();

            const res = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ idToken })
            });

            const data = await res.json();

            if (res.ok) {
                responseBox.classList.remove('text-danger');
                responseBox.classList.add('text-success');
                responseBox.innerText = data.message || 'Login berhasil';

                setTimeout(() => {
                    if (data.role === 'counselor') {
                        window.location.href = '{{ route("counselor.dashboard") }}';
                    } else {
                        window.location.href = '/home';
                    }
                }, 1000);
            } else {
                responseBox.innerText = data.error || 'Login gagal, coba lagi.';
                console.error("Backend Error:", data.error);
            }
        } catch (error) {
            const errorCode = error.code;
            const errorMessage = error.message;

            let userFriendlyMessage = "Terjadi kesalahan yang tidak diketahui.";

            switch (errorCode) {
                case 'auth/invalid-login-credentials':
                case 'auth/invalid-credential':
                case 'auth/wrong-password':
                case 'auth/user-not-found':
                case 'auth/invalid-email':
                    userFriendlyMessage = "Email atau password salah.";
                    break;
                case 'auth/user-disabled':
                    userFriendlyMessage = "Akun Anda telah dinonaktifkan. Silakan hubungi administrator.";
                    break;
                case 'auth/too-many-requests':
                    userFriendlyMessage = "Terlalu banyak percobaan login gagal. Silakan coba lagi nanti.";
                    break;
                default:
                    userFriendlyMessage = "Login gagal. Mohon periksa kembali kredensial Anda.";
                    console.error("Firebase Auth Client Error:", errorCode, errorMessage);
            }
            responseBox.innerText = userFriendlyMessage;
        } finally {
            loginSubmitBtn.disabled = false;
        }
    });

    // Event handler form Register User
    document.getElementById('registerUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        clearModalValidationErrors('registerUserForm');

        const name = document.getElementById('register_user_name').value.trim();
        const email = document.getElementById('register_user_email').value.trim();
        const password = document.getElementById('register_user_password').value.trim();
        const passwordConfirmation = document.getElementById('register_user_password_confirmation').value.trim();
        const termsAccepted = document.getElementById('register_user_terms').checked;
        const responseBox = document.getElementById('registerUserResponse');
        const submitBtn = document.getElementById('registerUserSubmitBtn');

        submitBtn.disabled = true;
        responseBox.innerText = 'Sedang mendaftar...';
        responseBox.classList.remove('text-success', 'text-danger');
        responseBox.classList.add('text-info'); // Loading color

        try {
            const res = await fetch('{{ route('register.user.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password,
                    password_confirmation: passwordConfirmation,
                    terms: termsAccepted ? '1' : '', // Kirim '1' jika dicentang, kosong jika tidak
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                responseBox.classList.remove('text-info', 'text-danger');
                responseBox.classList.add('text-success');
                responseBox.innerText = data.message || 'Pendaftaran berhasil!';
                // Kosongkan form setelah sukses
                document.getElementById('registerUserForm').reset();
                // Opsional: Tutup modal register user dan buka modal login
                setTimeout(() => {
                    const registerUserModal = bootstrap.Modal.getInstance(document.getElementById('registerUserModal'));
                    registerUserModal.hide();
                    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                    document.getElementById('loginResponse').innerText = data.message || 'Pendaftaran berhasil! Silakan login.';
                    document.getElementById('loginResponse').classList.remove('text-danger');
                    document.getElementById('loginResponse').classList.add('text-success');
                }, 1500);
            } else {
                responseBox.classList.remove('text-info', 'text-success');
                responseBox.classList.add('text-danger');
                if (data.errors) {
                    showModalValidationErrors('registerUserForm', data.errors);
                } else {
                    responseBox.innerText = data.message || 'Pendaftaran gagal, coba lagi.';
                }
            }
        } catch (error) {
            responseBox.classList.remove('text-info', 'text-success');
            responseBox.classList.add('text-danger');
            responseBox.innerText = 'Terjadi kesalahan jaringan atau server.';
            console.error('Error during user registration:', error);
        } finally {
            submitBtn.disabled = false;
        }
    });

    // Event handler form Register Counselor
    document.getElementById('registerCounselorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        clearModalValidationErrors('registerCounselorForm');

        const name = document.getElementById('register_counselor_name').value.trim();
        const email = document.getElementById('register_counselor_email').value.trim();
        const password = document.getElementById('register_counselor_password').value.trim();
        const passwordConfirmation = document.getElementById('register_counselor_password_confirmation').value.trim();
        const bidang = document.getElementById('register_counselor_bidang').value.trim();
        const license = document.getElementById('register_counselor_license').value.trim();
        const termsAccepted = document.getElementById('register_counselor_terms').checked;
        const responseBox = document.getElementById('registerCounselorResponse');
        const submitBtn = document.getElementById('registerCounselorSubmitBtn');

        submitBtn.disabled = true;
        responseBox.innerText = 'Sedang mendaftar...';
        responseBox.classList.remove('text-success', 'text-danger');
        responseBox.classList.add('text-info'); // Loading color

        try {
            const res = await fetch('{{ route('register.counselor.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password,
                    password_confirmation: passwordConfirmation,
                    bidang: bidang,
                    license: license,
                    terms: termsAccepted ? '1' : '',
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                responseBox.classList.remove('text-info', 'text-danger');
                responseBox.classList.add('text-success');
                responseBox.innerText = data.message || 'Pendaftaran berhasil!';
                // Kosongkan form setelah sukses
                document.getElementById('registerCounselorForm').reset();
                // Opsional: Tutup modal register counselor dan buka modal login
                setTimeout(() => {
                    const registerCounselorModal = bootstrap.Modal.getInstance(document.getElementById('registerCounselorModal'));
                    registerCounselorModal.hide();
                    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                    document.getElementById('loginResponse').innerText = data.message || 'Pendaftaran berhasil! Silakan login.';
                    document.getElementById('loginResponse').classList.remove('text-danger');
                    document.getElementById('loginResponse').classList.add('text-success');
                }, 1500);
            } else {
                responseBox.classList.remove('text-info', 'text-success');
                responseBox.classList.add('text-danger');
                if (data.errors) {
                    showModalValidationErrors('registerCounselorForm', data.errors);
                } else {
                    responseBox.innerText = data.message || 'Pendaftaran gagal, coba lagi.';
                }
            }
        } catch (error) {
            responseBox.classList.remove('text-info', 'text-success');
            responseBox.classList.add('text-danger');
            responseBox.innerText = 'Terjadi kesalahan jaringan atau server.';
            console.error('Error during counselor registration:', error);
        } finally {
            submitBtn.disabled = false;
        }
    });


    document.addEventListener('DOMContentLoaded', function() {
        const loginModalInstance = new bootstrap.Modal(document.getElementById('loginModal'));
        const registerRoleModalInstance = new bootstrap.Modal(document.getElementById('registerRoleModal'));
        const registerUserModalInstance = new bootstrap.Modal(document.getElementById('registerUserModal'));
        const registerCounselorModalInstance = new bootstrap.Modal(document.getElementById('registerCounselorModal'));

        // Handle opening register modals from role selection
        document.querySelector('button[data-bs-target="#registerUserModal"]').addEventListener('click', function() {
            registerRoleModalInstance.hide(); // Sembunyikan modal role
            registerUserModalInstance.show(); // Tampilkan modal user
            clearModalValidationErrors('registerUserForm'); // Bersihkan error saat membuka
        });
        document.querySelector('button[data-bs-target="#registerCounselorModal"]').addEventListener('click', function() {
            registerRoleModalInstance.hide(); // Sembunyikan modal role
            registerCounselorModalInstance.show(); // Tampilkan modal counselor
            clearModalValidationErrors('registerCounselorForm'); // Bersihkan error saat membuka
        });

        // Handle opening register role modal from login modal's "Sign Up" link
        document.querySelector('#loginModal a[data-bs-target="#registerRoleModal"]').addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link default
            loginModalInstance.hide(); // Sembunyikan modal login
            registerRoleModalInstance.show(); // Tampilkan modal role
        });


        // Tampilkan modal login jika ada pesan sukses atau error dari redirect Laravel
        @if(session('success') || session('error'))
            loginModalInstance.show();
            const responseBox = document.getElementById('loginResponse');
            @if(session('error'))
                responseBox.innerText = "{{ session('error') }}";
                responseBox.classList.add('text-danger');
            @elseif(session('success'))
                responseBox.innerText = "{{ session('success') }}";
                responseBox.classList.remove('text-danger');
                responseBox.classList.add('text-success');
            @endif
            responseBox.style.display = 'block';
        @endif
    });
</script>

</body>
</html>