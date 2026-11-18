<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profil.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    /** Upload / ganti foto profil. Foto lama otomatis dihapus dari penyimpanan. */
    public function foto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        $path = $request->file('foto')->store('profil', 'public');

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        $user->update(['foto' => $path]);

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function hapusFoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
            $user->update(['foto' => null]);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }
}
