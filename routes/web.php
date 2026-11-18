<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\SaranaController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\KomentarController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Siswa\BerandaController;
use App\Http\Controllers\Siswa\PengaduanController;
use App\Http\Controllers\Teknisi\DashboardController as TeknisiDashboardController;
use App\Http\Controllers\Teknisi\PekerjaanController;
use App\Http\Controllers\Teknisi\TugasController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman awal: arahkan sesuai role
Route::get('/', function (Request $request) {
    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    // Role tidak dikenal (mis. data diubah manual) -> keluarkan, jangan sampai terjadi redirect loop
    if (! in_array($user->role, User::ROLES, true)) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Role akun tidak dikenali. Hubungi admin sekolah.');
    }

    // Tiap role diarahkan ke halamannya sendiri (admin / siswa / teknisi)
    return redirect()->route($user->homeRoute());
})->name('home');

// Route fallback untuk menyajikan file storage pada shared hosting (seperti InfinityFree) yang tidak mendukung symlink
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/'.$path);

    if (! file_exists($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})->where('path', '.*');

// ---------- Autentikasi (FR-01) ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profil & ubah password (semua role)
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');
    Route::post('/profil/foto', [ProfilController::class, 'foto'])->name('profil.foto');
    Route::delete('/profil/foto', [ProfilController::class, 'hapusFoto'])->name('profil.foto.hapus');

    // Notifikasi in-app (semua role; hanya milik sendiri)
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notifikasi}', [NotifikasiController::class, 'buka'])->whereNumber('notifikasi')->name('notifikasi.buka');

    // Komentar pada pengaduan (admin, pelapor, teknisi yang ditugaskan -> dicek PengaduanPolicy::komentar)
    Route::post('/pengaduan/{pengaduan}/komentar', [KomentarController::class, 'store'])
        ->middleware('throttle:30,1')->whereNumber('pengaduan')->name('komentar.store');

    // ---------- Siswa / User (FR-03) — role 'user' (baru) maupun 'siswa' (lama) diterima ----------
    Route::middleware('role:siswa,user')->group(function () {
        Route::get('/beranda', BerandaController::class)->name('beranda');
        Route::get('/pengaduan/ajukan', [PengaduanController::class, 'create'])->name('pengaduan.create');
        Route::post('/pengaduan', [PengaduanController::class, 'store'])->name('pengaduan.store');
        Route::get('/riwayat', [PengaduanController::class, 'index'])->name('pengaduan.riwayat');
        Route::get('/pengaduan/{pengaduan}', [PengaduanController::class, 'show'])->name('pengaduan.show');
    });

    // ---------- Teknisi (FASE 2): hanya melihat tugas yang ditugaskan kepadanya ----------
    Route::prefix('teknisi')->name('teknisi.')->middleware('role:teknisi')->group(function () {
        Route::get('/dashboard', TeknisiDashboardController::class)->name('dashboard');
        Route::get('/tugas', [TugasController::class, 'index'])->name('tugas.index');
        Route::get('/tugas/{tugas}', [TugasController::class, 'show'])->whereNumber('tugas')->name('tugas.show');
        Route::get('/riwayat', [TugasController::class, 'riwayat'])->name('riwayat');

        // FASE 3: aksi pekerjaan (status diubah lewat PengaduanWorkflow)
        Route::post('/tugas/{tugas}/terima', [PekerjaanController::class, 'terima'])->whereNumber('tugas')->name('tugas.terima');
        Route::post('/tugas/{tugas}/mulai', [PekerjaanController::class, 'mulai'])->whereNumber('tugas')->name('tugas.mulai');
        Route::post('/tugas/{tugas}/progres', [PekerjaanController::class, 'progres'])->whereNumber('tugas')->name('tugas.progres');
        Route::post('/tugas/{tugas}/bukti', [PekerjaanController::class, 'bukti'])->whereNumber('tugas')->name('tugas.bukti');
        Route::post('/tugas/{tugas}/ajukan', [PekerjaanController::class, 'ajukan'])->whereNumber('tugas')->name('tugas.ajukan');
        Route::post('/tugas/{tugas}/tolak', [PekerjaanController::class, 'tolak'])->whereNumber('tugas')->name('tugas.tolak');
        Route::delete('/tugas/{tugas}/bukti/{bukti}', [PekerjaanController::class, 'hapusBukti'])->whereNumber(['tugas', 'bukti'])->name('tugas.bukti.hapus');
    });

    // ---------- Admin / Petugas ----------
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');          // FR-05

        // FR-02 Master data
        Route::resource('pengguna', PenggunaController::class)->parameters(['pengguna' => 'user'])->except('show');
        Route::resource('sarana', SaranaController::class)->except('show');
        Route::resource('kategori', KategoriController::class)->except(['index', 'show']);

        // FR-04 Verifikasi
        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi');
        Route::put('/verifikasi/{pengaduan}', [VerifikasiController::class, 'update'])->name('verifikasi.update');
        Route::put('/verifikasi/{pengaduan}/teknisi', [VerifikasiController::class, 'tugaskan'])->name('verifikasi.tugaskan');
        Route::put('/verifikasi/{pengaduan}/validasi', [VerifikasiController::class, 'validasi'])->name('verifikasi.validasi');
        Route::put('/verifikasi/{pengaduan}/revisi', [VerifikasiController::class, 'revisi'])->name('verifikasi.revisi');

        // FR-05 Laporan
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
        Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
        Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
    });
});
