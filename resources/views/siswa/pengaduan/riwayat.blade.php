@extends('layouts.app')
@section('title', 'Riwayat Pengaduan Saya')

@section('content')
    <!-- Header & Filter -->
    <div class="page-head" style="margin-bottom:24px">
        <h1 style="margin:0;font-size:24px;font-weight:800;color:#0A2463">Riwayat Pengaduan Saya</h1>
        <form method="GET" action="{{ route('pengaduan.riwayat') }}" style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <label class="sr-only" for="status">Filter status</label>
            <select id="status" name="status" onchange="this.form.submit()" style="min-width:140px;padding:6px 10px;font-size:13px;border:1px solid #DCE6F5;border-radius:8px;background:#FFFFFF">
                <option value="" {{ $status === null ? 'selected' : '' }}>Semua Status</option>
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                    <option value="{{ $val }}" {{ $status === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <noscript>
                <button class="btn btn-outline btn-sm" type="submit" style="font-size:12.5px;padding:4px 10px">Terapkan</button>
            </noscript>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        @if ($pengaduan->isEmpty())
            <x-empty>{{ $status ? 'Tidak ada pengaduan dengan status tersebut.' : 'Kamu belum pernah mengajukan pengaduan.' }}</x-empty>
        @else
            <div class="table-wrap" style="max-height:720px;overflow-y:auto">
                <table class="stack" style="border-collapse:collapse;width:100%">
                    <thead style="background:#F8FAFD;position:sticky;top:0;z-index:2;box-shadow:0 1px 2px rgba(10,36,99,0.06)">
                        <tr>
                            <th style="padding:12px 8px;text-align:left;font-weight:600;color:#0A2463">ID</th>
                            <th style="padding:12px 8px;text-align:left;font-weight:600;color:#0A2463">Tanggal</th>
                            <th style="padding:12px 8px;text-align:left;font-weight:600;color:#0A2463">Kategori</th>
                            <th style="padding:12px 8px;text-align:left;font-weight:600;color:#0A2463">Lokasi</th>
                            <th style="padding:12px 8px;text-align:left;font-weight:600;color:#0A2463">Status</th>
                            <th style="padding:12px 8px;text-align:center;font-weight:600;color:#0A2463">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengaduan as $p)
                            <tr>
                                <td data-label="ID" style="padding:10px 8px"><span class="table-code" style="font-weight:600;color:#0A2463">{{ $p->kode() }}</span></td>
                                <td data-label="Tanggal" style="padding:10px 8px;white-space:nowrap;font-size:13px;color:#0A2463">{{ $p->created_at->translatedFormat('d M Y, H:i') }} WIB</td>
                                <td data-label="Kategori" style="padding:10px 8px"><span class="badge" style="background:#EEF4FD;color:#1D4FA8;font-weight:600;font-size:12px;border:1px solid #D2E3FC">{{ $p->kategori->nama }}</span></td>
                                <td data-label="Lokasi" style="padding:10px 8px">{{ $p->lokasi }}</td>
                                <td data-label="Status" style="padding:10px 8px"><x-status-badge :status="$p->status" /></td>
                                <td data-label="Aksi" style="padding:10px 8px;text-align:center">
                                    <a class="btn btn-primary btn-sm" href="{{ route('pengaduan.show', $p) }}" style="font-size:12px;padding:4px 8px;border-radius:6px">Detail →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $pengaduan->links() }}
@endsection
