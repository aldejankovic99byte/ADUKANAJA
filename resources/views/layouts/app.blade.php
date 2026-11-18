<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Beranda') — ADUKANAJA</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $user = auth()->user();

    $menu = [];
    // Menu hanya dimuat jika user sudah login; isi menu mengikuti role
    if ($user) {
        if ($user->isAdmin()) {
            $menu = [
                ['Dashboard', 'dashboard', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
                ['Pengguna', 'pengguna', route('admin.pengguna.index'), request()->routeIs('admin.pengguna.*')],
                ['Sarana', 'sarana', route('admin.sarana.index'), request()->routeIs('admin.sarana.*', 'admin.kategori.*')],
                ['Verifikasi', 'verifikasi', route('admin.verifikasi'), request()->routeIs('admin.verifikasi*')],
                ['Laporan', 'laporan', route('admin.laporan'), request()->routeIs('admin.laporan*')],
            ];
        } elseif ($user->isTeknisi()) {
            $menu = [
                ['Dashboard', 'dashboard', route('teknisi.dashboard'), request()->routeIs('teknisi.dashboard')],
                ['Tugas', 'tugas', route('teknisi.tugas.index'), request()->routeIs('teknisi.tugas.*')],
                ['Riwayat', 'riwayat', route('teknisi.riwayat'), request()->routeIs('teknisi.riwayat')],
                ['Profil', 'profil', route('profil'), request()->routeIs('profil')],
            ];
        } else {
            $menu = [
                ['Beranda', 'home', route('beranda'), request()->routeIs('beranda')],
                ['Ajukan', 'tambah', route('pengaduan.create'), request()->routeIs('pengaduan.create')],
                ['Riwayat', 'riwayat', route('pengaduan.riwayat'), request()->routeIs('pengaduan.riwayat', 'pengaduan.show')],
                ['Profil', 'profil', route('profil'), request()->routeIs('profil')],
            ];
        }
    }
@endphp

@php
    // Notifikasi in-app (lonceng di topbar)
    $notifJumlah = $user ? $user->notifikasi()->whereNull('dibaca_pada')->count() : 0;
    $notifTerbaru = $user ? $user->notifikasi()->latest()->latest('id')->limit(5)->get() : collect();
@endphp

@auth
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a class="sidebar-brand" href="{{ route('home') }}">
        <img class="brand-logo" src="{{ asset('img/logo-putih.svg') }}" alt="">
        <span><strong>ADUKANAJA</strong><small>Pengaduan Sarana Sekolah</small></span>
    </a>

    <nav class="nav" aria-label="Menu utama">
        <div class="nav-label">Menu</div>
        @foreach ($menu as [$label, $ik, $url, $active])
            <a href="{{ $url }}" @class(['active' => $active]) @if($active) aria-current="page" @endif>
                <x-icon :name="$ik" class="nav-ico" />
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-foot">
        <strong>{{ $user->name }}</strong>
        {{ $user->roleLabel() }}
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" data-drawer-close></div>
@endauth

<div class="shell">
    <header class="topbar">
        <div class="topbar-inner">
            @auth
                <button type="button" class="menu-btn" id="menu-btn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false" data-drawer-toggle>
                    <x-icon name="menu" />
                </button>
            @endauth
            <div class="topbar-title">@yield('title', 'Beranda')</div>

            @auth
            <details class="notif-menu" id="notif-menu">
                <summary class="notif-btn" aria-label="Notifikasi{{ $notifJumlah > 0 ? ', '.$notifJumlah.' belum dibaca' : '' }}">
                    <x-icon name="lonceng" />
                    @if ($notifJumlah > 0)<span class="notif-badge">{{ $notifJumlah > 99 ? '99+' : $notifJumlah }}</span>@endif
                </summary>
                <div class="menu-pop notif-pop">
                    <div class="notif-head">
                        <strong>Notifikasi</strong>
                        @if ($notifJumlah > 0)
                            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                                @csrf
                                <button type="submit" class="notif-link">Tandai semua dibaca</button>
                            </form>
                        @endif
                    </div>
                    @forelse ($notifTerbaru as $n)
                        <a class="notif-item @if (! $n->sudahDibaca()) unread @endif" href="{{ route('notifikasi.buka', $n->id) }}">
                            <span class="notif-judul">{{ $n->judul }}</span>
                            @if ($n->pesan)<span class="notif-pesan">{{ $n->pesan }}</span>@endif
                            <span class="notif-waktu">{{ $n->created_at->locale('id')->diffForHumans() }}</span>
                        </a>
                    @empty
                        <div class="notif-kosong">Belum ada notifikasi.</div>
                    @endforelse
                    <a class="notif-semua" href="{{ route('notifikasi.index') }}">Lihat semua notifikasi</a>
                </div>
            </details>

            <details class="user-menu" id="user-menu">
                <summary aria-label="Menu akun">
                    <x-avatar :user="$user" size="sm" />
                    <span class="user-name">{{ $user->name }}</span>
                </summary>
                <div class="menu-pop">
                    <div class="role"><strong>{{ $user->name }}</strong>{{ $user->roleLabel() }}</div>
                    <a href="{{ route('profil') }}">Profil &amp; foto</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Keluar</button>
                    </form>
                </div>
            </details>
            @endauth
        </div>
    </header>

    <div class="toasts" id="toasts" aria-live="polite">
        @if (session('success'))
            <div class="toast success" role="status"><x-icon name="cek" /><div class="toast-msg">{{ session('success') }}</div><button type="button" class="toast-close" aria-label="Tutup notifikasi">&times;</button></div>
        @endif
        @if (session('error'))
            <div class="toast error" role="alert"><x-icon name="silang" /><div class="toast-msg">{{ session('error') }}</div><button type="button" class="toast-close" aria-label="Tutup notifikasi">&times;</button></div>
        @endif
    </div>

    <main class="container">
        @yield('content')
    </main>
</div>

<script>
    (function () {
        var body = document.body;
        function setDrawer(open) {
            body.classList.toggle('drawer-open', open);
            var b = document.getElementById('menu-btn');
            if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-drawer-toggle]')) { setDrawer(!body.classList.contains('drawer-open')); return; }
            if (e.target.closest('[data-drawer-close]') || e.target.closest('#sidebar a')) setDrawer(false);

            // tutup menu akun / notifikasi saat klik di luar
            document.querySelectorAll('details.user-menu[open], details.notif-menu[open]').forEach(function (m) {
                if (!m.contains(e.target)) m.open = false;
            });

            // tutup toast
            var c = e.target.closest('.toast-close');
            if (c) dismiss(c.closest('.toast'));
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setDrawer(false); });
        window.addEventListener('resize', function () { if (window.innerWidth > 1024) setDrawer(false); });

        function dismiss(t) { if (!t) return; t.classList.add('out'); setTimeout(function () { t.remove(); }, 250); }
        document.querySelectorAll('.toast.success').forEach(function (t) { setTimeout(function () { dismiss(t); }, 6000); });
        document.querySelectorAll('.toast.error').forEach(function (t) { setTimeout(function () { dismiss(t); }, 12000); });
    })();
</script>
@stack('scripts')
</body>
</html>
