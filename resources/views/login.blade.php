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

<!-- Modal Login -->
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
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
            <div class="text-center">
                <a href="#" class="small">Forgot password?</a><br />
<span class="small">No Account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Sign Up</a></span>            </div>
        </form>
        <div id="loginResponse" class="text-center mt-2 text-danger small"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Register -->
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
          <a href="{{ route('register.counselor') }}" class="btn btn-primary btn-lg">Counselor</a>
          <a href="{{ route('register.user') }}" class="btn btn-outline-primary btn-lg">User</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-auth-compat.js"></script>


<script>
  // Konfigurasi Firebase (ganti dengan config projectmu)
  const firebaseConfig = {
    apiKey: "AIzaSyAWFHOHpnWHn6LQ8hcfo8qJr1Ug5Rscs7E",
    authDomain: "PROJECT_ID.firebaseapp.com",
    projectId: "PROJECT_ID",
  };
  // Inisialisasi Firebase
  firebase.initializeApp(firebaseConfig);

  // Event handler form login Firebase
  document.getElementById('firebaseLoginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const responseBox = document.getElementById('loginResponse');
    
    responseBox.innerText = ''; // Kosongkan pesan sebelumnya
    responseBox.classList.remove('text-success', 'text-danger');
    responseBox.classList.add('text-danger');

    try {
      // Sign in dengan Firebase Authentication
      const userCredential = await firebase.auth().signInWithEmailAndPassword(email, password);
      const idToken = await userCredential.user.getIdToken();

      // Kirim token ke backend Laravel via fetch
      const res = await fetch('/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ idToken })
      });

      const data = await res.json();

      if (res.ok) {
        // Login berhasil
        responseBox.classList.remove('text-danger');
        responseBox.classList.add('text-success');
        responseBox.innerText = data.message || 'Login berhasil';

        // Redirect sesuai role user
        setTimeout(() => {
          if (data.role === 'counselor') {
            window.location.href = '/chat-counselor';
          } else {
            window.location.href = '/home';
          }
        }, 1000);
      } else {
        responseBox.innerText = data.error || 'Login gagal, coba lagi.';
      }
    } catch (error) {
      responseBox.innerText = error.message;
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));

        @if(session('success') || session('error'))
            loginModal.show();
        @endif
    });
</script>

</body>
</html>
