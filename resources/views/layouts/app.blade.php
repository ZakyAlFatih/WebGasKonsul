<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'GasKonsul')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #EAF2FF;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-user-custom {
            background-color: #C7E3FF;
            border-bottom: 1px solid #d0e9ff;
        }

        /* Penyesuaian warna teks di navbar agar kontras */
        .navbar-user-custom .navbar-brand .text-primary {
            color: #0056b3 !important; /* Warna biru yang lebih gelap untuk brand */
        }
        .navbar-user-custom .navbar-nav .nav-link.text-dark {
            color: #343a40 !important; /* Warna teks gelap agar lebih terbaca */
        }
        .navbar-user-custom .navbar-nav .nav-link.active.fw-bold.text-primary {
            color: #007bff !important; /* Warna biru Bootstrap untuk link aktif */
        }
        .navbar-user-custom .dropdown-toggle .text-dark {
            color: #343a40 !important; /* Warna untuk nama user di dropdown */
        }

        /* Pastikan container utama tidak terpengaruh oleh centering body style sebelumnya */
        main.container-fluid {
            flex-grow: 1; /* Memungkinkan main content mengambil ruang yang tersedia */
        }
    </style>

    @stack('styles')
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top navbar-user-custom">
        <div class="container-fluid px-4">
            {{-- 1. Brand/Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="GasKonsul Logo" style="height: 30px;" class="me-2">
                <span class="fw-bold text-primary">GasKonsul</span>
            </a>

            {{-- Tombol Toggler untuk Mobile --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbarContent" aria-controls="userNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="userNavbarContent">
                {{-- 2. Link Navigasi Utama (Beranda, Riwayat, Chat, Profil) --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('home') }}">
                            Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('history') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('history') }}">
                            Riwayat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('chat') || request()->routeIs('chat.show') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('chat') }}">
                            Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('profile') ? 'active fw-bold text-primary' : 'text-dark' }}"
                           href="{{ route('profile') }}">
                            Profil
                        </a>
                    </li>
                </ul>

                {{-- 3. Dropdown User dengan Foto Profil --}}
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ Session::get('userAvatar', asset('images/default_profile.png')) }}"
                                 alt="Avatar {{ Session::get('userName', 'User') }}"
                                 class="rounded-circle me-2"
                                 style="width: 32px; height: 32px; object-fit: cover;">
                            <span class="text-dark">{{ Session::get('userName', 'User') }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userProfileDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile') }}">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="user-logout-form" style="display: inline;">
                                    @csrf
                                    <a class="dropdown-item" href="#"
                                       onclick="event.preventDefault(); document.getElementById('user-logout-form').submit();">
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

    <footer class="text-center mt-auto py-3 bg-light">
        <p class="mb-0">&copy; {{ date('Y') }} GasKonsul. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
