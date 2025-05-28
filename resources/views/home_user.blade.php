@extends('layouts.app')

@section('title', 'Beranda User - GasKonsul')

@section('content')
<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white shadow-sm">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/gasKonsul_logo.png') }}" alt="Logo" width="40" class="me-2">
            <h4 class="text-primary fw-bold m-0">GasKonsul</h4>
        </div>
        <div class="d-flex align-items-center">
            {{-- Input Cari di Header (fungsionalitas perlu diimplementasikan di JS/backend jika diinginkan) --}}
            <input type="text" class="form-control me-3" placeholder="Cari" style="width: 200px;">
            {{-- Navigasi Utama --}}
            <a href="{{ route('profile') }}" class="me-3 text-decoration-none text-dark">Profil</a>
            <a href="{{ route('history') }}" class="me-3 text-decoration-none text-dark">Riwayat</a>
            <a href="{{ route('chat') }}" class="me-3 text-decoration-none text-dark">Chat</a>
            {{-- Info Pengguna --}}
            <div class="d-flex align-items-center bg-white border rounded-pill px-3 py-1">
                <img src="{{ asset('images/default_profile.png') }}" alt="User" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                <span class="ms-2">{{ $userName ?? 'User' }}</span>
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
    </div>

    <div class="bg-primary text-white rounded-4 p-4 mx-5 my-4 d-flex flex-wrap justify-content-between align-items-center">
        <div class="me-4 flex-grow-1">
            <h5 class="fw-bold mb-2">Ada masalah apa?</h5>
            <p class="mb-3">Ceritakan dan kami carikan orang yang tepat</p>
            <form id="problemForm">
                @csrf
                <div class="input-group">
                    <input type="text" id="cerita" name="cerita" class="form-control rounded-start-3" placeholder="Ketik sesuatu" style="border-radius: 0.5rem 0 0 0.5rem;">
                    <button type="submit" class="btn btn-light text-primary fw-bold" style="border-radius: 0 0.5rem 0.5rem 0;">Cari</button>
                </div>
            </form>
            <div id="rekomendasiBidangContainer" class="mt-3" style="display: none;">
                <div class="alert alert-success fw-bold" role="alert" style="background-color: rgba(255,255,255,0.8); color: #198754;">
                    Rekomendasi Bidang: <span id="rekomendasiBidangText"></span>
                </div>
            </div>
            <div id="rekomendasiError" class="alert alert-danger mt-3" role="alert" style="display: none; background-color: rgba(255,0,0,0.8); color: white;"></div>
        </div>
        <img src="{{ asset('images/ilust.png') }}" alt="Ilustrasi" class="ms-auto d-none d-md-block" style="width: 150px;">
    </div>

    <div class="bg-white mx-5 p-4 rounded-4 border border-primary">
        <h5 class="mb-3 fw-bold">Kategori</h5>
        <div class="mb-4 d-flex flex-wrap" id="categoryButtons">
            <button class="btn btn-outline-primary rounded-pill px-3 py-1 m-1 category-button active" data-category="">Semua</button>
            @foreach($categories as $categoryData)
                <button class="btn btn-outline-primary rounded-pill px-3 py-1 m-1 category-button" data-category="{{ $categoryData['category'] }}">{{ $categoryData['category'] }}</button>
            @endforeach
        </div>

        <div id="counselorListDisplay">
            {{-- Konten ini akan diisi dan diubah oleh JavaScript --}}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Melewatkan data kategori awal dari PHP ke JavaScript
    const initialCategoriesData = @json($categories);

    document.addEventListener('DOMContentLoaded', function() {
        const ceritaInput = document.getElementById('cerita');
        const problemForm = document.getElementById('problemForm');
        const rekomendasiBidangContainer = document.getElementById('rekomendasiBidangContainer');
        const rekomendasiBidangText = document.getElementById('rekomendasiBidangText');
        const rekomendasiError = document.getElementById('rekomendasiError');
        const categoryButtons = document.querySelectorAll('.category-button');
        const counselorListDisplay = document.getElementById('counselorListDisplay');

        // Fungsi untuk me-render card konselor sesuai format UI
        function renderCounselorCards(counselors) {
            let html = '';
            if (counselors.length === 0) {
                html = '<div class="col-12"><p class="text-center text-muted">Tidak ada konselor di kategori ini.</p></div>';
            } else {
                counselors.forEach(counselor => {
                    const availabilityDays = (() => {
                        const days = [];
                        if (counselor.availability.day1 && counselor.availability.day1 !== 'Unknown') days.push(counselor.availability.day1);
                        if (counselor.availability.day2 && counselor.availability.day2 !== 'Unknown') days.push(counselor.availability.day2);
                        if (counselor.availability.day3 && counselor.availability.day3 !== 'Unknown') days.push(counselor.availability.day3);
                        return days.join(', ') || 'Tidak Tersedia';
                    })();

                    // Logika penentuan imageUrl sepenuhnya di JavaScript
                    let imageUrl;
                    if (counselor.image && (counselor.image.startsWith('http://') || counselor.image.startsWith('https://'))) {
                        // Jika image adalah URL lengkap (misal dari Firebase Storage)
                        imageUrl = counselor.image;
                    } else if (counselor.image) {
                        // Jika image adalah nama file lokal, asumsikan di public/images/
                        imageUrl = `{{ asset('images') }}/${counselor.image.split('/').pop()}`;
                    } else {
                        // Fallback jika image tidak ada
                        imageUrl = `{{ asset('images/default_profile.png') }}`;
                    }

                    html += `
                        <div class="col-md-2 col-sm-4 col-6 text-center mb-4">
                            <img src="${imageUrl}" alt="Konselor" class="img-fluid rounded shadow-sm mb-2" style="height: 100px; width: 100px; object-fit: cover;">
                            <p class="mb-0 fw-semibold">${counselor.name}</p>
                            <small class="text-muted">${availabilityDays}</small><br>
                            <a href="/counselor/${counselor.uid}" class="text-primary small">Selengkapnya</a>
                        </div>
                    `;
                });
                html = `<div class="row">${html}</div>`; // Bungkus dalam satu row Bootstrap
            }
            return html;
        }

        // Fungsi untuk me-render semua konselor yang dikelompokkan per kategori
        function renderAllGroupedCounselors(categoriesData) {
            let fullHtml = '';
            if (categoriesData.length === 0) {
                fullHtml = '<p class="text-center text-muted">Tidak ada konselor yang ditemukan.</p>';
            } else {
                categoriesData.forEach(categoryData => {
                    if (categoryData.counselors.length > 0) {
                        fullHtml += `<h5 class="fw-bold mt-4">${categoryData.category}</h5>`;
                        fullHtml += renderCounselorCards(categoryData.counselors);
                    }
                });
            }
            counselorListDisplay.innerHTML = fullHtml;
        }


        // Event Listener untuk Form Cerita (Rekomendasi AI)
        problemForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            rekomendasiBidangContainer.style.display = 'none';
            rekomendasiError.style.display = 'none';

            const cerita = ceritaInput.value.trim();
            if (!cerita) {
                rekomendasiError.textContent = 'Silakan ceritakan masalah Anda.';
                rekomendasiError.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('{{ route('home.recommend') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ cerita: cerita })
                });

                const data = await response.json();

                if (response.ok) {
                    rekomendasiBidangText.textContent = data.rekomendasiBidang;
                    rekomendasiBidangContainer.style.display = 'block';
                } else {
                    rekomendasiError.textContent = data.error || 'Gagal mendapatkan rekomendasi AI.';
                    rekomendasiError.style.display = 'block';
                }
            } catch (error) {
                rekomendasiError.textContent = 'Terjadi kesalahan jaringan atau server.';
                rekomendasiError.style.display = 'block';
                console.error('Error fetching AI recommendation:', error);
            }
        });

        // Event Listener untuk Tombol Kategori (Filter)
        categoryButtons.forEach(button => {
            button.addEventListener('click', async function() {
                const category = this.dataset.category;

                // Update styling tombol aktif
                categoryButtons.forEach(btn => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-primary');
                });
                this.classList.add('active', 'btn-primary');
                this.classList.remove('btn-outline-primary');

                try {
                    // Jika kategori kosong (tombol "Semua" diklik), render semua yang dikelompokkan
                    if (category === '') {
                        renderAllGroupedCounselors(initialCategoriesData);
                    } else {
                        // Jika kategori spesifik dipilih, lakukan AJAX filter
                        let url = '{{ route('home.filter') }}';
                        let queryParams = new URLSearchParams();
                        queryParams.append('category', category); // category sudah tidak kosong di sini
                        url += '?' + queryParams.toString();

                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Meskipun GET, tetap sertakan untuk konsistensi
                            }
                        });

                        const data = await response.json();

                        if (response.ok) {
                            const filteredHtml = `
                                <h5 class="fw-bold mt-4">${category}</h5>
                                ${renderCounselorCards(data.counselors)}
                            `;
                            counselorListDisplay.innerHTML = filteredHtml;
                        } else {
                            counselorListDisplay.innerHTML = `<p class="text-center text-danger">${data.error || 'Gagal memfilter konselor.'}</p>`;
                        }
                    }
                } catch (error) {
                    counselorListDisplay.innerHTML = `<p class="text-center text-danger">Terjadi kesalahan jaringan atau server saat memfilter.</p>`;
                    console.error('Error fetching filtered counselors:', error);
                }
            });
        });

        // Trigger click pada tombol "Semua" saat halaman dimuat untuk inisialisasi tampilan
        document.querySelector('.category-button[data-category=""]').click();
    });
</script>
@endsection