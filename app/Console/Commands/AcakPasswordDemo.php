<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Mengganti SEMUA akun yang masih memakai password demo ("password") dengan password acak, lalu menampilkannya satu kali. */
class AcakPasswordDemo extends Command
{
    protected $signature = 'adukanaja:acak-password-demo {--panjang=12 : Panjang password acak (min 8)}';

    protected $description = 'Ganti semua password demo dengan password acak dan tampilkan daftarnya sekali (catat segera!)';

    public function handle(): int
    {
        $panjang = max(8, (int) $this->option('panjang'));
        $hasil = [];

        foreach (User::query()->orderBy('id')->get() as $user) {
            if (! Hash::check('password', $user->password)) {
                continue;
            }

            $baru = Str::password($panjang, letters: true, numbers: true, symbols: false);
            $user->forceFill(['password' => $baru])->save(); // cast "hashed" pada model User meng-hash otomatis
            $hasil[] = [$user->email, $user->role, $baru];
        }

        if ($hasil === []) {
            $this->info('Tidak ada akun yang memakai password demo. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        $this->table(['Email', 'Role', 'Password baru'], $hasil);
        $this->warn('Catat password di atas SEKARANG. Password tidak disimpan dalam bentuk asli dan tidak akan ditampilkan lagi.');

        return self::SUCCESS;
    }
}
