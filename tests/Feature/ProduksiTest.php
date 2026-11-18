<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * FASE 6 — kesiapan produksi/pameran: header keamanan, font lokal (tanpa internet), perintah Artisan keamanan,
 * contoh pengaturan produksi, dan dokumentasi.
 */
class ProduksiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email, string $password = 'password', ?string $nis = null): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'nis' => $nis, 'role' => $role, 'password' => $password]);
    }

    // ------------------------------------------------------------------ header keamanan

    public function test_header_keamanan_dikirim_pada_semua_respons(): void
    {
        foreach (['/login', '/'] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Permissions-Policy');
        }

        $this->actingAs($this->user('admin', 'a@sch.id'))->get('/admin/dashboard')->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_hsts_hanya_dikirim_lewat_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
        $this->withServerVariables(['HTTPS' => 'on'])->get('/login')->assertHeader('Strict-Transport-Security');
    }

    // ------------------------------------------------------------------ font lokal

    public function test_font_inter_dihosting_lokal_tanpa_google_fonts(): void
    {
        $css = file_get_contents(public_path('css/fonts.css'));
        foreach (['Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold'] as $bobot) {
            $this->assertStringContainsString("Inter-{$bobot}.woff", $css);
            $this->assertFileExists(public_path("fonts/inter/Inter-{$bobot}.woff"));
            $this->assertGreaterThan(5000, filesize(public_path("fonts/inter/Inter-{$bobot}.woff")));
        }
        $this->assertFileExists(public_path('fonts/inter/LICENSE.txt'));
        $this->assertStringContainsString('Open Font License', file_get_contents(public_path('fonts/inter/LICENSE.txt')));

        $this->get('/login')->assertOk()->assertSee('css/fonts.css', false)
            ->assertDontSee('fonts.googleapis.com', false)->assertDontSee('fonts.gstatic.com', false);
        $this->actingAs($this->user('admin', 'a@sch.id'))->get('/admin/dashboard')->assertOk()
            ->assertSee('css/fonts.css', false)->assertDontSee('fonts.googleapis.com', false);
    }

    // ------------------------------------------------------------------ perintah Artisan

    public function test_acak_password_demo_hanya_mengubah_akun_berpassword_demo(): void
    {
        $demo = $this->user('siswa', 'demo@sch.id', 'password', '1001');
        $aman = $this->user('admin', 'aman@sch.id', 'KataSandiKuat9');
        $hashAman = $aman->password;

        $this->assertSame(0, Artisan::call('adukanaja:acak-password-demo', ['--panjang' => 14]));
        $keluaran = Artisan::output();

        $this->assertStringContainsString('demo@sch.id', $keluaran);
        $this->assertStringNotContainsString('aman@sch.id', $keluaran);
        $this->assertFalse(Hash::check('password', $demo->fresh()->password), 'password demo harus sudah diganti');
        $this->assertSame($hashAman, $aman->fresh()->password, 'akun dengan password sendiri tidak boleh disentuh');

        // password baru yang ditampilkan benar-benar bisa dipakai login
        preg_match('/demo@sch\.id\s*\|\s*siswa\s*\|\s*(\S+)/', $keluaran, $m);
        $this->assertNotEmpty($m[1] ?? null, 'password baru harus tampil di keluaran');
        $this->assertSame(14, strlen($m[1]));
        $this->assertTrue(Hash::check($m[1], $demo->fresh()->password));

        // dijalankan lagi: tidak ada yang diubah
        Artisan::call('adukanaja:acak-password-demo');
        $this->assertStringContainsString('Tidak ada akun yang memakai password demo', Artisan::output());
    }

    public function test_cek_keamanan_melaporkan_password_demo_dan_gagal_pada_kondisi_kritis(): void
    {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]); // kunci terisi agar hasil tidak bergantung pada .env
        $this->user('admin', 'a@sch.id', 'password');

        // lingkungan non-produksi: peringatan saja, exit 0; mode --strict gagal
        $this->assertSame(0, Artisan::call('adukanaja:cek-keamanan'));
        $this->assertStringContainsString('Password demo', Artisan::output());
        $this->assertSame(1, Artisan::call('adukanaja:cek-keamanan', ['--strict' => true]));

        // produksi dengan debug aktif + password demo -> kritis
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.debug' => true]);
        $this->assertSame(1, Artisan::call('adukanaja:cek-keamanan'));
        $this->assertStringContainsString('BAHAYA', Artisan::output());

        // produksi, debug mati, tanpa password demo -> lolos
        config(['app.debug' => false]);
        User::query()->update(['password' => Hash::make('KataSandiKuat9')]);
        $this->assertSame(0, Artisan::call('adukanaja:cek-keamanan'));
    }

    public function test_ganti_password(): void
    {
        $u = $this->user('siswa', 's@sch.id', 'password', '1001');

        $this->assertSame(1, Artisan::call('adukanaja:ganti-password', ['email' => 'tidakada@sch.id', '--password' => 'KataSandiBaru1']));
        $this->assertSame(1, Artisan::call('adukanaja:ganti-password', ['email' => 's@sch.id', '--password' => 'pendek']));
        $this->assertTrue(Hash::check('password', $u->fresh()->password), 'password tidak boleh berubah pada kasus gagal');

        $this->assertSame(0, Artisan::call('adukanaja:ganti-password', ['email' => 's@sch.id', '--password' => 'KataSandiBaru1']));
        $this->assertTrue(Hash::check('KataSandiBaru1', $u->fresh()->password));
    }

    public function test_buat_admin(): void
    {
        $this->assertSame(0, Artisan::call('adukanaja:buat-admin', ['email' => 'baru@sch.id', '--name' => 'Kepala Sekolah', '--password' => 'KataSandiKuat9']));
        $admin = User::where('email', 'baru@sch.id')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Kepala Sekolah', $admin->name);
        $this->assertTrue(Hash::check('KataSandiKuat9', $admin->password));
        $this->assertNotSame('KataSandiKuat9', $admin->getRawOriginal('password'));

        // email ganda, email tidak valid, password pendek
        $this->assertSame(1, Artisan::call('adukanaja:buat-admin', ['email' => 'baru@sch.id', '--password' => 'KataSandiKuat9']));
        $this->assertSame(1, Artisan::call('adukanaja:buat-admin', ['email' => 'bukan-email', '--password' => 'KataSandiKuat9']));
        $this->assertSame(1, Artisan::call('adukanaja:buat-admin', ['email' => 'lain@sch.id', '--password' => 'pendek']));
        $this->assertDatabaseMissing('users', ['email' => 'lain@sch.id']);
        $this->assertSame(1, User::count());
    }

    // ------------------------------------------------------------------ berkas produksi & dokumentasi

    public function test_contoh_env_produksi_aman(): void
    {
        $env = file_get_contents(base_path('.env.production.example'));

        $this->assertMatchesRegularExpression('/^APP_ENV=production/m', $env);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false/m', $env);
        $this->assertMatchesRegularExpression('/^SESSION_SECURE_COOKIE=true/m', $env);
        $this->assertDoesNotMatchRegularExpression('/^DB_USERNAME=root/m', $env);
        $this->assertDoesNotMatchRegularExpression('/^APP_KEY=base64:/m', $env, 'contoh .env tidak boleh memuat APP_KEY sungguhan');
    }

    public function test_dokumentasi_penilaian_tersedia(): void
    {
        foreach (['docs/DEPLOY.md', 'docs/DOKUMENTASI_TEKNIS.md', 'docs/SKENARIO_DEMO.md', 'docs/TESTING.md', 'docs/erd.png'] as $f) {
            $this->assertFileExists(base_path($f));
            $this->assertGreaterThan(300, filesize(base_path($f)), "{$f} terlalu kecil");
        }

        $deploy = file_get_contents(base_path('docs/DEPLOY.md'));
        foreach (['APP_DEBUG=false', 'adukanaja:acak-password-demo', 'storage:link', 'migrate --force', 'mysqldump'] as $kata) {
            $this->assertStringContainsString($kata, $deploy, "Panduan deploy harus menyebut {$kata}");
        }
    }
}
