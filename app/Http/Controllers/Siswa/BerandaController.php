<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('siswa.beranda', [
            'total' => $user->pengaduan()->count(),
            'diproses' => $user->pengaduan()->where('status', Pengaduan::PENDING)->count(),
            'selesai' => $user->pengaduan()->where('status', Pengaduan::APPROVED)->count(),
            'terbaru' => $user->pengaduan()->with('kategori')->latest()->take(5)->get(),
        ]);
    }
}