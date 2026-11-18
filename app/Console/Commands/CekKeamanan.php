<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/** Pemeriksaan kesiapan keamanan sebelum aplikasi dipublikasikan / dipamerkan. */
class CekKeamanan extends Command
{
    protected $signature = 'adukanaja:cek-keamanan {--strict : Gagal (exit code 1) bila ada satu peringatan pun}';

    protected $description = 'Periksa pengaturan produksi (APP_DEBUG, APP_KEY, HTTPS, password demo, storage link)';

    public function handle(): int
    {
        $produksi = app()->environment('production');
        $peringatan = [];
        $baris = [];

        $catat = function (string $item, bool $ok, string $keterangan, bool $kritis = false) use (&$baris, &$peringatan) {
            $baris[] = [$item, $ok ? 'OK' : ($kritis ? 'BAHAYA' : 'PERINGATAN'), $keterangan];
            if (! $ok) {
                $peringatan[] = [$item, $kritis];
            }
        };

        $catat('APP_ENV', $produksi, 'Nilai saat ini: '.app()->environment().($produksi ? '' : ' (untuk server sungguhan harus "production")'));
        $catat('APP_DEBUG', ! config('app.debug'), config('app.debug') ? 'AKTIF — pesan error & jejak kode terlihat pengunjung. Set APP_DEBUG=false' : 'Nonaktif', config('app.debug') && $produksi);
        $catat('APP_KEY', filled(config('app.key')), filled(config('app.key')) ? 'Terisi' : 'Kosong — jalankan: php artisan key:generate', true);
        $catat('APP_URL memakai HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Nilai: '.config('app.url'));
        $catat('Storage link', is_link(public_path('storage')) || is_dir(public_path('storage')), 'public/storage (foto unggahan)');

        $demo = User::query()->get()->filter(fn (User $u) => Hash::check('password', $u->password));
        $catat(
            'Password demo ("password")', $demo->isEmpty(),
            $demo->isEmpty() ? 'Tidak ada akun memakai password demo' : $demo->count().' akun: '.$demo->pluck('email')->take(6)->implode(', ').($demo->count() > 6 ? ', …' : '').' — jalankan: php artisan adukanaja:acak-password-demo',
            $produksi
        );

        $this->table(['Pemeriksaan', 'Status', 'Keterangan'], $baris);

        $kritis = collect($peringatan)->where(1, true)->count();
        if ($peringatan === []) {
            $this->info('Semua pemeriksaan lolos. Aplikasi siap dipublikasikan.');

            return self::SUCCESS;
        }

        $this->warn(count($peringatan).' pemeriksaan perlu perhatian'.($kritis > 0 ? " ({$kritis} kritis)" : '').'.');

        return ($kritis > 0 || $this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }
}
