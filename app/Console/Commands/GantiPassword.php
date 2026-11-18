<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GantiPassword extends Command
{
    protected $signature = 'adukanaja:ganti-password {email : Email akun} {--password= : Password baru (jika kosong akan ditanyakan, tersembunyi)}';

    protected $description = 'Ganti password satu akun (minimal 8 karakter)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Akun dengan email itu tidak ditemukan.');

            return self::FAILURE;
        }

        $baru = $this->option('password') ?: $this->secret('Password baru (min. 8 karakter)');

        if (! is_string($baru) || mb_strlen($baru) < 8) {
            $this->error('Password minimal 8 karakter. Tidak ada yang diubah.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => $baru])->save();
        $this->info("Password {$user->email} berhasil diganti.");

        return self::SUCCESS;
    }
}
