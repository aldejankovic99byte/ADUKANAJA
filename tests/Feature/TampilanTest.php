<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 4 — tampilan: shell (sidebar/topbar/drawer), notifikasi toast, kartu statistik, design system.
 * Business logic tidak diuji ulang di sini (sudah dicakup test FASE 1-3).
 */
class TampilanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email, ?string $nis = null): User
    {
        return User::create([
            'name' => ucfirst($role).' Uji', 'email' => $email, 'nis' => $nis,
            'kelas' => $role === 'siswa' ? 'XII RPL B' : null, 'role' => $role, 'password' => 'password123',
        ]);
    }

    private function pengaduan(User $pelapor, string $status, string $penanganan = 'not_assigned'): Pengaduan
    {
        $k = Kategori::firstOrCreate(['nama' => 'Listrik']);
        $p = new Pengaduan();
        $p->forceFill([
            'user_id' => $pelapor->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.',
            'status' => $status, 'status_penanganan' => $penanganan,
        ])->save();

        return $p;
    }

    public function test_shell_sidebar_dan_drawer_tampil_untuk_setiap_role(): void
    {
        $kasus = [
            ['admin', 'admin@sch.id', null, '/admin/dashboard', ['Pengguna', 'Verifikasi', 'Laporan']],
            ['siswa', 'siswa@sch.id', '1001', '/beranda', ['Beranda', 'Ajukan', 'Riwayat']],
            ['teknisi', 'teknisi@sch.id', null, '/teknisi/dashboard', ['Tugas', 'Riwayat', 'Profil']],
        ];

        foreach ($kasus as [$role, $email, $nis, $url, $menu]) {
            $res = $this->actingAs($this->user($role, $email, $nis))->get($url)->assertOk()
                ->assertSee('id="sidebar"', false)
                ->assertSee('data-drawer-toggle', false)
                ->assertSee('css/app.css', false)
                ->assertSee('css/fonts.css', false)
                ->assertSee('class="topbar"', false);

            foreach ($menu as $label) {
                $res->assertSee($label);
            }
        }
    }

    public function test_menu_menandai_halaman_aktif(): void
    {
        $this->actingAs($this->user('teknisi', 't@sch.id'))->get('/teknisi/tugas')->assertOk()
            ->assertSee('aria-current="page"', false);
    }

    public function test_flash_message_tampil_sebagai_toast(): void
    {
        $this->withSession(['success' => 'Berhasil uji', 'error' => 'Gagal uji'])
            ->actingAs($this->user('admin', 'admin@sch.id'))
            ->get('/admin/dashboard')->assertOk()
            ->assertSee('toast success', false)->assertSee('Berhasil uji')
            ->assertSee('toast error', false)->assertSee('Gagal uji');
    }

    public function test_kartu_statistik_memiliki_ikon_di_semua_dashboard(): void
    {
        foreach ([['admin', 'a@sch.id', null, '/admin/dashboard'], ['siswa', 's@sch.id', '1', '/beranda'], ['teknisi', 't@sch.id', null, '/teknisi/dashboard']] as [$role, $email, $nis, $url]) {
            $this->actingAs($this->user($role, $email, $nis))->get($url)->assertOk()
                ->assertSee('stat-ico', false)->assertSee('viewBox="0 0 24 24"', false);
        }
    }

    public function test_dashboard_admin_menghitung_ringkasan_penanganan(): void
    {
        $admin = $this->user('admin', 'admin@sch.id');
        $siswa = $this->user('siswa', 'siswa@sch.id', '1001');

        $this->pengaduan($siswa, 'approved', 'not_assigned');
        $this->pengaduan($siswa, 'approved', 'not_assigned');
        $this->pengaduan($siswa, 'approved', 'in_progress');
        $this->pengaduan($siswa, 'approved', 'accepted');
        $this->pengaduan($siswa, 'approved', 'waiting_confirmation');
        $this->pengaduan($siswa, 'approved', 'completed');
        $this->pengaduan($siswa, 'pending');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertViewHas('total', 7)
            ->assertViewHas('pending', 1)
            ->assertViewHas('approved', 6)
            ->assertViewHas('belumDitugaskan', 2)
            ->assertViewHas('sedangDiperbaiki', 2)
            ->assertViewHas('menungguValidasi', 1)
            ->assertViewHas('selesaiTuntas', 1)
            ->assertSee('Penanganan perbaikan')
            ->assertSee('Belum ditugaskan');
    }

    public function test_halaman_login_memakai_tampilan_baru(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('auth-wrap', false)->assertSee('ADUKANAJA')->assertSee('btn-primary', false);
    }

    public function test_stylesheet_memuat_design_system_dan_breakpoint(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach (['#0B2A6B', '#0A2463', '#F7F9FD', "'Inter'", '.sidebar', '.toast', '.timeline', '.badge-p-completed', '.hero', '.btn-light'] as $token) {
            $this->assertStringContainsString($token, $css, "CSS tidak memuat {$token}");
        }

        // breakpoint untuk HP, tablet/drawer, dan layar lebar
        foreach (['max-width: 720px', 'max-width: 1024px', 'max-width: 1100px'] as $mq) {
            $this->assertStringContainsString($mq, $css, "Media query {$mq} hilang");
        }
    }

    public function test_komponen_tampilan_baru_ada(): void
    {
        foreach (['icon', 'timeline', 'bukti-galeri', 'status-badge', 'status-penanganan', 'avatar', 'empty'] as $c) {
            $this->assertFileExists(resource_path("views/components/{$c}.blade.php"));
        }
    }

    // ------------------------------------------------------------------ halaman login (FASE 6)

    public function test_halaman_login_memuat_identitas_visual_dan_form_yang_sama(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('img/logo.svg', false)
            ->assertSee('css/login.css', false)
            ->assertSee('Masuk ke Akun Anda')
            ->assertSee('Sistem Pengaduan Sarana Sekolah')
            ->assertSee('Transparan')->assertSee('Respon Cepat')->assertSee('Kolaboratif')
            ->assertSee('id="toggle-password"', false)
            ->assertSee('name="login"', false)->assertSee('name="password"', false)
            ->assertSee('autocomplete="username"', false)->assertSee('autocomplete="current-password"', false)
            ->assertSee('action="'.route('login.proses').'"', false);
    }

    public function test_login_gagal_menampilkan_error_pada_tampilan_baru(): void
    {
        $this->from('/login')->followingRedirects()
            ->post('/login', ['login' => 'tidakada@sch.id', 'password' => 'salah'])
            ->assertOk()->assertSee('has-error', false)->assertSee('field-error', false);
    }

    public function test_aset_halaman_login_tersedia_dan_valid(): void
    {
        foreach (['img/logo.svg', 'img/login/sekolah.svg', 'img/login/gelombang-atas.svg', 'img/login/gelombang-bawah.svg', 'css/login.css'] as $f) {
            $this->assertFileExists(public_path($f));
            $this->assertGreaterThan(200, filesize(public_path($f)), "{$f} terlalu kecil/kosong");
        }

        foreach (['img/logo.svg', 'img/login/sekolah.svg', 'img/login/gelombang-atas.svg', 'img/login/gelombang-bawah.svg'] as $svg) {
            libxml_use_internal_errors(true);
            $this->assertNotFalse(simplexml_load_file(public_path($svg)), "{$svg} bukan XML/SVG yang valid");
        }
    }

    public function test_favicon_memakai_logo_di_layout_utama(): void
    {
        $this->actingAs($this->user('admin', 'admin@sch.id'))->get('/admin/dashboard')->assertOk()
            ->assertSee('rel="icon"', false)->assertSee('img/logo.svg', false);
    }

    // ------------------------------------------------------------------ palet navy & putih (FASE 6)

    public function test_stylesheet_tidak_lagi_memakai_biru_terang_lama(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach (['#2563EB', '#0F172A', 'rgba(37,99,235', '#38BDF8'] as $lama) {
            $this->assertStringNotContainsString($lama, $css, "Warna lama {$lama} masih tertinggal di app.css");
        }
    }

    public function test_dashboard_setiap_role_memakai_banner_hero(): void
    {
        foreach ([['admin', 'a@sch.id', null, '/admin/dashboard'], ['siswa', 's@sch.id', '1', '/beranda'], ['teknisi', 't@sch.id', null, '/teknisi/dashboard']] as [$role, $email, $nis, $url]) {
            $this->actingAs($this->user($role, $email, $nis))->get($url)->assertOk()
                ->assertSee('class="hero"', false)->assertSee('hero-actions', false);
        }
    }

    public function test_logo_putih_untuk_sidebar_tersedia_dan_dipakai(): void
    {
        $this->assertFileExists(public_path('img/logo-putih.svg'));
        libxml_use_internal_errors(true);
        $this->assertNotFalse(simplexml_load_file(public_path('img/logo-putih.svg')));

        $this->actingAs($this->user('admin', 'admin@sch.id'))->get('/admin/dashboard')->assertOk()
            ->assertSee('img/logo-putih.svg', false)->assertSee('brand-logo', false);
    }
}
