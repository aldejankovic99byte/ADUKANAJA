<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->data($request);

        return view('admin.laporan.index', [
            'pengaduan' => $this->query($request)->paginate(15)->withQueryString(),
            'ringkasan' => $data['ringkasan'],
            'f' => $this->filters($request),
        ]);
    }

    /** Export Excel (.xlsx) sesuai filter yang sedang aktif. */
    public function excel(Request $request): BinaryFileResponse
    {
        $rows = $this->query($request)->get()->map(fn (Pengaduan $p) => [
            $p->kode(),
            $p->created_at->format('d/m/Y'),
            $p->tanggal_kejadian->format('d/m/Y'),
            $p->pelapor->name,
            (string) $p->pelapor->kelas,
            $p->kategori->nama,
            $p->lokasi,
            $p->deskripsi,
            $p->statusLabel(),
            (string) $p->tanggapan,
        ])->all();

        $path = SimpleXlsx::create(
            'Laporan Pengaduan',
            ['ID', 'Tanggal Lapor', 'Tanggal Kejadian', 'Siswa', 'Kelas', 'Kategori', 'Lokasi', 'Deskripsi', 'Status', 'Tanggapan Admin'],
            $rows,
            [10, 14, 16, 26, 14, 14, 22, 50, 12, 40],
        );

        return response()->download($path, 'laporan-pengaduan-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Halaman cetak: otomatis membuka dialog print -> pilih "Save as PDF". */
    public function cetak(Request $request): View
    {
        return view('admin.laporan.cetak', [
            'pengaduan' => $this->query($request)->get(),
            'ringkasan' => $this->data($request)['ringkasan'],
            'f' => $this->filters($request),
        ]);
    }

    /** Filter divalidasi: tanggal harus valid & status harus salah satu status yang dikenal (selain itu diabaikan). */
    private function filters(Request $request): array
    {
        $tanggal = function ($v) {
            if (! is_string($v) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) {
                return null;
            }

            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $v : null;
        };
        $status = $request->query('status');

        return [
            'dari' => $tanggal($request->query('dari')),
            'sampai' => $tanggal($request->query('sampai')),
            'status' => in_array($status, Pengaduan::STATUS, true) ? $status : null,
        ];
    }

    private function query(Request $request)
    {
        $f = $this->filters($request);

        return Pengaduan::with(['pelapor', 'kategori'])
            ->when($f['dari'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['sampai'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->status($f['status'])
            ->latest();
    }

    private function data(Request $request): array
    {
        $hitung = $this->query($request)->reorder()->pluck('status')->countBy();

        return ['ringkasan' => [
            'total' => $hitung->sum(),
            'pending' => $hitung[Pengaduan::PENDING] ?? 0,
            'approved' => $hitung[Pengaduan::APPROVED] ?? 0,
            'rejected' => $hitung[Pengaduan::REJECTED] ?? 0,
        ]];
    }
}