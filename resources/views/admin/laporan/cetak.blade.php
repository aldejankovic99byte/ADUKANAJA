<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pengaduan Sarana — {{ now()->format('Ymd') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="print-page">
    <div class="print-bar no-print">
        <a class="btn btn-outline" href="{{ route('admin.laporan', array_filter($f)) }}">← Kembali</a>
        <button class="btn btn-dark" type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>

    <h1>LAPORAN REKAPITULASI PENGADUAN SARANA SEKOLAH</h1>
    <p class="sub">
        ADUKANAJA — SMK Negeri 5 Surakarta<br>
        Periode:
        {{ $f['dari'] ? \Illuminate\Support\Carbon::parse($f['dari'])->translatedFormat('d M Y') : 'awal' }}
        s/d
        {{ $f['sampai'] ? \Illuminate\Support\Carbon::parse($f['sampai'])->translatedFormat('d M Y') : 'sekarang' }}
        · Status: {{ $f['status'] ? ucfirst($f['status']) : 'Semua' }}
    </p>

    <div class="print-sum">
        <span>Total: <strong>{{ $ringkasan['total'] }}</strong></span>
        <span>Pending: <strong>{{ $ringkasan['pending'] }}</strong></span>
        <span>Approved: <strong>{{ $ringkasan['approved'] }}</strong></span>
        <span>Rejected: <strong>{{ $ringkasan['rejected'] }}</strong></span>
    </div>

    <table>
        <thead><tr><th>No</th><th>ID</th><th>Tanggal</th><th>Siswa</th><th>Kategori</th><th>Lokasi</th><th>Deskripsi</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($pengaduan as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->kode() }}</td>
                <td>{{ $p->created_at->translatedFormat('d M Y') }}</td>
                <td>{{ $p->pelapor->name }}</td>
                <td>{{ $p->kategori->nama }}</td>
                <td>{{ $p->lokasi }}</td>
                <td>{{ \Illuminate\Support\Str::limit($p->deskripsi, 90) }}</td>
                <td>{{ ucfirst($p->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="small" style="margin-top:14px">Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB oleh {{ auth()->user()->name }}</p>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>