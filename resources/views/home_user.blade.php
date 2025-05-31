@extends('layouts.app')

@section('title', 'Beranda User - GasKonsul')

@section('content')
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

    {{-- Bagian "Ada masalah apa?" --}}
    <div class="bg-primary text-white rounded-4 p-4 mx-auto my-4 d-flex flex-wrap justify-content-between align-items-center" style="max-width: 900px;">
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

    {{-- Bagian Kategori dan Daftar Konselor --}}
    <div class="bg-white mx-auto p-4 rounded-4 border border-primary" style="max-width: 900px;">
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
@endsection

@section('scripts')
<script>
    // --- DEBUGGING: Pesan ini seharusnya muncul jika script dimuat dan mulai dieksekusi ---
    console.log('DEBUG: home_user.blade.php script is loaded and starting to execute.');

    const initialCategoriesData = @json($categories);

    // --- DEBUGGING: Log data kategori awal ke konsol ---
    console.log('DEBUG: Initial Categories Data:', initialCategoriesData);

    // Fungsi untuk me-render card konselor sesuai format UI
    function renderCounselorCards(counselors) {
        console.log('DEBUG: renderCounselorCards called with counselors:', counselors);
        let html = '';
        if (!counselors || counselors.length === 0) {
            html = '<div class="col-12"><p class="text-center text-muted">Tidak ada konselor di kategori ini.</p></div>';
        } else {
            html += '<div class="row">'; // Bungkus dalam satu row Bootstrap
            counselors.forEach(counselor => {

                const availabilityDays = (() => {
                    const days = [];
                    // Pastikan counselor.availability ada sebelum mengakses propertinya
                    if (counselor.availability && counselor.availability.day1 && counselor.availability.day1 !== 'Unknown') days.push(counselor.availability.day1);
                    if (counselor.availability && counselor.availability.day2 && counselor.availability.day2 !== 'Unknown') days.push(counselor.availability.day2);
                    if (counselor.availability && counselor.availability.day3 && counselor.availability.day3 !== 'Unknown') days.push(counselor.availability.day3);
                    return days.join(', ') || 'Tidak Tersedia';
                })();

                let imageUrl;
                if (counselor.image && (counselor.image.startsWith('http://') || counselor.image.startsWith('https://'))) {
                    imageUrl = counselor.image;
                } else {
                    imageUrl = `{{ asset('images/default_profile.png') }}`;
                }

                html += `
                    <div class="col-md-2 col-sm-4 col-6 text-center mb-4">
                        <img src="${imageUrl}" alt="Konselor" class="shadow-sm mb-2"
                            style="height: 100px; width: 100px; object-fit: cover; border-radius: 50%;">
                        <p class="mb-0 fw-semibold">${counselor.name}</p>
                        <small class="text-muted">${availabilityDays}</small><br>
                        <a href="/counselor/${counselor.uid}" class="text-primary small">Selengkapnya</a>
                    </div>
                `;
            });
            html += '</div>';
        }
        return html;
    }

    // Fungsi untuk me-render semua konselor yang dikelompokkan per kategori
    function renderAllGroupedCounselors(categoriesData) {
        console.log('DEBUG: renderAllGroupedCounselors called with data:', categoriesData);
        const counselorListDisplay = document.getElementById('counselorListDisplay');
        let fullHtml = '';
        if (!categoriesData || categoriesData.length === 0) { // Tambahkan pengecekan null/undefined pada categoriesData
            fullHtml = '<p class="text-center text-muted">Tidak ada konselor yang ditemukan.</p>';
        } else {
            categoriesData.forEach(categoryData => {
                // Pastikan categoryData.counselors ada dan bukan array kosong
                if (categoryData.counselors && categoryData.counselors.length > 0) {
                    fullHtml += `<h5 class="fw-bold mt-4">${categoryData.category}</h5>`;
                    fullHtml += renderCounselorCards(categoryData.counselors);
                }
            });
        }
        if (counselorListDisplay) {
            counselorListDisplay.innerHTML = fullHtml;
            console.log('DEBUG: Updated counselorListDisplay innerHTML. Length:', counselorListDisplay.innerHTML.length);
        } else {
            console.error('DEBUG: counselorListDisplay element not found when trying to update HTML.');
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
        try {
            // --- DEBUGGING: Pesan ini seharusnya muncul jika DOMContentLoaded fired ---
            console.log('DEBUG: DOMContentLoaded fired. Initializing home_user script functions.');

            const ceritaInput = document.getElementById('cerita');
            const problemForm = document.getElementById('problemForm');
            const rekomendasiBidangContainer = document.getElementById('rekomendasiBidangContainer');
            const rekomendasiBidangText = document.getElementById('rekomendasiBidangText');
            const rekomendasiError = document.getElementById('rekomendasiError');
            const categoryButtons = document.querySelectorAll('.category-button');
            const counselorListDisplay = document.getElementById('counselorListDisplay');

            // --- DEBUGGING: Memeriksa apakah elemen-elemen kunci ditemukan ---
            console.log('DEBUG: Element #counselorListDisplay:', counselorListDisplay);
            console.log('DEBUG: Elements .category-button:', categoryButtons);
            console.log('DEBUG: Number of category buttons found:', categoryButtons.length);


            // Event Listener untuk Form Cerita (Rekomendasi AI)
            if (problemForm) {
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
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ cerita: cerita })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            rekomendasiBidangText.textContent = data.rekomendasiBidang;
                            rekomendasiBidangContainer.style.display = 'block';
                        } else {
                            rekomendasiError.textContent = data.error || 'Gagal mendapatkan rekomendasi dari AI.';
                            rekomendasiError.style.display = 'block';
                        }
                    } catch (error) {
                        rekomendasiError.textContent = 'Terjadi kesalahan jaringan atau server.';
                        rekomendasiError.style.display = 'block';
                        console.error('Error fetching AI recommendation:', error);
                    }
                });
            }


            // Event Listener untuk Tombol Kategori (Filter)
            if (categoryButtons.length > 0) {
                categoryButtons.forEach(button => {
                    button.addEventListener('click', async function() {
                        const category = this.dataset.category;
                        console.log(`DEBUG: Category button clicked: "${category}"`);

                        categoryButtons.forEach(btn => {
                            btn.classList.remove('active', 'btn-primary');
                            btn.classList.add('btn-outline-primary');
                        });
                        this.classList.add('active', 'btn-primary');
                        this.classList.remove('btn-outline-primary');

                        try {
                            if (category === '') {
                                renderAllGroupedCounselors(initialCategoriesData);
                            } else {
                                let url = '{{ route('home.filter') }}';
                                let queryParams = new URLSearchParams();
                                queryParams.append('category', category);
                                url += '?' + queryParams.toString();

                                const response = await fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                const data = await response.json();
                                console.log(`DEBUG: AJAX Response for category "${category}":`, data);

                                if (response.ok) {
                                    const filteredCounselors = data.counselors;
                                    const filteredHtml = `
                                        <h5 class="fw-bold mt-4">${category}</h5>
                                        ${renderCounselorCards(filteredCounselors)}
                                    `;
                                    if (counselorListDisplay) {
                                        counselorListDisplay.innerHTML = filteredHtml;
                                    }
                                } else {
                                    if (counselorListDisplay) {
                                        counselorListDisplay.innerHTML = `<p class="text-center text-danger">${data.error || 'Gagal memfilter konselor.'}</p>`;
                                    }
                                }
                            }
                        } catch (error) {
                            if (counselorListDisplay) {
                                counselorListDisplay.innerHTML = `<p class="text-center text-danger">Terjadi kesalahan jaringan atau server saat memfilter.</p>`;
                            }
                            console.error('Error fetching filtered counselors:', error);
                        }
                    });
                });
            } else {
                console.error('DEBUG: No category buttons found for attaching event listeners.');
            }

            // Trigger click pada tombol "Semua" saat halaman dimuat untuk inisialisasi tampilan
            const allCategoryButton = document.querySelector('.category-button[data-category=""]');
            if (allCategoryButton) {
                console.log('DEBUG: "Semua" category button found. Triggering click.');
                allCategoryButton.click();
            } else {
                console.error('DEBUG: "Semua" category button NOT found for initial click!');
            }

        } catch (e) {
            console.error("DEBUG: An unexpected error occurred in home_user.blade.php's DOMContentLoaded listener:", e);
        }
    });
</script>
@endsection