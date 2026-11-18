@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
    @php $q = array_filter($f); @endphp

    <div class="page-head">
        <div>
            <h1>Laporan &amp; Rekapitulasi Pengaduan</h1>
            <p>Filter berdasarkan tanggal lapor dan status, lalu export.</p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.laporan.excel', $q) }}">Export Excel</a>
            <a class="btn btn-dark" href="{{ route('admin.laporan.cetak', $q) }}" target="_blank" rel="noopener">Export PDF</a>
        </div>
    </div>

    <form class="card card-pad filters" method="GET" action="{{ route('admin.laporan') }}">
        <div class="field"><label for="dari">Dari tanggal</label><input id="dari" name="dari" type="date" value="{{ $f['dari'] }}"></div>
        <div class="field"><label for="sampai">Sampai tanggal</label><input id="sampai" name="sampai" type="date" value="{{ $f['sampai'] }}"></div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua Status</option>
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $v => $l)
                    <option value="{{ $v }}" @selected($f['status'] === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-dark" type="submit">Filter</button>
        @if ($q) <a class="btn btn-outline" href="{{ route('admin.laporan') }}">Reset</a> @endif
    </form>

    <div class="grid grid-4 mt">
        <div class="card stat"><div class="label">Total</div><div class="value">{{ $ringkasan['total'] }}</div></div>
        <div class="card stat warning"><div class="label">Pending</div><div class="value">{{ $ringkasan['pending'] }}</div></div>
        <div class="card stat success"><div class="label">Approved</div><div class="value">{{ $ringkasan['approved'] }}</div></div>
        <div class="card stat error"><div class="label">Rejected</div><div class="value">{{ $ringkasan['rejected'] }}</div></div>
    </div>

    <div class="card mt" style="border-radius:14px;overflow:hidden;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7;padding:0">
        @if ($pengaduan->isEmpty())
            <x-empty>Tidak ada data untuk filter ini.</x-empty>
        @else
            <div class="table-wrap">
                <table class="stack" style="border-collapse:collapse;width:100%">
                    <thead style="background:#F8FAFD;box-shadow:0 1px 2px rgba(10,36,99,0.06)">
                        <tr>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">ID</th>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">Siswa</th>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">Kategori</th>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">Lokasi</th>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">Tanggal</th>
                            <th style="padding:12px 14px;text-align:left;font-weight:700;color:#0A2463">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($pengaduan as $p)
                        <tr>
                            <td data-label="ID" style="padding:11px 14px"><span class="table-code" style="font-weight:700;color:#1D4FA8">{{ $p->kode() }}</span></td>
                            <td data-label="Siswa" style="padding:11px 14px"><strong style="color:#0A2463">{{ $p->pelapor->name }}</strong></td>
                            <td data-label="Kategori" style="padding:11px 14px"><span class="badge" style="background:#EEF4FD;color:#1D4FA8;font-weight:600;font-size:12px;border:1px solid #D2E3FC">{{ $p->kategori->nama }}</span></td>
                            <td data-label="Lokasi" style="padding:11px 14px"><span style="display:inline-block;background:#F1F5FB;color:#0A2463;padding:2px 8px;border-radius:6px;font-size:12px;border:1px solid #DCE6F5">{{ $p->lokasi }}</span></td>
                            <td data-label="Tanggal" style="padding:11px 14px;white-space:nowrap;font-size:13px;color:#0A2463">{{ $p->created_at->translatedFormat('d M Y, H:i') }} WIB</td>
                            <td data-label="Status" style="padding:11px 14px"><x-status-badge :status="$p->status" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 16px">
                {{ $pengaduan->links() }}
            </div>
        @endif
    </div>
@endsection