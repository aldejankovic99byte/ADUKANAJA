@props(['name'])
@php
    $paths = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'tambah' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'riwayat' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'profil' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'pengguna' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 010 7M18 14.5c2.5.6 4 2.3 4 5.5"/>',
        'sarana' => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'verifikasi' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'laporan' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'tugas' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h9"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'tutup' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'inbox' => '<path d="M4 13l2-8h12l2 8"/><path d="M4 13v6a1 1 0 001 1h14a1 1 0 001-1v-6h-5l-1 2h-4l-1-2z"/>',
        'jam' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'cek' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'silang' => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'kunci' => '<path d="M14.7 6.3a4 4 0 01-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 005.4-5.4l-2.4 2.4-2.6-.6-.6-2.6z"/>',
        'pasir' => '<path d="M6 3h12M6 21h12M7 3v4l5 5-5 5v4M17 3v4l-5 5 5 5v4"/>',
        'user-plus' => '<circle cx="10" cy="8" r="4"/><path d="M2 21c0-4 4-6 8-6M19 8v6M16 11h6"/>',
        'lonceng' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'sampah' => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-5.4A8 8 0 1 1 21 12z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    ];
@endphp
<svg {{ $attributes->class(['ico']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
