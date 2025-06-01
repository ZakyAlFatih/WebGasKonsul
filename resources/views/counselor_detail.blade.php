@extends('layouts.app')

@section('title', 'Detail Konselor - GasKonsul')

@section('content')
<div class="container-fluid py-4" style="min-height: 100vh; background-color: #e0f0ff;">
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
        <div id="counselorDetailAjaxAlert" class="alert mt-3 text-center" style="display: none;"></div>
    </div>

    {{-- Konten Utama Detail Konselor --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm" style="max-width: 900px;">
        @if(empty($counselorData) && !empty($errorMessage))
            <p class="text-center text-danger">{{ $errorMessage }}</p>
        @elseif(empty($counselorData))
            <p class="text-center text-muted">Memuat data konselor...</p>
        @else
            <div id="counselorProfileView" style="display: block;">
                <div class="mb-4">
                    <a href="{{ route('home') }}" class="btn btn-link text-primary"><i class="bi bi-arrow-left"></i> Kembali</a>
                    <h4 class="text-primary fw-bold mt-2">Profil Konselor</h4>
                </div>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="{{ $counselorData['avatar'] ?? asset('images/default_profile.png') }}" alt="Avatar" class="rounded-circle border border-primary border-3 mb-3" style="width: 180px; height: 180px; object-fit: cover;">
                        <h5 class="fw-bold text-primary">{{ $counselorData['name'] ?? 'Nama Konselor' }}</h5>
                        <p class="text-muted">{{ $counselorData['bidang'] ?? 'Bidang Konseling' }}</p>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6 class="text-primary fw-bold">Rating</h6>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star-fill" style="color: {{ $i <= round($counselorData['rate']) ? '#2897FF' : '#E0E0E0' }}; font-size: 1.2rem; margin-right: 2px;"></i>
                                @endfor
                                <span class="ms-2 text-muted">({{ number_format($counselorData['rate'] ?? 0, 1) }})</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-primary fw-bold">Tentang</h6>
                            <p class="text-secondary">{{ $counselorData['about'] ?? 'Tidak ada deskripsi tersedia.' }}</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-primary fw-bold">Jadwal Tersedia</h6>
                            <div class="d-flex flex-wrap gap-2" id="scheduleButtons">
                                @forelse($schedules as $schedule)
                                    <button
                                        class="btn btn-sm {{ $schedule['isBooked'] ? 'btn-secondary disabled' : 'btn-outline-primary' }} rounded-pill"
                                        data-schedule-id="{{ $schedule['id'] }}"
                                        data-is-booked="{{ $schedule['isBooked'] ? 'true' : 'false' }}"
                                        style="padding: 0.5rem 1rem;">
                                        {{ $schedule['day'] }}, {{ $schedule['time'] }}
                                    </button>
                                @empty
                                    <p class="text-muted">Tidak ada jadwal tersedia.</p>
                                @endforelse
                            </div>
                            <p class="text-danger mt-2" id="bookingError" style="display: none;"></p>
                            <button id="bookScheduleBtn" class="btn btn-primary rounded-pill px-4 mt-2" disabled>Booking</button>
                        </div>
                        <div class="text-end">
                            <button id="showRatingPageBtn" class="btn btn-outline-primary rounded-pill px-4">Beri Rating</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tampilan Halaman Rating --}}
            <div id="ratingView" style="display: none;" class="mt-4">
                <div class="text-center mb-4">
                    <h5 class="mb-4 text-dark">Berikan Rating untuk {{ $counselorData['name'] ?? 'Konselor' }}</h5>
                    <div id="starRating" class="mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star" data-rating="{{ $i }}" style="font-size: 2rem; cursor: pointer; color: #E0E0E0;"></i>
                        @endfor
                    </div>
                    <button id="saveRatingBtn" class="btn btn-primary rounded-pill px-4">Simpan Rating</button>
                    <p class="text-danger mt-2" id="ratingError" style="display: none;"></p>
                    <button id="cancelRatingBtn" class="btn btn-outline-secondary rounded-pill px-4 mt-2">Batal</button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const counselorProfileView = document.getElementById('counselorProfileView');
        const ratingView = document.getElementById('ratingView');
        const showRatingPageBtn = document.getElementById('showRatingPageBtn');
        const cancelRatingBtn = document.getElementById('cancelRatingBtn');
        const bookScheduleBtn = document.getElementById('bookScheduleBtn');
        const scheduleButtons = document.querySelectorAll('#scheduleButtons button');
        const bookingError = document.getElementById('bookingError');
        const starRating = document.getElementById('starRating');
        const saveRatingBtn = document.getElementById('saveRatingBtn');
        const ratingError = document.getElementById('ratingError');
        const counselorDetailAjaxAlert = document.getElementById('counselorDetailAjaxAlert');

        let selectedScheduleId = '';
        let currentRating = 0;
        const counselorUid = "{{ $counselorUid }}";

        function showAlert(message, type) {
            counselorDetailAjaxAlert.textContent = message;
            counselorDetailAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            counselorDetailAjaxAlert.style.display = 'block';
            setTimeout(() => {
                counselorDetailAjaxAlert.style.display = 'none';
            }, 5000);
        }

        function clearErrors(elementId) {
            const errorElement = document.getElementById(elementId);
            if (errorElement) errorElement.style.display = 'none';
        }

        function updateScheduleSelection(selectedId) {
            scheduleButtons.forEach(button => {
                // Pastikan hanya tombol yang TIDAK dibooking yang bisa di-select
                if (button.dataset.isBooked === 'false') {
                    if (button.dataset.scheduleId === selectedId) {
                        button.classList.remove('btn-outline-primary');
                        button.classList.add('btn-primary');
                    } else {
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-outline-primary');
                    }
                }
            });
            bookScheduleBtn.disabled = !selectedId;
            selectedScheduleId = selectedId;
            clearErrors('bookingError');
        }

        function updateStarRating(rating) {
            currentRating = rating;
            starRating.querySelectorAll('.bi-star, .bi-star-fill').forEach((star, index) => {
                if (index < rating) {
                    star.classList.remove('bi-star');
                    star.classList.add('bi-star-fill');
                    star.style.color = '#2897FF';
                } else {
                    star.classList.remove('bi-star-fill');
                    star.classList.add('bi-star');
                    star.style.color = '#E0E0E0';
                }
            });
            clearErrors('ratingError');
        }

        // Event Listeners
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

        scheduleButtons.forEach(button => {
            const isBooked = button.dataset.isBooked === 'true';
            if (!isBooked) { // Hanya yang belum dibooking yang bisa diklik
                button.addEventListener('click', function() {
                    updateScheduleSelection(this.dataset.scheduleId);
                });
            } else {
                // Tidak perlu mengubah kelas di sini karena sudah diatur di blade (btn-secondary disabled)
                // Jika ingin memastikan, bisa tambahkan:
                // button.classList.remove('btn-outline-primary', 'btn-primary');
                // button.classList.add('btn-secondary', 'disabled');
            }
        });


        bookScheduleBtn.addEventListener('click', async function() {
            if (!selectedScheduleId) {
                bookingError.textContent = 'Silakan pilih jadwal terlebih dahulu.';
                bookingError.style.display = 'block';
                return;
            }

            showAlert('Memproses booking...', 'info');
            bookScheduleBtn.disabled = true;
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
                bookScheduleBtn.disabled = false;
            }
        });

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
            saveRatingBtn.disabled = true;
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
                saveRatingBtn.disabled = false;
            }
        });

        // Inisialisasi tampilan awal
        if (counselorProfileView) {
            counselorProfileView.style.display = 'block';
        }
        if (ratingView) {
            ratingView.style.display = 'none';
        }
        updateScheduleSelection(selectedScheduleId); // Memastikan tombol booking dinonaktifkan di awal jika tidak ada jadwal terpilih
    });
</script>
@endsection