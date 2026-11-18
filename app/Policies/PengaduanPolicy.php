<?php

namespace App\Policies;

use App\Models\Pengaduan;
use App\Models\User;

/**
 * Otorisasi per-objek pengaduan (ditemukan otomatis oleh Laravel lewat konvensi nama).
 * - admin   : boleh melihat semua pengaduan
 * - siswa   : hanya pengaduan miliknya sendiri
 * - teknisi : hanya pengaduan yang ditugaskan kepadanya
 */
class PengaduanPolicy
{
    public function view(User $user, Pengaduan $pengaduan): bool
    {
        return match (true) {
            $user->isAdmin()   => true,
            $user->isUser()    => (int) $pengaduan->user_id === (int) $user->id,
            $user->isTeknisi() => (int) $pengaduan->teknisi_id === (int) $user->id,
            default            => false,
        };
    }

    /** Komentar: boleh oleh semua pihak yang terlibat pada pengaduan tersebut. */
    public function komentar(User $user, Pengaduan $pengaduan): bool
    {
        return $this->view($user, $pengaduan);
    }
}
