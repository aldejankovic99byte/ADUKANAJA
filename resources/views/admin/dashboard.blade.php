@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <section class="hero">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Rekap pengaduan sarana sekolah secara keseluruhan.</p>
        </div>
        <div class="hero-actions">
            @if ($menungguValidasi > 0)
                <a class="btn btn-light" href="{{ route('admin.verifikasi', ['penanganan' => 'waiting_confirmation']) }}">Validasi {{ $menungguValidasi }} hasil pekerjaan →</a>
            @endif
            @if ($pending > 0)
                <a class="btn btn-ghost-light" href="{{ route('admin.verifikasi', ['status' => 'pending']) }}">Verifikasi {{ $pending }} pengaduan pending →</a>
            @endif
        </div>
    </section>

    <div class="stats-auto">
        <div class="card stat"><div class="label">Total</div><div class="value">{{ $total }}</div><span class="stat-ico"><x-icon name="inbox" /></span></div>
        <div class="card stat warning"><div class="label">Pending</div><div class="value">{{ $pending }}</div><span class="stat-ico"><x-icon name="jam" /></span></div>
        <div class="card stat success"><div class="label">Approved</div><div class="value">{{ $approved }}</div><span class="stat-ico"><x-icon name="cek" /></span></div>
        <div class="card stat error"><div class="label">Rejected</div><div class="value">{{ $rejected }}</div><span class="stat-ico"><x-icon name="silang" /></span></div>
    </div>

    <div class="section-title">Penanganan perbaikan</div>
    <div class="stats-auto">
        <div class="card stat info"><div class="label">Belum ditugaskan</div><div class="value">{{ $belumDitugaskan }}</div><span class="stat-ico"><x-icon name="user-plus" /></span></div>
        <div class="card stat warning"><div class="label">Sedang diperbaiki</div><div class="value">{{ $sedangDiperbaiki }}</div><span class="stat-ico"><x-icon name="kunci" /></span></div>
        <div class="card stat purple"><div class="label">Menunggu validasi</div><div class="value">{{ $menungguValidasi }}</div><span class="stat-ico"><x-icon name="pasir" /></span></div>
        <div class="card stat success"><div class="label">Selesai</div><div class="value">{{ $selesaiTuntas }}</div><span class="stat-ico"><x-icon name="tugas" /></span></div>
    </div>

    <div class="chart-grid mt-lg">
        <div class="card chart-card">
            <div class="chart-head">
                <div class="label">Pengaduan per bulan</div>
                <div class="seg" role="group" aria-label="Rentang waktu">
                    <button type="button" data-range="6" class="on">6 bln</button>
                    <button type="button" data-range="12">12 bln</button>
                </div>
            </div>
            <div id="chart-bulan"></div>
        </div>
        <div class="card chart-card">
            <div class="label" style="margin-bottom:12px">Komposisi status</div>
            <div id="chart-status"></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="card chart-card">
            <div class="label" style="margin-bottom:8px">Kategori terbanyak</div>
            <div id="chart-kategori"></div>
        </div>
        <div class="card" style="min-width:0;overflow:hidden">
            <div style="padding:14px 18px;border-bottom:1px solid #EDF2FA;display:flex;align-items:center;justify-content:space-between">
                <div class="label" style="margin:0;font-size:12px;font-weight:700;color:#0B2A6B;letter-spacing:.05em">Pengaduan Terbaru</div>
                <span class="badge" style="background:#EEF3FB;color:#0B2A6B;font-size:11px;font-weight:600">6 Terakhir</span>
            </div>
            @if ($terbaru->isEmpty())
                <x-empty>Belum ada pengaduan masuk.</x-empty>
            @else
                <div class="table-wrap">
                    <table class="stack">
                        <thead><tr><th>Siswa</th><th>Kategori</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @foreach ($terbaru as $p)
                            <tr>
                                <td data-label="Siswa"><strong>{{ $p->pelapor->name }}</strong></td>
                                <td data-label="Kategori"><span class="table-cat">{{ $p->kategori->nama }}</span></td>
                                <td data-label="Lokasi">{{ $p->lokasi }}</td>
                                <td data-label="Status"><x-status-badge :status="$p->status" /></td>
                                <td data-label="Aksi"><a class="table-action" href="{{ route('admin.verifikasi', ['id' => $p->id]) }}#detail">Tinjau &rarr;</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script id="chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/charts.js') }}"></script>
@endpush
