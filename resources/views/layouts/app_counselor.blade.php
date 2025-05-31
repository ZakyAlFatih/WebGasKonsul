<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Konselor') - GasKonsul</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- <link href="{{ asset('css/counselor_dashboard.css') }}" rel="stylesheet"> --}}

    <style>
        /* Style untuk navbar konselor kustom */
        .navbar-counselor-custom {
            background-color: #e7f5ff; /* GANTI #e7f5ff DENGAN KODE HEX BIRU MUDA PILIHAN ANDA */
                                    /* Contoh lain: #add8e6 (lightblue), #b0e0e6 (powderblue), #cfe2ff (Bootstrap-like light blue) */
        }

        /* Penyesuaian warna teks di navbar agar kontras dengan background biru muda (OPSIONAL) */
        .navbar-counselor-custom .navbar-brand .text-primary {
            /* Biarkan default atau sesuaikan jika kurang kontras */
        }
        .navbar-counselor-custom .navbar-nav .nav-link.text-dark {
            color: #343a40 !important; /* Warna teks gelap agar lebih terbaca, sesuaikan jika perlu */
        }
        .navbar-counselor-custom .navbar-nav .nav-link.active.fw-bold.text-primary {
            /* Warna untuk link aktif, pastikan kontras. Default text-primary mungkin sudah cukup. */
            /* color: #0056b3 !important; */ /* Contoh jika ingin warna biru yang lebih tua untuk aktif */
        }
        .navbar-counselor-custom .dropdown-toggle .text-dark {
            color: #343a40 !important; /* Warna untuk nama user di dropdown */
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top navbar-counselor-custom">
        <div class="container-fluid px-4">
            {{-- 1. Brand/Logo (Tetap di kiri) --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('counselor.dashboard') }}">
                <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="GasKonsul Logo" style="height: 30px;" class="me-2">
                <span class="fw-bold text-primary">GasKonsul</span>
            </a>

            {{-- Tombol Toggler untuk Mobile --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#counselorNavbarContent" aria-controls="counselorNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="counselorNavbarContent">
                {{-- 2. Link Navigasi Utama (Chat, Profil) - Pindah ke sini agar setelah logo --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0"> {{-- Gunakan me-auto agar item berikutnya (dropdown user) terdorong ke kanan --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('counselor.chat') || request()->routeIs('counselor.chat.*') || request()->routeIs('counselor.dashboard') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('counselor.chat') }}"> {{-- Mengarah ke daftar chat konselor --}}
                           Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('counselor.profile.*') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('counselor.profile.show') }}">
                           Profil
                        </a>
                    </li>
                </ul>

                {{-- 3. Dropdown User dengan Foto Profil (Tetap di kanan) --}}
                <ul class="navbar-nav ms-auto"> {{-- ms-auto mendorong ini ke paling kanan --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="counselorProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ Session::get('userAvatar', asset('images/default_profile.png')) }}"
                                 alt="Avatar {{ Session::get('userName', 'Konselor') }}"
                                 class="rounded-circle me-2"
                                 style="width: 32px; height: 32px; object-fit: cover;">
                            <span class="text-dark">{{ Session::get('userName', 'Konselor') }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="counselorProfileDropdown">
                            <li><a class="dropdown-item" href="{{ route('counselor.profile.show') }}">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="counselor-logout-form" style="display: inline;">
                                    @csrf
                                    <a class="dropdown-item" href="#"
                                       onclick="event.preventDefault(); document.getElementById('counselor-logout-form').submit();">
                                        Logout
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-4 py-4"> {{-- Memberi sedikit margin atas pada konten utama --}}
        @yield('content')
    </main>

    <footer class="text-center mt-auto py-3 bg-light"> {{-- mt-auto untuk mendorong footer ke bawah jika konten pendek --}}
        <p class="mb-0">&copy; {{ date('Y') }} GasKonsul. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>