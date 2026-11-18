@extends('layouts.app')
@php $edit = $kategori->exists; @endphp
@section('title', $edit ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
    <div class="page-head"><h1>{{ $edit ? 'Edit Kategori Kerusakan' : 'Tambah Kategori Kerusakan' }}</h1></div>

    <form class="card card-pad form-narrow" method="POST" action="{{ $edit ? route('admin.kategori.update', $kategori) : route('admin.kategori.store') }}" novalidate>
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="field @error('nama') has-error @enderror">
            <label for="nama">Nama kategori</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $kategori->nama) }}" placeholder="Contoh: Listrik" required>
            @error('nama') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-actions">
            <a class="btn btn-outline" href="{{ route('admin.sarana.index') }}">Batal</a>
            <button class="btn btn-dark" type="submit">Simpan</button>
        </div>
    </form>
@endsection