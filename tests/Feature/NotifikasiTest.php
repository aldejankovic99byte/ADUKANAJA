<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\User;
use App\Services\PengaduanWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 6 — notifikasi in-app: penerima yang tepat untuk setiap peristiwa workflow, lonceng di layout,
 * tandai dibaca, pembatasan akses (hanya milik sendiri), dan keamanan tautan.
 */
class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private PengaduanWorkflow $wf;
    private User $admin;
    private User $siswa;
    private User $teknisi;
    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->wf = app(PengaduanWorkflow::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123']);
        $this->siswa = User::create(['name' => 'Siswa', 'email' => 'siswa@sch.id', 'nis' => '1001', 'role' => 'siswa', 'password' => 'password123']);
        $this->teknisi = User::create(['name' => 'Teknisi', 'email' => 'teknisi@sch.id', 'role' => 'teknisi', 'password' => 'password123']);
        $this->kategori = Kategori::create(['nama' => 'Listrik']);
    }

    private function pengaduan(string $status = 'pending'): Pengaduan
    {
        $p = new Pengaduan();
        $p->forceFill([
            'user_id' => $this->siswa->id, 'kategori_id' => $this->kategori->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.', 'status' => $status,
        ])->save();

        return $p->fresh();
    }

    private function jumlah(User $u, ?string $jenis = null): int
    {
        return Notifikasi::where('user_id', $u->id)->when($jenis, fn ($q, $j) => $q->where('jenis', $j))->count();
    }

    // ------------------------------------------------------------------ penerima per peristiwa

    public function test_pengajuan_memberi_tahu_admin_bukan_pelapor(): void
    {
        $p = $this->pengaduan();
        $this->wf->pengajuan($p, $this->siswa);

        $this->assertSame(1, $this->jumlah($this->admin, 'pengaduan_baru'));
        $this->assertSame(0, $this->jumlah($this->siswa));
        $n = Notifikasi::where('user_id', $this->admin->id)->firstOrFail();
        $this->assertSame("/admin/verifikasi?id={$p->id}", $n->url);
        $this->assertSame($p->id, $n->pengaduan_id);
        $this->assertNull($n->dibaca_pada);
    }

    public function test_verifikasi_memberi_tahu_siswa_dan_tidak_menggandakan(): void
    {
        $p = $this->pengaduan();

        $this->wf->verifikasi($p, $this->admin, 'approved', 'ok');
        $this->wf->verifikasi($p, $this->admin, 'approved', 'catatan lain'); // status sama -> tidak ada notifikasi baru
        $this->assertSame(1, $this->jumlah($this->siswa, 'verifikasi'));
        $this->assertSame(0, $this->jumlah($this->admin), 'pelaku tidak menerima notifikasi tindakannya sendiri');

        $q = $this->pengaduan();
        $this->wf->verifikasi($q, $this->admin, 'rejected', 'Duplikat laporan');
        $this->assertSame(2, $this->jumlah($this->siswa, 'verifikasi'));
        $this->assertStringContainsString('Duplikat laporan', Notifikasi::where('user_id', $this->siswa->id)->latest('id')->first()->pesan);
        $this->assertSame("/pengaduan/{$q->id}", Notifikasi::where('user_id', $this->siswa->id)->latest('id')->first()->url);

        // pending tidak memicu notifikasi
        $r = $this->pengaduan('approved');
        $this->wf->verifikasi($r, $this->admin, 'pending', null);
        $this->assertSame(2, $this->jumlah($this->siswa, 'verifikasi'));
    }

    public function test_seluruh_alur_menghasilkan_notifikasi_untuk_penerima_yang_tepat(): void
    {
        $p = $this->pengaduan('approved');

        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $this->assertSame(1, $this->jumlah($this->teknisi, 'tugas_baru'));
        $this->assertSame(1, $this->jumlah($this->siswa, 'penugasan'));
        $this->assertSame("/teknisi/tugas/{$p->id}", Notifikasi::where('user_id', $this->teknisi->id)->first()->url);

        $this->wf->terima($p, $this->teknisi);
        $this->assertSame(0, $this->jumlah($this->siswa, 'perbaikan'), 'menerima tugas tidak perlu diberitahukan');

        $this->wf->mulai($p, $this->teknisi);
        $this->assertSame(1, $this->jumlah($this->siswa, 'perbaikan'));

        $this->wf->perbaruiProgres($p, $this->teknisi, 40, 'catatan');
        $this->assertSame(1, $this->jumlah($this->siswa, 'perbaikan'), 'update progres tidak membanjiri notifikasi');

        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', UploadedFile::fake()->image('a.jpg'), null);
        $this->wf->ajukanPenyelesaian($p, $this->teknisi, null);
        $this->assertSame(1, $this->jumlah($this->admin, 'menunggu_validasi'));

        $this->wf->mintaRevisi($p, $this->admin, 'Kabel masih berantakan');
        $this->assertSame(1, $this->jumlah($this->teknisi, 'revisi'));
        $this->assertSame(0, $this->jumlah($this->siswa, 'revisi'), 'revisi bersifat internal admin-teknisi');

        $this->wf->mulai($p, $this->teknisi);
        $this->assertSame(1, $this->jumlah($this->siswa, 'perbaikan'), 'mulai revisi tidak diumumkan ke siswa lagi');
        $this->travel(3)->seconds();
        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', UploadedFile::fake()->image('b.jpg'), null);
        $this->wf->ajukanPenyelesaian($p, $this->teknisi, null);
        $this->wf->validasi($p, $this->admin, null);

        $this->assertSame(1, $this->jumlah($this->siswa, 'selesai'));
        $this->assertSame(1, $this->jumlah($this->teknisi, 'selesai'));
        $this->assertSame(2, $this->jumlah($this->admin, 'menunggu_validasi'));
    }

    public function test_notifikasi_ikut_hilang_bila_transaksi_gagal(): void
    {
        $p = $this->pengaduan('approved');
        $sebelum = Notifikasi::count();

        try {
            $this->wf->tugaskan($p, $this->siswa, $this->admin); // bukan teknisi -> ditolak
        } catch (\Throwable $e) {
            // diharapkan
        }

        $this->assertSame($sebelum, Notifikasi::count());
    }

    // ------------------------------------------------------------------ tampilan lonceng & halaman

    public function test_lonceng_menampilkan_jumlah_belum_dibaca_dan_isi_terbaru(): void
    {
        foreach ([1, 2, 3] as $i) {
            Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'verifikasi', 'judul' => "Judul uji {$i}", 'pesan' => "Pesan {$i}", 'url' => '/beranda']);
        }
        Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'Sudah dibaca', 'dibaca_pada' => now()]);

        $this->actingAs($this->siswa)->get('/beranda')->assertOk()
            ->assertSee('notif-badge">3<', false)
            ->assertSee('Judul uji 3')->assertSee('Pesan 3')->assertSee('Lihat semua notifikasi');

        // admin tidak melihat notifikasi milik siswa
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()
            ->assertDontSee('Judul uji 3')->assertDontSee('notif-badge', false);
    }

    public function test_buka_notifikasi_menandai_dibaca_dan_mengarahkan(): void
    {
        $n = Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'Uji', 'url' => '/riwayat']);

        $this->actingAs($this->siswa)->get(route('notifikasi.buka', $n->id))->assertRedirect('/riwayat');
        $this->assertNotNull($n->fresh()->dibaca_pada);
    }

    public function test_notifikasi_milik_orang_lain_tidak_dapat_dibuka(): void
    {
        $milikSiswa = Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'Rahasia siswa', 'url' => '/riwayat']);

        $this->actingAs($this->teknisi)->get(route('notifikasi.buka', $milikSiswa->id))->assertNotFound();
        $this->actingAs($this->admin)->get(route('notifikasi.buka', $milikSiswa->id))->assertNotFound();
        $this->assertNull($milikSiswa->fresh()->dibaca_pada, 'tidak boleh ditandai dibaca oleh orang lain');
    }

    public function test_tautan_eksternal_tidak_diikuti(): void
    {
        foreach (['https://jahat.example/phish', '//jahat.example', 'javascript:alert(1)', null] as $url) {
            $n = Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'Uji', 'url' => $url]);
            $this->actingAs($this->siswa)->get(route('notifikasi.buka', $n->id))->assertRedirect(route('notifikasi.index'));
        }
    }

    public function test_tandai_semua_dibaca_hanya_untuk_milik_sendiri(): void
    {
        Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'A']);
        Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => 'B']);
        Notifikasi::create(['user_id' => $this->admin->id, 'jenis' => 'x', 'judul' => 'C']);

        $this->actingAs($this->siswa)->post(route('notifikasi.baca-semua'))->assertRedirect();

        $this->assertSame(0, Notifikasi::where('user_id', $this->siswa->id)->whereNull('dibaca_pada')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $this->admin->id)->whereNull('dibaca_pada')->count());
    }

    public function test_halaman_notifikasi_daftar_pagination_dan_escape(): void
    {
        foreach (range(1, 16) as $i) {
            Notifikasi::create(['user_id' => $this->siswa->id, 'jenis' => 'x', 'judul' => sprintf('Judul %02d', $i), 'pesan' => '<script>alert("x")</script>']);
        }

        $this->actingAs($this->siswa)->get('/notifikasi')->assertOk()
            ->assertSee('Halaman 1 dari 2')->assertSee('Judul 16')->assertDontSee('Judul 01')
            ->assertDontSee('<script>alert("x")</script>', false)->assertSee('&lt;script&gt;', false);

        $this->actingAs($this->siswa)->get('/notifikasi?page=2')->assertOk()->assertSee('Judul 01');
    }

    public function test_halaman_notifikasi_kosong(): void
    {
        $this->actingAs($this->teknisi)->get('/notifikasi')->assertOk()->assertSee('Belum ada notifikasi');
    }

    public function test_notifikasi_dihapus_bersama_pengaduan(): void
    {
        $p = $this->pengaduan();
        $this->wf->pengajuan($p, $this->siswa);
        $this->assertGreaterThan(0, Notifikasi::count());

        $p->delete();

        $this->assertSame(0, Notifikasi::count());
    }
}
