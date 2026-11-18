<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Sarana;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaranaController extends Controller
{
    /** Satu halaman untuk dua tabel master: Sarana & Kategori (sesuai wireframe 08). */
    public function index(): View
    {
        return view('admin.sarana.index', [
            'sarana' => Sarana::orderBy('nama')->get(),
            'kategori' => Kategori::withCount('pengaduan')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sarana.form', ['sarana' => new Sarana()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Sarana::create($this->validated($request));

        return redirect()->route('admin.sarana.index')->with('success', 'Data sarana berhasil ditambahkan.');
    }

    public function edit(Sarana $sarana): View
    {
        return view('admin.sarana.form', compact('sarana'));
    }

    public function update(Request $request, Sarana $sarana): RedirectResponse
    {
        $sarana->update($this->validated($request));

        return redirect()->route('admin.sarana.index')->with('success', 'Data sarana berhasil diperbarui.');
    }

    public function destroy(Sarana $sarana): RedirectResponse
    {
        $sarana->delete();

        return redirect()->route('admin.sarana.index')->with('success', 'Data sarana berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:100'],
        ], [
            'nama.required' => 'Nama sarana wajib diisi.',
            'lokasi.required' => 'Lokasi wajib diisi.',
        ]);
    }
}