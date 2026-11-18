<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function create(): View
    {
        return view('admin.kategori.form', ['kategori' => new Kategori()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Kategori::create($this->validated($request));

        return redirect()->route('admin.sarana.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori): View
    {
        return view('admin.kategori.form', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($this->validated($request, $kategori));

        return redirect()->route('admin.sarana.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->pengaduan()->exists()) {
            return back()->with('error', "Kategori {$kategori->nama} sudah dipakai pada pengaduan sehingga tidak dapat dihapus.");
        }

        $kategori->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Kategori $kategori = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:60', Rule::unique('kategori', 'nama')->ignore($kategori?->id)],
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique' => 'Kategori tersebut sudah ada.',
        ]);
    }
}