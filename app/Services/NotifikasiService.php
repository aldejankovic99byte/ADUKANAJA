<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\User;

/**
 * Pembuat notifikasi in-app. Dipanggil dari PengaduanWorkflow (di dalam transaksi yang sama dengan perubahan status)
 * dan dari KomentarController. Pelaku tindakan tidak pernah menerima notifikasi atas tindakannya sendiri.
 */
class NotifikasiService
{
    public function ke(?User $penerima, Pengaduan $pengaduan, string $jenis, string $judul, ?string $pesan = null, ?User $pelaku = null): void
    {
        if ($penerima === null || ($pelaku !== null && $penerima->id === $pelaku->id)) {
            return;
        }

        Notifikasi::create([
            'user_id' => $penerima->id,
            'pengaduan_id' => $pengaduan->id,
            'jenis' => $jenis,
            'judul' => mb_substr($judul, 0, 150),
            'pesan' => $pesan !== null ? mb_substr($pesan, 0, 500) : null,
            'url' => $this->urlUntuk($penerima, $pengaduan),
        ]);
    }

    public function keAdmin(Pengaduan $pengaduan, string $jenis, string $judul, ?string $pesan = null, ?User $pelaku = null): void
    {
        foreach (User::where('role', User::ROLE_ADMIN)->get() as $admin) {
            $this->ke($admin, $pengaduan, $jenis, $judul, $pesan, $pelaku);
        }
    }

    /** Pelapor (siswa) pengaduan. */
    public function kePelapor(Pengaduan $pengaduan, string $jenis, string $judul, ?string $pesan = null, ?User $pelaku = null): void
    {
        $this->ke($pengaduan->pelapor, $pengaduan, $jenis, $judul, $pesan, $pelaku);
    }

    /** Teknisi yang saat ini memegang tugas (jika ada). */
    public function keTeknisi(Pengaduan $pengaduan, string $jenis, string $judul, ?string $pesan = null, ?User $pelaku = null): void
    {
        $this->ke($pengaduan->teknisi_id ? $pengaduan->teknisi : null, $pengaduan, $jenis, $judul, $pesan, $pelaku);
    }

    /** Tautan tujuan notifikasi (relatif) sesuai role penerima. */
    public function urlUntuk(User $penerima, Pengaduan $pengaduan): string
    {
        return match ($penerima->role) {
            User::ROLE_ADMIN => route('admin.verifikasi', ['id' => $pengaduan->id], false),
            User::ROLE_TEKNISI => route('teknisi.tugas.show', $pengaduan->id, false),
            default => route('pengaduan.show', $pengaduan->id, false),
        };
    }
}
