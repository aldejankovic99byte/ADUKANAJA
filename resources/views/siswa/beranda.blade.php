@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
    <section class="hero">
        <div>
            <h1>Halo, {{ auth()->user()->name }}</h1>
            <p>Berikut ringkasan pengaduan sarana yang sudah kamu ajukan.</p>
        </div>
        <div class="hero-actions">
            <a class="btn btn-light" href="{{ route('pengaduan.create') }}">+ Buat Pengaduan Baru</a>
        </div>
    </section>

    <div class="grid grid-3">
        <div class="card stat"><div class="label">Total Pengaduan</div><div class="value">{{ $total }}</div><span class="stat-ico"><x-icon name="inbox" /></span></div>
        <div class="card stat warning"><div class="label">Diproses</div><div class="value">{{ $diproses }}</div><span class="stat-ico"><x-icon name="jam" /></span></div>
        <div class="card stat success"><div class="label">Selesai</div><div class="value">{{ $selesai }}</div><span class="stat-ico"><x-icon name="cek" /></span></div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin:32px 0 12px">
        <div class="section-title" style="margin:0">Pengaduan terbaru</div>
        @if ($total > 0)
            <a href="{{ route('pengaduan.riwayat') }}" style="font-size:12.5px;font-weight:600;color:var(--accent)">Lihat Semua &rarr;</a>
        @endif
    </div>

    @if ($terbaru->isEmpty())
        <div class="card">
            <x-empty>Belum ada pengaduan. Temukan fasilitas yang rusak? <a href="{{ route('pengaduan.create') }}">Ajukan pengaduan pertamamu</a>.</x-empty>
        </div>
    @else
        <div class="card" style="overflow:hidden">
            <div class="table-wrap">
                <table class="stack">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($terbaru as $p)
                        <tr>
                            <td data-label="ID"><span class="table-code">{{ $p->kode() }}</span></td>
                            <td data-label="Foto">
                                <div class="thumb" style="width:40px;height:40px;border-radius:10px;font-size:13px">
                                    @if ($p->fotoUrl())
                                        <img src="{{ $p->fotoUrl() }}" alt="Foto bukti {{ $p->kode() }}">
                                    @else
                                        {{ mb_strtoupper(mb_substr($p->kategori->nama, 0, 1)) }}
                                    @endif
                                </div>
                            </td>
                            <td data-label="Kategori"><span class="table-cat">{{ $p->kategori->nama }}</span></td>
                            <td data-label="Lokasi">{{ $p->lokasi }}</td>
                            <td data-label="Tanggal" style="white-space:nowrap;color:#4A5D82">{{ $p->created_at->translatedFormat('d M Y') }}</td>
                            <td data-label="Status"><x-status-badge :status="$p->status" /></td>
                            <td data-label="Aksi"><a class="table-action" href="{{ route('pengaduan.show', $p) }}">Detail &rarr;</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($total > $terbaru->count())
        <p class="mt center"><a href="{{ route('pengaduan.riwayat') }}" style="font-weight:600">Lihat semua riwayat pengaduan ({{ $total }}) &rarr;</a></p>
    @endif
@endsection