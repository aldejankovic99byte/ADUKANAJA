<?php

namespace App\Http\Controllers;

use App\Models\KomentarPengaduan;
use App\Models\Pengaduan;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Komentar pada pengaduan. Boleh oleh pihak yang terlibat: admin, siswa pelapor, dan teknisi yang ditugaskan
 * (PengaduanPolicy::komentar). Peserta lain menerima notifikasi.
 */
class KomentarController extends Controller
{
    public function __construct(private NotifikasiService $notif)
    {
    }

    public function store(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        Gate::authorize('komentar', $pengaduan);

        $data = $request->validate([
            'isi' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'isi.required' => 'Tulis komentar terlebih dahulu.',
            'isi.min' => 'Komentar terlalu pendek.',
            'isi.max' => 'Komentar maksimal 1000 karakter.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($pengaduan, $user, $data) {
            KomentarPengaduan::create(['pengaduan_id' => $pengaduan->id, 'user_id' => $user->id, 'isi' => $data['isi']]);

            $judul = "Komentar baru pada {$pengaduan->kode()}";
            $pesan = "{$user->name}: ".mb_substr($data['isi'], 0, 120);

            $this->notif->keAdmin($pengaduan, 'komentar', $judul, $pesan, $user);
            $this->notif->kePelapor($pengaduan, 'komentar', $judul, $pesan, $user);
            $this->notif->keTeknisi($pengaduan, 'komentar', $judul, $pesan, $user);
        });

        return redirect($this->tujuan($user, $pengaduan).'#komentar')->with('success', 'Komentar terkirim.');
    }

    private function tujuan(User $user, Pengaduan $pengaduan): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => route('admin.verifikasi', ['id' => $pengaduan->id]),
            User::ROLE_TEKNISI => route('teknisi.tugas.show', $pengaduan->id),
            default => route('pengaduan.show', $pengaduan->id),
        };
    }
}
