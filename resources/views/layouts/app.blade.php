<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'GasKonsul')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #e0f0ff; /* Warna latar belakang dari login.blade.php */
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh; /* Pastikan body mengisi seluruh tinggi viewport */
            display: flex;
            flex-direction: column;
            justify-content: center; /* Pusatkan konten secara vertikal */
            align-items: center; /* Pusatkan konten secara horizontal */
        }
        .container {
            max-width: 600px; /* Batasi lebar container utama */
            padding: 20px;
        }
        /* Gaya tambahan yang mungkin ada di app.blade.php */
    </style>
</head>
<body>

    <div id="app">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>