@extends('layouts.app')
@section('title', 'Profil')

@section('content')
    <div class="page-head"><h1>Profil Saya</h1></div>

    <form class="card card-pad" id="form-foto" method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data" style="margin-bottom:16px" novalidate>
        @csrf
        <div class="photo-row" style="margin-bottom:0">
            <span class="avatar avatar-xl" id="foto-preview">
                @if ($user->fotoUrl()) <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $user->name }}"> @else {{ $user->inisial() }} @endif
            </span>
            <div>
                <strong>{{ $user->name }}</strong>
                <div class="muted" style="margin-bottom:10px">{{ $user->roleLabel() }}</div>
                <div class="photo-actions">
                    <label class="btn btn-outline btn-sm" for="foto-input">Pilih foto</label>
                    <input id="foto-input" name="foto" type="file" accept="image/png,image/jpeg,image/webp">
                    <button class="btn btn-dark btn-sm hidden" id="foto-simpan" type="submit">Simpan foto</button>
                    @if ($user->foto)
                        <button class="btn btn-outline btn-sm link-danger" type="submit" form="form-foto-hapus" style="text-decoration:none">Hapus foto</button>
                    @endif
                </div>
                <p class="hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                <p class="field-error" id="foto-error">@error('foto') {{ $message }} @enderror</p>
            </div>
        </div>
    </form>
    @if ($user->foto)
        <form id="form-foto-hapus" method="POST" action="{{ route('profil.foto.hapus') }}">@csrf @method('DELETE')</form>
    @endif

    <div class="grid grid-2" style="align-items:start">
        <form class="card card-pad" method="POST" action="{{ route('profil.update') }}" novalidate>
            @csrf @method('PUT')

            <div class="field @error('name') has-error @enderror">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($user->isSiswa())
                <div class="field">
                    <label for="kelas">Kelas / NIS</label>
                    <input id="kelas" type="text" value="{{ trim($user->kelas.' / '.$user->nis, ' /') }}" readonly>
                    <p class="hint">Data kelas dan NIS hanya dapat diubah oleh admin.</p>
                </div>
            @endif

            <div class="field @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button class="btn btn-dark" type="submit">Simpan Perubahan</button>
        </form>

        <form class="card card-pad" method="POST" action="{{ route('profil.password') }}" novalidate>
            @csrf @method('PUT')
            <h2 style="margin-bottom:14px">Ubah Password</h2>

            <div class="field @error('password_lama') has-error @enderror">
                <label for="password_lama">Password lama</label>
                <input id="password_lama" name="password_lama" type="password" autocomplete="current-password" required>
                @error('password_lama') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field @error('password') has-error @enderror">
                <label for="password">Password baru</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <p class="hint">Minimal 8 karakter.</p>
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi password baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <button class="btn btn-outline" type="submit">Ubah Password</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('foto-input'), err = document.getElementById('foto-error'),
        btn = document.getElementById('foto-simpan'), prev = document.getElementById('foto-preview');
    input.addEventListener('change', function () {
        var f = input.files[0]; err.textContent = '';
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { err.textContent = 'Ukuran foto maksimal 2 MB.'; input.value = ''; btn.classList.add('hidden'); return; }
        if (!/^image\/(png|jpe?g|webp)$/.test(f.type)) { err.textContent = 'Format harus JPG, PNG, atau WEBP.'; input.value = ''; btn.classList.add('hidden'); return; }
        var r = new FileReader();
        r.onload = function (e) { prev.innerHTML = '<img alt="Pratinjau" src="' + e.target.result + '">'; };
        r.readAsDataURL(f);
        btn.classList.remove('hidden');
    });
})();
</script>
@endpush
