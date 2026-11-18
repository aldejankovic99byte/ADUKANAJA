@extends('layouts.app')
@section('title', 'Verifikasi Pengaduan')

@section('content')
<style>
    /* Styling khusus Halaman Verifikasi Admin - Tema Modern Navy & Putih */
    .verif-filter-group {
        margin-bottom: 12px;
    }
    .verif-filter-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #5B6F96;
        margin-bottom: 6px;
        display: block;
    }
    .verif-chips {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .verif-chip {
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid #DCE6F5;
        background: #FFFFFF;
        color: #4A5D82;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .verif-chip:hover {
        border-color: #1D4FA8;
        color: #1D4FA8;
        background: #F4F8FD;
        text-decoration: none;
    }
    .verif-chip.on {
        background: #0A2463;
        border-color: #0A2463;
        color: #FFFFFF;
        box-shadow: 0 2px 6px rgba(10,36,99,0.18);
    }

    /* Ticket Card di Kolom Kiri */
    .verif-ticket {
        display: block;
        background: #FFFFFF;
        border: 1.5px solid #E2EDF8;
        border-radius: 12px;
        padding: 13px 15px;
        margin-bottom: 9px;
        text-decoration: none;
        color: inherit;
        transition: all .15s ease;
        box-shadow: 0 1px 3px rgba(10,36,99,0.02);
    }
    .verif-ticket:hover {
        border-color: #1D4FA8;
        background: #FAFBFD;
        box-shadow: 0 3px 10px rgba(10,36,99,0.06);
        text-decoration: none;
        color: inherit;
        transform: translateY(-1px);
    }
    .verif-ticket.on {
        border-color: #1D4FA8;
        background: #F4F8FD;
        box-shadow: 0 0 0 1px #1D4FA8, 0 4px 12px rgba(29,79,168,0.09);
    }
    .verif-ticket-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }
    .verif-ticket-meta {
        font-size: 12px;
        color: #5B6F96;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    /* Detail Card di Kolom Kanan */
    .verif-detail-card {
        background: #FFFFFF;
        border: 1px solid #E5EDF7;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(10,36,99,0.04);
        padding: 24px;
    }
    .verif-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 640px) {
        .verif-meta-grid {
            grid-template-columns: 1fr;
        }
    }
    .verif-meta-item {
        background: #FAFBFD;
        border: 1px solid #E8F0FA;
        border-radius: 10px;
        padding: 10px 14px;
        min-width: 0;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .verif-meta-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #5B6F96;
        margin-bottom: 6px;
    }
    .verif-meta-val {
        font-size: 13.5px;
        font-weight: 600;
        color: #0A2463;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .verif-cat-badge {
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

    /* Radio pills status verifikasi */
    .verif-pill-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .verif-pill {
        position: relative;
        cursor: pointer;
        margin: 0;
    }
    .verif-pill input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .verif-pill span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 16px;
        border-radius: 8px;
        border: 1.5px solid #DCE6F5;
        background: #FFFFFF;
        font-weight: 600;
        font-size: 13px;
        color: #4A5D82;
        transition: all .15s ease;
    }
    .verif-pill:hover span {
        border-color: #CBD5E1;
        background: #F8FAFD;
    }
    .verif-pill-approved input:checked + span {
        background: #DCFCE7;
        border-color: #86EFAC;
        color: #15803D;
        box-shadow: 0 1px 4px rgba(22,163,74,0.15);
    }
    .verif-pill-pending input:checked + span {
        background: #FEF3C7;
        border-color: #FDE68A;
        color: #B45309;
        box-shadow: 0 1px 4px rgba(245,158,11,0.15);
    }
    .verif-pill-rejected input:checked + span {
        background: #FEE2E2;
        border-color: #FECACA;
        color: #B91C1C;
        box-shadow: 0 1px 4px rgba(220,38,38,0.15);
    }

    /* Subcard penugasan teknisi */
    .verif-subcard {
        background: #F8FAFD;
        border: 1px solid #E2EDF8;
        border-radius: 12px;
        padding: 16px;
        margin-top: 14px;
    }
