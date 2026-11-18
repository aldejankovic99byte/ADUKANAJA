<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BuatAdmin extends Command
{
    protected $signature = 'adukanaja:buat-admin {email} {--name=Administrator : Nama lengkap} {--password= : Password (jika kosong akan ditanyakan, tersembunyi)}';

    protected $description = 'Buat akun admin baru (untuk instalasi produksi tanpa seeder demo)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Email sudah dipakai akun lain.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('Password minimal 8 karakter. Akun tidak dibuat.');

            return self::FAILURE;
        }

        User::create(['name' => (string) $this->option('name'), 'email' => $email, 'role' => User::ROLE_ADMIN, 'password' => $password]);
        $this->info("Admin {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
