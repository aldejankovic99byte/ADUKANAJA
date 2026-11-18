@extends('layouts.app')
@php $edit = $sarana->exists; @endphp
@section('title', $edit ? 'Edit Sarana' : 'Tambah Sarana')

@section('content')
    <div class="page-head"><h1>{{ $edit ? 'Edit Sarana' : 'Tambah Sarana' }}</h1></div>

    <form class="card card-pad form-narrow" method="POST" action="{{ $edit ? route('admin.sarana.update', $sarana) : route('admin.sarana.store') }}" novalidate>
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="field @error('nama') has-error @enderror">
            <label for="nama">Nama sarana</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $sarana->nama) }}" placeholder="Contoh: Proyektor" required>
            @error('nama') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="field @error('lokasi') has-error @enderror">
            <label for="lokasi">Lokasi</label>
            <input id="lokasi" name="lokasi" type="text" value="{{ old('lokasi', $sarana->lokasi) }}" placeholder="Contoh: Lab Komputer" required>
            @error('lokasi') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-actions">
            <a class="btn btn-outline" href="{{ route('admin.sarana.index') }}">Batal</a>
            <button class="btn btn-dark" type="submit">Simpan</button>
        </div>
    </form>
@endsection