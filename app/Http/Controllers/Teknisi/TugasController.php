<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Halaman tugas untuk role teknisi. Semua query dimulai dari $request->user()->tugas(),
 * sehingga teknisi tidak pernah dapat menjangkau pengaduan milik teknisi lain (anti-IDOR):
 * id milik orang lain -> 404, bukan 403, agar keberadaannya tidak bocor.
 */
class TugasController extends Controller
{
    /** Penanganan yang boleh dipakai sebagai filter pada daftar tugas aktif. */
    private const FILTER_AKTIF = [
        Pengaduan::ASSIGNED, Pengaduan::ACCEPTED, Pengaduan::IN_PROGRESS,
        Pengaduan::WAITING_CONFIRMATION, Pengaduan::REVISION_REQUIRED,
    ];

    /** Daftar tugas yang masih aktif (belum selesai). */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = in_array($status, self::FILTER_AKTIF, true) ? $status : null;
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $daftar = $request->user()->tugas()
            ->with(['kategori', 'sarana', 'pelapor'])
            ->where('status_penanganan', '!=', Pengaduan::COMPLETED)
            ->penanganan($status)
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('lokasi', 'like', "%{$q}%")->orWhere('deskripsi', 'like', "%{$q}%");
            }))
            ->orderByDesc('ditugaskan_pada')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('teknisi.tugas.index', [
            'daftar' => $daftar,
            'status' => $status,
            'q' => $q,
            'filterStatus' => self::FILTER_AKTIF,
        ]);
    }

    public function show(Request $request, int $tugas): View
    {
        $pengaduan = $request->user()->tugas()
            ->with(['kategori', 'sarana', 'pelapor', 'penugas', 'bukti', 'riwayat.user', 'komentar.penulis'])
            ->findOrFail($tugas);

        // Lapisan kedua: policy memastikan teknisi hanya boleh melihat tugas miliknya
        Gate::authorize('view', $pengaduan);

        return view('teknisi.tugas.show', ['pengaduan' => $pengaduan]);
    }

    /** Riwayat pekerjaan: tugas yang sudah dinyatakan selesai. */
    public function riwayat(Request $request): View
    {
        $riwayat = $request->user()->tugas()
            ->with(['kategori', 'sarana'])
            ->where('status_penanganan', Pengaduan::COMPLETED)
            ->orderByRaw('COALESCE(selesai_pada, updated_at) DESC')
            ->paginate(10)
            ->withQueryString();

        return view('teknisi.riwayat', ['riwayat' => $riwayat]);
    }
}
