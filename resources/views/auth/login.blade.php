<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Masuk — ADUKANAJA</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-body">
<div class="login-page auth-wrap">
    {{-- Latar dekoratif (tidak dibaca pembaca layar) --}}
    <div class="lg-art lg-building" aria-hidden="true"></div>
    <div class="lg-art lg-wave-top" aria-hidden="true"></div>
    <div class="lg-art lg-dots lg-dots-top" aria-hidden="true"></div>
    <div class="lg-art lg-dots lg-dots-bottom" aria-hidden="true"></div>

    <main class="login-main">
        <header class="login-brand">
            <img class="login-logo" src="{{ asset('img/logo.svg') }}" alt="Logo ADUKANAJA: gedung sekolah di atas buku terbuka dalam perisai">
            <h1>ADUKANAJA</h1>
            <p class="login-tagline"><span>Sistem Pengaduan Sarana Sekolah</span></p>
        </header>

        @if (session('error')) <div class="alert alert-error login-alert" role="alert">{{ session('error') }}</div> @endif
        @if (session('success')) <div class="alert alert-success login-alert" role="status">{{ session('success') }}</div> @endif

        <form class="login-card" method="POST" action="{{ route('login.proses') }}" novalidate>
            @csrf

            <div class="login-card-head">
                <svg class="lg-ico lg-ico-user" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7.5" r="4.6"/><path d="M3.2 21.5c0-4.6 3.9-7.6 8.8-7.6s8.8 3 8.8 7.6c0 .5-.4.9-.9.9H4.1a.9.9 0 0 1-.9-.9z"/></svg>
                <div>
                    <h2>Masuk ke Akun Anda</h2>
                    <p>Akses sistem pengaduan dengan akun yang valid</p>
                </div>
            </div>

            <div class="lg-field @error('login') has-error @enderror">
                <label class="sr-only" for="login">Email / NIS</label>
                <div class="lg-input">
                    <svg class="lg-ico lg-ico-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="M3 7l9 6.5L21 7"/></svg>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" placeholder="Email / NIS" autocomplete="username" autofocus required>
                </div>
                @error('login') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="lg-field @error('password') has-error @enderror">
                <label class="sr-only" for="password">Password</label>
                <div class="lg-input">
                    <svg class="lg-ico lg-ico-left" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 10V7.5a5 5 0 0 1 10 0V10h.5A2.5 2.5 0 0 1 20 12.5v7a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 19.5v-7A2.5 2.5 0 0 1 6.5 10H7zm2 0h6V7.5a3 3 0 0 0-6 0V10zm3 4.2a1.6 1.6 0 0 0-.8 3v1.6h1.6v-1.6a1.6 1.6 0 0 0-.8-3z"/></svg>
                    <input id="password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
                    <button class="lg-eye" type="button" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7c2.1 0 3.9.7 5.4 1.6M22 12s-3.6 7-10 7c-2.1 0-3.9-.7-5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button class="btn btn-primary btn-block lg-submit" type="submit">
                <svg class="lg-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M4 12h11M11 8l4 4-4 4"/></svg>
                Masuk
            </button>

            <div class="lg-or"><span>atau</span></div>

            <p class="lg-help">
                <svg class="lg-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M9.2 9.3a2.9 2.9 0 1 1 4.4 2.5c-.9.6-1.6 1.1-1.6 2.2" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>
                Lupa password? Hubungi admin sekolah.
            </p>
        </form>
    </main>

    <footer class="login-foot">
        <ul class="lg-features">
            <li>
                <svg class="lg-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.2l8 3v6.1c0 5-3.3 8.7-8 10.5-4.7-1.8-8-5.5-8-10.5V5.2l8-3zm-1.2 13.3l5.4-5.4-1.4-1.4-4 4-1.9-1.9-1.4 1.4 3.3 3.3z"/></svg>
                <span>Transparan <br>&amp; Terpercaya</span>
            </li>
            <li>
                <svg class="lg-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.5L4.5 13.4h6l-1.5 9.1 9.5-12.4h-6.2l1.2-8.6z"/></svg>
                <span>Respon Cepat</span>
            </li>
            <li>
                <svg class="lg-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>
                <span>Kolaboratif</span>
            </li>
        </ul>
    </footer>
</div>

<script>
    (function () {
        var btn = document.getElementById('toggle-password');
        var input = document.getElementById('password');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            btn.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            btn.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
            btn.classList.toggle('on', tampil);
            input.focus();
        });
    })();
</script>
</body>
</html>
