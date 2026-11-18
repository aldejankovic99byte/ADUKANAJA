@extends('layouts.app')
@section('title', 'Riwayat Pekerjaan')

@section('content')
    <div class="page-head">
        <div>
            <h1>Riwayat Pekerjaan</h1>
            <p>Tugas yang sudah dinyatakan selesai.</p>
        </div>
    </div>

    <div class="card">
        @if ($riwayat->isEmpty())
            <x-empty>Belum ada pekerjaan yang selesai.</x-empty>
        @else
            <div class="table-wrap">
                <table class="stack">
                    <thead><tr><th>ID</th><th>Kategori</th><th>Lokasi</th><th>Ditugaskan</th><th>Selesai</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($riwayat as $p)
                        <tr>
                            <td data-label="ID"><span class="table-code">{{ $p->kode() }}</span></td>
                            <td data-label="Kategori"><span class="table-cat">{{ $p->kategori->nama }}</span></td>
                            <td data-label="Lokasi">{{ $p->lokasi }}</td>
                            <td data-label="Ditugaskan" style="white-space:nowrap;color:#4A5D82">{{ $p->ditugaskan_pada?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td data-label="Selesai" style="white-space:nowrap;color:#4A5D82">{{ ($p->selesai_pada ?? $p->updated_at)->translatedFormat('d M Y') }}</td>
                            <td data-label="Aksi"><a class="table-action" href="{{ route('teknisi.tugas.show', $p->id) }}">Detail &rarr;</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $riwayat->links() }}
        @endif
    </div>
@endsection
