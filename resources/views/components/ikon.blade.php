@props([
    'name' => 'bunga',
    'class' => 'ikon-ui',
])

@php
    $petaAlias = [
        '✂️' => 'gunting',
        '✂' => 'gunting',
        '🌿' => 'daun',
        '🏦' => 'bank',
        '💵' => 'uang',
        '📱' => 'ponsel',
        '💳' => 'kartu',
        '🔒' => 'gembok',
        '🔍' => 'cari',
        '⏳' => 'jam',
        '✋' => 'tangan',
        '🛠️' => 'perbaikan',
        '🛠' => 'perbaikan',
        '🧹' => 'sapu',
        '🌸' => 'bunga',
    ];
    $kode = $petaAlias[$name] ?? $name;
@endphp

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.85', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($kode)
        @case('bunga')
            <path d="M12 3c-3 4-4 7.5-4 10.5a4 4 0 0 0 8 0C16 10.5 15 7 12 3z" />
            <path d="M8.2 11.2c-3.5-1.5-5.7.2-6.2 3.3 2.8 2.2 6.2 2.4 8.8.4" />
            <path d="M15.8 11.2c3.5-1.5 5.7.2 6.2 3.3-2.8 2.2-6.2 2.4-8.8.4" />
            <path d="M7.5 19.5c2.8 1.3 6.2 1.3 9 0" />
            @break
        @case('kilau')
            <path d="M12 3l1.8 4.8L18.5 9.5l-4.7 1.7L12 16l-1.8-4.8L5.5 9.5l4.7-1.7L12 3z" />
            <path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15z" />
            @break
        @case('kunci')
            <circle cx="7.5" cy="15.5" r="4.5" />
            <path d="M21 2l-9.6 9.6" />
            <path d="M15.5 7.5l3 3L22 7l-3-3" />
            @break
        @case('gembok')
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            @break
        @case('buka-gembok')
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 7.6-1.7" />
            @break
        @case('kunci-akses')
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            <circle cx="12" cy="16" r="1.2" />
            @break
        @case('cek')
            <circle cx="12" cy="12" r="9" />
            <path d="M8.5 12.2l2.4 2.4 4.8-5.1" />
            @break
        @case('peringatan')
            <path d="M10.3 4.3L2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
            @break
        @case('silang')
            <circle cx="12" cy="12" r="9" />
            <path d="M9.5 9.5l5 5M14.5 9.5l-5 5" />
            @break
        @case('tanda-cek')
            <path d="M5 12.5l4.5 4.5L19 7.5" />
            @break
        @case('tanda-silang')
            <path d="M6 6l12 12M18 6L6 18" />
            @break
        @case('jam')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.2 2" />
            @break
        @case('kalender')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('grafik-batang')
            <path d="M4 20h16M7 16v-5M12 16V6M17 16v-8" />
            @break
        @case('tren-naik')
            <path d="M3 17l6-6 4 4 7-7" />
            <path d="M15 8h5v5" />
            @break
        @case('pengguna')
            <circle cx="12" cy="8" r="4" />
            <path d="M5 20a7 7 0 0 1 14 0" />
            @break
        @case('pengguna-grup')
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M16 14.2a4.5 4.5 0 0 1 4.5 4.8" />
            @break
        @case('gunting')
            <circle cx="6" cy="6" r="2.5" />
            <circle cx="6" cy="18" r="2.5" />
            <path d="M20 4L8.1 15.9M14.5 14.5L20 20M8.1 8.1L12 12" />
            @break
        @case('botol')
            <path d="M10 3h4v3l2.5 2.5V20a1.5 1.5 0 0 1-1.5 1.5H9A1.5 1.5 0 0 1 7.5 20V8.5L10 6V3z" />
            <path d="M7.5 13h9" />
            @break
        @case('daun')
            <path d="M6 20c8 0 13-5 13-14-9 0-14 5-14 14z" />
            <path d="M6 20c2-4 5-7 9-9" />
            @break
        @case('tag')
            <path d="M20 12.5l-7.5 7.5a2 2 0 0 1-2.8 0L3 13.3V4a1 1 0 0 1 1-1h9.3l6.7 6.7a2 2 0 0 1 0 2.8z" />
            <circle cx="7.5" cy="7.5" r="1.2" />
            @break
        @case('kartu')
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <path d="M2 10h20M6 15h4" />
            @break
        @case('struk')
            <path d="M5 3h14v18l-2.3-1.5L14.3 21 12 19.5 9.7 21l-2.4-1.5L5 21V3z" />
            <path d="M8.5 8h7M8.5 12h7M8.5 16h4" />
            @break
        @case('perisai')
            <path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3z" />
            @break
        @case('monitor')
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path d="M8 20h8M12 16v4" />
            @break
        @case('surat')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M3 7l9 6 9-6" />
            @break
        @case('catatan')
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <path d="M9 4V2.8h6V4M8.5 10h7M8.5 14h7M8.5 18h4" />
            @break
        @case('cari')
            <circle cx="11" cy="11" r="7" />
            <path d="M20 20l-4-4" />
            @break
        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
            @break
        @case('cetak')
            <path d="M6 9V3h12v6" />
            <rect x="4" y="9" width="16" height="8" rx="1.5" />
            <rect x="6" y="14" width="12" height="7" />
            @break
        @case('simpan')
            <path d="M5 3h11l3 3v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
            <path d="M7 3v5h8V3M7 21v-6h10v6" />
            @break
        @case('unduh')
            <path d="M12 3v12M7 10l5 5 5-5M4 20h16" />
            @break
        @case('segar')
            <path d="M20 11a8 8 0 0 0-14.5-4.6M4 4v4.5h4.5" />
            <path d="M4 13a8 8 0 0 0 14.5 4.6M20 20v-4.5h-4.5" />
            @break
        @case('mata')
            <path d="M2 12s3.8-7 10-7 10 7 10 7-3.8 7-10 7-10-7-10-7z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('kamera')
            <path d="M4 7h3l2-2.5h6L17 7h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z" />
            <circle cx="12" cy="13" r="3.5" />
            @break
        @case('lonceng')
            <path d="M6 9a6 6 0 0 1 12 0c0 6 2 8 2 8H4s2-2 2-8" />
            <path d="M10.3 20a2 2 0 0 0 3.4 0" />
            @break
        @case('keluar')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="M16 17l5-5-5-5M21 12H9" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('whatsapp')
            <path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 20l1.2-5A8.5 8.5 0 1 1 21 11.5z" />
            @break
        @case('uang')
            <rect x="2" y="6" width="20" height="12" rx="2" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('bank')
            <path d="M3 10h18M4 10l8-5 8 5M6 10v8M10 10v8M14 10v8M18 10v8M3 19h18" />
            @break
        @case('ponsel')
            <rect x="6" y="2" width="12" height="20" rx="2" />
            <path d="M11 18h2" />
            @break
        @case('roda')
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.65 1.65 0 0 0-1.8-.3 1.65 1.65 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.65 1.65 0 0 0-1-1.5 1.65 1.65 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.65 1.65 0 0 0 .3-1.8 1.65 1.65 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.65 1.65 0 0 0 1.5-1 1.65 1.65 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.65 1.65 0 0 0 1.8.3h.1a1.65 1.65 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.65 1.65 0 0 0 1 1.5 1.65 1.65 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.65 1.65 0 0 0-.3 1.8v.1a1.65 1.65 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.65 1.65 0 0 0-1.5 1z" />
            @break
        @case('robot')
            <rect x="4" y="7" width="16" height="12" rx="3" />
            <path d="M12 7V3M9 3h6M9 12h.01M15 12h.01M8.5 16h7" />
            @break
        @case('tangan')
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12h8" />
            @break
        @case('perbaikan')
            <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6 2 2 6-6a4 4 0 0 0 5.4-5.4l-2.3 2.3-2-2 2.3-2.3z" />
            @break
        @case('sapu')
            <circle cx="12" cy="12" r="9" />
            <path d="M8 15c1.5-2 2.5-3 4-6 1.5 3 2.5 4 4 6" />
            @break
        @case('jeda')
            <circle cx="12" cy="12" r="9" />
            <path d="M10 9v6M14 9v6" />
            @break
        @case('status-hijau')
            <circle cx="12" cy="12" r="5" fill="#15803d" stroke="none" />
            <circle cx="12" cy="12" r="8.5" stroke="#86efac" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
