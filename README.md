# ADUKANAJA — Sistem Pengaduan & Pemeliharaan Sarana Sekolah

Aplikasi web (Laravel 12, Blade, MySQL/SQLite) untuk melaporkan kerusakan sarana sekolah, memverifikasi,
menugaskan teknisi, memantau perbaikan, dan memvalidasi hasil.

## Fitur utama
Workflow pengaduan lengkap (verifikasi → penugasan → terima/**tolak** → progres & **foto bukti** (tambah/hapus) → validasi/revisi), **notifikasi** in-app, **komentar** pada pengaduan, laporan Excel/cetak, dashboard per role, dan tampilan responsif (HP–desktop). Font Inter lokal — berjalan penuh tanpa internet.

## Role
| Role | Fungsi utama |
|---|---|
| **Admin** | Kelola pengguna/sarana/kategori, verifikasi pengaduan, tugaskan teknisi, validasi hasil, laporan (Excel & cetak) |
| **Siswa** | Ajukan pengaduan + foto, lihat riwayat, progres & timeline, komentar, notifikasi |
| **Teknisi** | Terima atau tolak tugas, mulai pekerjaan, update progres, unggah/hapus foto bukti, ajukan penyelesaian, kerjakan revisi, komentar, notifikasi |

## Alur pengaduan
`status` (verifikasi): `pending → approved | rejected`
`status_penanganan`: `not_assigned → assigned → accepted → in_progress → waiting_confirmation → completed`
(`waiting_confirmation → revision_required → in_progress` bila admin meminta revisi).
Semua perubahan status melewati `App\Services\PengaduanWorkflow` dan tercatat di tabel `riwayat_status`.

## Menjalankan (XAMPP / lokal)
```powershell
composer install
copy .env.example .env      # lalu atur DB_* dan jalankan: php artisan key:generate
php artisan migrate --seed  # JANGAN di database yang sudah berisi data penting
php artisan storage:link
php artisan serve
```
Akun demo (password `password`): admin `rudi@sch.id`; siswa `aldejan@sch.id` / NIS `2526003`; teknisi `budi@sch.id`, `sari@sch.id`.
Ganti seluruh password demo sebelum dipakai sungguhan, dan set `APP_DEBUG=false` di produksi.

## Pengujian
```powershell
php artisan test                    # seluruh suite (memakai SQLite di memori, MySQL tidak tersentuh)
php artisan test --filter=Otorisasi # contoh: hanya satu suite
```
Total **173 test**. Butuh ekstensi PHP: `pdo_sqlite`, `sqlite3`, `gd`, `zip`. Detail suite dan checklist uji manual: [docs/TESTING.md](docs/TESTING.md).

## Dokumentasi
- [docs/DOKUMENTASI_TEKNIS.md](docs/DOKUMENTASI_TEKNIS.md) — arsitektur, ERD, alur status, keamanan
- [docs/DEPLOY.md](docs/DEPLOY.md) — memindahkan ke server sungguhan (APP_DEBUG=false, ganti password demo, backup)
- [docs/SKENARIO_DEMO.md](docs/SKENARIO_DEMO.md) — skenario presentasi & tanya jawab
- [docs/TESTING.md](docs/TESTING.md) — suite test & checklist uji manual

## Perintah keamanan
```powershell
php artisan adukanaja:cek-keamanan          # laporan kesiapan produksi
php artisan adukanaja:acak-password-demo    # ganti semua password demo
php artisan adukanaja:ganti-password <email>
php artisan adukanaja:buat-admin <email>
```

## Backup sebelum migration
```powershell
C:\xampp\mysql\bin\mysqldump.exe -u root db_pengaduan_sarana > backup.sql
```
Hentikan MySQL lewat tombol **Stop** di XAMPP sebelum menutup/mematikan komputer (mencegah database korup).

## Struktur penting
- `app/Services/PengaduanWorkflow.php` — satu-satunya pintu perubahan status pengaduan
- `app/Policies/PengaduanPolicy.php`, `app/Http/Middleware/RoleMiddleware.php` — otorisasi
- `resources/views/layouts/app.blade.php`, `public/css/app.css` — shell & design system
- `resources/views/auth/login.blade.php`, `public/css/login.css`, `public/img/` — halaman login, logo (`logo.svg`) dan latar; ganti `public/img/logo.svg` untuk memakai logo sendiri
- `database/migrations/` — migration aditif (aman untuk data lama)
