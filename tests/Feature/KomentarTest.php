<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\KomentarPengaduan;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 6 — komentar pada pengaduan: siapa boleh berkomentar, validasi, notifikasi ke pihak lain, tampilan & escape.
 */
class KomentarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $siswa;
    private User $siswaLain;
    private User $teknisi;
    private User $teknisiLain;
    private Pengaduan $p;

    protected function setUp(): void
    {
        parent::setUp();
        $mk = fn (string $role, string $email, ?string $nis = null) => User::create(['name' => ucfirst($role).' '.$email, 'email' => $email, 'nis' => $nis, 'role' => $role, 'password' => 'password123']);
        $this->admin = $mk('admin', 'admin@sch.id');
        $this->siswa = $mk('siswa', 's1@sch.id', '1001');
        $this->siswaLain = $mk('siswa', 's2@sch.id', '1002');
        $this->teknisi = $mk('teknisi', 't1@sch.id');
        $this->teknisiLain = $mk('teknisi', 't2@sch.id');

        $k = Kategori::create(['nama' => 'Listrik']);
        $this->p = new Pengaduan();
        $this->p->forceFill([
            'user_id' => $this->siswa->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.', 'status' => 'approved',
            'teknisi_id' => $this->teknisi->id, 'status_penanganan' => 'assigned', 'ditugaskan_pada' => now(),
        ])->save();
    }

    private function kirim(User $u, string $isi = 'Halo, ada kabar terbaru?')
    {
        return $this->actingAs($u)->post(route('komentar.store', $this->p->id), ['isi' => $isi]);
    }

    public function test_pihak_yang_terlibat_boleh_berkomentar_dan_diarahkan_ke_halamannya(): void
    {
        $this->kirim($this->siswa)->assertRedirect(route('pengaduan.show', $this->p->id).'#komentar')->assertSessionHas('success');
        $this->kirim($this->teknisi)->assertRedirect(route('teknisi.tugas.show', $this->p->id).'#komentar');
        $this->kirim($this->admin)->assertRedirect(route('admin.verifikasi', ['id' => $this->p->id]).'#komentar');

        $this->assertSame(3, KomentarPengaduan::where('pengaduan_id', $this->p->id)->count());
        $this->assertSame($this->siswa->id, KomentarPengaduan::orderBy('id')->first()->user_id);
    }

    public function test_pihak_lain_tidak_boleh_berkomentar(): void
    {
        $this->kirim($this->siswaLain)->assertForbidden();
        $this->kirim($this->teknisiLain)->assertForbidden();
        $this->assertSame(0, KomentarPengaduan::count());

        // guest diarahkan ke login
        auth()->logout();
        $this->post(route('komentar.store', $this->p->id), ['isi' => 'x kosong'])->assertRedirect('/login');
    }

    public function test_teknisi_yang_belum_ditugaskan_tidak_boleh_berkomentar(): void
    {
        $this->p->forceFill(['teknisi_id' => null, 'status_penanganan' => 'not_assigned'])->save();

        $this->kirim($this->teknisi)->assertForbidden();
        $this->kirim($this->admin)->assertRedirect(); // admin tetap boleh
    }

    public function test_validasi_komentar(): void
    {
        foreach ([[''], ['x'], [str_repeat('a', 1001)]] as [$isi]) {
            $this->kirim($this->siswa, $isi)->assertSessionHasErrors('isi');
        }
        $this->assertSame(0, KomentarPengaduan::count());

        $this->kirim($this->siswa, str_repeat('a', 1000))->assertSessionHasNoErrors();
        $this->assertSame(1, KomentarPengaduan::count());
    }

    public function test_komentar_memberi_tahu_pihak_lain_bukan_penulisnya(): void
    {
        $this->kirim($this->siswa, 'Mohon dipercepat ya');

        $this->assertSame(1, Notifikasi::where('user_id', $this->admin->id)->where('jenis', 'komentar')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $this->teknisi->id)->where('jenis', 'komentar')->count());
        $this->assertSame(0, Notifikasi::where('user_id', $this->siswa->id)->count(), 'penulis tidak diberi tahu');
        $this->assertSame(0, Notifikasi::where('user_id', $this->siswaLain->id)->count());
        $this->assertSame(0, Notifikasi::where('user_id', $this->teknisiLain->id)->count());

        $n = Notifikasi::where('user_id', $this->teknisi->id)->first();
        $this->assertStringContainsString('Mohon dipercepat ya', $n->pesan);
        $this->assertSame("/teknisi/tugas/{$this->p->id}", $n->url);
    }

    public function test_komentar_tampil_di_halaman_ketiga_role_dengan_escape(): void
    {
        KomentarPengaduan::create(['pengaduan_id' => $this->p->id, 'user_id' => $this->siswa->id, 'isi' => 'Komentar aman dari siswa']);
        KomentarPengaduan::create(['pengaduan_id' => $this->p->id, 'user_id' => $this->teknisi->id, 'isi' => "<script>alert('xss')</script>"]);

        $this->actingAs($this->siswa)->get(route('pengaduan.show', $this->p->id))->assertOk()
            ->assertSee('Komentar (2)')->assertSee('Komentar aman dari siswa')->assertSee('Petugas Teknisi')
            ->assertDontSee("<script>alert('xss')</script>", false)->assertSee('&lt;script&gt;', false);
        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $this->p->id))->assertOk()
            ->assertSee('Komentar aman dari siswa')->assertSee('Kirim komentar');
        $this->actingAs($this->admin)->get(route('admin.verifikasi', ['id' => $this->p->id]))->assertOk()
            ->assertSee('Komentar aman dari siswa')->assertSee('Kirim komentar');
    }

    public function test_komentar_dari_pengguna_yang_dihapus_tetap_tampil(): void
    {
        KomentarPengaduan::create(['pengaduan_id' => $this->p->id, 'user_id' => $this->teknisi->id, 'isi' => 'Pesan lama']);
        $this->teknisi->delete();

        $this->actingAs($this->siswa)->get(route('pengaduan.show', $this->p->id))->assertOk()
            ->assertSee('Pesan lama')->assertSee('Pengguna dihapus');
    }

    public function test_pembatasan_laju_komentar(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->kirim($this->siswa, "Komentar nomor {$i}")->assertRedirect();
        }

        $this->kirim($this->siswa, 'Komentar ke-31')->assertStatus(429);
        $this->assertSame(30, KomentarPengaduan::count());
    }

    public function test_komentar_dihapus_bersama_pengaduan(): void
    {
        $this->kirim($this->siswa);
        $this->p->delete();

        $this->assertSame(0, KomentarPengaduan::count());
    }
}
