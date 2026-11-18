# Panduan Pengujian — ADUKANAJA

## 1. Suite otomatis (`php artisan test`)
| File | Cakupan |
|---|---|
| `AdukanajaTest` | Fitur bawaan: login, verifikasi, CRUD, dashboard, laporan, profil, foto, sesi 419 |
| `StrukturProjectTest` | Nama file/class/view (aman di Linux), route/view/komponen yang dipakai terdaftar |
| `TeknisiTest` | Role teknisi: akses, isolasi data (anti-IDOR), akun, penugasan |
| `WorkflowPenangananTest` | Setiap transisi status (valid & tidak valid), syarat bukti, revisi, riwayat |
| `TampilanTest` | Shell/sidebar/drawer, toast, ikon, design system, hitungan dashboard |
| `OtorisasiRouteTest` | **Pemindaian otomatis seluruh route** × guest + 3 role (403/redirect) |
| `KeamananTest` | XSS (escape), token CSRF di semua form, hash password, throttle login, upload berbahaya, file sensitif |
| `ValidasiFormTest` | Validasi server-side tiap form (pengaduan, kategori, sarana, pengguna, profil, login) |
| `IntegritasDatabaseTest` | Unique/FK/cascade/set null/restrict, atomisitas workflow, **uji migrasi data lama** |
| `RegresiFiturLamaTest` | Laporan (filter, ringkasan, Excel, cetak), pagination, pencarian, CRUD sarana |
| `NotifikasiTest` | Penerima notifikasi untuk setiap peristiwa workflow, lonceng, tandai dibaca, akses terbatas, anti open-redirect |
| `KomentarTest` | Siapa boleh berkomentar, validasi, notifikasi ke pihak lain, tampilan & escape, pembatasan laju |
| `TolakDanHapusBuktiTest` | Teknisi menolak tugas (assigned → not_assigned), penugasan ulang, hapus foto bukti (file fisik, IDOR) |
| `ProduksiTest` | Header keamanan, font lokal, perintah Artisan (cek keamanan, acak password demo, buat admin), berkas produksi & dokumentasi |

Catatan: test memakai SQLite di memori. Perilaku khusus MySQL (mis. `lockForUpdate`) diuji manual (bagian 3).

## 2. Checklist uji manual — fungsional per role
**Siswa** (`aldejan@sch.id`)
- [ ] Login dengan email dan dengan NIS; password salah menampilkan pesan umum
- [ ] Ajukan pengaduan (dengan & tanpa foto); error validasi tampil di field yang benar
- [ ] Riwayat: filter status, pagination; detail hanya milik sendiri (buka ID milik siswa lain → 403)
- [ ] Detail menampilkan progres dan timeline; catatan internal teknisi tidak tampil

**Admin** (`rudi@sch.id`)
- [ ] Dashboard: angka & grafik sesuai data; tombol "Validasi N hasil pekerjaan" muncul bila ada
- [ ] Pengguna: tambah (siswa/teknisi/admin), edit diri sendiri, hapus (ditolak bila punya data), cari & filter role
- [ ] Sarana & kategori: tambah/edit/hapus (kategori terpakai tidak bisa dihapus)
- [ ] Verifikasi: setujui → tugaskan teknisi → (setelah teknisi mengajukan) nyatakan selesai / minta revisi
- [ ] Pengaduan yang sudah ditugaskan tidak bisa ditolak/di-pending-kan
- [ ] Laporan: filter tanggal/status, ekspor Excel terbuka di Excel, cetak

**Teknisi** (`budi@sch.id`)
- [ ] Hanya melihat tugas sendiri; URL tugas teknisi lain → 404
- [ ] Terima → Mulai → progres (tidak bisa turun) → unggah foto sebelum/sesudah → ajukan
- [ ] Ajukan tanpa foto "sesudah" ditolak; setelah revisi wajib foto "sesudah" baru
- [ ] Tolak tugas (alasan wajib) → tugas kembali ke admin; hapus foto bukti sebelum diajukan
- [ ] Notifikasi: lonceng menampilkan jumlah belum dibaca; klik membuka halaman terkait; komentar memberi tahu pihak lain
- [ ] Tidak bisa membuka URL admin/siswa (403)

## 3. Checklist responsif (DevTools F12 → ikon perangkat)
Uji di lebar **360, 390, 768, 1366, 1920 px** untuk: login, dashboard (3 role), verifikasi, pengguna, daftar tugas, detail tugas, form ajukan, laporan.
- [ ] Tidak ada scroll horizontal pada halaman (tabel boleh scroll di dalam kartunya)
- [ ] ≤1024px: sidebar menjadi drawer (tombol menu, tutup via Esc / klik area gelap / pilih menu)
- [ ] ≤720px: tabel tampil sebagai kartu bertumpuk; form satu kolom; tombol mudah disentuh (≥40px)
- [ ] Toast tidak menutupi tombol penting; modal/menu akun tidak keluar layar
- [ ] Uji sekali di HP sungguhan (Chrome Android / Safari iOS)

## 4. Checklist keamanan & operasional
- [ ] `APP_DEBUG=false` dan `APP_ENV=production` di server produksi; `APP_KEY` dibuat per-instalasi
- [ ] Semua password demo diganti; akun demo yang tidak dipakai dihapus
- [ ] `.env`, `vendor`, `storage/logs` tidak dibagikan/di-commit
- [ ] Backup database terjadwal (mysqldump) dan diuji pulihkan
- [ ] `php artisan storage:link` sudah dijalankan; folder `storage/app/public` ikut dibackup (foto)

## 5. Batasan yang diketahui
- Test berjalan di SQLite; perilaku penguncian baris MySQL (`lockForUpdate`) belum diuji otomatis
- Notifikasi bersifat in-app (belum email/WhatsApp); komentar belum mendukung lampiran
- Penugasan satu teknisi per pengaduan (riwayat penugasan tersimpan)
- Pengujian tampilan otomatis belum ada; dilakukan manual lewat checklist di atas
