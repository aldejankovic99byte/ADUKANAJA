<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $role = $request->query('role');
        $role = in_array($role, User::ROLES, true) ? $role : null;

        $users = User::query()
            ->when($role, fn ($query, $r) => $query->where('role', $r))
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%")
                    ->orWhere('kelas', 'like', "%{$q}%");
            }))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengguna.index', compact('users', 'q', 'role'));
    }

    public function create(): View
    {
        return view('admin.pengguna.form', ['user' => new User(['role' => User::ROLE_USER])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create($data);

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.pengguna.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Admin tidak boleh mengubah role dirinya sendiri (agar tidak terkunci).
        // <select> role pada form dinonaktifkan untuk akun sendiri sehingga tidak ikut terkirim -> isi dari data tersimpan.
        if ($user->id === $request->user()->id) {
            $request->merge(['role' => $user->role]);
        }

        $data = $this->validated($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang dipakai.');
        }

        if ($user->pengaduan()->exists()) {
            return back()->with('error', "Pengguna {$user->name} masih memiliki pengaduan sehingga tidak dapat dihapus.");
        }

        if ($user->tugas()->exists()) {
            return back()->with('error', "Teknisi {$user->name} masih memiliki tugas sehingga tidak dapat dihapus.");
        }

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $ignore = $user?->id;

        // Role yang boleh dipilih di form admin: hanya 3 pilihan utama (user, teknisi, admin)
        // 'siswa' adalah alias lama yang masih valid di DB, tapi form baru selalu pakai 'user'
        $allowedRoles = [User::ROLE_ADMIN, User::ROLE_USER, User::ROLE_TEKNISI, User::ROLE_SISWA];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in($allowedRoles)],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($ignore)],
            'nis' => ['nullable', 'required_if:role,user', 'required_if:role,siswa', 'string', 'max:20', Rule::unique('users', 'nis')->ignore($ignore)],
            'kelas' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
        ], [
            'nis.required_if' => 'NIS wajib diisi untuk siswa.',
            'nis.unique' => 'NIS sudah dipakai pengguna lain.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'role.in' => 'Role tidak valid.',
        ]);

        // Role tidak boleh diubah jika pengguna sudah punya data terkait (pengaduan sebagai siswa / tugas sebagai teknisi),
        // agar data tidak "yatim" dan akses tidak berubah mendadak.
        if ($user && $data['role'] !== $user->role && ($user->pengaduan()->exists() || $user->tugas()->exists())) {
            throw ValidationException::withMessages([
                'role' => 'Role tidak dapat diubah karena pengguna ini sudah memiliki data pengaduan atau tugas.',
            ]);
        }

        return $data;
    }
}
