# Panduan Memindahkan ADUKANAJA ke Server Sungguhan

Panduan ini untuk tiga skenario. Pilih salah satu:

| Skenario | Cocok untuk | Butuh internet? |
|---|---|---|
| **A. Mode pameran (laptop / XAMPP)** | Presentasi ke guru, demo di kelas | Tidak |
| **B. VPS Linux (Ubuntu + Nginx)** | Dipakai sekolah sungguhan, domain sendiri | Ya |
| **C. Shared hosting (cPanel)** | Hosting murah tanpa akses root | Ya |

Persyaratan umum: **PHP ≥ 8.2** dengan ekstensi `pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, fileinfo, gd, zip`, **MySQL/MariaDB**, dan **Composer**.

---

## A. Mode pameran di laptop (XAMPP)

1. Nyalakan **MySQL** di XAMPP (tekan **Stop** saat selesai — jangan menutup paksa).
2. Dari folder project:
   ```powershell
   php artisan optimize:clear
   php artisan serve
   ```
   Buka `http://127.0.0.1:8000`. Font Inter sudah lokal, jadi tampilan sama persis **tanpa internet**.
3. Agar bisa dibuka dari HP/laptop lain di Wi-Fi yang sama:
   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   lalu buka `http://<IP-laptop>:8000` (cek IP dengan `ipconfig`). Izinkan PHP di Windows Firewall bila diminta.
4. **Sebelum hari-H:** buat backup (`mysqldump`, lihat bagian Backup), uji semua alur sekali, dan siapkan akun demo (lihat `docs/SKENARIO_DEMO.md`).

---

## B. VPS Linux (Ubuntu 22.04/24.04 + Nginx)

### 1. Siapkan server
```bash
sudo apt update && sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-gd php8.3-zip php8.3-curl unzip git composer
sudo mysql -e "CREATE DATABASE adukanaja CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'adukanaja'@'localhost' IDENTIFIED BY '<password_kuat>';"
sudo mysql -e "GRANT ALL ON adukanaja.* TO 'adukanaja'@'localhost'; FLUSH PRIVILEGES;"
```
Gunakan user database khusus (bukan `root`).

### 2. Pasang aplikasi
```bash
cd /var/www && sudo mkdir adukanaja && sudo chown $USER adukanaja && cd adukanaja
# unggah/ekstrak project ke sini (tanpa folder vendor dan tanpa file .env lama)
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
nano .env                      # isi APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```
> **Jangan** menjalankan `php artisan db:seed` di server sungguhan — seeder berisi akun demo berpassword `password`.

### 3. Buat akun admin pertama
```bash
php artisan adukanaja:buat-admin kepsek@sekolahanda.sch.id --name="Admin Sekolah"
# password ditanyakan secara tersembunyi (minimal 8 karakter)
```
Akun siswa dan teknisi dibuat admin lewat menu **Pengguna**.

### 4. Izin folder
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 5. Nginx
`/etc/nginx/sites-available/adukanaja`:
```nginx
server {
    listen 80;
    server_name <domain-anda>;
    root /var/www/adukanaja/public;
    index index.php;
    client_max_body_size 8M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```
```bash
sudo ln -s /etc/nginx/sites-available/adukanaja /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d <domain-anda>   # HTTPS gratis
```

### 6. Optimasi produksi
```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
(Jalankan ulang setelah mengubah `.env` atau kode.)

---

## C. Shared hosting (cPanel)

1. Unggah project ke folder **di luar** `public_html` (mis. `/home/user/adukanaja`).
2. Salin **isi folder `public/`** ke `public_html/` (atau subfolder), lalu ubah `public_html/index.php`:
   ```php
   require __DIR__.'/../adukanaja/vendor/autoload.php';
   $app = require_once __DIR__.'/../adukanaja/bootstrap/app.php';
   ```
3. Buat database + user lewat **MySQL Databases**, isi `.env` (dari `.env.production.example`).
4. Jalankan lewat **Terminal cPanel** (atau SSH): `composer install --no-dev`, `php artisan key:generate`, `php artisan migrate --force`, `php artisan adukanaja:buat-admin ...`.
5. `php artisan storage:link` — bila hosting tidak mengizinkan symlink, salin isi `storage/app/public` ke `public_html/storage` setiap ada unggahan baru, atau minta penyedia hosting mengaktifkan symlink.
6. Pastikan PHP versi 8.2+ dipilih di **Select PHP Version**.

---

## Pengaturan `.env` yang WAJIB benar

```
APP_ENV=production
APP_DEBUG=false          # jika true, pesan error & potongan kode terlihat pengunjung!
APP_URL=https://<domain-anda>
SESSION_SECURE_COOKIE=true
DB_USERNAME=<bukan root>
```
`APP_KEY` dibuat per-instalasi dengan `php artisan key:generate` — jangan memakai kunci dari komputer pengembangan.

## Mengganti SEMUA password demo

Akun demo dari seeder (`rudi@sch.id`, siswa, teknisi) memakai password `password`. Sebelum dipublikasikan:

```bash
php artisan adukanaja:acak-password-demo     # semua akun berpassword "password" → password acak, ditampilkan SEKALI
php artisan adukanaja:ganti-password rudi@sch.id   # ganti password satu akun (ditanya tersembunyi)
php artisan adukanaja:cek-keamanan           # laporan kesiapan: APP_DEBUG, APP_KEY, HTTPS, password demo, storage link
```
Catat password acak yang tampil — tidak disimpan dalam bentuk asli dan tidak dapat ditampilkan lagi. Jalankan `adukanaja:cek-keamanan` sampai seluruh baris **OK**. Atau hapus akun demo dari menu **Pengguna** dan hanya pakai akun yang dibuat dengan `adukanaja:buat-admin`.

## Backup & pemulihan

```bash
# database (jadwalkan harian lewat cron)
mysqldump -u adukanaja -p adukanaja > backup-$(date +%F).sql
# foto unggahan
tar czf foto-$(date +%F).tar.gz storage/app/public
```
Contoh cron (setiap hari 02.00): `0 2 * * * cd /var/www/adukanaja && mysqldump -u adukanaja -p'<password>' adukanaja > /backup/adukanaja-$(date +\%F).sql`
Pulihkan: `mysql -u adukanaja -p adukanaja < backup.sql`. **Uji pemulihan** setidaknya sekali.

## Memperbarui aplikasi
```bash
# 1. backup database dulu
php artisan down
# 2. ganti kode (JANGAN menimpa .env dan folder storage)
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

## Troubleshooting
| Gejala | Penyebab & solusi |
|---|---|
| Halaman putih / error 500 | Lihat `storage/logs/laravel.log`; cek izin `storage` & `bootstrap/cache`; pastikan `.env` ada dan `APP_KEY` terisi |
| Foto tidak tampil | Jalankan `php artisan storage:link`; cek `APP_URL` |
| Error 419 (kedaluwarsa) | Sesi habis — muat ulang halaman; pastikan `SESSION_SECURE_COOKIE=true` hanya jika memakai HTTPS |
| Tampilan lama setelah update | Tekan Ctrl+F5; jalankan `php artisan optimize:clear` |
| `No connection could be made` | MySQL belum menyala / kredensial `.env` salah |
| XAMPP: MySQL mati sendiri | Selalu tekan **Stop** sebelum menutup XAMPP; lihat `mysql_error.log` |
