<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Exceptions\TransisiTidakValid;
use App\Models\User;
use App\Services\PengaduanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function __construct(private PengaduanWorkflow $workflow)
    {
    }

    /** Daftar pengaduan di kiri, detail + form verifikasi di kanan (wireframe 09). */
    public function index(Request $request): View
    {
        $daftar = Pengaduan::with(['pelapor', 'kategori'])
            ->status($request->query('status'))
            ->penanganan($request->query('penanganan'))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $dipilih = null;
        if ($request->filled('id') && is_scalar($request->query('id'))) {
            $dipilih = Pengaduan::find($request->query('id'));
        }
        $dipilih ??= $daftar->first();
        $dipilih?->load(['pelapor', 'kategori', 'sarana', 'verifikator', 'teknisi', 'bukti', 'riwayat.user', 'komentar.penulis']);

        return view('admin.verifikasi.index', [
            'daftar' => $daftar,
            'dipilih' => $dipilih,
            'status' => $request->query('status'),
            'penanganan' => in_array($request->query('penanganan'), Pengaduan::PENANGANAN, true) ? $request->query('penanganan') : null,
            'daftarTeknisi' => User::where('role', User::ROLE_TEKNISI)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Pengaduan::STATUS)],
            'tanggapan' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ], [
            'status.required' => 'Pilih status penanganan.',
            'tanggapan.required_if' => 'Alasan penolakan wajib diisi pada catatan tanggapan.',
        ]);

        // Pengaduan yang sudah ditugaskan ke teknisi harus tetap approved (dijaga di PengaduanWorkflow::verifikasi)
        try {
            $this->workflow->verifikasi($pengaduan, $request->user(), $data['status'], $data['tanggapan'] ?? null);
        } catch (TransisiTidakValid $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.verifikasi', ['id' => $pengaduan->id, 'status' => $request->input('filter_status')])
            ->with('success', "Status pengaduan {$pengaduan->kode()} diubah menjadi ".$pengaduan->statusLabel().'.');
    }

    /** Tetapkan teknisi untuk pengaduan yang sudah disetujui (FASE 2; alur penugasan lengkap dikembangkan di FASE 3). */
    public function tugaskan(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        $data = $request->validate([
            'teknisi_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_TEKNISI)],
        ], [
            'teknisi_id.required' => 'Pilih teknisi yang akan ditugaskan.',
            'teknisi_id.exists' => 'Teknisi tidak valid.',
        ]);

        $kembali = fn () => redirect()->route('admin.verifikasi', ['id' => $pengaduan->id, 'status' => $request->input('filter_status')]);

        $teknisi = User::where('role', User::ROLE_TEKNISI)->findOrFail($data['teknisi_id']);

        try {
            $this->workflow->tugaskan($pengaduan, $teknisi, $request->user());
        } catch (TransisiTidakValid $e) {
            return $kembali()->with('error', $e->getMessage());
        }

        return $kembali()->with('success', "Pengaduan {$pengaduan->kode()} ditugaskan kepada {$teknisi->name}.");
    }

    /** Validasi akhir: hasil pekerjaan teknisi dinyatakan baik -> pengaduan selesai. */
    public function validasi(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->workflow->validasi($pengaduan, $request->user(), $data['catatan'] ?? null);
        } catch (TransisiTidakValid $e) {
            return $this->kembaliKe($request, $pengaduan)->with('error', $e->getMessage());
        }

        return $this->kembaliKe($request, $pengaduan)->with('success', "Pengaduan {$pengaduan->kode()} dinyatakan selesai.");
    }

    /** Hasil belum sesuai -> dikembalikan ke teknisi dengan catatan revisi (wajib). */
    public function revisi(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'catatan.required' => 'Catatan revisi wajib diisi agar teknisi tahu apa yang harus diperbaiki.',
        ]);

        try {
            $this->workflow->mintaRevisi($pengaduan, $request->user(), $data['catatan']);
        } catch (TransisiTidakValid $e) {
            return $this->kembaliKe($request, $pengaduan)->with('error', $e->getMessage());
        }

        return $this->kembaliKe($request, $pengaduan)->with('success', "Pengaduan {$pengaduan->kode()} dikembalikan ke teknisi untuk revisi.");
    }

    private function kembaliKe(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        return redirect()->route('admin.verifikasi', ['id' => $pengaduan->id, 'status' => $request->input('filter_status')]);
    }
}
