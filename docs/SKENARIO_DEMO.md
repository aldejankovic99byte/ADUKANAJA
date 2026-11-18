# Skenario Demo & Persiapan Pameran — ADUKANAJA

Durasi: **±10–12 menit**. Siapkan **3 jendela browser/profil** (admin, siswa, teknisi) agar tidak perlu login-logout berulang.

## H-1 (sehari sebelum)
- [ ] `mysqldump` backup database; MySQL menyala normal (selalu **Stop** dengan benar)
- [ ] `php artisan optimize:clear` lalu `php artisan test` → semua hijau
- [ ] Muat ulang browser dengan **Ctrl+F5**; cek tampilan di layar proyektor/HP
- [ ] Siapkan 2–3 foto kerusakan contoh (JPG/PNG < 2 MB) di desktop
- [ ] Baterai laptop penuh + charger; matikan notifikasi/antivirus popup; tutup program lain
- [ ] Siapkan cadangan: tangkapan layar tiap tahap bila internet/laptop bermasalah

## Akun demo (password: `password`)
| Peran | Login |
|---|---|
| Admin | `rudi@sch.id` |
| Siswa | `aldejan@sch.id` (atau NIS `2526003`) |
| Teknisi | `budi@sch.id` (cadangan `sari@sch.id`) |

> Untuk instalasi sungguhan: ganti password demo (`php artisan adukanaja:acak-password-demo`) — lihat `docs/DEPLOY.md`.

## Alur demo
**0. Pembuka (30 dtk)** — Buka halaman login. *"ADUKANAJA memudahkan siswa melaporkan kerusakan sarana sekolah dan membuat proses perbaikannya transparan."* Tunjukkan logo, tombol lihat password, tampilan responsif.

**1. Siswa mengajukan (2 mnt)** — Login siswa → **Ajukan** → pilih kategori, lokasi, tanggal, deskripsi, unggah foto → kirim. Tunjukkan **nomor tiket**, status *Pending*, dan **timeline**.
*Poin:* validasi form (coba kirim kosong), siswa hanya melihat pengaduannya sendiri.

**2. Admin memverifikasi & menugaskan (2 mnt)** — Admin → lonceng **notifikasi** muncul (pengaduan baru) → **Verifikasi** → setujui dengan tanggapan → **tugaskan teknisi**. Tunjukkan dashboard (statistik, grafik).
*Poin:* status **verifikasi** dan **penanganan** terpisah.

**3. Teknisi bekerja (3 mnt)** — Teknisi (notifikasi tugas baru) → buka tugas → **Terima** → **Mulai** → update progres + catatan → unggah foto *sebelum* dan *sesudah* → **Ajukan penyelesaian**.
*Poin:* tombol **Tolak tugas** (dengan alasan) dan **Hapus foto**; teknisi hanya melihat tugas miliknya; tidak bisa mengajukan tanpa foto *sesudah*.

**4. Komentar (1 mnt)** — Siswa menulis komentar di detail pengaduan → teknisi/admin menerima notifikasi.

**5. Validasi & revisi (2 mnt)** — Admin membuka pengaduan → lihat foto bukti → **Kembalikan untuk revisi** (catatan wajib) → teknisi mengerjakan ulang → admin **Nyatakan selesai**.

**6. Siswa melihat hasil (1 mnt)** — Siswa: notifikasi *selesai*, status **Selesai**, **timeline lengkap** (riwayat setiap perubahan, siapa & kapan).

**7. Penutup (1 mnt)** — Admin → **Laporan** → filter → **Export Excel**. Sebut keamanan & pengujian: *"173 test otomatis lolos, termasuk pemeriksaan otorisasi seluruh route."*

## Perkiraan pertanyaan guru & jawaban singkat
| Pertanyaan | Jawaban |
|---|---|
| Kenapa Laravel? | Framework matang: autentikasi, validasi, ORM, keamanan (CSRF/XSS) bawaan; terstruktur MVC sehingga mudah dipelihara |
| Bagaimana mencegah siswa melihat data siswa lain? | Otorisasi di backend: middleware role + `PengaduanPolicy` + query dibatasi pemilik; diuji otomatis untuk semua route |
| Bagaimana jika dua orang mengubah status bersamaan? | Perubahan status dalam transaksi dengan penguncian baris (`lockForUpdate`) di satu kelas `PengaduanWorkflow` |
| Apa bedanya status verifikasi dan penanganan? | Verifikasi = apakah laporan sah (admin); penanganan = progres perbaikan (teknisi). Dipisah agar logika tidak bentrok |
| Apakah riwayat bisa diubah? | Tidak — `riwayat_status` hanya bertambah (audit trail) dan mencatat pelaku, waktu, catatan |
| Bagaimana keamanan password/upload? | Password bcrypt; upload dibatasi tipe/ukuran, nama diacak, file PHP ditolak |
| Bisa dipakai tanpa internet? | Ya — font dan seluruh aset lokal; cukup XAMPP/`php artisan serve` |
| Bagaimana jika data hilang? | Backup `mysqldump` terjadwal; migration aman untuk data lama (diuji) |
| Pengembangan selanjutnya? | Notifikasi email/WhatsApp, lampiran pada komentar, multi-teknisi, aplikasi mobile |

## Jika terjadi masalah saat demo
- **Halaman error** → Ctrl+F5; `php artisan optimize:clear`
- **MySQL mati** → XAMPP: Start MySQL; pulihkan dari backup bila perlu (`docs/DEPLOY.md`)
- **Foto tidak tampil** → `php artisan storage:link`
- Lanjutkan dengan **tangkapan layar cadangan** dan jelaskan alurnya.
