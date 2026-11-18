@extends('layouts.app')
@section('title', 'Dashboard Teknisi')

@section('content')
    <section class="hero">
        <div>
            <h1>Halo, {{ auth()->user()->name }}</h1>
            <p>Ringkasan pekerjaan perbaikan yang ditugaskan kepada kamu.</p>
        </div>
        <div class="hero-actions">
            <a class="btn btn-light" href="{{ route('teknisi.tugas.index') }}">Lihat daftar tugas →</a>
        </div>
    </section>

    <div class="stats-auto">
        <div class="card stat navy"><div class="label">Total tugas</div><div class="value">{{ $total }}</div><span class="stat-ico"><x-icon name="inbox" /></span></div>
        <div class="card stat info"><div class="label">Tugas baru</div><div class="value">{{ $baru }}</div><span class="stat-ico"><x-icon name="user-plus" /></span></div>
        <div class="card stat warning"><div class="label">Sedang dikerjakan</div><div class="value">{{ $dikerjakan }}</div><span class="stat-ico"><x-icon name="kunci" /></span></div>
        <div class="card stat purple"><div class="label">Menunggu validasi</div><div class="value">{{ $menunggu }}</div><span class="stat-ico"><x-icon name="pasir" /></span></div>
        <div class="card stat success"><div class="label">Pekerjaan selesai</div><div class="value">{{ $selesai }}</div><span class="stat-ico"><x-icon name="cek" /></span></div>
    </div>

    <div class="section-title">Tugas prioritas</div>

    @forelse ($prioritas as $p)
        <a class="card ticket s-{{ $p->status }}" href="{{ route('teknisi.tugas.show', $p->id) }}">
            <div class="thumb">
                @if ($p->fotoUrl()) <img src="{{ $p->fotoUrl() }}" alt="Foto kerusakan {{ $p->kode() }}"> @else {{ mb_strtoupper(mb_substr($p->kategori->nama, 0, 1)) }} @endif
            </div>
            <div class="ticket-body">
                <div class="ticket-title">{{ $p->kategori->nama }} — {{ $p->lokasi }}</div>
                <div class="ticket-meta">{{ $p->kode() }} · ditugaskan {{ $p->ditugaskan_pada?->translatedFormat('d M Y') ?? '-' }}</div>
            </div>
            <x-status-penanganan :status="$p->status_penanganan" />
        </a>
    @empty
        <div class="card">
            <x-empty>Belum ada tugas yang menunggu. Tugas baru akan muncul di sini setelah admin menugaskan kepada kamu.</x-empty>
        </div>
    @endforelse

    <p class="mt center">
        <a href="{{ route('teknisi.tugas.index') }}">Semua tugas aktif</a> ·
        <a href="{{ route('teknisi.riwayat') }}">Riwayat pekerjaan</a>
    </p>
@endsection
