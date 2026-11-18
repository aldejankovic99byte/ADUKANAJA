@extends('layouts.app')
@section('title', 'Sarana & Kategori')

@section('content')
<style>
    /* Styling tabel sarana & kategori agar seragam dengan tema dominan Navy & Putih */
    .sarana-table th,
    .sarana-table td {
        font-family: inherit;
        font-size: 13.5px;
        color: #0A2463;
    }
    .sarana-table th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .sarana-table td {
        vertical-align: middle;
    }
    .sarana-table .badge-location {
        background: #F1F5FB;
        color: #0A2463;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid #DCE6F5;
        font-weight: 500;
    }
</style>

<div class="page-head" style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5EDF7">
    <h1 style="margin:0;font-size:24px;font-weight:800;color:#0A2463">Kelola Data Sarana &amp; Kategori Kerusakan</h1>
    <p style="color:#5B6F96;margin:4px 0 0;font-size:13.5px">Master data sarana prasarana sekolah SMK Rekayasa Perangkat Lunak (RPL) dan kategori kerusakan.</p>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr));align-items:start;gap:20px">
    <!-- Kolom Kiri: Data Sarana / Fasilitas -->
    <section>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
            <div style="display:flex;align-items:center;gap:8px">
                <span class="label" style="margin:0;font-size:12px;font-weight:700;letter-spacing:.05em;color:#0A2463">DATA SARANA / FASILITAS</span>
                <span class="badge" style="background:#E2EDFC;color:#0A2463;font-weight:700;font-size:11.5px;padding:2px 8px">{{ $sarana->count() }} Data</span>
            </div>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.sarana.create') }}" style="display:inline-flex;align-items:center;gap:6px">
                <x-icon name="tambah" style="width:14px;height:14px" />
                <span>+ Tambah Sarana</span>
            </a>
        </div>

        <div class="card" style="padding:0;overflow:hidden;border-radius:14px;border:1px solid #E5EDF7;box-shadow:0 2px 10px rgba(10,36,99,0.04)">
            <div style="padding:12px 16px;background:#F8FAFD;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px">
                <input type="text" id="filter-sarana" placeholder="Cari nama sarana atau lokasi..." style="width:100%;font-size:13px;padding:8px 12px;background:#FFFFFF;border:1px solid #DCE6F5;border-radius:8px">
            </div>

            @if ($sarana->isEmpty())
                <x-empty>Belum ada data sarana.</x-empty>
            @else
                <div class="table-wrap" style="max-height:680px;overflow-y:auto">
                    <table class="stack sarana-table" id="tabel-sarana" style="border-collapse:collapse;width:100%">
                        <thead style="position:sticky;top:0;background:#FFFFFF;z-index:2;box-shadow:0 1px 2px rgba(10,36,99,0.06)">
                            <tr>
                                <th style="padding:12px 14px;text-align:left">Nama Sarana</th>
                                <th style="padding:12px 14px;text-align:left">Lokasi</th>
                                <th style="text-align:right;padding:12px 14px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sarana as $s)
                                <tr>
                                    <td data-label="Nama Sarana" style="font-weight:600;color:#0A2463;padding:10px 14px">
                                        {{ $s->nama }}
                                    </td>
                                    <td data-label="Lokasi" style="padding:10px 14px">
                                        <span class="badge-location">{{ $s->lokasi }}</span>
                                    </td>
                                    <td data-label="Aksi" style="text-align:right;padding:10px 14px">
                                        <div class="actions" style="justify-content:flex-end;gap:12px">
                                            <a href="{{ route('admin.sarana.edit', $s) }}" style="font-weight:600;font-size:13px;color:#1D4FA8">Edit</a>
                                            <form method="POST" action="{{ route('admin.sarana.destroy', $s) }}" onsubmit="return confirm('Hapus sarana {{ e($s->nama) }}?')">
                                                @csrf @method('DELETE')
                                                <button class="link-danger" type="submit" style="font-weight:600;font-size:13px;border:none;background:none;cursor:pointer">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <!-- Kolom Kanan: Kategori Kerusakan -->
    <section>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
            <div style="display:flex;align-items:center;gap:8px">
                <span class="label" style="margin:0;font-size:12px;font-weight:700;letter-spacing:.05em;color:#0A2463">KATEGORI KERUSAKAN</span>
                <span class="badge" style="background:#E2EDFC;color:#0A2463;font-weight:700;font-size:11.5px;padding:2px 8px">{{ $kategori->count() }} Kategori</span>
            </div>
            <a class="btn btn-outline btn-sm" href="{{ route('admin.kategori.create') }}" style="display:inline-flex;align-items:center;gap:6px">
                <x-icon name="tambah" style="width:14px;height:14px" />
                <span>+ Tambah Kategori</span>
            </a>
        </div>

        <div class="card" style="padding:0;overflow:hidden;border-radius:14px;border:1px solid #E5EDF7;box-shadow:0 2px 10px rgba(10,36,99,0.04)">
            @if ($kategori->isEmpty())
                <x-empty>Belum ada kategori.</x-empty>
            @else
                <div class="table-wrap">
                    <table class="stack" style="border-collapse:collapse;width:100%">
                        <thead style="background:#F8FAFD;box-shadow:0 1px 2px rgba(10,36,99,0.06)">
                            <tr>
                                <th style="padding:12px 14px;text-align:left;font-weight:600;color:#0A2463">Nama Kategori</th>
                                <th style="padding:12px 14px;text-align:left;font-weight:600;color:#0A2463">Digunakan</th>
                                <th style="text-align:right;padding:12px 14px;font-weight:600;color:#0A2463">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategori as $k)
                                <tr>
                                    <td data-label="Nama Kategori" style="font-weight:600;color:#0A2463;padding:10px 14px">
                                        {{ $k->nama }}
                                    </td>
                                    <td data-label="Digunakan" style="padding:10px 14px">
                                        @if ($k->pengaduan_count > 0)
                                            <span class="badge" style="background:#EEF4FD;color:#1D4FA8;font-weight:700;font-size:11.5px;border:1px solid #D0E0F7">
                                                {{ $k->pengaduan_count }}× laporan
                                            </span>
                                        @else
                                            <span style="color:#8898AA;font-size:12px;font-weight:500">0× dipakai</span>
                                        @endif
                                    </td>
                                    <td data-label="Aksi" style="text-align:right;padding:10px 14px">
                                        <div class="actions" style="justify-content:flex-end;gap:12px">
                                            <a href="{{ route('admin.kategori.edit', $k) }}" style="font-weight:600;font-size:13px;color:#1D4FA8">Edit</a>
                                            <form method="POST" action="{{ route('admin.kategori.destroy', $k) }}" onsubmit="return confirm('Hapus kategori {{ e($k->nama) }}?')">
                                                @csrf @method('DELETE')
                                                <button class="link-danger" type="submit" style="font-weight:600;font-size:13px;border:none;background:none;cursor:pointer">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('filter-sarana');
        var table = document.getElementById('tabel-sarana');
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                var query = this.value.toLowerCase().trim();
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
                });
            });
        }
    });
</script>
@endsection
