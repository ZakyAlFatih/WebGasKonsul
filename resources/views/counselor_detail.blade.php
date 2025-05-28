@extends('layouts.app')

@section('title', 'Detail Konselor - GasKonsul')

@section('content')
<div class="container-fluid bg-light py-4" style="min-height: 100vh;">
    {{-- Navbar di Bagian Atas --}}
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('home') }}" class="btn btn-link text-primary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0">{{ $counselorData['name'] ?? 'Konselor' }}</h4>
        </div>
        <div class="d-flex align-items-center">
            {{-- Navigasi Lain --}}
            <a href="{{ route('home') }}" class="me-3 text-decoration-none text-dark">Beranda</a>
            <a href="{{ route('profile') }}" class="me-3 text-decoration-none text-dark">Profil</a>
            <a href="{{ route('history') }}" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="{{ route('chat') }}" class="me-3 text-decoration-none text-dark">Chat</a>
            {{-- Info User --}}
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                {{-- Menggunakan gambar default jika avatar user tidak tersedia --}}
                <img src="{{ Session::get('userAvatar') ?? asset('images/default_profile.png') }}" alt="User" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                <span class="ms-2">{{ Session::get('userName') ?? 'User' }}</span> {{-- Asumsi userName ada di session --}}
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
        {{-- Pesan flash dari controller atau redirect --}}
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
        <div id="counselorDetailAjaxAlert" class="alert mt-3 text-center" style="display: none;"></div>
    </div>

    {{-- Konten Utama Detail Konselor --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 700px;">
        @if(empty($counselorData) && !empty($errorMessage))
            <p class="text-center text-danger">{{ $errorMessage }}</p>
        @elseif(empty($counselorData))
            {{-- Tampilkan pesan loading jika data belum ada dan tidak ada error --}}
            <p class="text-center text-muted">Memuat data konselor...</p>
        @else
            {{-- Tampilan Profil Konselor --}}
            <div id="counselorProfileView" style="display: block;">
                <div class="text-center mb-4 pb-4 bg-primary text-white rounded-bottom-4" style="margin-top: -30px; border-top-left-radius: 45px !important; border-top-right-radius: 45px !important; border-bottom-left-radius: 45px !important; border-bottom-right-radius: 45px !important;">
                    <img src="{{ $counselorData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-white border-3 mb-3" style="width: 120px; height: 120px; object-fit: cover; margin-top: 30px;">
                    <h4 class="fw-bold">{{ $counselorData['name'] ?? 'Nama Konselor' }}</h4>
                    <p class="mb-0">{{ $counselorData['bidang'] ?? 'Bidang Konseling' }}</p>
                </div>

                <div class="px-4 pt-4">
                    {{-- Bagian Rating --}}
                    <h5 class="text-primary fw-bold">Rating</h5>
                    <div class="d-flex mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star-fill" style="color: {{ $i <= round($counselorData['rate']) ? '#2897FF' : '#E0E0E0' }}; font-size: 1.8rem;"></i>
                        @endfor
                    </div>
                    <hr class="my-3">

                    {{-- Bagian Tentang --}}
                    <h5 class="text-primary fw-bold">Tentang</h5>
                    <p>{{ $counselorData['about'] ?? 'Tidak ada deskripsi tersedia.' }}</p>
                    <hr class="my-3">

                    {{-- Bagian Jadwal --}}
                    <h5 class="text-primary fw-bold">Jadwal</h5>
                    <div class="d-flex flex-wrap justify-content-center mb-4" id="scheduleButtons">
                        @forelse($schedules as $schedule)
                            <div class="schedule-box p-3 m-2 text-center rounded-3 shadow-sm {{ $schedule['isBooked'] ? 'bg-secondary text-white' : 'bg-primary text-white' }}"
                                 data-schedule-id="{{ $schedule['id'] }}"
                                 data-is-booked="{{ $schedule['isBooked'] ? 'true' : 'false' }}"
                                 style="width: 120px; cursor: {{ $schedule['isBooked'] ? 'not-allowed' : 'pointer' }};">
                                <strong class="d-block">{{ $schedule['day'] }}</strong>
                                <span>{{ $schedule['time'] }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Tidak ada jadwal tersedia.</p>
                        @endforelse
                    </div>

                    <div class="text-center mt-4">
                        <button id="bookScheduleBtn" class="btn btn-primary btn-lg rounded-pill px-5" disabled>Booking</button>
                        <p class="text-danger mt-2" id="bookingError" style="display: none;"></p>
                    </div>

                    <div class="text-center mt-3">
                        <button id="showRatingPageBtn" class="btn btn-outline-primary btn-lg rounded-pill px-5">Beri Rating</button>
                    </div>
                </div>
            </div>

            {{-- Tampilan Halaman Rating --}}
            <div id="ratingView" style="display: none;">
                <div class="text-center mb-4 pb-4 bg-primary text-white rounded-bottom-4" style="margin-top: -30px; border-top-left-radius: 45px !important; border-top-right-radius: 45px !important; border-bottom-left-radius: 45px !important; border-bottom-right-radius: 45px !important;">
                    <img src="{{ $counselorData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-white border-3 mb-3" style="width: 120px; height: 120px; object-fit: cover; margin-top: 30px;">
                    <h4 class="fw-bold">{{ $counselorData['name'] ?? 'Konselor' }}</h4>
                </div>

                <div class="px-4 pt-4 text-center">
                    <h5 class="mb-4 text-dark">Silakan beri rating kepada konselor</h5>
                    <div id="starRating" class="mb-5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star" data-rating="{{ $i }}" style="font-size: 3rem; cursor: pointer; color: #E0E0E0;"></i>
                        @endfor
                    </div>
                    <button id="saveRatingBtn" class="btn btn-primary btn-lg rounded-pill px-5">Simpan Rating</button>
                    <p class="text-danger mt-2" id="ratingError" style="display: none;"></p>
                    <button id="cancelRatingBtn" class="btn btn-outline-secondary btn-lg rounded-pill px-5 mt-3">Batal</button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Elemen UI ---
        const counselorProfileView = document.getElementById('counselorProfileView');
        const ratingView = document.getElementById('ratingView');
        const showRatingPageBtn = document.getElementById('showRatingPageBtn');
        const cancelRatingBtn = document.getElementById('cancelRatingBtn');
        const bookScheduleBtn = document.getElementById('bookScheduleBtn');
        const scheduleBoxes = document.querySelectorAll('.schedule-box');
        const bookingError = document.getElementById('bookingError');
        const starRating = document.getElementById('starRating');
        const saveRatingBtn = document.getElementById('saveRatingBtn');
        const ratingError = document.getElementById('ratingError');
        const counselorDetailAjaxAlert = document.getElementById('counselorDetailAjaxAlert');

        let selectedScheduleId = ''; // State untuk jadwal yang dipilih
        let currentRating = 0; // State untuk rating yang dipilih

        const counselorUid = "{{ $counselorUid }}"; // Ambil UID konselor dari PHP

        // --- Fungsi Helper ---
        function showAlert(message, type) {
            counselorDetailAjaxAlert.textContent = message;
            counselorDetailAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            counselorDetailAjaxAlert.style.display = 'block';
            setTimeout(() => {
                counselorDetailAjaxAlert.style.display = 'none';
            }, 5000); // Sembunyikan setelah 5 detik
        }

        function clearErrors(elementId) {
            const errorElement = document.getElementById(elementId);
            if (errorElement) errorElement.style.display = 'none';
        }

        function updateScheduleSelection(selectedId) {
            scheduleBoxes.forEach(box => {
                box.classList.remove('active-schedule'); // Hapus class active dari semua
                if (box.dataset.scheduleId === selectedId) {
                    box.classList.add('active-schedule'); // Tambahkan class active ke yang dipilih
                }
            });
            bookScheduleBtn.disabled = !selectedId; // Aktifkan/nonaktifkan tombol booking
            selectedScheduleId = selectedId; // Update state JS
            clearErrors('bookingError'); // Bersihkan error booking
        }

        function updateStarRating(rating) {
            currentRating = rating;
            starRating.querySelectorAll('.bi-star, .bi-star-fill').forEach(star => {
                const starValue = parseInt(star.dataset.rating);
                if (starValue <= currentRating) {
                    star.classList.remove('bi-star');
                    star.classList.add('bi-star-fill');
                    star.style.color = '#2897FF'; // Warna bintang aktif
                } else {
                    star.classList.remove('bi-star-fill');
                    star.classList.add('bi-star');
                    star.style.color = '#E0E0E0'; // Warna bintang tidak aktif
                }
            });
            clearErrors('ratingError'); // Bersihkan error rating
        }


        // --- Event Listeners ---

        // Toggle tampilan profil / rating
        showRatingPageBtn.addEventListener('click', function() {
            counselorProfileView.style.display = 'none';
            ratingView.style.display = 'block';
            updateStarRating(0); // Reset rating saat masuk halaman rating
        });

        cancelRatingBtn.addEventListener('click', function() {
            ratingView.style.display = 'none';
            counselorProfileView.style.display = 'block';
            updateStarRating(0); // Reset rating saat keluar dari halaman rating
        });

        // Pilih jadwal
        scheduleBoxes.forEach(box => {
            const isBooked = box.dataset.isBooked === 'true';
            if (!isBooked) { // Hanya yang belum dibooking yang bisa diklik
                box.addEventListener('click', function() {
                    const scheduleId = this.dataset.scheduleId;
                    updateScheduleSelection(scheduleId);
                });
            } else {
                box.classList.remove('bg-primary'); // Hapus warna primer untuk yang dibooking
                box.classList.add('bg-secondary'); // Beri warna sekunder untuk yang dibooking
            }
        });

        // Booking jadwal
        bookScheduleBtn.addEventListener('click', async function() {
            if (!selectedScheduleId) {
                bookingError.textContent = 'Silakan pilih jadwal terlebih dahulu.';
                bookingError.style.display = 'block';
                return;
            }

            showAlert('Memproses booking...', 'info');
            bookScheduleBtn.disabled = true; // Nonaktifkan tombol saat memproses
            bookingError.style.display = 'none';

            try {
                const response = await fetch('{{ route('counselor.bookSchedule') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        scheduleId: selectedScheduleId,
                        counselorId: counselorUid
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    // Refresh halaman atau perbarui UI jadwal setelah booking berhasil
                    location.reload();
                } else {
                    bookingError.textContent = data.message || 'Booking gagal.';
                    bookingError.style.display = 'block';
                    showAlert(data.message || 'Booking gagal.', 'danger');
                }
            } catch (error) {
                bookingError.textContent = 'Terjadi kesalahan jaringan atau server saat booking.';
                bookingError.style.display = 'block';
                showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                console.error('Error booking schedule:', error);
            } finally {
                bookScheduleBtn.disabled = false; // Aktifkan kembali tombol
            }
        });

        // Rating
        starRating.addEventListener('click', function(e) {
            const clickedStar = e.target.closest('.bi-star, .bi-star-fill');
            if (clickedStar) {
                const rating = parseInt(clickedStar.dataset.rating);
                updateStarRating(rating);
            }
        });

        saveRatingBtn.addEventListener('click', async function() {
            if (currentRating === 0) {
                ratingError.textContent = 'Silakan berikan rating (minimal 1 bintang).';
                ratingError.style.display = 'block';
                return;
            }

            showAlert('Menyimpan rating...', 'info');
            saveRatingBtn.disabled = true; // Nonaktifkan tombol saat memproses
            ratingError.style.display = 'none';

            try {
                const response = await fetch('{{ route('counselor.saveRating') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        counselorUid: counselorUid,
                        rating: currentRating
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    // Kembali ke tampilan profil konselor setelah menyimpan rating
                    ratingView.style.display = 'none';
                    counselorProfileView.style.display = 'block';
                    // Opsional: Reload data konselor untuk memperbarui rating di UI
                    location.reload();
                } else {
                    ratingError.textContent = data.message || 'Gagal menyimpan rating.';
                    ratingError.style.display = 'block';
                    showAlert(data.message || 'Gagal menyimpan rating.', 'danger');
                }
            } catch (error) {
                ratingError.textContent = 'Terjadi kesalahan jaringan atau server saat menyimpan rating.';
                ratingError.style.display = 'block';
                showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                console.error('Error saving rating:', error);
            } finally {
                saveRatingBtn.disabled = false; // Aktifkan kembali tombol
            }
        });

        // Inisialisasi tampilan awal
        // Jika counselorData kosong, tampilan 'Memuat data konselor...' akan aktif
        // Default tampilan adalah profil konselor
        if (counselorProfileView) { // Pastikan elemen ada sebelum diakses
            counselorProfileView.style.display = 'block';
        }
        if (ratingView) { // Pastikan elemen ada sebelum diakses
            ratingView.style.display = 'none';
        }


        // Set initial schedule selection status (jika ada jadwal yang dipilih dari state)
        // Ini akan nonaktifkan tombol booking jika tidak ada jadwal terpilih awal
        updateScheduleSelection(selectedScheduleId);
    });
</script>
@endsection