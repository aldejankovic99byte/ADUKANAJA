<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Pengaduan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $hitung = Pengaduan::query()->pluck('status')->countBy();

        // Data 12 bulan terakhir (dihitung di PHP agar jalan di MySQL maupun SQLite)
        $awal = now()->startOfMonth()->subMonths(11);
        $perBulan = Pengaduan::where('created_at', '>=', $awal)
            ->get(['created_at', 'status'])
            ->groupBy(fn ($p) => $p->created_at->format('Y-m'));

        $bulan = collect(range(0, 11))->map(function ($i) use ($awal, $perBulan) {
            $b = $awal->copy()->addMonths($i);
            $baris = $perBulan->get($b->format('Y-m'), collect());

            return [
                'label' => $b->translatedFormat('M'),
                'penuh' => $b->translatedFormat('F Y'),
                'pending' => $baris->where('status', Pengaduan::PENDING)->count(),
                'approved' => $baris->where('status', Pengaduan::APPROVED)->count(),
                'rejected' => $baris->where('status', Pengaduan::REJECTED)->count(),
            ];
        })->values();

        $kategori = Kategori::withCount('pengaduan')
            ->orderByDesc('pengaduan_count')->orderBy('nama')
            ->take(6)->get()
            ->map(fn ($k) => ['nama' => $k->nama, 'jumlah' => $k->pengaduan_count])
            ->filter(fn ($k) => $k['jumlah'] > 0)->values();

        // Ringkasan penanganan (FASE 4: kartu dashboard admin). Hanya membaca data.
        $penanganan = Pengaduan::query()->where('status', Pengaduan::APPROVED)->pluck('status_penanganan')->countBy();

        $status = [
            'pending' => (int) ($hitung[Pengaduan::PENDING] ?? 0),
            'approved' => (int) ($hitung[Pengaduan::APPROVED] ?? 0),
            'rejected' => (int) ($hitung[Pengaduan::REJECTED] ?? 0),
        ];

        return view('admin.dashboard', [
            'total' => array_sum($status),
            'pending' => $status['pending'],
            'approved' => $status['approved'],
            'rejected' => $status['rejected'],
            'chartData' => compact('bulan', 'status', 'kategori'),
            'belumDitugaskan' => (int) ($penanganan[Pengaduan::NOT_ASSIGNED] ?? 0),
            'sedangDiperbaiki' => collect(Pengaduan::PENANGANAN_DIKERJAKAN)->sum(fn ($s) => (int) ($penanganan[$s] ?? 0)),
            'selesaiTuntas' => (int) ($penanganan[Pengaduan::COMPLETED] ?? 0),
            'menungguValidasi' => Pengaduan::where('status_penanganan', Pengaduan::WAITING_CONFIRMATION)->count(),
            'terbaru' => Pengaduan::with(['pelapor', 'kategori'])->latest()->take(6)->get(),
        ]);
    }
}
