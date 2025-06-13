@extends('layouts.app')

@section('title', 'Riwayat Konseling - GasKonsul')

@section('content')
    {{-- Notifikasi Error/Sukses --}}
    <div class="container mt-3 mx-auto" style="max-width: 700px;">
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
        <div id="historyAjaxAlert" class="alert mt-3 text-center" style="display: none;"></div>
    </div>

    {{-- Konten Utama Riwayat --}}
    <div class="container bg-white rounded-4 p-4 shadow-sm mx-auto" style="max-width: 700px; min-height: 50vh;">
        @if(isset($bookingHistory))
            @if(empty($bookingHistory))
                <div class="text-center py-5">
                    <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="150" height="150" style="object-fit: contain;">
                    <h2 class="mt-4 text-primary fw-bold">MAAF</h2>
                    <p class="text-muted fs-5">Riwayat Anda masih kosong</p>
                    <p class="text-muted fs-5">Harap pesan konselor terlebih dahulu</p>
                </div>
            @else
                <h5 class="text-primary fw-bold mb-4">Daftar Riwayat Konseling Anda</h5>
                <div class="list-group">
                    @foreach($bookingHistory as $history)
                        <div class="card mb-3 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 50%;">
                                        <img src="{{ $history['counselorAvatar'] ?? asset('images/default_profile.png') }}" alt="Avatar Konselor" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $history['counselorName'] }}</h6>
                                        <p class="mb-0 text-muted">{{ $history['counselorBidang'] }}</p>
                                        <p class="mb-0 text-dark">{{ $history['day'] }}, {{ $history['time'] }}</p>
                                        <p class="mb-0 text-info">Status: {{ $history['status'] }}</p>
                                    </div>
                                </div>

                                {{-- Tombol Rating Kondisional --}}
                                @if($history['status'] === 'completed')
                                    @if(!$history['hasBeenRated'])
                                        <button class="btn btn-sm btn-primary rounded-pill mt-3 beriRatingBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#ratingModal"
                                                data-booking-id="{{ $history['bookingId'] }}"
                                                data-counselor-id="{{ $history['counselorId'] }}"
                                                data-counselor-name="{{ $history['counselorName'] }}">
                                            Beri Rating
                                        </button>
                                    @else
                                        <p class="mt-3 text-success"><i class="bi bi-check-circle-fill"></i> Anda sudah memberi rating untuk sesi ini.</p>
                                    @endif
                                @else
                                    <p class="mt-3 text-muted">Sesi belum selesai atau dibatalkan.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <p class="text-center text-muted">Memuat riwayat...</p>
        @endif
    </div>

    <div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ratingModalLabel">Beri Rating untuk <span id="modalCounselorName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="modalStarRating" class="mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star" data-rating="{{ $i }}" style="font-size: 2rem; cursor: pointer; color: #E0E0E0;"></i>
                        @endfor
                    </div>
                    <input type="hidden" id="modalBookingId">
                    <input type="hidden" id="modalCounselorId">
                    <p class="text-danger mt-2" id="modalRatingError" style="display: none;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary rounded-pill" id="saveModalRatingBtn">Simpan Rating</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ratingModal = document.getElementById('ratingModal');
        const modalCounselorName = document.getElementById('modalCounselorName');
        const modalBookingId = document.getElementById('modalBookingId');
        const modalCounselorId = document.getElementById('modalCounselorId');
        const modalStarRating = document.getElementById('modalStarRating');
        const saveModalRatingBtn = document.getElementById('saveModalRatingBtn');
        const modalRatingError = document.getElementById('modalRatingError');
        const historyAjaxAlert = document.getElementById('historyAjaxAlert');

        let currentModalRating = 0;

        function showAlert(message, type) {
            historyAjaxAlert.textContent = message;
            historyAjaxAlert.className = `alert mt-3 text-center alert-${type}`;
            historyAjaxAlert.style.display = 'block';
            setTimeout(() => {
                historyAjaxAlert.style.display = 'none';
            }, 5000);
        }

        // Function to update star visuals in modal
        function updateModalStarRating(rating) {
            currentModalRating = rating;
            modalStarRating.querySelectorAll('.bi-star, .bi-star-fill').forEach((star, index) => {
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
            modalRatingError.style.display = 'none';
        }

        // Event listener for opening the modal
        ratingModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const bookingId = button.dataset.bookingId;
            const counselorId = button.dataset.counselorId;
            const counselorName = button.dataset.counselorName;

            modalCounselorName.textContent = counselorName;
            modalBookingId.value = bookingId;
            modalCounselorId.value = counselorId;
            updateModalStarRating(0);
        });

        // Event listener for clicking stars in modal
        modalStarRating.addEventListener('click', function(e) {
            const clickedStar = e.target.closest('.bi-star, .bi-star-fill');
            if (clickedStar) {
                const rating = parseInt(clickedStar.dataset.rating);
                updateModalStarRating(rating);
            }
        });

        // Event listener for saving rating from modal
        saveModalRatingBtn.addEventListener('click', async function() {
            if (currentModalRating === 0) {
                modalRatingError.textContent = 'Silakan berikan rating (minimal 1 bintang).';
                modalRatingError.style.display = 'block';
                return;
            }

            showAlert('Menyimpan rating...', 'info');
            saveModalRatingBtn.disabled = true;
            modalRatingError.style.display = 'none';

            try {
                const response = await fetch('{{ route('counselor.saveRating') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bookingId: modalBookingId.value,
                        rating: currentModalRating
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    // Tutup modal
                    const bsModal = bootstrap.Modal.getInstance(ratingModal);
                    if (bsModal) bsModal.hide();
                    else $(ratingModal).modal('hide');

                    location.reload();
                } else {
                    modalRatingError.textContent = data.message || 'Gagal menyimpan rating.';
                    modalRatingError.style.display = 'block';
                    showAlert(data.message || 'Gagal menyimpan rating.', 'danger');
                }
            } catch (error) {
                modalRatingError.textContent = 'Terjadi kesalahan jaringan atau server.';
                modalRatingError.style.display = 'block';
                showAlert('Terjadi kesalahan jaringan atau server.', 'danger');
                console.error('Error saving rating:', error);
            } finally {
                saveModalRatingBtn.disabled = false;
            }
        });
    });
</script>
@endsection