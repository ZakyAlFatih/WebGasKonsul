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
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookScheduleBtn = document.getElementById('bookScheduleBtn');
        const scheduleButtons = document.querySelectorAll('#scheduleButtons button');
        const bookingError = document.getElementById('bookingError');
        const counselorDetailAjaxAlert = document.getElementById('counselorDetailAjaxAlert');

        let selectedScheduleId = '';
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

        scheduleButtons.forEach(button => {
            const isBooked = button.dataset.isBooked === 'true';
            if (!isBooked) {
                button.addEventListener('click', function() {
                    updateScheduleSelection(this.dataset.scheduleId);
                });
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

        // Inisialisasi tampilan awal
        updateScheduleSelection(selectedScheduleId); // Memastikan tombol booking dinonaktifkan di awal jika tidak ada jadwal terpilih
    });
</script>
@endsection