<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 5 — pemeriksaan keamanan: XSS, CSRF (keberadaan token di form), hashing, throttle login,
 * upload berbahaya, sesi, dan file sensitif.
 */
class KeamananTest extends TestCase
{
    use RefreshDatabase;

    private const XSS_SCRIPT = "<script>alert('xss')</script>";
    private const XSS_IMG = '<img src=x onerror=alert(1)>';

    private function user(string $role, string $email, ?string $nis = null, ?string $name = null): User
    {
        return User::create([
            'name' => $name ?? ucfirst($role).' Uji', 'email' => $email, 'nis' => $nis,
            'kelas' => $role === 'siswa' ? 'XII RPL B' : null, 'role' => $role, 'password' => 'password123',
        ]);
    }

    // ------------------------------------------------------------------ XSS

    public function test_input_pengguna_di_escape_pada_semua_halaman(): void
    {
        $admin = $this->user('admin', 'admin@sch.id');
        $siswa = $this->user('siswa', 'siswa@sch.id', '1001', self::XSS_IMG);
        $teknisi = $this->user('teknisi', 'teknisi@sch.id', null, self::XSS_IMG);
        $kategori = Kategori::create(['nama' => 'Listrik']);

        $p = new Pengaduan();
        $p->forceFill([
            'user_id' => $siswa->id, 'kategori_id' => $kategori->id, 'lokasi' => self::XSS_SCRIPT,
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => self::XSS_SCRIPT.' deskripsi panjang',
            'status' => 'approved', 'tanggapan' => self::XSS_SCRIPT,
            'teknisi_id' => $teknisi->id, 'status_penanganan' => 'assigned', 'ditugaskan_pada' => now(),
        ])->save();

        $halaman = [
            [$admin, '/admin/dashboard'], [$admin, "/admin/verifikasi?id={$p->id}"], [$admin, '/admin/laporan'],
            [$admin, '/admin/laporan/cetak'], [$admin, '/admin/pengguna'],
            [$siswa, '/beranda'], [$siswa, '/riwayat'], [$siswa, "/pengaduan/{$p->id}"],
            [$teknisi, '/teknisi/dashboard'], [$teknisi, '/teknisi/tugas'], [$teknisi, "/teknisi/tugas/{$p->id}"],
        ];

        foreach ($halaman as [$user, $url]) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertDontSee(self::XSS_SCRIPT, false)
                ->assertDontSee(self::XSS_IMG, false);
        }

        // dan teks yang sudah di-escape memang tampil (bukan hilang)
        $this->actingAs($admin)->get("/admin/verifikasi?id={$p->id}")->assertSee('&lt;script&gt;', false);
    }

    // ------------------------------------------------------------------ CSRF

    public function test_semua_form_post_memiliki_token_csrf(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \RecursiveDirectoryIterator::SKIP_DOTS));
        $diperiksa = 0;

        foreach ($iterator as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $src = file_get_contents($file->getPathname());
            $jumlahForm = preg_match_all('/<form\b[^>]*method="POST"/i', $src);
            $diperiksa += $jumlahForm;

            $this->assertGreaterThanOrEqual(
                $jumlahForm, substr_count($src, '@csrf'),
                "Form POST tanpa @csrf di {$file->getPathname()}"
            );
        }

        $this->assertGreaterThan(10, $diperiksa, 'Pemindaian form tidak menemukan form apa pun');
    }

    // ------------------------------------------------------------------ password, sesi, throttle

    public function test_password_disimpan_dalam_bentuk_hash(): void
    {
        $u = $this->user('siswa', 'siswa@sch.id', '1001');
        $mentah = DB::table('users')->where('id', $u->id)->value('password');

        $this->assertNotSame('password123', $mentah);
        $this->assertTrue(Hash::check('password123', $mentah));
        $this->assertStringStartsWith('$2y$', $mentah);
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $u = $this->user('siswa', 'siswa@sch.id', '1001');

        $this->actingAs($u)->get('/beranda')->assertOk();
        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
        $this->get('/beranda')->assertRedirect('/login');
    }

    public function test_login_dibatasi_setelah_terlalu_banyak_percobaan(): void
    {
        $this->user('siswa', 'siswa@sch.id', '1001');

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['login' => 'siswa@sch.id', 'password' => 'salah'])->assertStatus(302);
        }

        $this->post('/login', ['login' => 'siswa@sch.id', 'password' => 'salah'])->assertStatus(429);
        // bahkan password yang benar ditolak selama dibatasi
        $this->post('/login', ['login' => 'siswa@sch.id', 'password' => 'password123'])->assertStatus(429);
    }

    public function test_pesan_login_gagal_tidak_membocorkan_apakah_akun_ada(): void
    {
        $this->user('siswa', 'siswa@sch.id', '1001');
        $pesan = 'Email/NIS atau password salah.';

        // akun ada tapi password salah, dan akun tidak ada: pesan harus identik
        $this->post('/login', ['login' => 'siswa@sch.id', 'password' => 'salah'])->assertSessionHasErrors(['login' => $pesan]);
        $this->post('/login', ['login' => 'tidakada@sch.id', 'password' => 'salah'])->assertSessionHasErrors(['login' => $pesan]);
    }

    // ------------------------------------------------------------------ upload

    public function test_file_php_ditolak_di_semua_titik_unggah(): void
    {
        Storage::fake('public');
        $siswa = $this->user('siswa', 'siswa@sch.id', '1001');
        $kategori = Kategori::create(['nama' => 'Listrik']);
        $shell = fn () => UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->actingAs($siswa)->post('/pengaduan', [
            'kategori_id' => $kategori->id, 'lokasi' => 'Lab', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Deskripsi cukup panjang.', 'foto' => $shell(),
        ])->assertSessionHasErrors('foto');

        $this->actingAs($siswa)->post('/profil/foto', ['foto' => $shell()])->assertSessionHasErrors('foto');

        $this->assertSame(0, Pengaduan::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_nama_file_unggahan_diacak_dan_tidak_memakai_nama_asli(): void
    {
        Storage::fake('public');
        $siswa = $this->user('siswa', 'siswa@sch.id', '1001');
        $kategori = Kategori::create(['nama' => 'Listrik']);

        $this->actingAs($siswa)->post('/pengaduan', [
            'kategori_id' => $kategori->id, 'lokasi' => 'Lab', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Deskripsi cukup panjang.', 'foto' => UploadedFile::fake()->image('evil.php.jpg'),
        ])->assertSessionHasNoErrors();

        $foto = Pengaduan::firstOrFail()->foto;
        $this->assertStringStartsWith('pengaduan/', $foto);
        $this->assertStringNotContainsString('evil', $foto);
        $this->assertStringNotContainsString('.php', $foto);
    }

    // ------------------------------------------------------------------ file sensitif

    public function test_file_sensitif_tidak_masuk_repository_dan_tidak_publik(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));
        $this->assertMatchesRegularExpression('/^\.env$/m', $gitignore, '.env harus ada di .gitignore');

        foreach (['.env', 'composer.json', 'artisan'] as $nama) {
            $this->assertFileDoesNotExist(public_path($nama), "{$nama} tidak boleh berada di folder public");
        }
    }
}
