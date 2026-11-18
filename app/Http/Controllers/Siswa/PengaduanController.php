<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Sarana;
use App\Services\PengaduanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    public function __construct(private PengaduanWorkflow $workflow)
    {
    }

    /** Riwayat pengaduan milik siswa yang login (filter status). */
    public function index(Request $request): View
    {
        $pengaduan = $request->user()->pengaduan()
            ->with('kategori')
            ->status($request->query('status'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('siswa.pengaduan.riwayat', [
            'pengaduan' => $pengaduan,
            'status' => $request->query('status'),
        ]);
    }

    public function create(): View
    {
        return view('siswa.pengaduan.create', [
            'kategori' => Kategori::orderBy('nama')->get(),
            'sarana' => Sarana::orderBy('nama')->get(),
            'lokasiSaran' => Sarana::query()->distinct()->orderBy('lokasi')->pluck('lokasi'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'exists:kategori,id'],
            'sarana_id' => ['nullable', 'exists:sarana,id'],
            'lokasi' => ['required', 'string', 'max:100'],
            'tanggal_kejadian' => ['required', 'date', 'before_or_equal:today'],
            'deskripsi' => ['required', 'string', 'min:10', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'kategori_id.required' => 'Pilih kategori kerusakan.',
            'lokasi.required' => 'Lokasi / ruangan wajib diisi.',
            'tanggal_kejadian.required' => 'Tanggal kejadian wajib diisi.',
            'tanggal_kejadian.before_or_equal' => 'Tanggal kejadian tidak boleh melewati hari ini.',
            'deskripsi.required' => 'Deskripsi kerusakan wajib diisi.',
            'deskripsi.min' => 'Deskripsi minimal 10 karakter agar jelas bagi petugas.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $path = $request->hasFile('foto')
            ? $request->file('foto')->store('pengaduan', 'public')
            : null;

        $pengaduan = DB::transaction(function () use ($request, $data, $path) {
            $baru = $request->user()->pengaduan()->create([
                ...collect($data)->except('foto')->all(),
                'foto' => $path,
                'status' => Pengaduan::PENDING,
            ]);

            $this->workflow->pengajuan($baru, $request->user()); // riwayat status awal

            return $baru;
        });

        return redirect()->route('pengaduan.show', $pengaduan)
            ->with('success', 'Pengaduan '.$pengaduan->kode().' berhasil dikirim. Admin akan segera memverifikasi.');
    }

    public function show(Request $request, Pengaduan $pengaduan): View
    {
        // Siswa hanya boleh melihat pengaduannya sendiri (lihat App\Policies\PengaduanPolicy) -> 403 jika bukan miliknya
        Gate::authorize('view', $pengaduan);

        $pengaduan->load(['kategori', 'sarana', 'verifikator', 'teknisi', 'riwayat.user', 'komentar.penulis']);

        return view('siswa.pengaduan.show', compact('pengaduan'));
    }
}