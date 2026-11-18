<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notifikasi milik user yang login (semua role). Notifikasi orang lain -> 404. */
class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $daftar = $request->user()->notifikasi()->latest()->latest('id')->paginate(15);

        return view('notifikasi.index', ['daftar' => $daftar]);
    }

    /** Tandai dibaca lalu arahkan ke halaman terkait. */
    public function buka(Request $request, int $notifikasi): RedirectResponse
    {
        $n = $request->user()->notifikasi()->findOrFail($notifikasi);
        $n->tandaiDibaca();

        $tujuan = $n->url;
        // hanya tautan internal (diawali satu "/") yang diikuti, mencegah open-redirect
        $aman = is_string($tujuan) && str_starts_with($tujuan, '/') && ! str_starts_with($tujuan, '//');

        return redirect($aman ? $tujuan : route('notifikasi.index'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->belumDibaca()->update(['dibaca_pada' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
