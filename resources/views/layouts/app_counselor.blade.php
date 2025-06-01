<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- Penting untuk AJAX POST --}}
    <title>@yield('title', 'Area Konselor') - GasKonsul</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome jika belum ada (untuk ikon di chat detail, profil, dll.) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <style>
        body {
            background-color: #EAF2FF; /* Warna latar halaman utama (Contoh: AliceBlue). Sesuaikan jika perlu. */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Style untuk navbar konselor kustom */
        .navbar-counselor-custom {
            background-color: #C7E3FF; /* WARNA BIRU MUDA YANG ANDA SET SEBELUMNYA */
        }

        /* .navbar-counselor-custom .navbar-brand .text-primary { color: #0a58ca !important; } */
        
        .navbar-counselor-custom .navbar-nav .nav-link.text-dark { 
            /* color: #343a40 !important;  */
       
        }
        /* Atau jika Anda ingin warna default untuk link tidak aktif: */
        /* .navbar-counselor-custom .navbar-nav .nav-link {
            color: #003d7a !important; // Contoh biru tua untuk teks
        } */

        .navbar-counselor-custom .navbar-nav .nav-link.active.fw-bold.text-primary {
            /* Warna default text-primary Bootstrap biasanya sudah baik untuk link aktif.
               Jika ingin warna lain saat aktif: */
            /* color: #0056b3 !important; // Contoh biru yang lebih tua dan tebal */
        }
        
        .navbar-counselor-custom .nav-link.dropdown-toggle .text-dark { /* Jika nama user pakai class text-dark */
             color: #212529 !important; /* Pastikan kontras dengan background biru muda navbar */
        }
        /* Jika nama user tidak pakai text-dark, Anda bisa target langsung dropdown-toggle nya */
        /* .navbar-counselor-custom .nav-link.dropdown-toggle {
            color: #212529 !important; 
        } */

    </style>
    @stack('styles') {{-- Untuk style spesifik dari view anak --}}
</head>
<body>
    {{-- Navbar Utama Konselor --}}
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top navbar-counselor-custom">
        <div class="container-fluid px-4">
            {{-- 1. Brand/Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('counselor.dashboard') }}">
                <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="GasKonsul Logo" style="height: 30px;" class="me-2">
                <span class="fw-bold text-primary">GasKonsul</span>
            </a>

            {{-- Tombol Toggler untuk Mobile --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#counselorNavbarContent" aria-controls="counselorNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="counselorNavbarContent">
                {{-- 2. Link Navigasi Utama (Chat, Profil) - di sebelah kanan logo --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0"> {{-- me-auto akan mendorong elemen berikutnya (dropdown user) ke kanan --}}
                    <li class="nav-item">
                        {{-- Kondisi highlight yang sudah diperbaiki --}}
                        <a class="nav-link {{ request()->routeIs('counselor.dashboard') || request()->routeIs('counselor.chat*') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('counselor.chat') }}">
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

                {{-- 3. Dropdown User dengan Foto Profil (di paling kanan) --}}
                <ul class="navbar-nav ms-auto"> {{-- ms-auto mendorong ini ke paling kanan --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="counselorProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ Session::get('userAvatar', asset('images/default_profile.png')) }}"
                                 alt="Avatar {{ Session::get('userName', 'Konselor') }}"
                                 class="rounded-circle me-2"
                                 style="width: 32px; height: 32px; object-fit: cover;">
                            {{-- Pastikan warna teks untuk nama user kontras dengan background navbar biru muda --}}
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

    {{-- Konten Utama Halaman --}}
    <main class="container-fluid py-4 flex-grow-1"> {{-- flex-grow-1 agar main content mengisi ruang --}}
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="text-center mt-auto py-3 bg-light">
        <p class="mb-0">&copy; {{ date('Y') }} GasKonsul. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts') {{-- Untuk script spesifik dari view anak --}}
</body>
</html>