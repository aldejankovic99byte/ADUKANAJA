@extends('layouts.app')
@section('title', 'Tugas ' . $pengaduan->kode())

@section('content')
<style>
    /* Styling khusus Halaman Detail Tugas Teknisi - Tema Modern Navy & Putih */
    .task-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 640px) {
        .task-meta-grid {
            grid-template-columns: 1fr;
        }
    }
    .task-meta-item {
        background: #FAFBFD;
        border: 1px solid #E8F0FA;
        border-radius: 10px;
        padding: 10px 14px;
        min-width: 0;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .task-meta-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #5B6F96;
        margin-bottom: 6px;
    }
    .task-meta-val {
        font-size: 13.5px;
        font-weight: 600;
        color: #0A2463;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .task-cat-badge {
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
    .task-loc-badge {
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
    .task-action-box {
        background: #F8FAFD;
        border: 1px solid #E2EDF8;
        border-radius: 12px;
        padding: 16px 18px;
        margin-top: 16px;
    }
    .task-action-box h3 {
        margin: 0 0 6px;
        font-size: 14px;
        font-weight: 700;
        color: #0A2463;
    }
</style>

    <!-- Tombol Kembali -->
    <div style="margin-bottom: 14px">
        <a class="btn btn-outline btn-sm" href="{{ route('teknisi.tugas.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;padding:6px 14px;border-radius:8px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            <span>Kembali ke Daftar Tugas</span>
        </a>
    </div>

    <!-- Header Halaman Tugas (Tanpa Teks Duplikat) -->
    <div class="page-head" style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5EDF7;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1D4FA8;background:#EEF4FD;padding:4px 10px;border-radius:6px;border:1px solid #D2E3FC">ID TUGAS</span>
                <h1 style="margin:0;font-size:22px;font-weight:800;color:#0A2463;letter-spacing:0">{{ $pengaduan->kode() }}</h1>
            </div>
            <p style="margin:6px 0 0;font-size:13px;color:#5B6F96">
                Dilaporkan pada {{ $pengaduan->created_at->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>
        <div>
            <x-status-penanganan :status="$pengaduan->status_penanganan" />
        </div>
    </div>

    <!-- Layout Grid 2 Kolom -->
    <div class="grid grid-2" style="align-items:start;gap:20px">
        {{-- ===== Kolom Kiri: Informasi Tugas & Tindakan Teknisi ===== --}}
        <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #EDF2F9">
                <x-icon name="info" style="width:18px;height:18px;color:#0A2463" />
                <span style="font-size:15px;font-weight:700;color:#0A2463">Informasi Tugas &amp; Kerusakan</span>
            </div>

            <!-- Detail Grid Info -->
            <div class="task-meta-grid">
                <div class="task-meta-item">
                    <span class="task-meta-label">Kategori Kerusakan</span>
                    <div class="task-meta-val">
                        <span class="task-cat-badge">
                            {{ $pengaduan->kategori->nama }}
                        </span>
                    </div>
                </div>

                <div class="task-meta-item">
                    <span class="task-meta-label">Lokasi / Ruangan</span>
                    <div class="task-meta-val">
                        <span class="task-loc-badge">
                            {{ $pengaduan->lokasi }}
                        </span>
                    </div>
                </div>

                <div class="task-meta-item">
                    <span class="task-meta-label">Tanggal Kejadian</span>
                    <div class="task-meta-val" style="color:#1F2937">
                        {{ $pengaduan->tanggal_kejadian->translatedFormat('d F Y') }}
                    </div>
                </div>

                <div class="task-meta-item">
                    <span class="task-meta-label">Pelapor</span>
                    <div class="task-meta-val" style="color:#1F2937">
                        {{ $pengaduan->pelapor->name }}
                        @if ($pengaduan->pelapor->kelas)
                            <span style="font-size:12px;color:#5B6F96;font-weight:500">({{ $pengaduan->pelapor->kelas }})</span>
                        @endif
                    </div>
                </div>

                @if ($pengaduan->sarana)
                    <div class="task-meta-item" style="grid-column: 1 / -1">
                        <span class="task-meta-label">Sarana Terkait</span>
                        <div class="task-meta-val" style="color:#0A2463">
                            {{ $pengaduan->sarana->nama }} <span style="font-size:12px;color:#5B6F96;font-weight:500">· {{ $pengaduan->sarana->lokasi }}</span>
                        </div>
                    </div>
                @endif

                <div class="task-meta-item" style="grid-column: 1 / -1">
                    <span class="task-meta-label">Deskripsi Kerusakan</span>
                    <div style="font-size:13.5px;color:#1F2937;line-height:1.6;white-space:pre-line;word-break:break-word">
                        {{ $pengaduan->deskripsi }}
                    </div>
                </div>

                @if ($pengaduan->tanggapan)
                    <div class="task-meta-item" style="grid-column: 1 / -1;background:#F4F8FD;border-left:4px solid #0A2463;border-color:#D2E3FC">
                        <span class="task-meta-label" style="color:#0A2463">Catatan Admin</span>
                        <div style="font-size:13px;color:#0A2463;line-height:1.5;white-space:pre-line;font-weight:500">
                            {{ $pengaduan->tanggapan }}
                        </div>
                    </div>
                @endif

                <div class="task-meta-item" style="grid-column: 1 / -1">
                    <span class="task-meta-label">Informasi Penugasan</span>
                    <div style="font-size:12.5px;color:#4A5D82">
                        Ditugaskan pada <strong>{{ $pengaduan->ditugaskan_pada?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</strong>
                        @if ($pengaduan->penugas)
                            <span>oleh <strong>{{ $pengaduan->penugas->name }}</strong> (Admin Sekolah)</span>
                        @endif
                    </div>
                </div>
            </div>

            @php $st = $pengaduan->status_penanganan; @endphp

            <!-- Indikator Progres Pekerjaan -->
            <div style="margin-top:20px;padding:14px;background:#F8FAFD;border:1px solid #E2EDF8;border-radius:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;font-weight:700;margin-bottom:8px">
                    <span style="color:#5B6F96">Progres Pekerjaan Saat Ini</span>
                    <span style="color:#0A2463;font-size:13.5px">{{ (int) $pengaduan->progres }}%</span>
                </div>
                <div class="progres" style="height:8px;background:#E2EDF8;border-radius:999px;overflow:hidden">
                    <span style="width: {{ (int) $pengaduan->progres }}%;background:linear-gradient(90deg,#0A2463,#1D4FA8);display:block;height:100%;border-radius:999px"></span>
                </div>
            </div>

            <!-- Pesan Status Khusus -->
            @if ($st === 'revision_required' && $pengaduan->catatan_revisi)
                <div style="margin-top:14px;background:#FEF2F2;border:1px solid #FECACA;border-left:4px solid #DC2626;border-radius:8px;padding:12px 16px;color:#991B1B;font-size:13px">
                    <strong style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Catatan Permintaan Revisi Admin:
                    </strong>
                    <div style="white-space:pre-line">{{ $pengaduan->catatan_revisi }}</div>
                </div>
            @endif

            @if ($st === 'waiting_confirmation')
                <div style="margin-top:14px;background:#EFF6FF;border:1px solid #BFDBFE;border-left:4px solid #1D4FA8;border-radius:8px;padding:12px 16px;color:#1E40AF;font-size:13px;display:flex;align-items:center;gap:10px">
                    <x-icon name="pasir" style="width:18px;height:18px;flex:none;color:#1D4FA8" />
                    <span>Pengajuan penyelesaian sudah dikirim. Menunggu pemeriksaan dan validasi dari admin sekolah.</span>
                </div>
            @elseif ($st === 'completed')
                <div style="margin-top:14px;background:#F0FDF4;border:1px solid #BBF7D0;border-left:4px solid #16A34A;border-radius:8px;padding:12px 16px;color:#166534;font-size:13px;display:flex;align-items:center;gap:10px">
                    <x-icon name="cek" style="width:18px;height:18px;flex:none;color:#16A34A" />
                    <span>Pekerjaan ini telah diverifikasi dan dinyatakan selesai oleh admin sekolah.</span>
                </div>
            @endif

            {{-- ===== Formulir Aksi Sesuai Tahapan Status ===== --}}
            @if ($st === 'assigned')
                <!-- Panel Terima Tugas -->
                <div class="task-action-box" style="background:#F0FDF4;border-color:#DCFCE7">
                    <h3 style="color:#166534">Tugas Baru Masuk</h3>
                    <p class="small" style="color:#15803D;margin-bottom:12px">Terima tugas ini untuk menyatakan kesiapan menangani kerusakan.</p>
                    <form method="POST" action="{{ route('teknisi.tugas.terima', $pengaduan->id) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit" style="background:#16A34A;border-color:#16A34A;font-weight:600;padding:8px 18px;border-radius:8px">
                            Terima Tugas
                        </button>
                    </form>
                </div>

                <!-- Panel Tolak Tugas -->
                <div class="task-action-box" style="background:#FEF2F2;border-color:#FEE2E2">
                    <h3 style="color:#991B1B">Tidak Bisa Mengerjakan?</h3>
                    <p class="small" style="color:#B91C1C;margin-bottom:12px">Tolak tugas ini dan berikan alasan jelas agar admin dapat menugaskan teknisi lain.</p>
                    <form method="POST" action="{{ route('teknisi.tugas.tolak', $pengaduan->id) }}" novalidate onsubmit="return confirm('Apakah kamu yakin ingin menolak tugas ini?')">
                        @csrf
                        <div class="field @error('alasan') has-error @enderror" style="margin-bottom:10px">
                            <label for="alasan" style="font-size:12px;font-weight:700;color:#991B1B">Alasan Penolakan</label>
                            <textarea id="alasan" name="alasan" rows="2" maxlength="500" required placeholder="Tulis alasan tidak dapat mengerjakan..." style="border:1px solid #FECACA;border-radius:8px;padding:8px 12px;font-size:13px;width:100%">{{ old('alasan') }}</textarea>
                            @error('alasan') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <button class="btn btn-outline" type="submit" style="color:#DC2626;border-color:#FECACA;font-weight:600;padding:6px 14px;border-radius:8px">
                            Tolak Tugas
                        </button>
                    </form>
                </div>
            @elseif ($st === 'accepted' || $st === 'revision_required')
                <div class="task-action-box" style="background:#EEF4FD;border-color:#D2E3FC">
                    <h3>{{ $st === 'accepted' ? 'Siap Memulai Pengerjaan?' : 'Mulai Pengerjaan Revisi' }}</h3>
                    <p class="small" style="color:#1D4FA8;margin-bottom:12px">
                        {{ $st === 'accepted' ? 'Tekan tombol di bawah untuk mengubah status menjadi sedang dikerjakan.' : 'Tekan tombol untuk melanjutkan perbaikan sesuai catatan revisi admin.' }}
                    </p>
                    <form method="POST" action="{{ route('teknisi.tugas.mulai', $pengaduan->id) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit" style="background:#0A2463;color:#FFFFFF;padding:8px 18px;border-radius:8px;font-weight:600">
                            {{ $st === 'accepted' ? 'Mulai Pekerjaan' : 'Mulai Mengerjakan Revisi' }}
                        </button>
                    </form>
                </div>
            @endif

            <!-- Form Perbarui Progres Pengerjaan -->
            @if ($st === 'in_progress')
                <div class="task-action-box">
                    <h3>Perbarui Progres Pekerjaan</h3>
                    <form method="POST" action="{{ route('teknisi.tugas.progres', $pengaduan->id) }}" novalidate>
                        @csrf
                        <div class="field @error('progres') has-error @enderror" style="margin-bottom:12px">
                            <label for="progres" style="font-size:12px;font-weight:700;color:#0A2463">Estimasi Progres (%)</label>
                            <input id="progres" name="progres" type="number" min="{{ (int) $pengaduan->progres }}" max="99" value="{{ old('progres', $pengaduan->progres) }}" required style="width:100%;max-width:140px;padding:7px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px">
                            <span style="font-size:11.5px;color:#5B6F96;margin-left:6px">(Rentang: {{ (int) $pengaduan->progres }}% - 99%)</span>
                            @error('progres') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field @error('catatan') has-error @enderror" style="margin-bottom:12px">
                            <label for="catatan" style="font-size:12px;font-weight:700;color:#0A2463">Catatan Perkembangan Pengerjaan</label>
                            <textarea id="catatan" name="catatan" rows="2" placeholder="Jelaskan komponen yang sudah diganti atau diperbaiki..." style="width:100%;padding:8px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px">{{ old('catatan') }}</textarea>
                            @error('catatan') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit" style="padding:7px 16px;border-radius:8px;font-weight:600">
                            Simpan Progres
                        </button>
                    </form>
                </div>
            @endif

            <!-- Form Unggah Foto Bukti -->
            @if (in_array($st, ['accepted', 'in_progress'], true))
                <div class="task-action-box">
                    <h3>Unggah Foto Bukti Pekerjaan</h3>
                    <p class="small muted" style="margin-bottom:12px">Unggah foto sebelum perbaikan atau sesudah perbaikan untuk dokumentasi.</p>
                    <form method="POST" action="{{ route('teknisi.tugas.bukti', $pengaduan->id) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;margin-bottom:12px">
                            <div class="field @error('jenis') has-error @enderror" style="margin:0">
                                <label for="jenis" style="font-size:12px;font-weight:700;color:#0A2463">Jenis Foto</label>
                                <select id="jenis" name="jenis" required style="width:100%;padding:7px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px;background:#FFFFFF">
                                    <option value="sebelum" @selected(old('jenis') === 'sebelum')>Sebelum Perbaikan</option>
                                    <option value="sesudah" @selected(old('jenis') === 'sesudah')>Sesudah Perbaikan</option>
                                </select>
                                @error('jenis') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="field @error('foto') has-error @enderror" style="margin:0">
                                <label for="foto" style="font-size:12px;font-weight:700;color:#0A2463">File Foto (Maks. 4 MB)</label>
                                <input id="foto" name="foto" type="file" accept="image/png,image/jpeg,image/webp" required style="width:100%;padding:5px 8px;border:1px solid #DCE6F5;border-radius:8px;font-size:12px;background:#FFFFFF">
                                @error('foto') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="field @error('keterangan') has-error @enderror" style="margin-bottom:12px">
                            <label for="keterangan" style="font-size:12px;font-weight:700;color:#0A2463">Keterangan Foto (opsional)</label>
                            <input id="keterangan" name="keterangan" type="text" maxlength="255" value="{{ old('keterangan') }}" placeholder="Contoh: Kondisi kabel setelah diganti" style="width:100%;padding:7px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px">
                            @error('keterangan') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit" style="padding:7px 16px;border-radius:8px;font-weight:600">
                            Unggah Foto Bukti
                        </button>
                    </form>
                </div>
            @endif

            <!-- Form Ajukan Penyelesaian Pekerjaan -->
            @if ($st === 'in_progress')
                <div class="task-action-box" style="background:#F4F8FD;border-color:#D2E3FC">
                    <h3 style="color:#0A2463">Ajukan Penyelesaian Pekerjaan</h3>
                    <p class="small muted" style="margin-bottom:12px">
                        Pastikan kamu sudah mengunggah minimal satu foto bukti <strong>"sesudah"</strong> perbaikan{{ $pengaduan->direvisi_pada ? ' yang baru (setelah revisi)' : '' }}.
                    </p>
                    <form method="POST" action="{{ route('teknisi.tugas.ajukan', $pengaduan->id) }}" novalidate>
                        @csrf
                        <div class="field @error('catatan') has-error @enderror" style="margin-bottom:12px">
                            <label for="catatan-selesai" style="font-size:12px;font-weight:700;color:#0A2463">Catatan Hasil Akhir (opsional)</label>
                            <textarea id="catatan-selesai" name="catatan" rows="2" placeholder="Ringkasan hasil perbaikan sarana..." style="width:100%;padding:8px 12px;border:1px solid #DCE6F5;border-radius:8px;font-size:13px">{{ old('catatan') }}</textarea>
                        </div>
                        <button class="btn btn-primary" type="submit" style="background:#0A2463;color:#FFFFFF;padding:8px 20px;border-radius:8px;font-weight:600" onclick="return confirm('Apakah pekerjaan ini sudah benar-benar selesai dan siap diajukan ke admin?')">
                            Ajukan Penyelesaian
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- ===== Kolom Kanan: Foto Kerusakan, Bukti Perbaikan & Timeline ===== --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <!-- Kartu Bukti Foto Kerusakan Pelapor -->
            <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #EDF2F9">
                    <div style="display:flex;align-items:center;gap:8px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:#0A2463"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        <span style="font-size:13.5px;font-weight:700;color:#0A2463">Foto Bukti Kerusakan (Pelapor)</span>
                    </div>
                    @if ($pengaduan->fotoUrl())
                        <span style="font-size:11.5px;color:#1D4FA8;font-weight:600">Klik untuk perbesar</span>
                    @endif
                </div>

                @if ($pengaduan->fotoUrl())
                    <div style="border-radius:12px;overflow:hidden;border:1px solid #DCE6F5;background:#FAFBFD">
                        <a class="detail-photo" href="#foto-besar" title="Klik untuk memperbesar foto bukti" style="border:0;border-radius:0;display:block">
                            <img src="{{ $pengaduan->fotoUrl() }}" alt="Foto kerusakan {{ $pengaduan->kode() }}" style="width:100%;max-height:280px;object-fit:cover;display:block">
                        </a>
                    </div>
                    <a class="lightbox" id="foto-besar" href="#"><img src="{{ $pengaduan->fotoUrl() }}" alt="Foto kerusakan (diperbesar)"></a>
                @else
                    <div class="no-photo" style="height:90px;border-radius:10px;border:1.5px dashed #CBD5E1;font-size:12.5px;color:#64748B;background:#FAFBFD">
                        Pelapor tidak melampirkan foto bukti
                    </div>
                @endif
            </div>

            <!-- Kartu Bukti Galeri Foto Perbaikan Teknisi -->
            <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #EDF2F9">
                    <x-icon name="kunci" style="width:16px;height:16px;color:#0A2463" />
                    <span style="font-size:13.5px;font-weight:700;color:#0A2463">Foto Bukti Hasil Perbaikan</span>
                </div>
                <x-bukti-galeri :bukti="$pengaduan->bukti" :hapus-tugas="in_array($st, ['accepted', 'in_progress'], true) ? $pengaduan->id : null" />
            </div>

            <!-- Kartu Riwayat Status Timeline -->
            <div class="card card-pad" style="border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #EDF2F9">
                    <x-icon name="riwayat" style="width:16px;height:16px;color:#0A2463" />
                    <span style="font-size:13.5px;font-weight:700;color:#0A2463">Riwayat Perjalanan Tugas</span>
                </div>
                <x-timeline :riwayat="$pengaduan->riwayat" />
            </div>
        </div>
    </div>

    <!-- Aktivitas Diskusi & Komentar -->
    <div class="card card-pad" style="margin-top:20px;border-radius:16px;box-shadow:0 2px 10px rgba(10,36,99,0.04);border:1px solid #E5EDF7">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #EDF2F9">
            <x-icon name="chat" style="width:18px;height:18px;color:#0A2463" />
            <span style="font-weight:700;font-size:15px;color:#0A2463">Aktivitas &amp; Diskusi Tugas</span>
        </div>
        <x-komentar-thread :pengaduan="$pengaduan" />
    </div>
@endsection
