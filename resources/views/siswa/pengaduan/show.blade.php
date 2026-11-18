@extends('layouts.app')
@section('title', 'Detail Pengaduan ' . $pengaduan->kode())

@section('content')
<style>
    /* Styling khusus Halaman Detail Pengaduan Siswa / User - Tema Modern Navy & Putih */
    .siswa-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 640px) {
        .siswa-meta-grid {
            grid-template-columns: 1fr;
        }
    }
    .siswa-meta-item {
        background: #FAFBFD;
        border: 1px solid #E8F0FA;
        border-radius: 10px;
        padding: 10px 14px;
        min-width: 0;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .siswa-meta-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #5B6F96;
        margin-bottom: 6px;
    }
    .siswa-meta-val {
        font-size: 13.5px;
        font-weight: 600;
        color: #0A2463;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .siswa-cat-badge {
        display: inline-block;
        background: #EEF4FD;
        color: #1D4FA8;
        font-weight: 600;
        font-size: 12.5px;
        line-height: 1.45;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #D2E3FC;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
    .siswa-loc-badge {
        display: inline-block;
        background: #F1F5FB;
        color: #0A2463;
        font-weight: 600;
        font-size: 12.5px;
        line-height: 1.45;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #DCE6F5;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
</style>

    <!-- Tombol Kembali -->
    <div style="margin-bottom: 14px">
        <a class="btn btn-outline btn-sm" href="{{ route('pengaduan.riwayat') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;padding:6px 14px;border-radius:8px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            <span>Kembali ke Riwayat</span>
        </a>
    </div>

    <!-- Header Halaman Detail (Tanpa Teks Duplikat) -->
    <div class="page-head" style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5EDF7;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1D4FA8;background:#EEF4FD;padding:4px 10px;border-radius:6px;border:1px solid #D2E3FC">ID PENGADUAN</span>
                <h1 style="margin:0;font-size:22px;font-weight:800;color:#0A2463;letter-spacing:0">{{ $pengaduan->kode() }}</h1>
            </div>
            <p style="margin:6px 0 0;font-size:13px;color:#5B6F96">
                Diajukan pada {{ $pengaduan->created_at->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>
        <div>
            <x-status-badge :status="$pengaduan->status" />
        </div>
    </div>

    <!-- Layout Grid 2 Kolom -->
    <div class="grid grid-2" style="align-items:start;gap:20px">
        <!-- Kolom Kiri: Informasi Fasilitas, Kerusakan & Tanggapan Admin -->
        <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7;background:#FFFFFF">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #EDF2F9">
                <x-icon name="info" style="width:18px;height:18px;color:#0A2463" />
                <span style="font-size:15px;font-weight:700;color:#0A2463">Informasi Fasilitas &amp; Kerusakan</span>
            </div>

            <!-- Detail Grid Info Terstruktur -->
            <div class="siswa-meta-grid">
                <div class="siswa-meta-item">
                    <span class="siswa-meta-label">Kategori Kerusakan</span>
                    <div class="siswa-meta-val">
                        <span class="siswa-cat-badge">
                            {{ $pengaduan->kategori->nama }}
                        </span>
                    </div>
                </div>

                <div class="siswa-meta-item">
                    <span class="siswa-meta-label">Tanggal Kejadian</span>
                    <div class="siswa-meta-val" style="color:#1F2937">
                        {{ $pengaduan->tanggal_kejadian->translatedFormat('d F Y') }}
                    </div>
                </div>

                <div class="siswa-meta-item">
                    <span class="siswa-meta-label">Lokasi / Ruangan</span>
                    <div class="siswa-meta-val">
                        <span class="siswa-loc-badge">
                            {{ $pengaduan->lokasi }}
                        </span>
                    </div>
                </div>

                @if ($pengaduan->sarana)
                    <div class="siswa-meta-item">
                        <span class="siswa-meta-label">Sarana Terkait</span>
                        <div class="siswa-meta-val" style="color:#0A2463">
                            {{ $pengaduan->sarana->nama }} <span style="font-size:12px;color:#5B6F96;font-weight:500">· {{ $pengaduan->sarana->lokasi }}</span>
                        </div>
                    </div>
                @else
                    <div class="siswa-meta-item">
                        <span class="siswa-meta-label">Sarana Spesifik</span>
                        <div class="siswa-meta-val" style="color:#64748B;font-weight:500;font-size:13px">
                            Tidak ditentukan
                        </div>
                    </div>
                @endif

                <div class="siswa-meta-item" style="grid-column: 1 / -1">
                    <span class="siswa-meta-label">Deskripsi Kerusakan</span>
                    <div style="font-size:13.5px;color:#1F2937;line-height:1.6;white-space:pre-line;word-break:break-word">
                        {{ $pengaduan->deskripsi }}
                    </div>
                </div>
            </div>

            <!-- Bagian Tanggapan Admin -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #EDF2F9">
                <span style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#5B6F96;margin-bottom:8px">
                    Tanggapan Petugas Admin
                </span>

                @if ($pengaduan->status === 'pending' && ! $pengaduan->tanggapan)
                    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px">
                        <x-icon name="jam" style="width:18px;height:18px;color:#D97706;flex:none" />
                        <span style="font-size:13px;color:#92400E">Pengaduan kamu telah masuk dan sedang menunggu antrean verifikasi admin sekolah.</span>
                    </div>
                @else
                    <div style="background:#F4F8FD;border:1px solid #D2E3FC;border-left:4px solid #0A2463;border-radius:10px;padding:14px 18px">
                        <p style="margin:0;font-size:13.5px;color:#0A2463;line-height:1.6;white-space:pre-line;font-weight:500">
                            {{ $pengaduan->tanggapan ?: 'Tidak ada catatan tambahan.' }}
                        </p>
                        @if ($pengaduan->verifikator)
                            <div style="margin-top:10px;font-size:12px;color:#5B6F96;display:flex;align-items:center;gap:6px">
                                <span>— <strong style="color:#0A2463">{{ $pengaduan->verifikator->name }}</strong> (Admin Sekolah)</span>
                                <span>· {{ $pengaduan->diverifikasi_pada?->translatedFormat('d M Y, H:i') }} WIB</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Status Penanganan, Timeline & Bukti Foto -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <!-- Kartu Status & Penanganan -->
            <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7;background:#FFFFFF">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #EDF2F9">
                    <x-icon name="kunci" style="width:16px;height:16px;color:#0A2463" />
                    <span style="font-size:13.5px;font-weight:700;color:#0A2463">Status &amp; Penanganan Perbaikan</span>
                </div>

                @if ($pengaduan->status !== 'approved')
                    <div style="background:#F8FAFD;border:1px dashed #CBD5E1;border-radius:10px;padding:14px;font-size:13px;color:#64748B">
                        {{ $pengaduan->status === 'rejected' ? 'Pengaduan ini ditolak, proses perbaikan tidak dilakukan.' : 'Proses perbaikan akan dimulai setelah laporan disetujui oleh admin sekolah.' }}
                    </div>
                @else
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                        <x-status-penanganan :status="$pengaduan->status_penanganan" />
                        @if ($pengaduan->teknisi)
                            <span style="font-size:12.5px;color:#4B5563;font-weight:500;background:#F1F5FB;padding:3px 10px;border-radius:6px;border:1px solid #DCE6F5">
                                Teknisi: <strong style="color:#0A2463">{{ $pengaduan->teknisi->name }}</strong>
                            </span>
                        @endif
                    </div>

                    <!-- Indikator Progres Bar -->
                    <div style="margin-bottom:16px;background:#F8FAFD;padding:10px 14px;border-radius:10px;border:1px solid #E5EDF7">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:700;margin-bottom:6px">
                            <span style="color:#5B6F96">Progres Pekerjaan</span>
                            <span style="color:#0A2463;font-size:12.5px">{{ (int) $pengaduan->progres }}%</span>
                        </div>
                        <div class="progres" style="height:7px;background:#E2EDF8;border-radius:999px;overflow:hidden">
                            <span style="width: {{ (int) $pengaduan->progres }}%;background:linear-gradient(90deg,#0A2463,#1D4FA8);display:block;height:100%;border-radius:999px"></span>
                        </div>
                    </div>
                @endif

                <!-- Timeline Perjalanan Pengaduan -->
                <div style="margin-top:16px">
                    <span style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#5B6F96;margin-bottom:12px">
                        Timeline Pengaduan
                    </span>
                    <x-timeline :riwayat="$pengaduan->riwayat->whereIn('jenis', ['pengajuan', 'verifikasi', 'penanganan'])" :tampil-catatan="false" />
                </div>
            </div>

            <!-- Kartu Bukti Foto Kerusakan -->
            <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7;background:#FFFFFF">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #EDF2F9">
                    <div style="display:flex;align-items:center;gap:8px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:#0A2463"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        <span style="font-size:13.5px;font-weight:700;color:#0A2463">Bukti Foto Kerusakan</span>
                    </div>
                    @if ($pengaduan->fotoUrl())
                        <span style="font-size:11.5px;color:#1D4FA8;font-weight:600">Klik untuk perbesar</span>
                    @endif
                </div>

                @if ($pengaduan->fotoUrl())
                    <div style="border-radius:12px;overflow:hidden;border:1px solid #DCE6F5;background:#FAFBFD">
                        <a class="detail-photo" href="#foto-besar" title="Klik untuk memperbesar foto bukti" style="border:0;border-radius:0;display:block">
                            <img src="{{ $pengaduan->fotoUrl() }}" alt="Foto bukti kerusakan {{ $pengaduan->kode() }}" style="width:100%;max-height:260px;object-fit:cover;display:block">
                        </a>
                    </div>
                    <a class="lightbox" id="foto-besar" href="#"><img src="{{ $pengaduan->fotoUrl() }}" alt="Foto bukti kerusakan (diperbesar)"></a>
                @else
                    <div class="no-photo" style="height:100px;border-radius:10px;border:1.5px dashed #CBD5E1;font-size:12.5px;color:#64748B;background:#FAFBFD">
                        Tidak ada foto bukti terlampir
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Diskusi & Komentar Pengaduan -->
    <div class="card card-pad" style="margin-top:20px;border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7;background:#FFFFFF">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #EDF2F9">
            <x-icon name="chat" style="width:18px;height:18px;color:#0A2463" />
            <span style="font-weight:700;font-size:15px;color:#0A2463">Aktivitas &amp; Diskusi Pengaduan</span>
        </div>
        <x-komentar-thread :pengaduan="$pengaduan" />
    </div>
@endsection
