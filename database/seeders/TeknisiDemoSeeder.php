<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat akun teknisi demo TANPA menyentuh data lain (aman dijalankan di database yang sudah berisi data):
 *   php artisan db:seed --class=TeknisiDemoSeeder
 * Idempotent: akun yang emailnya sudah ada tidak ditimpa. Password demo: "password" (ganti di produksi!).
 */
class TeknisiDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Pak Budi Santoso', 'budi@sch.id'],
            ['Bu Sari Wulandari', 'sari@sch.id'],
        ] as [$nama, $email]) {
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $nama, 'role' => User::ROLE_TEKNISI, 'password' => 'password'],
            );
        }
    }
}