</style>

    <!-- Header Halaman -->
    <div class="page-head" style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5EDF7;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="margin:0;font-size:24px;font-weight:800;color:#0A2463">Verifikasi Pengaduan Masuk</h1>
            <p style="margin:4px 0 0;font-size:13.5px;color:#5B6F96">
                Kelola validasi status pengaduan, penugasan teknisi, dan pantau progres perbaikan sarana sekolah.
            </p>
        </div>
    </div>

    <!-- Layout Grid 2 Kolom -->
    <div class="grid grid-split" style="gap:20px">
        {{-- ===== Kolom Kiri: Filter & Daftar Pengaduan ===== --}}
        <aside>
            <!-- Filter Status Pengaduan -->
            <div class="verif-filter-group">
                <span class="verif-filter-label">Filter Status</span>
                <div class="verif-chips">
                    @foreach ([null => 'Semua', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $v => $l)
                        <a class="verif-chip @if ($status === $v) on @endif" href="{{ route('admin.verifikasi', array_filter(['status' => $v])) }}">
                            {{ $l }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Filter Status Penanganan -->
            <div class="verif-filter-group" style="margin-bottom:16px">
                <span class="verif-filter-label">Status Penanganan</span>
                <div class="verif-chips">
                    <a class="verif-chip @if ($penanganan === 'waiting_confirmation') on @endif" href="{{ route('admin.verifikasi', ['penanganan' => 'waiting_confirmation']) }}">
                        Menunggu validasi
                    </a>
                    <a class="verif-chip @if ($penanganan === 'not_assigned') on @endif" href="{{ route('admin.verifikasi', ['status' => 'approved', 'penanganan' => 'not_assigned']) }}">
                        Belum ditugaskan
                    </a>
                    <a class="verif-chip @if ($penanganan === 'in_progress') on @endif" href="{{ route('admin.verifikasi', ['penanganan' => 'in_progress']) }}">
                        Sedang dikerjakan
                    </a>
                </div>
            </div>

            <!-- Daftar Pengaduan -->
            <div style="max-height:720px;overflow-y:auto;padding-right:2px">
                @forelse ($daftar as $p)
                    <a class="verif-ticket @if ($dipilih && $dipilih->id === $p->id) on @endif"
                       href="{{ route('admin.verifikasi', array_filter(['id' => $p->id, 'status' => $status, 'penanganan' => $penanganan])) }}#detail">
                        <div class="verif-ticket-head">
                            <div style="display:flex;align-items:center;gap:7px;overflow:hidden">
                                <span style="font-size:11.5px;font-weight:700;color:#1D4FA8;background:#EEF4FD;padding:2px 7px;border-radius:6px;border:1px solid #D2E3FC;flex:none">
                                    {{ $p->kode() }}
                                </span>
                                <span style="font-weight:700;font-size:13px;color:#0A2463;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $p->kategori->nama }}
                                </span>
                            </div>
                            <x-status-badge :status="$p->status" />
                        </div>
                        <div class="verif-ticket-meta">
                            <span style="font-weight:600;color:#1F2937">{{ $p->pelapor->name }}</span>
                            <span>·</span>
                            <span style="color:#4A5D82">{{ $p->lokasi }}</span>
                            <span>·</span>
                            <span style="color:#5B6F96">{{ $p->created_at->translatedFormat('d M Y') }}</span>
                        </div>
                    </a>
                @empty
                    <div class="card" style="border-radius:12px;border:1px solid #E5EDF7">
                        <x-empty>Tidak ada pengaduan pada filter ini.</x-empty>
                    </div>
                @endforelse
            </div>

            @if ($daftar->hasPages())
                <div style="margin-top:12px">
                    {{ $daftar->links() }}
                </div>
            @endif
        </aside>

        {{-- ===== Kolom Kanan: Detail & Form Verifikasi ===== --}}
        <section class="verif-detail-card" id="detail">
            @if (! $dipilih)
                <x-empty>Pilih salah satu pengaduan di sebelah kiri untuk melihat detail &amp; memverifikasi.</x-empty>
            @else
                <!-- Header Kartu Detail (Tanpa Teks Duplikat) -->
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #EDF2F9">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1D4FA8;background:#EEF4FD;padding:4px 10px;border-radius:6px;border:1px solid #D2E3FC">
                                DETAIL PENGADUAN
                            </span>
                            <h2 style="margin:0;font-size:20px;font-weight:800;color:#0A2463;letter-spacing:0">{{ $dipilih->kode() }}</h2>
                        </div>
                        <p style="margin:6px 0 0;font-size:12.5px;color:#5B6F96">
                            Diajukan oleh <strong>{{ $dipilih->pelapor->name }}</strong> pada {{ $dipilih->created_at->translatedFormat('d F Y, H:i') }} WIB
                        </p>
                    </div>
                    <div>
                        <x-status-badge :status="$dipilih->status" />
                    </div>
                </div>

                <!-- Bukti Foto Kerusakan -->
                @if ($dipilih->fotoUrl())
                    <div style="margin-bottom:20px">
                        <div style="border-radius:12px;overflow:hidden;border:1px solid #DCE6F5;background:#FAFBFD;position:relative">
                            <a class="detail-photo" href="#foto-besar" title="Klik untuk memperbesar foto bukti" style="border:0;border-radius:0;display:block">
                                <img src="{{ $dipilih->fotoUrl() }}" alt="Foto bukti kerusakan {{ $dipilih->kode() }}" style="width:100%;max-height:280px;object-fit:cover;display:block">
                            </a>
                        </div>
                        <a class="lightbox" id="foto-besar" href="#"><img src="{{ $dipilih->fotoUrl() }}" alt="Foto bukti kerusakan (diperbesar)"></a>
                    </div>
                @else
                    <div class="no-photo" style="height:90px;border-radius:10px;border:1.5px dashed #CBD5E1;font-size:12.5px;color:#64748B;background:#FAFBFD;margin-bottom:20px">
                        Pelapor tidak melampirkan foto bukti
                    </div>
                @endif

                <!-- Metadata Grid -->
                <div class="verif-meta-grid">
                    <div class="verif-meta-item">
                        <span class="verif-meta-label">Pelapor</span>
                        <div class="verif-meta-val">
                            {{ $dipilih->pelapor->name }}
                            @if ($dipilih->pelapor->kelas)
                                <span style="font-size:12px;color:#5B6F96;font-weight:500">({{ $dipilih->pelapor->kelas }})</span>
                            @endif
                        </div>
                    </div>

                    <div class="verif-meta-item">
                        <span class="verif-meta-label">Kategori Kerusakan</span>
                        <div class="verif-meta-val">
                            <span class="verif-cat-badge">
                                {{ $dipilih->kategori->nama }}
                            </span>
                        </div>
                    </div>

                    <div class="verif-meta-item">
                        <span class="verif-meta-label">Lokasi / Ruangan</span>
                        <div class="verif-meta-val">
                            <span style="display:inline-block;background:#F1F5FB;color:#0A2463;padding:2px 8px;border-radius:6px;font-size:12px;border:1px solid #DCE6F5">
                                {{ $dipilih->lokasi }}
                            </span>
                        </div>
                    </div>

                    <div class="verif-meta-item">
                        <span class="verif-meta-label">Tanggal Kejadian</span>
                        <div class="verif-meta-val" style="color:#1F2937">
                            {{ $dipilih->tanggal_kejadian->translatedFormat('d F Y') }}
                        </div>
                    </div>

                    @if ($dipilih->sarana)
                        <div class="verif-meta-item" style="grid-column: 1 / -1">
                            <span class="verif-meta-label">Sarana Terkait</span>
                            <div class="verif-meta-val" style="color:#0A2463">
                                {{ $dipilih->sarana->nama }} <span style="font-size:12px;color:#5B6F96;font-weight:500">· {{ $dipilih->sarana->lokasi }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="verif-meta-item" style="grid-column: 1 / -1">
                        <span class="verif-meta-label">Deskripsi Kerusakan</span>
                        <div style="font-size:13.5px;color:#1F2937;line-height:1.6;white-space:pre-line;word-break:break-word">
                            {{ $dipilih->deskripsi }}
                        </div>
                    </div>

                    @if ($dipilih->verifikator)
                        <div class="verif-meta-item" style="grid-column: 1 / -1;background:#F4F8FD;border-color:#D2E3FC">
                            <span class="verif-meta-label" style="color:#1D4FA8">Terakhir Diverifikasi</span>
                            <div style="font-size:12.5px;color:#0A2463;font-weight:500">
                                Oleh <strong>{{ $dipilih->verifikator->name }}</strong> · {{ $dipilih->diverifikasi_pada?->translatedFormat('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Form Verifikasi Status Pengaduan -->
                <div style="margin-top:20px;padding-top:18px;border-top:1px solid #EDF2F9">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                        <x-icon name="verifikasi" style="width:16px;height:16px;color:#0A2463" />
                        <span style="font-size:14px;font-weight:700;color:#0A2463">Ubah Status Verifikasi</span>
                    </div>

                    <form method="POST" action="{{ route('admin.verifikasi.update', $dipilih) }}" novalidate>
                        @csrf @method('PUT')
                        <input type="hidden" name="filter_status" value="{{ $status }}">

                        <div class="field @error('status') has-error @enderror" style="margin-bottom:16px">
                            <div class="verif-pill-group">
                                @foreach (['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $v => $l)
                                    <label class="verif-pill verif-pill-{{ $v }}">
                                        <input type="radio" name="status" value="{{ $v }}" @checked(old('status', $dipilih->status) === $v)>
                                        <span>
                                            @if ($v === 'approved')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg>
                                            @elseif ($v === 'pending')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            @endif
                                            {{ $l }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('status') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field @error('tanggapan') has-error @enderror" style="margin-bottom:16px">
                            <label for="tanggapan" style="font-size:12.5px;font-weight:700;color:#0A2463">Catatan Tanggapan Admin</label>
                            <textarea id="tanggapan" name="tanggapan" rows="3" placeholder="Tuliskan tindak lanjut perbaikan, estimasi waktu, atau alasan jika ditolak..." style="border:1.5px solid #DCE6F5;border-radius:10px;padding:10px 14px;font-size:13.5px;width:100%">{{ old('tanggapan', $dipilih->tanggapan) }}</textarea>
                            <p class="hint" style="font-size:12px;color:#5B6F96;margin-top:4px">Wajib diisi jika status Rejected. Catatan ini langsung tampil di halaman riwayat siswa.</p>
                            @error('tanggapan') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <button class="btn btn-primary" type="submit" style="background:#0A2463;color:#FFFFFF;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;border:0">
                            Simpan Status Verifikasi
                        </button>
                    </form>
                </div>

                {{-- ===== Penugasan Teknisi (Khusus Pengaduan Disetujui / Approved) ===== --}}
                @if ($dipilih->status === 'approved')
                    <div style="margin-top:24px;padding-top:18px;border-top:1px solid #EDF2F9">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                            <x-icon name="kunci" style="width:16px;height:16px;color:#0A2463" />
                            <span style="font-size:14px;font-weight:700;color:#0A2463">Penugasan Teknisi &amp; Progres Penanganan</span>
                        </div>

                        <div class="verif-subcard">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                                <span style="font-size:12px;font-weight:700;color:#5B6F96">Status Penanganan:</span>
                                <x-status-penanganan :status="$dipilih->status_penanganan" />
                                @if ($dipilih->teknisi)
                                    <span style="font-size:12.5px;color:#4B5563;background:#FFFFFF;padding:3px 10px;border-radius:6px;border:1px solid #DCE6F5">
                                        Teknisi saat ini: <strong style="color:#0A2463">{{ $dipilih->teknisi->name }}</strong>
                                    </span>
                                @endif
                            </div>

                            @if (in_array($dipilih->status_penanganan, ['not_assigned', 'assigned'], true))
                                @if ($daftarTeknisi->isEmpty())
                                    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#92400E">
                                        Belum ada akun teknisi aktif. Tambahkan lewat menu <strong>Pengguna</strong> dengan role Petugas Teknisi.
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('admin.verifikasi.tugaskan', $dipilih) }}" novalidate style="margin-top:10px">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="filter_status" value="{{ $status }}">
                                        <div class="field @error('teknisi_id') has-error @enderror" style="margin-bottom:10px">
                                            <label for="teknisi_id" style="font-size:12px;font-weight:700;color:#0A2463">
                                                {{ $dipilih->teknisi ? 'Ganti Teknisi Bertugas' : 'Tugaskan Teknisi' }}
                                            </label>
                                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                                <select id="teknisi_id" name="teknisi_id" required style="flex:1;min-width:200px;padding:7px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px;background:#FFFFFF">
                                                    <option value="">— Pilih Teknisi —</option>
                                                    @foreach ($daftarTeknisi as $t)
                                                        <option value="{{ $t->id }}" @selected((string) old('teknisi_id', $dipilih->teknisi_id) === (string) $t->id)>
                                                            {{ $t->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-outline btn-sm" type="submit" style="padding:7px 14px;font-size:12.5px;border-radius:8px">
                                                    {{ $dipilih->teknisi ? 'Simpan Perubahan' : 'Tugaskan' }}
                                                </button>
                                            </div>
                                            @error('teknisi_id') <p class="field-error">{{ $message }}</p> @enderror
                                        </div>
                                    </form>
                                @endif
                            @else
                                <p style="font-size:12.5px;color:#5B6F96;margin:4px 0 0">
                                    Pengaduan sedang atau telah dikerjakan oleh teknisi sehingga penugasan tidak dapat diubah lagi.
                                </p>
                            @endif

                            @if ($dipilih->teknisi)
                                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #E5EDF7">
                                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:700;margin-bottom:6px">
                                        <span style="color:#5B6F96">Progres Pengerjaan</span>
                                        <span style="color:#0A2463;font-size:13px">{{ (int) $dipilih->progres }}%</span>
                                    </div>
                                    <div class="progres" style="height:8px;background:#E2EDF8;border-radius:999px;overflow:hidden">
                                        <span style="width: {{ (int) $dipilih->progres }}%;background:linear-gradient(90deg,#0A2463,#1D4FA8);display:block;height:100%;border-radius:999px"></span>
                                    </div>
                                </div>
                            @endif

                            @if ($dipilih->status_penanganan === 'revision_required' && $dipilih->catatan_revisi)
                                <div style="margin-top:12px;background:#EFF6FF;border-left:4px solid #1D4FA8;border-radius:6px;padding:10px 14px;font-size:12.5px;color:#1E3A8A">
                                    <strong>Catatan Revisi Terakhir:</strong><br>
                                    {{ $dipilih->catatan_revisi }}
                                </div>
                            @endif

                            @if ($dipilih->bukti->isNotEmpty())
                                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #E5EDF7">
                                    <span style="display:block;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#5B6F96;margin-bottom:8px">
                                        Foto Bukti Hasil Pengerjaan Teknisi
                                    </span>
                                    <x-bukti-galeri :bukti="$dipilih->bukti" />
                                </div>
                            @endif

                            @if ($dipilih->status_penanganan === 'waiting_confirmation')
                                <div style="margin-top:14px;padding:14px;background:#FFFFFF;border:1px solid #D2E3FC;border-radius:10px">
                                    <h4 style="margin:0 0 4px;font-size:13.5px;font-weight:700;color:#0A2463">Validasi Hasil Pekerjaan</h4>
                                    <p style="font-size:12.5px;color:#5B6F96;margin-bottom:12px">
                                        Periksa foto bukti dari teknisi di atas, lalu nyatakan selesai atau kembalikan untuk revisi.
                                    </p>

                                    <!-- Form Validasi Selesai -->
                                    <form method="POST" action="{{ route('admin.verifikasi.validasi', $dipilih) }}" novalidate style="margin-bottom:12px">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="filter_status" value="{{ $status }}">
                                        <div class="field" style="margin-bottom:8px">
                                            <label for="catatan-validasi" style="font-size:12px;font-weight:600">Catatan Validasi Selesai (opsional)</label>
                                            <input id="catatan-validasi" name="catatan" type="text" maxlength="1000" placeholder="Contoh: Pekerjaan sudah diperiksa dan berfungsi baik." style="font-size:13px;padding:6px 12px;border-radius:8px;border:1px solid #DCE6F5">
                                        </div>
                                        <button class="btn btn-primary btn-sm" type="submit" style="background:#16A34A;border-color:#16A34A;color:#FFFFFF;padding:6px 14px;font-weight:600">
                                            ✓ Nyatakan Selesai (Valid)
                                        </button>
                                    </form>

                                    <!-- Form Minta Revisi -->
                                    <form method="POST" action="{{ route('admin.verifikasi.revisi', $dipilih) }}" novalidate>
                                        @csrf @method('PUT')
                                        <input type="hidden" name="filter_status" value="{{ $status }}">
                                        <div class="field @error('catatan') has-error @enderror" style="margin-bottom:8px">
                                            <label for="catatan-revisi" style="font-size:12px;font-weight:600">Catatan Permintaan Revisi (wajib)</label>
                                            <textarea id="catatan-revisi" name="catatan" rows="2" placeholder="Jelaskan apa yang masih perlu diperbaiki teknisi..." required style="font-size:13px;padding:6px 12px;border-radius:8px;border:1px solid #DCE6F5">{{ old('catatan') }}</textarea>
                                            @error('catatan') <p class="field-error">{{ $message }}</p> @enderror
                                        </div>
                                        <button class="btn btn-outline btn-sm" type="submit" style="color:#B91C1C;border-color:#FECACA;padding:6px 14px;font-weight:600">
                                            ↺ Kembalikan untuk Revisi
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Aktivitas Diskusi & Komentar -->
                <div style="margin-top:24px;padding-top:18px;border-top:1px solid #EDF2F9">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <x-icon name="chat" style="width:16px;height:16px;color:#0A2463" />
                        <span style="font-size:14px;font-weight:700;color:#0A2463">Aktivitas &amp; Komentar Pengaduan</span>
                    </div>
                    <x-komentar-thread :pengaduan="$dipilih" />
                </div>

                <!-- Riwayat Perjalanan Status Timeline -->
                <div style="margin-top:24px;padding-top:18px;border-top:1px solid #EDF2F9">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <x-icon name="riwayat" style="width:16px;height:16px;color:#0A2463" />
                        <span style="font-size:14px;font-weight:700;color:#0A2463">Riwayat Status &amp; Catatan Timeline</span>
                    </div>
                    <x-timeline :riwayat="$dipilih->riwayat" />
                </div>
            @endif
        </section>
    </div>
@endsection
