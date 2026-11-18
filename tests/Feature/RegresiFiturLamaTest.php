<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Sarana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 5 — regresi fitur lama (FASE 0): laporan (filter, ringkasan, export Excel, cetak), pagination, pencarian,
 * dan CRUD sarana. Memastikan seluruh perubahan FASE 1-4 tidak merusak fitur bawaan.
 */
class RegresiFiturLamaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $siswa;
    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123']);
        $this->siswa = User::create(['name' => 'Siswa Satu', 'email' => 'siswa@sch.id', 'nis' => '1001', 'kelas' => 'XII', 'role' => 'siswa', 'password' => 'password123']);
        $this->kategori = Kategori::create(['nama' => 'Listrik']);
    }

    private function pengaduan(string $lokasi, string $status, string $dibuat, array $o = []): Pengaduan
    {
        $p = new Pengaduan();
        $p->forceFill(array_merge([
            'user_id' => $this->siswa->id, 'kategori_id' => $this->kategori->id, 'lokasi' => $lokasi,
            'tanggal_kejadian' => '2026-09-01', 'deskripsi' => 'Deskripsi pengaduan '.$lokasi, 'status' => $status,
            'created_at' => $dibuat, 'updated_at' => $dibuat,
        ], $o))->save();

        return $p;
    }

    /** Tiga pengaduan pada bulan berbeda dengan status berbeda. */
    private function tigaPengaduan(): void
    {
        $this->pengaduan('Ruang Alfa', 'approved', '2026-09-10 08:00:00');
        $this->pengaduan('Ruang Beta', 'pending', '2026-10-10 08:00:00');
        $this->pengaduan('Ruang Gamma', 'rejected', '2026-11-10 08:00:00', ['tanggapan' => 'Duplikat']);
    }

    // ------------------------------------------------------------------ laporan

    public function test_laporan_filter_status(): void
    {
        $this->tigaPengaduan();

        $this->actingAs($this->admin)->get('/admin/laporan?status=approved')->assertOk()
            ->assertSee('Ruang Alfa')->assertDontSee('Ruang Beta')->assertDontSee('Ruang Gamma');
        $this->actingAs($this->admin)->get('/admin/laporan?status=pending')->assertOk()
            ->assertSee('Ruang Beta')->assertDontSee('Ruang Alfa');
        // status tidak dikenal diabaikan -> semua tampil
        $this->actingAs($this->admin)->get('/admin/laporan?status=ngawur')->assertOk()
            ->assertSee('Ruang Alfa')->assertSee('Ruang Beta')->assertSee('Ruang Gamma');
    }

    public function test_laporan_filter_rentang_tanggal(): void
    {
        $this->tigaPengaduan();

        $this->actingAs($this->admin)->get('/admin/laporan?dari=2026-10-01&sampai=2026-10-31')->assertOk()
            ->assertSee('Ruang Beta')->assertDontSee('Ruang Alfa')->assertDontSee('Ruang Gamma');
        $this->actingAs($this->admin)->get('/admin/laporan?dari=2026-10-15')->assertOk()
            ->assertSee('Ruang Gamma')->assertDontSee('Ruang Beta');
        // tanggal tidak valid diabaikan, tidak error
        $this->actingAs($this->admin)->get('/admin/laporan?dari=bukan&sampai=2026-99-99')->assertOk()
            ->assertSee('Ruang Alfa')->assertSee('Ruang Gamma');
    }

    public function test_laporan_ringkasan_menghitung_per_status(): void
    {
        $this->tigaPengaduan();

        $this->actingAs($this->admin)->get('/admin/laporan')->assertOk()
            ->assertViewHas('ringkasan', fn ($r) => $r['total'] === 3 && $r['pending'] === 1 && $r['approved'] === 1 && $r['rejected'] === 1);
    }

    public function test_export_excel_menghasilkan_xlsx_valid_sesuai_filter(): void
    {
        $this->tigaPengaduan();
        $this->pengaduan('=HYPERLINK("http://jahat.example","klik")', 'approved', '2026-09-11 08:00:00');

        $res = $this->actingAs($this->admin)->get('/admin/laporan/excel?status=approved');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', (string) $res->headers->get('Content-Disposition'));

        $path = $res->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr(file_get_contents($path), 0, 2), 'File xlsx harus berupa arsip zip');

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Ruang Alfa', $sheet);
        $this->assertStringNotContainsString('Ruang Beta', $sheet, 'Filter status harus diterapkan pada export');
        $this->assertStringNotContainsString('Ruang Gamma', $sheet);
        // isi yang diawali "=" harus tersimpan sebagai teks, bukan rumus (anti CSV/Excel injection)
        $this->assertStringNotContainsString('<f>', $sheet);
    }

    public function test_halaman_cetak_laporan(): void
    {
        $this->tigaPengaduan();

        $this->actingAs($this->admin)->get('/admin/laporan/cetak')->assertOk()
            ->assertSee('Ruang Alfa')->assertSee('Ruang Beta')->assertSee('Ruang Gamma');
        $this->actingAs($this->admin)->get('/admin/laporan/cetak?status=rejected')->assertOk()
            ->assertSee('Ruang Gamma')->assertDontSee('Ruang Alfa');
    }

    // ------------------------------------------------------------------ pagination & pencarian

    public function test_pagination_riwayat_siswa(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->pengaduan(sprintf('Lokasi %02d', $i), 'pending', now()->subMinutes(30 - $i)->toDateTimeString());
        }

        $hal1 = $this->actingAs($this->siswa)->get('/riwayat')->assertOk();
        $hal1->assertSee('Halaman 1 dari 2')->assertSee('Lokasi 12')->assertDontSee('Lokasi 01');

        $this->actingAs($this->siswa)->get('/riwayat?page=2')->assertOk()
            ->assertSee('Halaman 2 dari 2')->assertSee('Lokasi 01')->assertDontSee('Lokasi 12');
    }

    public function test_riwayat_siswa_filter_status(): void
    {
        $this->pengaduan('Ruang Alfa', 'approved', now()->toDateTimeString());
        $this->pengaduan('Ruang Beta', 'pending', now()->toDateTimeString());

        $this->actingAs($this->siswa)->get('/riwayat?status=approved')->assertOk()
            ->assertSee('Ruang Alfa')->assertDontSee('Ruang Beta');
        $this->actingAs($this->siswa)->get('/riwayat?status[]=x')->assertOk(); // parameter aneh tidak membuat error
    }

    public function test_pencarian_pengguna_dan_pagination(): void
    {
        foreach (range(1, 12) as $i) {
            User::create(['name' => sprintf('Murid %02d', $i), 'email' => "murid{$i}@sch.id", 'nis' => (string) (5000 + $i), 'role' => 'siswa', 'password' => 'password123']);
        }

        $this->actingAs($this->admin)->get('/admin/pengguna')->assertOk()->assertSee('Halaman 1 dari');
        $this->actingAs($this->admin)->get('/admin/pengguna?q=murid7@sch.id')->assertOk()
            ->assertSee('Murid 07')->assertDontSee('Murid 08');
        $this->actingAs($this->admin)->get('/admin/pengguna?q=5003')->assertOk()->assertSee('Murid 03');
        $this->actingAs($this->admin)->get('/admin/pengguna?q[]=x')->assertOk();
    }

    // ------------------------------------------------------------------ sarana

    public function test_crud_sarana_dan_efek_hapus_pada_pengaduan(): void
    {
        $this->actingAs($this->admin)->post('/admin/sarana', ['nama' => 'Proyektor', 'lokasi' => 'Lab 1'])->assertSessionHasNoErrors();
        $sarana = Sarana::firstOrFail();

        $this->actingAs($this->admin)->get('/admin/sarana')->assertOk()->assertSee('Proyektor');

        $this->actingAs($this->admin)->put("/admin/sarana/{$sarana->id}", ['nama' => 'Proyektor Epson', 'lokasi' => 'Lab 2'])->assertSessionHasNoErrors();
        $this->assertSame('Proyektor Epson', $sarana->fresh()->nama);

        $p = $this->pengaduan('Lab 2', 'approved', now()->toDateTimeString(), ['sarana_id' => $sarana->id]);

        $this->actingAs($this->admin)->delete("/admin/sarana/{$sarana->id}")->assertRedirect();
        $this->assertModelMissing($sarana);
        $this->assertNull($p->fresh()->sarana_id, 'Pengaduan tetap ada, hanya referensi sarana yang dikosongkan');
    }
}
