<?php

namespace Tests\Feature;

use App\Exceptions\TransisiTidakValid;
use App\Models\BuktiPerbaikan;
use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\PenugasanTeknisi;
use App\Models\RiwayatStatus;
use App\Models\Sarana;
use App\Models\User;
use App\Services\PengaduanWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 5 — integritas database: constraint (unique, foreign key, cascade/restrict/set null), nilai default,
 * atomisitas workflow, dan UJI MIGRASI DATA LAMA (backfill riwayat/penugasan tanpa kehilangan data).
 */
class IntegritasDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $siswa;
    private User $teknisi;
    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123']);
        $this->siswa = User::create(['name' => 'Siswa', 'email' => 'siswa@sch.id', 'nis' => '1001', 'role' => 'siswa', 'password' => 'password123']);
        $this->teknisi = User::create(['name' => 'Teknisi', 'email' => 'teknisi@sch.id', 'role' => 'teknisi', 'password' => 'password123']);
        $this->kategori = Kategori::create(['nama' => 'Listrik']);
    }

    private function pengaduan(array $o = []): Pengaduan
    {
        $p = new Pengaduan();
        $p->forceFill(array_merge([
            'user_id' => $this->siswa->id, 'kategori_id' => $this->kategori->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.', 'status' => 'approved',
        ], $o))->save();

        return $p->fresh();
    }

    /** Pengaduan lengkap dengan seluruh data turunan (riwayat, penugasan, bukti). */
    private function pengaduanLengkap(): Pengaduan
    {
        Storage::fake('public');
        $wf = app(PengaduanWorkflow::class);
        $p = $this->pengaduan();
        $wf->pengajuan($p, $this->siswa);
        $wf->tugaskan($p, $this->teknisi, $this->admin);
        $wf->terima($p, $this->teknisi);
        $wf->mulai($p, $this->teknisi);
        $wf->tambahBukti($p, $this->teknisi, 'sesudah', UploadedFile::fake()->image('a.jpg'), null);

        return $p->fresh();
    }

    private function galat(callable $aksi): bool
    {
        try {
            $aksi();
        } catch (QueryException $e) {
            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------ unique & default

    public function test_constraint_unique(): void
    {
        $this->assertTrue($this->galat(fn () => User::create(['name' => 'X', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123'])), 'email harus unik');
        $this->assertTrue($this->galat(fn () => User::create(['name' => 'X', 'email' => 'x@sch.id', 'nis' => '1001', 'role' => 'siswa', 'password' => 'password123'])), 'nis harus unik');
        $this->assertTrue($this->galat(fn () => Kategori::create(['nama' => 'Listrik'])), 'nama kategori harus unik');

        // NIS kosong (null) untuk banyak akun admin/teknisi tetap diperbolehkan
        User::create(['name' => 'T2', 'email' => 't2@sch.id', 'role' => 'teknisi', 'password' => 'password123']);
        $this->assertSame(2, User::whereNull('nis')->where('role', 'teknisi')->count());
    }

    public function test_nilai_default_kolom_workflow_pada_baris_baru(): void
    {
        DB::table('pengaduan')->insert([
            'user_id' => $this->siswa->id, 'kategori_id' => $this->kategori->id, 'lokasi' => 'A',
            'tanggal_kejadian' => '2026-10-01', 'deskripsi' => 'Deskripsi lama', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $baris = DB::table('pengaduan')->first();
        $this->assertSame('pending', $baris->status);
        $this->assertSame('not_assigned', $baris->status_penanganan);
        $this->assertSame(0, (int) $baris->progres);
        $this->assertNull($baris->teknisi_id);
        $this->assertNull($baris->selesai_pada);
    }

    // ------------------------------------------------------------------ foreign key

    public function test_hapus_pengaduan_menghapus_seluruh_data_turunannya(): void
    {
        $p = $this->pengaduanLengkap();
        $this->assertGreaterThan(0, RiwayatStatus::where('pengaduan_id', $p->id)->count());
        $this->assertSame(1, PenugasanTeknisi::where('pengaduan_id', $p->id)->count());
        $this->assertSame(1, BuktiPerbaikan::where('pengaduan_id', $p->id)->count());

        $p->delete();

        $this->assertSame(0, RiwayatStatus::count());
        $this->assertSame(0, PenugasanTeknisi::count());
        $this->assertSame(0, BuktiPerbaikan::count());
    }

    public function test_hapus_siswa_menghapus_pengaduan_dan_turunannya(): void
    {
        $this->pengaduanLengkap();

        $this->siswa->delete();

        $this->assertSame(0, Pengaduan::count());
        $this->assertSame(0, RiwayatStatus::count());
        $this->assertSame(0, BuktiPerbaikan::count());
    }

    public function test_hapus_teknisi_mengosongkan_referensi_tetapi_riwayat_tetap_ada(): void
    {
        $p = $this->pengaduanLengkap();
        $riwayat = RiwayatStatus::count();

        $this->teknisi->delete();

        $p->refresh();
        $this->assertNull($p->teknisi_id, 'teknisi_id harus menjadi NULL (nullOnDelete)');
        $this->assertSame($riwayat, RiwayatStatus::count(), 'Riwayat tidak boleh ikut terhapus');
        $this->assertSame(0, RiwayatStatus::where('user_id', $this->teknisi->id)->count());
        $this->assertNull(PenugasanTeknisi::first()->teknisi_id);
        $this->assertNull(BuktiPerbaikan::first()->user_id);
    }

    public function test_hapus_admin_tidak_menghapus_pengaduan_hanya_mengosongkan_pemverifikasi(): void
    {
        $p = $this->pengaduan(['diverifikasi_oleh' => $this->admin->id, 'diverifikasi_pada' => now(), 'ditugaskan_oleh' => $this->admin->id]);

        $this->admin->delete();

        $p->refresh();
        $this->assertNull($p->diverifikasi_oleh);
        $this->assertNull($p->ditugaskan_oleh);
        $this->assertSame(1, Pengaduan::count());
    }

    public function test_kategori_yang_dipakai_tidak_bisa_dihapus_dan_sarana_menjadi_null(): void
    {
        $sarana = Sarana::create(['nama' => 'Proyektor', 'lokasi' => 'Lab']);
        $p = $this->pengaduan(['sarana_id' => $sarana->id]);

        $this->assertTrue($this->galat(fn () => $this->kategori->delete()), 'restrictOnDelete pada kategori');
        $this->assertSame(1, Kategori::count());

        $sarana->delete();
        $this->assertNull($p->fresh()->sarana_id, 'sarana_id harus menjadi NULL (nullOnDelete)');
        $this->assertSame(1, Pengaduan::count());
    }

    public function test_foreign_key_menolak_referensi_yang_tidak_ada(): void
    {
        $this->assertTrue($this->galat(fn () => $this->pengaduan(['user_id' => 99999])), 'user_id fiktif harus ditolak');
        $this->assertTrue($this->galat(fn () => $this->pengaduan(['kategori_id' => 99999])), 'kategori_id fiktif harus ditolak');
        $this->assertTrue($this->galat(fn () => $this->pengaduan(['teknisi_id' => 99999])), 'teknisi_id fiktif harus ditolak');
    }

    // ------------------------------------------------------------------ atomisitas workflow

    public function test_transisi_gagal_tidak_meninggalkan_jejak_di_database(): void
    {
        $p = $this->pengaduan();
        $wf = app(PengaduanWorkflow::class);

        $sebelum = [RiwayatStatus::count(), PenugasanTeknisi::count(), BuktiPerbaikan::count(), $p->fresh()->status_penanganan];

        foreach ([
            fn () => $wf->terima($p, $this->teknisi),
            fn () => $wf->validasi($p, $this->admin, null),
            fn () => $wf->tugaskan($p, $this->siswa, $this->admin),
            fn () => $wf->mintaRevisi($p, $this->admin, 'x'),
        ] as $aksi) {
            try {
                $aksi();
            } catch (TransisiTidakValid|\Illuminate\Auth\Access\AuthorizationException $e) {
                // diharapkan
            }
        }

        $sesudah = [RiwayatStatus::count(), PenugasanTeknisi::count(), BuktiPerbaikan::count(), $p->fresh()->status_penanganan];
        $this->assertSame($sebelum, $sesudah);
    }

    public function test_bukti_di_atas_batas_tidak_meninggalkan_file_yatim(): void
    {
        Storage::fake('public');
        $wf = app(PengaduanWorkflow::class);
        $p = $this->pengaduan();
        $wf->tugaskan($p, $this->teknisi, $this->admin);
        $wf->terima($p, $this->teknisi);

        for ($i = 0; $i < PengaduanWorkflow::MAKS_BUKTI; $i++) {
            $wf->tambahBukti($p, $this->teknisi, 'sebelum', UploadedFile::fake()->image("f{$i}.jpg"), null);
        }

        try {
            $wf->tambahBukti($p, $this->teknisi, 'sesudah', UploadedFile::fake()->image('lebih.jpg'), null);
            $this->fail('Seharusnya ditolak karena melebihi batas');
        } catch (TransisiTidakValid $e) {
            $this->assertSame(PengaduanWorkflow::MAKS_BUKTI, BuktiPerbaikan::count());
            $this->assertCount(PengaduanWorkflow::MAKS_BUKTI, Storage::disk('public')->allFiles('bukti'));
        }
    }

    // ------------------------------------------------------------------ MIGRASI DATA LAMA

    public function test_migrasi_fase3_mengisi_riwayat_dari_data_lama_tanpa_kehilangan_data(): void
    {
        $migrasi = require database_path('migrations/2026_11_05_000001_create_workflow_penanganan_tables.php');

        // Kembali ke kondisi FASE 2 (tanpa tabel/kolom FASE 3), lalu isi data "lama"
        $migrasi->down();
        $this->assertFalse(Schema::hasTable('riwayat_status'));
        $this->assertFalse(Schema::hasColumn('pengaduan', 'progres'));

        $dasar = ['user_id' => $this->siswa->id, 'kategori_id' => $this->kategori->id, 'lokasi' => 'Lama', 'tanggal_kejadian' => '2026-10-01', 'deskripsi' => 'Data lama sebelum FASE 3'];
        // Satu per satu: insert massal Laravel mewajibkan semua baris memiliki kolom yang sama persis
        DB::table('pengaduan')->insert(array_merge($dasar, [
            'id' => 101, 'status' => 'pending', 'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00',
        ]));
        DB::table('pengaduan')->insert(array_merge($dasar, [
            'id' => 102, 'status' => 'approved', 'tanggapan' => 'Disetujui', 'diverifikasi_oleh' => $this->admin->id,
            'diverifikasi_pada' => '2026-10-02 09:00:00', 'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-02 09:00:00',
        ]));
        DB::table('pengaduan')->insert(array_merge($dasar, [
            'id' => 103, 'status' => 'approved', 'tanggapan' => 'OK', 'diverifikasi_oleh' => $this->admin->id,
            'diverifikasi_pada' => '2026-10-02 09:00:00', 'teknisi_id' => $this->teknisi->id, 'status_penanganan' => 'in_progress',
            'ditugaskan_pada' => '2026-10-03 10:00:00', 'ditugaskan_oleh' => $this->admin->id,
            'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-05 12:00:00',
        ]));

        $migrasi->up();

        // data lama utuh
        $this->assertSame(3, DB::table('pengaduan')->count());
        $this->assertSame('in_progress', DB::table('pengaduan')->where('id', 103)->value('status_penanganan'));
        $this->assertSame('Data lama sebelum FASE 3', DB::table('pengaduan')->where('id', 101)->value('deskripsi'));
        $this->assertSame(0, (int) DB::table('pengaduan')->where('id', 103)->value('progres'));

        // riwayat hasil backfill
        $jenis = fn (int $id) => DB::table('riwayat_status')->where('pengaduan_id', $id)->orderBy('id')->pluck('jenis')->all();
        $this->assertSame(['pengajuan'], $jenis(101));
        $this->assertSame(['pengajuan', 'verifikasi'], $jenis(102));
        $this->assertSame(['pengajuan', 'verifikasi', 'penanganan', 'penanganan'], $jenis(103));

        $verifikasi = DB::table('riwayat_status')->where('pengaduan_id', 102)->where('jenis', 'verifikasi')->first();
        $this->assertSame('approved', $verifikasi->ke);
        $this->assertSame('Disetujui', $verifikasi->catatan);

        $penanganan = DB::table('riwayat_status')->where('pengaduan_id', 103)->where('jenis', 'penanganan')->orderBy('id')->pluck('ke')->all();
        $this->assertSame(['assigned', 'in_progress'], $penanganan);

        // penugasan aktif untuk yang sudah ditugaskan saja
        $this->assertSame(1, DB::table('penugasan_teknisi')->count());
        $tugas = DB::table('penugasan_teknisi')->first();
        $this->assertSame(103, (int) $tugas->pengaduan_id);
        $this->assertSame($this->teknisi->id, (int) $tugas->teknisi_id);
        $this->assertSame(1, (int) $tugas->aktif);

        // menjalankan ulang migrasi tidak menggandakan data (idempotent)
        $totalRiwayat = DB::table('riwayat_status')->count();
        $migrasi->up();
        $this->assertSame($totalRiwayat, DB::table('riwayat_status')->count());
        $this->assertSame(1, DB::table('penugasan_teknisi')->count());
        $this->assertSame(3, DB::table('pengaduan')->count());
    }
}
