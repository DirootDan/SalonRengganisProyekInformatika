@props([
    'judul' => 'Salon Rengganis',
    'deskripsi' => 'Salon Rengganis: perawatan rambut, wajah & tubuh dengan booking online tanpa antre.',
    'indeks' => false,
])

<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<meta name="theme-color" content="#db2777">
<meta name="description" content="{{ $deskripsi }}">
<meta name="robots" content="{{ $indeks ? 'index, follow' : 'noindex, nofollow' }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Salon Rengganis">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="{{ $judul }}">
<meta property="og:description" content="{{ $deskripsi }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('images/og-salon.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
