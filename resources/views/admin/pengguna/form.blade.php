@extends('layouts.app')
@php $edit = $user->exists; @endphp
@section('title', $edit ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('content')
    <div class="page-head"><h1>{{ $edit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1></div>

    <form class="card card-pad form-narrow" method="POST" action="{{ $edit ? route('admin.pengguna.update', $user) : route('admin.pengguna.store') }}" novalidate>
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="field @error('name') has-error @enderror">
            <label for="name">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field @error('role') has-error @enderror">
            <label for="role">Role</label>
            @php
                // Normalisasi: role 'siswa' (lama) ditampilkan sebagai 'user' di form
                $currentRole = old('role', $user->role);
                if ($currentRole === 'siswa') $currentRole = 'user';
            @endphp
            <select id="role" name="role" @disabled($edit && $user->id === auth()->id())>
                @foreach (\App\Models\User::roleLabels() as $v => $l)
                    <option value="{{ $v }}" @selected($currentRole === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @if ($edit && $user->id === auth()->id()) <p class="hint">Role akun yang sedang dipakai tidak dapat diubah.</p> @endif
            @error('role') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field @error('email') has-error @enderror">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-row">
            <div class="field @error('nis') has-error @enderror">
                <label for="nis">NIS <span style="text-transform:none;letter-spacing:0;font-weight:500">(wajib untuk siswa, kosongkan untuk teknisi/admin)</span></label>
                <input id="nis" name="nis" type="text" value="{{ old('nis', $user->nis) }}">
                @error('nis') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field @error('kelas') has-error @enderror">
                <label for="kelas">Kelas</label>
                <input id="kelas" name="kelas" type="text" value="{{ old('kelas', $user->kelas) }}" placeholder="XII RPL B">
                @error('kelas') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field @error('password') has-error @enderror">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" @required(! $edit)>
            <p class="hint">{{ $edit ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.' }}</p>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-actions">
            <a class="btn btn-outline" href="{{ route('admin.pengguna.index') }}">Batal</a>
            <button class="btn btn-dark" type="submit">Simpan</button>
        </div>
    </form>
@endsection