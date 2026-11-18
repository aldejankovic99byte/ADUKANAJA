@extends('layouts.app')
@section('title', 'Daftar Tugas')

@section('content')
    <div class="page-head">
        <div>
            <h1>Daftar Tugas Saya</h1>
            <p>Pengaduan yang ditugaskan kepada kamu dan belum selesai.</p>
        </div>
    </div>

    <div class="chips">
        <a class="chip @if (! $status) on @endif" href="{{ route('teknisi.tugas.index', array_filter(['q' => $q])) }}">Semua</a>
        @foreach ($filterStatus as $s)
            <a class="chip @if ($status === $s) on @endif" href="{{ route('teknisi.tugas.index', array_filter(['status' => $s, 'q' => $q])) }}">{{ \App\Models\Pengaduan::penangananLabels()[$s] }}</a>
        @endforeach
    </div>

    <form class="filters" method="GET" action="{{ route('teknisi.tugas.index') }}" style="margin-bottom:14px">
        @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
        <div class="field" style="min-width:260px">
            <label class="sr-only" for="q" style="position:absolute;left:-9999px">Cari tugas</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari lokasi atau deskripsi…">
        </div>
        <button class="btn btn-outline" type="submit">Cari</button>
        @if ($q !== '') <a class="btn btn-outline" href="{{ route('teknisi.tugas.index', array_filter(['status' => $status])) }}">Reset</a> @endif
    </form>

    <div class="card">
        @if ($daftar->isEmpty())
            <x-empty>{{ ($status || $q !== '') ? 'Tidak ada tugas yang cocok dengan filter.' : 'Belum ada tugas aktif untuk kamu.' }}</x-empty>
        @else
            <div class="table-wrap">
                <table class="stack">
                    <thead><tr><th>ID</th><th>Kategori</th><th>Lokasi</th><th>Pelapor</th><th>Ditugaskan</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($daftar as $p)
                        <tr>
                            <td data-label="ID"><span class="table-code">{{ $p->kode() }}</span></td>
                            <td data-label="Kategori"><span class="table-cat">{{ $p->kategori->nama }}</span></td>
                            <td data-label="Lokasi">{{ $p->lokasi }}@if ($p->sarana) <div class="small muted">{{ $p->sarana->nama }}</div>@endif</td>
                            <td data-label="Pelapor"><strong>{{ $p->pelapor->name }}</strong></td>
                            <td data-label="Ditugaskan" style="white-space:nowrap;color:#4A5D82">{{ $p->ditugaskan_pada?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td data-label="Status"><x-status-penanganan :status="$p->status_penanganan" /></td>
                            <td data-label="Aksi"><a class="table-action" href="{{ route('teknisi.tugas.show', $p->id) }}">Detail &rarr;</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $daftar->links() }}
        @endif
    </div>
@endsection
