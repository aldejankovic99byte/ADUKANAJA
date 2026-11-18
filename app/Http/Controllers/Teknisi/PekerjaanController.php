<?php

namespace App\Http\Controllers\Teknisi;

use App\Exceptions\TransisiTidakValid;
use App\Http\Controllers\Controller;
use App\Models\BuktiPerbaikan;
use App\Models\Pengaduan;
use App\Services\PengaduanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi teknisi pada tugasnya. Tugas dicari lewat $request->user()->tugas() (milik sendiri saja -> 404 jika bukan),
 * lalu status diubah HANYA lewat PengaduanWorkflow yang memvalidasi transisi.
 */
class PekerjaanController extends Controller
{
    public function __construct(private PengaduanWorkflow $workflow)
    {
    }

    public function terima(Request $request, int $tugas): RedirectResponse
    {
        return $this->jalankan($request, $tugas, 'Tugas diterima. Silakan mulai pekerjaan jika sudah siap.',
            fn (Pengaduan $p) => $this->workflow->terima($p, $request->user()));
    }

    public function mulai(Request $request, int $tugas): RedirectResponse
    {
        return $this->jalankan($request, $tugas, 'Pekerjaan dimulai.',
            fn (Pengaduan $p) => $this->workflow->mulai($p, $request->user()));
    }

    public function progres(Request $request, int $tugas): RedirectResponse
    {
        $data = $request->validate([
            'progres' => ['required', 'integer', 'min:0', 'max:99'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'progres.required' => 'Isi persentase progres.',
            'progres.integer' => 'Progres harus berupa angka bulat.',
            'progres.max' => 'Progres maksimal 99% (100% diisi saat mengajukan penyelesaian).',
        ]);

        return $this->jalankan($request, $tugas, 'Progres pekerjaan diperbarui.',
            fn (Pengaduan $p) => $this->workflow->perbaruiProgres($p, $request->user(), (int) $data['progres'], $data['catatan'] ?? null));
    }

    public function bukti(Request $request, int $tugas): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:'.implode(',', BuktiPerbaikan::JENIS)],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'jenis.required' => 'Pilih jenis bukti (sebelum / sesudah).',
            'jenis.in' => 'Jenis bukti tidak valid.',
            'foto.required' => 'Pilih foto bukti terlebih dahulu.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 4 MB.',
        ]);

        return $this->jalankan($request, $tugas, 'Foto bukti berhasil diunggah.',
            fn (Pengaduan $p) => $this->workflow->tambahBukti($p, $request->user(), $data['jenis'], $request->file('foto'), $data['keterangan'] ?? null));
    }

    public function ajukan(Request $request, int $tugas): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:1000']]);

        return $this->jalankan($request, $tugas, 'Penyelesaian diajukan. Menunggu validasi admin.',
            fn (Pengaduan $p) => $this->workflow->ajukanPenyelesaian($p, $request->user(), $data['catatan'] ?? null));
    }

    /** Tolak tugas (hanya saat belum diterima). Tugas kembali ke antrean admin; teknisi diarahkan ke daftar tugasnya. */
    public function tolak(Request $request, int $tugas): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan.required' => 'Alasan penolakan wajib diisi agar admin dapat menugaskan ulang.',
            'alasan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $pengaduan = $request->user()->tugas()->findOrFail($tugas);

        try {
            $this->workflow->tolak($pengaduan, $request->user(), $data['alasan']);
        } catch (TransisiTidakValid $e) {
            return redirect()->route('teknisi.tugas.show', $pengaduan->id)->with('error', $e->getMessage());
        }

        return redirect()->route('teknisi.tugas.index')->with('success', 'Tugas ditolak dan dikembalikan ke admin untuk ditugaskan ulang.');
    }

    public function hapusBukti(Request $request, int $tugas, int $bukti): RedirectResponse
    {
        $pengaduan = $request->user()->tugas()->findOrFail($tugas);
        $foto = $pengaduan->bukti()->findOrFail($bukti); // bukti milik tugas lain -> 404

        try {
            $this->workflow->hapusBukti($pengaduan, $request->user(), $foto);
        } catch (TransisiTidakValid $e) {
            return redirect()->route('teknisi.tugas.show', $pengaduan->id)->with('error', $e->getMessage());
        }

        return redirect()->route('teknisi.tugas.show', $pengaduan->id)->with('success', 'Foto bukti dihapus.');
    }

    private function jalankan(Request $request, int $tugas, string $pesanSukses, callable $aksi): RedirectResponse
    {
        $pengaduan = $request->user()->tugas()->findOrFail($tugas); // bukan tugas miliknya -> 404

        try {
            $aksi($pengaduan);
        } catch (TransisiTidakValid $e) {
            return redirect()->route('teknisi.tugas.show', $pengaduan->id)->with('error', $e->getMessage());
        }

        return redirect()->route('teknisi.tugas.show', $pengaduan->id)->with('success', $pesanSukses);
    }
}
