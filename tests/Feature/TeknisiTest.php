<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 2 — role teknisi: akses, isolasi data tugas, pengelolaan akun, dan penugasan.
 */
class TeknisiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email, ?string $nis = null): User
    {
        return User::create([
            'name' => ucfirst($role).' '.$email, 'email' => $email, 'nis' => $nis,
            'kelas' => $role === 'siswa' ? 'XII RPL B' : null, 'role' => $role, 'password' => 'password123',
        ]);
    }

    private function admin(): User
    {
        return $this->user('admin', 'admin@sch.id');
    }

    private function siswa(string $nis = '1001', string $email = 'siswa@sch.id'): User
    {
        return $this->user('siswa', $email, $nis);
    }

    private function teknisi(string $email = 'teknisi@sch.id'): User
    {
        return $this->user('teknisi', $email);
    }

    /** Membuat pengaduan; kolom penugasan diisi lewat forceFill (memang tidak mass-assignable). */
    private function pengaduan(User $pelapor, array $o = []): Pengaduan
    {
        $k = Kategori::firstOrCreate(['nama' => 'Listrik']);

        $p = new Pengaduan();
        $p->forceFill(array_merge([
            'user_id' => $pelapor->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.',
            'status' => 'approved',
        ], $o))->save();

        return $p;
    }

    private function tugas(User $pelapor, User $teknisi, string $lokasi, string $penanganan = 'assigned'): Pengaduan
    {
        return $this->pengaduan($pelapor, [
            'lokasi' => $lokasi, 'teknisi_id' => $teknisi->id, 'status_penanganan' => $penanganan,
            'ditugaskan_pada' => now(),
        ]);
    }

    // ---------------------------------------------------------------- redirect & akses

    public function test_login_teknisi_diarahkan_ke_dashboard_teknisi(): void
    {
        $this->teknisi();

        $this->post('/login', ['login' => 'teknisi@sch.id', 'password' => 'password123'])->assertRedirect('/');
        $this->get('/')->assertRedirect('/teknisi/dashboard');
        $this->get('/teknisi/dashboard')->assertOk()->assertSee('Dashboard Teknisi', false);
    }

    public function test_guest_tidak_bisa_membuka_halaman_teknisi(): void
    {
        $this->get('/teknisi/dashboard')->assertRedirect('/login');
        $this->get('/teknisi/tugas')->assertRedirect('/login');
        $this->get('/teknisi/riwayat')->assertRedirect('/login');
    }

    public function test_matriks_akses_ketiga_role(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswa();
        $teknisi = $this->teknisi();

        $adminUrls = ['/admin/dashboard', '/admin/pengguna', '/admin/sarana', '/admin/verifikasi', '/admin/laporan', '/admin/laporan/excel'];
        $siswaUrls = ['/beranda', '/pengaduan/ajukan', '/riwayat'];
        $teknisiUrls = ['/teknisi/dashboard', '/teknisi/tugas', '/teknisi/riwayat'];

        // admin: hanya area admin
        foreach ($adminUrls as $u) { $this->actingAs($admin)->get($u)->assertOk(); }
        foreach (array_merge($siswaUrls, $teknisiUrls) as $u) { $this->actingAs($admin)->get($u)->assertForbidden(); }

        // siswa: hanya area siswa
        foreach ($siswaUrls as $u) { $this->actingAs($siswa)->get($u)->assertOk(); }
        foreach (array_merge($adminUrls, $teknisiUrls) as $u) { $this->actingAs($siswa)->get($u)->assertForbidden(); }

        // teknisi: hanya area teknisi
        foreach ($teknisiUrls as $u) { $this->actingAs($teknisi)->get($u)->assertOk(); }
        foreach (array_merge($adminUrls, $siswaUrls) as $u) { $this->actingAs($teknisi)->get($u)->assertForbidden(); }

        // semua role boleh membuka profil sendiri
        foreach ([$admin, $siswa, $teknisi] as $u) { $this->actingAs($u)->get('/profil')->assertOk(); }
    }

    public function test_teknisi_tidak_bisa_melakukan_aksi_admin_dan_siswa(): void
    {
        $teknisi = $this->teknisi();
        $siswa = $this->siswa();
        $p = $this->pengaduan($siswa, ['status' => 'pending']);

        // tidak bisa mengubah status verifikasi
        $this->actingAs($teknisi)->put(route('admin.verifikasi.update', $p), ['status' => 'approved'])->assertForbidden();
        $this->assertSame('pending', $p->fresh()->status);

        // tidak bisa menugaskan diri sendiri
        $this->actingAs($teknisi)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $teknisi->id])->assertForbidden();
        $this->assertNull($p->fresh()->teknisi_id);

        // tidak bisa membuat user / mengajukan pengaduan / membuka detail pengaduan siswa
        $this->actingAs($teknisi)->post('/admin/pengguna', ['name' => 'X', 'email' => 'x@sch.id', 'role' => 'admin', 'password' => 'rahasia123'])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@sch.id']);
        $this->actingAs($teknisi)->post('/pengaduan', [])->assertForbidden();
        $this->actingAs($teknisi)->get(route('pengaduan.show', $p))->assertForbidden();
    }

    public function test_menu_teknisi_tidak_memuat_tautan_admin(): void
    {
        $teknisi = $this->teknisi();

        $this->actingAs($teknisi)->get('/teknisi/dashboard')->assertOk()
            ->assertDontSee(route('admin.pengguna.index'), false)
            ->assertDontSee(route('admin.verifikasi'), false)
            ->assertSee(route('teknisi.tugas.index'), false);
    }

    public function test_role_tidak_dikenal_dikeluarkan_tanpa_redirect_loop(): void
    {
        $aneh = $this->user('hacker', 'aneh@sch.id');

        $this->actingAs($aneh)->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- isolasi data (anti-IDOR)

    public function test_teknisi_hanya_melihat_tugas_miliknya(): void
    {
        $siswa = $this->siswa();
        $t1 = $this->teknisi('t1@sch.id');
        $t2 = $this->teknisi('t2@sch.id');

        $a = $this->tugas($siswa, $t1, 'Ruang Alfa');
        $b = $this->tugas($siswa, $t2, 'Ruang Beta');
        $c = $this->pengaduan($siswa, ['lokasi' => 'Ruang Gamma']); // belum ditugaskan

        $this->actingAs($t1)->get('/teknisi/tugas')->assertOk()
            ->assertSee('Ruang Alfa')->assertDontSee('Ruang Beta')->assertDontSee('Ruang Gamma');

        $this->actingAs($t1)->get(route('teknisi.tugas.show', $a->id))->assertOk()->assertSee('Ruang Alfa');
        $this->actingAs($t1)->get(route('teknisi.tugas.show', $b->id))->assertNotFound(); // milik teknisi lain
        $this->actingAs($t1)->get(route('teknisi.tugas.show', $c->id))->assertNotFound(); // belum ditugaskan
        $this->actingAs($t1)->get('/teknisi/tugas/999999')->assertNotFound();
        $this->actingAs($t1)->get('/teknisi/tugas/abc')->assertNotFound();

        $this->actingAs($t2)->get(route('teknisi.tugas.show', $b->id))->assertOk();
        $this->actingAs($t2)->get(route('teknisi.tugas.show', $a->id))->assertNotFound();
    }

    public function test_dashboard_teknisi_menghitung_hanya_tugas_sendiri(): void
    {
        $siswa = $this->siswa();
        $t1 = $this->teknisi('t1@sch.id');
        $t2 = $this->teknisi('t2@sch.id');

        $this->tugas($siswa, $t1, 'R1', 'assigned');
        $this->tugas($siswa, $t1, 'R2', 'in_progress');
        $this->tugas($siswa, $t1, 'R3', 'accepted');
        $this->tugas($siswa, $t1, 'R4', 'waiting_confirmation');
        $this->tugas($siswa, $t1, 'R5', 'completed');
        $this->tugas($siswa, $t2, 'R6', 'assigned'); // tidak boleh ikut terhitung

        $this->actingAs($t1)->get('/teknisi/dashboard')->assertOk()
            ->assertViewHas('total', 5)
            ->assertViewHas('baru', 1)
            ->assertViewHas('dikerjakan', 2)
            ->assertViewHas('menunggu', 1)
            ->assertViewHas('selesai', 1);
    }

    public function test_riwayat_hanya_memuat_pekerjaan_selesai_milik_sendiri(): void
    {
        $siswa = $this->siswa();
        $t1 = $this->teknisi('t1@sch.id');
        $t2 = $this->teknisi('t2@sch.id');

        $this->tugas($siswa, $t1, 'Ruang Selesai', 'completed');
        $this->tugas($siswa, $t1, 'Ruang Aktif', 'in_progress');
        $this->tugas($siswa, $t2, 'Ruang Orang Lain', 'completed');

        $this->actingAs($t1)->get('/teknisi/riwayat')->assertOk()
            ->assertSee('Ruang Selesai')->assertDontSee('Ruang Aktif')->assertDontSee('Ruang Orang Lain');

        // tugas selesai tidak muncul di daftar tugas aktif
        $this->actingAs($t1)->get('/teknisi/tugas')->assertOk()
            ->assertSee('Ruang Aktif')->assertDontSee('Ruang Selesai');
    }

    public function test_filter_dan_pencarian_daftar_tugas(): void
    {
        $siswa = $this->siswa();
        $t1 = $this->teknisi('t1@sch.id');
        $this->tugas($siswa, $t1, 'Ruang Baru', 'assigned');
        $this->tugas($siswa, $t1, 'Ruang Kerja', 'in_progress');

        $this->actingAs($t1)->get('/teknisi/tugas?status=assigned')->assertOk()
            ->assertSee('Ruang Baru')->assertDontSee('Ruang Kerja');
        $this->actingAs($t1)->get('/teknisi/tugas?q=Kerja')->assertOk()
            ->assertSee('Ruang Kerja')->assertDontSee('Ruang Baru');
        // nilai filter tidak dikenal diabaikan (tidak error)
        $this->actingAs($t1)->get('/teknisi/tugas?status=ngawur&q[]=x')->assertOk();
    }

    public function test_siswa_tetap_hanya_melihat_pengaduan_sendiri_setelah_ada_role_teknisi(): void
    {
        $a = $this->siswa('1001', 'a@sch.id');
        $b = $this->siswa('1002', 'b@sch.id');
        $milikB = $this->pengaduan($b);

        $this->actingAs($a)->get(route('pengaduan.show', $milikB))->assertForbidden();
        $this->actingAs($b)->get(route('pengaduan.show', $milikB))->assertOk();
    }

    // ---------------------------------------------------------------- pengelolaan akun teknisi

    public function test_admin_membuat_akun_teknisi_tanpa_nis_lalu_teknisi_bisa_login(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/pengguna/create')->assertOk()->assertSee('Petugas Teknisi');

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'Pak Joko', 'email' => 'joko@sch.id', 'role' => 'teknisi', 'password' => 'rahasia123',
        ])->assertRedirect('/admin/pengguna')->assertSessionHasNoErrors();

        $joko = User::where('email', 'joko@sch.id')->firstOrFail();
        $this->assertSame('teknisi', $joko->role);
        $this->assertNull($joko->nis);
        $this->assertTrue($joko->isTeknisi());
        $this->assertSame('teknisi.dashboard', $joko->homeRoute());

        auth()->logout();
        $this->post('/login', ['login' => 'joko@sch.id', 'password' => 'rahasia123'])->assertRedirect('/');
    }

    public function test_role_tidak_valid_ditolak_dan_nis_tetap_wajib_untuk_siswa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'X', 'email' => 'x@sch.id', 'role' => 'superadmin', 'password' => 'rahasia123',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'Y', 'email' => 'y@sch.id', 'role' => 'siswa', 'password' => 'rahasia123',
        ])->assertSessionHasErrors('nis');

        $this->assertDatabaseMissing('users', ['email' => 'x@sch.id']);
        $this->assertDatabaseMissing('users', ['email' => 'y@sch.id']);
    }

    public function test_filter_role_di_daftar_pengguna(): void
    {
        $admin = $this->admin();
        $this->teknisi('t1@sch.id');
        $this->siswa('1001', 'murid@sch.id');

        $this->actingAs($admin)->get('/admin/pengguna?role=teknisi')->assertOk()
            ->assertSee('t1@sch.id')->assertDontSee('murid@sch.id');
        $this->actingAs($admin)->get('/admin/pengguna?role=ngawur')->assertOk();
    }

    public function test_admin_dapat_mengedit_akun_sendiri_tanpa_mengubah_role(): void
    {
        $admin = $this->admin();

        // <select role> dinonaktifkan untuk akun sendiri -> field role tidak ikut terkirim
        $this->actingAs($admin)->put("/admin/pengguna/{$admin->id}", [
            'name' => 'Admin Baru', 'email' => 'admin@sch.id',
        ])->assertRedirect('/admin/pengguna')->assertSessionHasNoErrors();

        $this->assertSame('Admin Baru', $admin->fresh()->name);
        $this->assertSame('admin', $admin->fresh()->role);

        // mencoba menurunkan role sendiri lewat request tetap diabaikan
        $this->actingAs($admin)->put("/admin/pengguna/{$admin->id}", [
            'name' => 'Admin Baru', 'email' => 'admin@sch.id', 'role' => 'siswa', 'nis' => '777',
        ]);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_teknisi_dengan_tugas_tidak_bisa_dihapus_atau_diganti_role(): void
    {
        $admin = $this->admin();
        $t = $this->teknisi();
        $this->tugas($this->siswa(), $t, 'Ruang Alfa');

        $this->actingAs($admin)->delete("/admin/pengguna/{$t->id}")->assertSessionHas('error');
        $this->assertModelExists($t);

        $this->actingAs($admin)->put("/admin/pengguna/{$t->id}", [
            'name' => $t->name, 'email' => $t->email, 'role' => 'admin',
        ])->assertSessionHasErrors('role');
        $this->assertSame('teknisi', $t->fresh()->role);
    }

    public function test_teknisi_tanpa_tugas_dapat_dihapus(): void
    {
        $admin = $this->admin();
        $t = $this->teknisi();

        $this->actingAs($admin)->delete("/admin/pengguna/{$t->id}")->assertSessionHas('success');
        $this->assertModelMissing($t);
    }

    public function test_halaman_profil_teknisi_tidak_menampilkan_kelas_nis(): void
    {
        $this->actingAs($this->teknisi())->get('/profil')->assertOk()
            ->assertSee('Petugas Teknisi')->assertDontSee('Kelas / NIS');
    }

    // ---------------------------------------------------------------- penugasan oleh admin

    public function test_admin_menugaskan_teknisi_ke_pengaduan_yang_disetujui(): void
    {
        $admin = $this->admin();
        $t = $this->teknisi();
        $p = $this->pengaduan($this->siswa(), ['lokasi' => 'Ruang Alfa']);

        $this->actingAs($admin)->get("/admin/verifikasi?id={$p->id}")->assertOk()
            ->assertSee('Penugasan teknisi')->assertSee($t->name);

        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $t->id])
            ->assertRedirect()->assertSessionHas('success');

        $p->refresh();
        $this->assertSame($t->id, $p->teknisi_id);
        $this->assertSame('assigned', $p->status_penanganan);
        $this->assertSame($admin->id, $p->ditugaskan_oleh);
        $this->assertNotNull($p->ditugaskan_pada);

        // teknisi langsung melihatnya
        $this->actingAs($t)->get('/teknisi/tugas')->assertOk()->assertSee('Ruang Alfa');
    }

    public function test_penugasan_menolak_user_non_teknisi_dan_pengaduan_belum_disetujui(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswa();
        $t = $this->teknisi();

        $disetujui = $this->pengaduan($siswa);
        $pending = $this->pengaduan($siswa, ['status' => 'pending']);

        // target bukan teknisi (siswa / admin)
        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $disetujui), ['teknisi_id' => $siswa->id])->assertSessionHasErrors('teknisi_id');
        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $disetujui), ['teknisi_id' => $admin->id])->assertSessionHasErrors('teknisi_id');
        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $disetujui), [])->assertSessionHasErrors('teknisi_id');
        $this->assertNull($disetujui->fresh()->teknisi_id);

        // pengaduan belum disetujui
        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $pending), ['teknisi_id' => $t->id])->assertSessionHas('error');
        $this->assertNull($pending->fresh()->teknisi_id);
    }

    public function test_penugasan_ulang_hanya_sebelum_teknisi_bekerja(): void
    {
        $admin = $this->admin();
        $t1 = $this->teknisi('t1@sch.id');
        $t2 = $this->teknisi('t2@sch.id');
        $p = $this->tugas($this->siswa(), $t1, 'Ruang Alfa', 'assigned');

        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $t2->id])->assertSessionHas('success');
        $this->assertSame($t2->id, $p->fresh()->teknisi_id);

        $p->forceFill(['status_penanganan' => 'in_progress'])->save();
        $this->actingAs($admin)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $t1->id])->assertSessionHas('error');
        $this->assertSame($t2->id, $p->fresh()->teknisi_id);
    }

    public function test_pengaduan_yang_sudah_ditugaskan_tidak_bisa_ditolak_atau_dikembalikan_ke_pending(): void
    {
        $admin = $this->admin();
        $p = $this->tugas($this->siswa(), $this->teknisi(), 'Ruang Alfa');

        $this->actingAs($admin)->put(route('admin.verifikasi.update', $p), ['status' => 'rejected', 'tanggapan' => 'ditolak'])->assertSessionHas('error');
        $this->actingAs($admin)->put(route('admin.verifikasi.update', $p), ['status' => 'pending'])->assertSessionHas('error');
        $this->assertSame('approved', $p->fresh()->status);

        // tetap boleh memperbarui catatan selama status approved
        $this->actingAs($admin)->put(route('admin.verifikasi.update', $p), ['status' => 'approved', 'tanggapan' => 'catatan baru'])->assertSessionHas('success');
        $this->assertSame('catatan baru', $p->fresh()->tanggapan);
    }

    public function test_kolom_penugasan_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $siswa = $this->siswa();
        $t = $this->teknisi();
        $k = Kategori::create(['nama' => 'Air']);

        $this->actingAs($siswa)->post('/pengaduan', [
            'kategori_id' => $k->id, 'lokasi' => 'Toilet', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Keran bocor terus menerus.',
            'teknisi_id' => $t->id, 'status_penanganan' => 'completed', 'status' => 'approved',
        ]);

        $p = Pengaduan::firstOrFail();
        $this->assertNull($p->teknisi_id);
        $this->assertSame('not_assigned', $p->status_penanganan);
        $this->assertSame('pending', $p->status);
    }
}
