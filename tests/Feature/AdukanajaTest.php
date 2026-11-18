<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdukanajaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123']);
    }

    private function siswa(string $nis = '1001', string $email = 'siswa@sch.id'): User
    {
        return User::create(['name' => 'Siswa '.$nis, 'email' => $email, 'nis' => $nis, 'kelas' => 'XII RPL B', 'role' => 'siswa', 'password' => 'password123']);
    }

    private function pengaduan(User $u, array $o = []): Pengaduan
    {
        $k = Kategori::firstOrCreate(['nama' => 'Listrik']);

        return Pengaduan::create(array_merge([
            'user_id' => $u->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.',
        ], $o));
    }

    public function test_halaman_login_tampil_dan_guest_dialihkan(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sistem Pengaduan Sarana Sekolah');
        $this->get('/beranda')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_login_dengan_email_dan_nis_diarahkan_sesuai_role(): void
    {
        $this->admin();
        $this->siswa();

        $this->post('/login', ['login' => 'admin@sch.id', 'password' => 'password123'])->assertRedirect('/');
        $this->get('/')->assertRedirect('/admin/dashboard');
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', ['login' => '1001', 'password' => 'password123'])->assertRedirect('/');
        $this->get('/')->assertRedirect('/beranda');
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $this->siswa();
        $this->from('/login')->post('/login', ['login' => '1001', 'password' => 'salah'])
            ->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_role_dibatasi_middleware(): void
    {
        $siswa = $this->siswa();
        $admin = $this->admin();

        $this->actingAs($siswa)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($siswa)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($admin)->get('/beranda')->assertForbidden();
    }

    public function test_siswa_mengajukan_pengaduan_dengan_foto(): void
    {
        Storage::fake('public');
        $siswa = $this->siswa();
        $k = Kategori::create(['nama' => 'Listrik']);

        $this->actingAs($siswa)->get('/pengaduan/ajukan')->assertOk();

        $res = $this->actingAs($siswa)->post('/pengaduan', [
            'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Stop kontak korslet dan berbau gosong.',
            'foto' => UploadedFile::fake()->image('bukti.jpg', 600, 400),
        ]);

        $p = Pengaduan::first();
        $res->assertRedirect(route('pengaduan.show', $p));
        $this->assertSame('pending', $p->status);
        $this->assertNotNull($p->foto);
        Storage::disk('public')->assertExists($p->foto);
    }

    public function test_validasi_pengaduan(): void
    {
        $siswa = $this->siswa();

        $this->actingAs($siswa)->post('/pengaduan', [
            'tanggal_kejadian' => now()->addDays(3)->toDateString(), 'deskripsi' => 'pendek',
            'foto' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['kategori_id', 'lokasi', 'tanggal_kejadian', 'deskripsi', 'foto']);
    }

    public function test_siswa_hanya_melihat_pengaduan_miliknya(): void
    {
        $a = $this->siswa('1001', 'a@sch.id');
        $b = $this->siswa('1002', 'b@sch.id');
        $milikA = $this->pengaduan($a);
        $milikB = $this->pengaduan($b);

        $this->actingAs($a)->get(route('pengaduan.show', $milikA))->assertOk();
        $this->actingAs($a)->get(route('pengaduan.show', $milikB))->assertForbidden();
        $this->actingAs($a)->get('/riwayat')->assertOk()->assertSee($milikA->kode())->assertDontSee($milikB->kode());
        $this->actingAs($a)->get('/beranda')->assertOk()->assertSee('Halo,');
    }

    public function test_admin_verifikasi_mengubah_status_dan_menyimpan_tanggapan(): void
    {
        $admin = $this->admin();
        $p = $this->pengaduan($this->siswa());

        $this->actingAs($admin)->get('/admin/verifikasi')->assertOk()->assertSee($p->kode());

        $this->actingAs($admin)->put(route('admin.verifikasi.update', $p), [
            'status' => 'approved', 'tanggapan' => 'Teknisi dijadwalkan besok.',
        ])->assertRedirect();

        $p->refresh();
        $this->assertSame('approved', $p->status);
        $this->assertSame($admin->id, $p->diverifikasi_oleh);
        $this->assertSame('Teknisi dijadwalkan besok.', $p->tanggapan);
    }

    public function test_penolakan_wajib_menyertakan_alasan(): void
    {
        $admin = $this->admin();
        $p = $this->pengaduan($this->siswa());

        $this->actingAs($admin)->put(route('admin.verifikasi.update', $p), ['status' => 'rejected'])
            ->assertSessionHasErrors('tanggapan');
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_crud_pengguna_sarana_kategori(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'Dina', 'email' => 'dina@sch.id', 'nis' => '9001', 'kelas' => 'X RPL', 'role' => 'siswa', 'password' => 'rahasia123',
        ])->assertRedirect('/admin/pengguna');
        $dina = User::where('email', 'dina@sch.id')->first();
        $this->assertTrue(password_verify('rahasia123', $dina->password));

        $this->actingAs($admin)->put("/admin/pengguna/{$dina->id}", [
            'name' => 'Dina Baru', 'email' => 'dina@sch.id', 'nis' => '9001', 'kelas' => 'X RPL', 'role' => 'siswa',
        ])->assertRedirect('/admin/pengguna');
        $this->assertSame('Dina Baru', $dina->fresh()->name);
        $this->assertTrue(password_verify('rahasia123', $dina->fresh()->password), 'password kosong = tidak berubah');

        $this->actingAs($admin)->delete("/admin/pengguna/{$dina->id}")->assertSessionHas('success');
        $this->assertModelMissing($dina);

        $this->actingAs($admin)->post('/admin/sarana', ['nama' => 'Proyektor', 'lokasi' => 'Lab'])->assertRedirect('/admin/sarana');
        $this->actingAs($admin)->post('/admin/kategori', ['nama' => 'Air'])->assertRedirect('/admin/sarana');
        $this->actingAs($admin)->get('/admin/sarana')->assertOk()->assertSee('Proyektor')->assertSee('Air');
        $this->actingAs($admin)->post('/admin/kategori', ['nama' => 'Air'])->assertSessionHasErrors('nama');
    }

    public function test_tidak_bisa_hapus_diri_sendiri_dan_kategori_terpakai(): void
    {
        $admin = $this->admin();
        $p = $this->pengaduan($this->siswa());

        $this->actingAs($admin)->delete("/admin/pengguna/{$admin->id}")->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/pengguna/{$p->user_id}")->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/kategori/{$p->kategori_id}")->assertSessionHas('error');
    }

    public function test_dashboard_dan_laporan_beserta_export(): void
    {
        $admin = $this->admin();
        $s = $this->siswa();
        $this->pengaduan($s, ['status' => 'approved']);
        $this->pengaduan($s, ['status' => 'pending']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Dashboard Admin');
        $this->actingAs($admin)->get('/admin/laporan?status=approved')->assertOk()->assertSee('Approved');
        $this->actingAs($admin)->get('/admin/laporan/cetak')->assertOk()->assertSee('window.print');

        $res = $this->actingAs($admin)->get('/admin/laporan/excel');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('content-type'));
        $this->assertStringStartsWith('PK', file_get_contents($res->baseResponse->getFile()->getPathname()));
    }

    public function test_profil_dan_ubah_password(): void
    {
        $siswa = $this->siswa();

        $this->actingAs($siswa)->get('/profil')->assertOk();
        $this->actingAs($siswa)->put('/profil/password', [
            'password_lama' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHasErrors('password_lama');

        $this->actingAs($siswa)->put('/profil/password', [
            'password_lama' => 'password123', 'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHas('success');
        $this->assertTrue(password_verify('baru12345', $siswa->fresh()->password));
    }

    public function test_halaman_form_tambah_dan_edit_tampil(): void
    {
        $admin = $this->admin();
        $p = $this->pengaduan($this->siswa());
        $sarana = \App\Models\Sarana::create(['nama' => 'AC', 'lokasi' => 'Lab']);

        foreach ([
            '/admin/pengguna/create', "/admin/pengguna/{$admin->id}/edit", "/admin/pengguna/{$p->user_id}/edit",
            '/admin/sarana/create', "/admin/sarana/{$sarana->id}/edit",
            '/admin/kategori/create', "/admin/kategori/{$p->kategori_id}/edit",
            "/admin/verifikasi?id={$p->id}&status=pending",
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_foto_profil_upload_ganti_dan_hapus(): void
    {
        Storage::fake('public');
        $u = $this->siswa();

        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->image('a.jpg', 200, 200)])->assertRedirect();
        $u->refresh();
        $this->assertNotNull($u->foto);
        Storage::disk('public')->assertExists($u->foto);
        $lama = $u->foto;

        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->image('b.png', 200, 200)]);
        $u->refresh();
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($u->foto);

        $this->actingAs($u)->get('/profil')->assertOk()->assertSee('storage/'.$u->foto, false);

        $this->actingAs($u)->delete('/profil/foto')->assertRedirect();
        $this->assertNull($u->refresh()->foto);
        $this->assertNull($u->fotoUrl());
    }

    public function test_foto_profil_menolak_file_tidak_valid(): void
    {
        Storage::fake('public');
        $u = $this->siswa();
        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('foto');
        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->image('big.jpg')->size(3000)])->assertSessionHasErrors('foto');
        $this->assertNull($u->refresh()->foto);
    }

    public function test_dashboard_memuat_data_grafik_dan_hapus_pengguna_menghapus_foto(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $s = $this->siswa();
        $this->pengaduan($s);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('id="chart-data"', false)->assertSee('chart-kategori', false);

        $this->actingAs($s)->post('/profil/foto', ['foto' => UploadedFile::fake()->image('a.jpg')]);
        $path = $s->refresh()->foto;
        Storage::disk('public')->assertExists($path);
        $kosong = $this->siswa('2002', 'lain@sch.id');
        $kosong->update(['foto' => $path]);
        $this->actingAs($admin)->delete(route('admin.pengguna.destroy', $kosong))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_sesi_kedaluwarsa_419_dialihkan_ramah(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->post('/uji-419', fn () => abort(419));
        $r = $this->from('/login')->post('/uji-419', ['login' => 'x', 'password' => 'rahasia']);
        $r->assertRedirect('/login')->assertSessionHas('error');
    }
}
