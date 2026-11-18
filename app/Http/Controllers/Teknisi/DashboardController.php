<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Seluruh query lewat relasi $user->tugas() sehingga otomatis dibatasi ke tugas milik teknisi ini.
        $hitung = $user->tugas()->pluck('status_penanganan')->countBy();

        $dikerjakan = collect(Pengaduan::PENANGANAN_DIKERJAKAN)->sum(fn ($s) => (int) ($hitung[$s] ?? 0));

        $prioritas = $user->tugas()
            ->with(['kategori', 'sarana'])
            ->whereIn('status_penanganan', [
                Pengaduan::ASSIGNED, Pengaduan::REVISION_REQUIRED, Pengaduan::ACCEPTED, Pengaduan::IN_PROGRESS,
            ])
            ->orderByRaw("CASE status_penanganan WHEN 'assigned' THEN 0 WHEN 'revision_required' THEN 1 ELSE 2 END")
            ->orderBy('ditugaskan_pada')
            ->take(5)
            ->get();

        return view('teknisi.dashboard', [
            'total' => (int) $hitung->sum(),
            'baru' => (int) ($hitung[Pengaduan::ASSIGNED] ?? 0),
            'dikerjakan' => $dikerjakan,
            'menunggu' => (int) ($hitung[Pengaduan::WAITING_CONFIRMATION] ?? 0),
            'selesai' => (int) ($hitung[Pengaduan::COMPLETED] ?? 0),
            'prioritas' => $prioritas,
        ]);
    }
}
