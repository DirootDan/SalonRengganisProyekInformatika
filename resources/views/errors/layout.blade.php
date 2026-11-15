<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul') - Salon Rengganis</title>
    <x-meta-situs />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pages/error.css'])
</head>
<body>
    <main class="er-kartu" role="main">
        <div class="er-ikon" aria-hidden="true"><x-ikon :name="trim($__env->yieldContent('ikon', 'bunga'))" /></div>
        <span class="er-kode">@yield('kode')</span>
        <h1>@yield('judul')</h1>
        <p>@yield('pesan')</p>

        <div class="er-aksi">
            @yield('aksi')
            <a href="{{ url('/') }}" class="er-tombol er-tombol-garis">Kembali ke Beranda</a>
        </div>
    </main>
</body>
</html>
