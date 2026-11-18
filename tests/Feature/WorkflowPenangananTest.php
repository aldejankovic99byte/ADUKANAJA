<?php

namespace Tests\Feature;

use App\Exceptions\TransisiTidakValid;
use App\Models\BuktiPerbaikan;
use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use App\Services\PengaduanWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 3 — workflow: pengajuan -> verifikasi -> penugasan -> terima -> mulai -> progres/bukti -> ajukan -> validasi/revisi.
 * Setiap transisi diuji (valid & tidak valid), termasuk otorisasi, syarat bukti, dan riwayat status.
 */
class WorkflowPenangananTest extends TestCase
{
    use RefreshDatabase;

    private PengaduanWorkflow $wf;
    private User $admin;
    private User $siswa;
    private User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->wf = app(PengaduanWorkflow::class);

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@sch.id', 'role' => 'admin', 'password' => 'password123']);
        $this->siswa = User::create(['name' => 'Siswa', 'email' => 'siswa@sch.id', 'nis' => '1001', 'kelas' => 'XII', 'role' => 'siswa', 'password' => 'password123']);
        $this->teknisi = User::create(['name' => 'Teknisi', 'email' => 'teknisi@sch.id', 'role' => 'teknisi', 'password' => 'password123']);
    }

    // ------------------------------------------------------------------ helper

    private function pengaduan(string $status = 'approved'): Pengaduan
    {
        $k = Kategori::firstOrCreate(['nama' => 'Listrik']);
        $p = new Pengaduan();
        $p->forceFill([
            'user_id' => $this->siswa->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.', 'status' => $status,
        ])->save();

        return $p->fresh();
    }

    private function foto(string $nama = 'a.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 600, 400);
    }

    /** Majukan pengaduan lewat service sampai tahap tertentu. */
    private function sampai(string $tahap): Pengaduan
    {
        $p = $this->pengaduan();
        $urut = ['assigned', 'accepted', 'in_progress', 'waiting_confirmation', 'completed'];

        foreach ($urut as $t) {
            match ($t) {
                'assigned' => $this->wf->tugaskan($p, $this->teknisi, $this->admin),
                'accepted' => $this->wf->terima($p, $this->teknisi),
                'in_progress' => $this->wf->mulai($p, $this->teknisi),
                'waiting_confirmation' => $this->ajukanDenganBukti($p),
                'completed' => $this->wf->validasi($p, $this->admin, null),
            };
            if ($t === $tahap) {
                break;
            }
        }

        return $p->fresh();
    }

    private function ajukanDenganBukti(Pengaduan $p): void
    {
        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto(), null);
        $this->wf->ajukanPenyelesaian($p, $this->teknisi, 'Sudah diperbaiki');
    }

    private function urutanStatus(Pengaduan $p): array
    {
        return $p->riwayat()->whereIn('jenis', ['pengajuan', 'verifikasi', 'penanganan'])->pluck('ke')->all();
    }

    // ------------------------------------------------------------------ alur lengkap lewat HTTP

    public function test_alur_lengkap_dari_pengajuan_sampai_validasi_admin(): void
    {
        $k = Kategori::create(['nama' => 'Listrik']);

        // 1. siswa mengajukan
        $this->actingAs($this->siswa)->post('/pengaduan', [
            'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Stop kontak korslet dan berbau gosong.',
        ])->assertSessionHasNoErrors();
        $p = Pengaduan::firstOrFail();
        $this->assertSame('pending', $p->status);
        $this->assertSame('not_assigned', $p->status_penanganan);
        $this->assertSame(['pending'], $this->urutanStatus($p));

        // 2. admin menyetujui
        $this->actingAs($this->admin)->put(route('admin.verifikasi.update', $p), ['status' => 'approved', 'tanggapan' => 'OK'])->assertSessionHas('success');
        // 3. admin menugaskan
        $this->actingAs($this->admin)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $this->teknisi->id])->assertSessionHas('success');
        $this->assertSame('assigned', $p->fresh()->status_penanganan);

        // 4-6. teknisi menerima, mulai, update progres
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.terima', $p->id))->assertSessionHas('success');
        $this->assertSame('accepted', $p->fresh()->status_penanganan);
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.mulai', $p->id))->assertSessionHas('success');
        $this->assertSame('in_progress', $p->fresh()->status_penanganan);
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.progres', $p->id), ['progres' => 40, 'catatan' => 'Kabel diganti'])->assertSessionHas('success');
        $this->assertSame(40, (int) $p->fresh()->progres);

        // 7. bukti sebelum & sesudah
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.bukti', $p->id), ['jenis' => 'sebelum', 'foto' => $this->foto('s.jpg')])->assertSessionHas('success');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.bukti', $p->id), ['jenis' => 'sesudah', 'foto' => $this->foto('t.png'), 'keterangan' => 'selesai'])->assertSessionHas('success');
        $this->assertSame(2, $p->bukti()->count());
        foreach ($p->bukti as $b) {
            Storage::disk('public')->assertExists($b->foto);
        }

        // 8. ajukan penyelesaian
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.ajukan', $p->id), ['catatan' => 'Beres'])->assertSessionHas('success');
        $p->refresh();
        $this->assertSame('waiting_confirmation', $p->status_penanganan);
        $this->assertSame(100, (int) $p->progres);
        $this->assertNotNull($p->diajukan_pada);

        // 9. admin melihat halaman validasi lalu menyatakan selesai
        $this->actingAs($this->admin)->get(route('admin.verifikasi', ['id' => $p->id]))->assertOk()
            ->assertSee('Validasi hasil pekerjaan')->assertSee('Nyatakan selesai');
        $this->actingAs($this->admin)->put(route('admin.verifikasi.validasi', $p), ['catatan' => 'Bagus'])->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('completed', $p->status_penanganan);
        $this->assertSame('approved', $p->status); // status verifikasi tidak ikut berubah
        $this->assertNotNull($p->selesai_pada);

        $this->assertSame(
            ['pending', 'approved', 'assigned', 'accepted', 'in_progress', 'waiting_confirmation', 'completed'],
            $this->urutanStatus($p)
        );

        // siswa melihat prosesnya
        $this->actingAs($this->siswa)->get(route('pengaduan.show', $p))->assertOk()
            ->assertSee($this->teknisi->name)->assertSee('Selesai')->assertSee('Timeline');
        // teknisi melihat di riwayat pekerjaan
        $this->actingAs($this->teknisi)->get('/teknisi/riwayat')->assertOk()->assertSee('Lab Komputer');
    }

    public function test_alur_revisi_lalu_validasi(): void
    {
        $p = $this->sampai('waiting_confirmation');

        // catatan revisi wajib
        $this->actingAs($this->admin)->put(route('admin.verifikasi.revisi', $p), [])->assertSessionHasErrors('catatan');
        $this->assertSame('waiting_confirmation', $p->fresh()->status_penanganan);

        $this->travel(5)->seconds();
        $this->actingAs($this->admin)->put(route('admin.verifikasi.revisi', $p), ['catatan' => 'Kabel masih terlihat'])->assertSessionHas('success');
        $p->refresh();
        $this->assertSame('revision_required', $p->status_penanganan);
        $this->assertSame('Kabel masih terlihat', $p->catatan_revisi);
        $this->assertNotNull($p->direvisi_pada);

        // teknisi melihat catatan revisi
        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk()->assertSee('Kabel masih terlihat');

        // tidak bisa langsung mengajukan dari revision_required, harus mulai revisi dulu
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.ajukan', $p->id))->assertSessionHas('error');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.mulai', $p->id))->assertSessionHas('success');
        $this->assertSame('in_progress', $p->fresh()->status_penanganan);
        $this->assertLessThanOrEqual(90, (int) $p->fresh()->progres);

        // bukti "sesudah" lama tidak cukup setelah revisi
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.ajukan', $p->id))->assertSessionHas('error');
        $this->assertSame('in_progress', $p->fresh()->status_penanganan);

        $this->travel(5)->seconds();
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.bukti', $p->id), ['jenis' => 'sesudah', 'foto' => $this->foto('baru.jpg')])->assertSessionHas('success');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.ajukan', $p->id))->assertSessionHas('success');
        $this->assertSame('waiting_confirmation', $p->fresh()->status_penanganan);

        $this->actingAs($this->admin)->put(route('admin.verifikasi.validasi', $p))->assertSessionHas('success');
        $p->refresh();
        $this->assertSame('completed', $p->status_penanganan);
        $this->assertNull($p->catatan_revisi);

        // pengaduan helper dibuat tanpa langkah pengajuan/verifikasi, jadi hanya riwayat penanganan yang tercatat
        $this->assertSame(
            ['assigned', 'accepted', 'in_progress', 'waiting_confirmation', 'revision_required', 'in_progress', 'waiting_confirmation', 'completed'],
            $this->urutanStatus($p)
        );
    }

    // ------------------------------------------------------------------ matriks transisi

    public function test_matriks_transisi_penanganan(): void
    {
        $semua = Pengaduan::PENANGANAN;
        $diizinkan = [
            'not_assigned' => ['assigned'],
            'assigned' => ['assigned', 'accepted', 'not_assigned'], // not_assigned = teknisi menolak tugas
            'accepted' => ['in_progress'],
            'in_progress' => ['in_progress', 'waiting_confirmation'],
            'waiting_confirmation' => ['completed', 'revision_required'],
            'revision_required' => ['in_progress'],
            'completed' => [],
        ];

        foreach ($semua as $dari) {
            $p = new Pengaduan(['status' => 'approved']);
            $p->status_penanganan = $dari;
            foreach ($semua as $ke) {
                $this->assertSame(
                    in_array($ke, $diizinkan[$dari], true), $p->bolehKe($ke),
                    "Transisi {$dari} -> {$ke} seharusnya ".(in_array($ke, $diizinkan[$dari], true) ? 'diizinkan' : 'ditolak')
                );
            }
        }
    }

    public function test_transisi_tidak_valid_ditolak_oleh_service_dan_status_tidak_berubah(): void
    {
        // belum ditugaskan: tidak bisa terima / mulai / ajukan / validasi / revisi
        $p = $this->pengaduan();
        foreach ([
            fn () => $this->wf->terima($p, $this->teknisi),
            fn () => $this->wf->mulai($p, $this->teknisi),
            fn () => $this->wf->perbaruiProgres($p, $this->teknisi, 10, null),
            fn () => $this->wf->ajukanPenyelesaian($p, $this->teknisi, null),
            fn () => $this->wf->validasi($p, $this->admin, null),
            fn () => $this->wf->mintaRevisi($p, $this->admin, 'x'),
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya dilempar exception');
            } catch (TransisiTidakValid|AuthorizationException $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('not_assigned', $p->fresh()->status_penanganan);
    }

    public function test_assigned_tidak_bisa_mulai_atau_diajukan_sebelum_diterima(): void
    {
        $p = $this->sampai('assigned');

        $this->expectException(TransisiTidakValid::class);
        try {
            $this->wf->mulai($p, $this->teknisi);
        } finally {
            $this->assertSame('assigned', $p->fresh()->status_penanganan);
        }
    }

    public function test_accepted_tidak_bisa_diajukan_atau_diubah_progresnya(): void
    {
        $p = $this->sampai('accepted');

        foreach ([
            fn () => $this->wf->ajukanPenyelesaian($p, $this->teknisi, null),
            fn () => $this->wf->perbaruiProgres($p, $this->teknisi, 10, null),
            fn () => $this->wf->terima($p, $this->teknisi),
            fn () => $this->wf->validasi($p, $this->admin, null),
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak');
            } catch (TransisiTidakValid $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('accepted', $p->fresh()->status_penanganan);
    }

    public function test_in_progress_tidak_bisa_divalidasi_atau_diterima_ulang(): void
    {
        $p = $this->sampai('in_progress');

        foreach ([
            fn () => $this->wf->validasi($p, $this->admin, null),
            fn () => $this->wf->mintaRevisi($p, $this->admin, 'x'),
            fn () => $this->wf->terima($p, $this->teknisi),
            fn () => $this->wf->tugaskan($p, $this->teknisi, $this->admin),
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak');
            } catch (TransisiTidakValid $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('in_progress', $p->fresh()->status_penanganan);
    }

    public function test_ajukan_tanpa_bukti_sesudah_ditolak_walau_ada_bukti_sebelum(): void
    {
        $p = $this->sampai('in_progress');
        $this->wf->tambahBukti($p, $this->teknisi, 'sebelum', $this->foto(), null);

        $this->expectException(TransisiTidakValid::class);
        try {
            $this->wf->ajukanPenyelesaian($p, $this->teknisi, null);
        } finally {
            $this->assertSame('in_progress', $p->fresh()->status_penanganan);
            $this->assertNull($p->fresh()->diajukan_pada);
        }
    }

    public function test_waiting_confirmation_hanya_bisa_ke_completed_atau_revision(): void
    {
        $p = $this->sampai('waiting_confirmation');

        foreach ([
            fn () => $this->wf->terima($p, $this->teknisi),
            fn () => $this->wf->mulai($p, $this->teknisi),
            fn () => $this->wf->ajukanPenyelesaian($p, $this->teknisi, null),
            fn () => $this->wf->perbaruiProgres($p, $this->teknisi, 50, null),
            fn () => $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto(), null),
            fn () => $this->wf->tugaskan($p, $this->teknisi, $this->admin),
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak');
            } catch (TransisiTidakValid $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('waiting_confirmation', $p->fresh()->status_penanganan);
    }

    public function test_completed_adalah_status_akhir(): void
    {
        $p = $this->sampai('completed');
        $this->assertSame('completed', $p->status_penanganan);

        foreach ([
            fn () => $this->wf->validasi($p, $this->admin, null),
            fn () => $this->wf->mintaRevisi($p, $this->admin, 'x'),
            fn () => $this->wf->mulai($p, $this->teknisi),
            fn () => $this->wf->terima($p, $this->teknisi),
            fn () => $this->wf->tugaskan($p, $this->teknisi, $this->admin),
            fn () => $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto(), null),
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak');
            } catch (TransisiTidakValid $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('completed', $p->fresh()->status_penanganan);
    }

    // ------------------------------------------------------------------ status verifikasi vs penanganan

    public function test_pengaduan_belum_disetujui_tidak_bisa_ditugaskan_atau_ditangani(): void
    {
        foreach (['pending', 'rejected'] as $status) {
            $p = $this->pengaduan($status);
            try {
                $this->wf->tugaskan($p, $this->teknisi, $this->admin);
                $this->fail('Seharusnya ditolak');
            } catch (TransisiTidakValid $e) {
                $this->assertSame('not_assigned', $p->fresh()->status_penanganan);
                $this->assertNull($p->fresh()->teknisi_id);
            }
        }

        // data rusak: ditugaskan tetapi verifikasinya bukan approved -> teknisi tetap tidak bisa menerima
        $p = $this->pengaduan('pending');
        $p->forceFill(['teknisi_id' => $this->teknisi->id, 'status_penanganan' => 'assigned'])->save();
        $this->expectException(TransisiTidakValid::class);
        $this->wf->terima($p, $this->teknisi);
    }

    public function test_status_verifikasi_dan_penanganan_terpisah_serta_alias_tersedia(): void
    {
        $p = $this->sampai('in_progress');
        $this->assertSame('approved', $p->status_verifikasi);
        $this->assertSame('approved', $p->status);
        $this->assertSame('in_progress', $p->status_penanganan);
    }

    public function test_verifikasi_dicatat_di_riwayat_dan_status_sama_tidak_menggandakan(): void
    {
        $p = $this->pengaduan('pending');

        $this->wf->verifikasi($p, $this->admin, 'approved', 'ok');
        $this->wf->verifikasi($p, $this->admin, 'approved', 'catatan baru'); // status sama
        $this->wf->verifikasi($p, $this->admin, 'rejected', 'ditolak karena ganda');

        $rows = $p->riwayat()->where('jenis', 'verifikasi')->get();
        $this->assertSame(['approved', 'rejected'], $rows->pluck('ke')->all());
        $this->assertSame('pending', $rows[0]->dari);
        $this->assertSame('approved', $rows[1]->dari);
        $this->assertSame('ditolak karena ganda', $rows[1]->catatan);
        $this->assertSame($this->admin->id, $rows[1]->user_id);
        $this->assertSame('ditolak karena ganda', $p->fresh()->tanggapan);
        $this->assertSame('rejected', $p->fresh()->status);
    }

    // ------------------------------------------------------------------ otorisasi

    public function test_teknisi_lain_tidak_dapat_mengakses_atau_mengubah_tugas(): void
    {
        $p = $this->sampai('assigned');
        $lain = User::create(['name' => 'Lain', 'email' => 'lain@sch.id', 'role' => 'teknisi', 'password' => 'password123']);

        foreach (['terima', 'mulai', 'progres', 'bukti', 'ajukan'] as $aksi) {
            $this->actingAs($lain)->post(route("teknisi.tugas.{$aksi}", $p->id), ['progres' => 10, 'jenis' => 'sesudah', 'foto' => $this->foto()])->assertNotFound();
        }
        $this->assertSame('assigned', $p->fresh()->status_penanganan);

        // lapisan service juga menolak (defense in depth)
        $this->expectException(AuthorizationException::class);
        $this->wf->terima($p, $lain);
    }

    public function test_role_lain_tidak_bisa_memakai_aksi_teknisi_maupun_validasi_admin(): void
    {
        $p = $this->sampai('waiting_confirmation');

        foreach (['terima', 'mulai', 'progres', 'bukti', 'ajukan'] as $aksi) {
            $this->actingAs($this->admin)->post(route("teknisi.tugas.{$aksi}", $p->id))->assertForbidden();
            $this->actingAs($this->siswa)->post(route("teknisi.tugas.{$aksi}", $p->id))->assertForbidden();
        }

        $this->actingAs($this->teknisi)->put(route('admin.verifikasi.validasi', $p))->assertForbidden();
        $this->actingAs($this->teknisi)->put(route('admin.verifikasi.revisi', $p), ['catatan' => 'x'])->assertForbidden();
        $this->actingAs($this->siswa)->put(route('admin.verifikasi.validasi', $p))->assertForbidden();
        $this->assertSame('waiting_confirmation', $p->fresh()->status_penanganan);
    }

    public function test_service_menolak_pelaku_dengan_role_salah(): void
    {
        $p = $this->pengaduan();

        foreach ([
            fn () => $this->wf->tugaskan($p, $this->teknisi, $this->teknisi),   // teknisi menugaskan
            fn () => $this->wf->verifikasi($p, $this->teknisi, 'approved', null),
            fn () => $this->wf->terima($p, $this->admin),                        // admin menerima tugas
        ] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak');
            } catch (AuthorizationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_penugasan_hanya_untuk_user_teknisi(): void
    {
        $p = $this->pengaduan();
        $this->expectException(TransisiTidakValid::class);
        $this->wf->tugaskan($p, $this->siswa, $this->admin);
    }

    public function test_input_status_dari_request_tidak_mempengaruhi_status(): void
    {
        $p = $this->sampai('in_progress');

        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.progres', $p->id), [
            'progres' => 20, 'status_penanganan' => 'completed', 'status' => 'rejected', 'teknisi_id' => 999,
        ]);

        $p->refresh();
        $this->assertSame('in_progress', $p->status_penanganan);
        $this->assertSame('approved', $p->status);
        $this->assertSame($this->teknisi->id, $p->teknisi_id);
    }

    // ------------------------------------------------------------------ progres & bukti

    public function test_aturan_progres(): void
    {
        $p = $this->sampai('in_progress');
        $url = route('teknisi.tugas.progres', $p->id);

        $this->actingAs($this->teknisi)->post($url, ['progres' => 50, 'catatan' => 'setengah jalan'])->assertSessionHas('success');
        $this->assertSame(50, (int) $p->fresh()->progres);

        $this->actingAs($this->teknisi)->post($url, ['progres' => 30])->assertSessionHas('error');   // tidak boleh turun
        $this->actingAs($this->teknisi)->post($url, ['progres' => 100])->assertSessionHasErrors('progres'); // 100 hanya lewat pengajuan
        $this->actingAs($this->teknisi)->post($url, ['progres' => -5])->assertSessionHasErrors('progres');
        $this->actingAs($this->teknisi)->post($url, ['progres' => 'abc'])->assertSessionHasErrors('progres');
        $this->actingAs($this->teknisi)->post($url, [])->assertSessionHasErrors('progres');
        $this->assertSame(50, (int) $p->fresh()->progres);

        $this->assertDatabaseHas('riwayat_status', ['pengaduan_id' => $p->id, 'jenis' => 'catatan', 'catatan' => 'Progres 50%: setengah jalan']);
    }

    public function test_progres_hanya_saat_in_progress(): void
    {
        $p = $this->sampai('accepted');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.progres', $p->id), ['progres' => 10])->assertSessionHas('error');
        $this->assertSame(0, (int) $p->fresh()->progres);
    }

    public function test_validasi_upload_bukti(): void
    {
        $p = $this->sampai('in_progress');
        $url = route('teknisi.tugas.bukti', $p->id);

        $this->actingAs($this->teknisi)->post($url, ['jenis' => 'lainnya', 'foto' => $this->foto()])->assertSessionHasErrors('jenis');
        $this->actingAs($this->teknisi)->post($url, ['jenis' => 'sesudah'])->assertSessionHasErrors('foto');
        $this->actingAs($this->teknisi)->post($url, ['jenis' => 'sesudah', 'foto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('foto');
        $this->actingAs($this->teknisi)->post($url, ['jenis' => 'sesudah', 'foto' => UploadedFile::fake()->image('big.jpg')->size(5000)])->assertSessionHasErrors('foto');
        $this->assertSame(0, $p->bukti()->count());
    }

    public function test_bukti_hanya_bisa_diunggah_saat_accepted_atau_in_progress(): void
    {
        $assigned = $this->sampai('assigned');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.bukti', $assigned->id), ['jenis' => 'sebelum', 'foto' => $this->foto()])->assertSessionHas('error');
        $this->assertSame(0, $assigned->bukti()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('bukti'));

        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.terima', $assigned->id));
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.bukti', $assigned->id), ['jenis' => 'sebelum', 'foto' => $this->foto()])->assertSessionHas('success');
        $this->assertSame(1, $assigned->bukti()->count());
    }

    public function test_batas_jumlah_bukti(): void
    {
        $p = $this->sampai('in_progress');
        for ($i = 0; $i < PengaduanWorkflow::MAKS_BUKTI; $i++) {
            $this->wf->tambahBukti($p, $this->teknisi, 'sebelum', $this->foto("f{$i}.jpg"), null);
        }

        $this->expectException(TransisiTidakValid::class);
        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto('lebih.jpg'), null);
    }

    // ------------------------------------------------------------------ penugasan & riwayat

    public function test_penugasan_ulang_mencatat_riwayat_penugasan(): void
    {
        $t2 = User::create(['name' => 'Teknisi Dua', 'email' => 't2@sch.id', 'role' => 'teknisi', 'password' => 'password123']);
        $p = $this->pengaduan();

        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $this->wf->tugaskan($p, $t2, $this->admin);

        $p->refresh();
        $this->assertSame($t2->id, $p->teknisi_id);
        $this->assertSame(2, $p->penugasan()->count());
        $this->assertSame(1, $p->penugasan()->where('aktif', true)->count());
        $aktif = $p->penugasan()->where('aktif', true)->first();
        $this->assertSame($t2->id, $aktif->teknisi_id);
        $lama = $p->penugasan()->where('aktif', false)->first();
        $this->assertSame($this->teknisi->id, $lama->teknisi_id);
        $this->assertNotNull($lama->dilepas_pada);

        $this->assertSame(['assigned', 'assigned'], $p->riwayat()->where('jenis', 'penanganan')->pluck('ke')->all());
    }

    public function test_riwayat_mencatat_pelaku_dan_status_asal(): void
    {
        $p = $this->sampai('accepted');
        $rows = $p->riwayat()->where('jenis', 'penanganan')->get();

        $this->assertSame(['not_assigned', 'assigned'], $rows->pluck('dari')->all());
        $this->assertSame(['assigned', 'accepted'], $rows->pluck('ke')->all());
        $this->assertSame($this->admin->id, $rows[0]->user_id);
        $this->assertSame($this->teknisi->id, $rows[1]->user_id);
    }

    // ------------------------------------------------------------------ tampilan

    public function test_dashboard_admin_menawarkan_validasi_dan_filter_menunggu_validasi(): void
    {
        $p = $this->sampai('waiting_confirmation');
        $lain = $this->pengaduan(); // approved, belum ditugaskan

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()
            ->assertSee('Validasi 1 hasil pekerjaan');

        $res = $this->actingAs($this->admin)->get(route('admin.verifikasi', ['penanganan' => 'waiting_confirmation']));
        $res->assertOk()->assertSee($p->kode());
        $this->assertSame([$p->id], $res->viewData('daftar')->pluck('id')->all());
        $this->assertNotContains($lain->id, $res->viewData('daftar')->pluck('id')->all());
    }

    public function test_halaman_tugas_teknisi_menampilkan_aksi_sesuai_status(): void
    {
        $p = $this->sampai('assigned');
        $show = fn () => $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk();

        $show()->assertSee('Terima tugas')->assertDontSee('Ajukan penyelesaian');
        $this->wf->terima($p, $this->teknisi);
        $show()->assertSee('Mulai pekerjaan')->assertSee('Unggah foto bukti');
        $this->wf->mulai($p, $this->teknisi);
        $show()->assertSee('Perbarui progres')->assertSee('Ajukan penyelesaian');
        $this->ajukanDenganBukti($p);
        $show()->assertSee('Menunggu validasi admin')->assertDontSee('Perbarui progres');
    }

    public function test_siswa_hanya_melihat_catatan_verifikasi_bukan_catatan_internal_teknisi(): void
    {
        $p = $this->sampai('in_progress');
        $this->wf->perbaruiProgres($p, $this->teknisi, 30, 'RAHASIA-INTERNAL-TEKNISI');

        $this->actingAs($this->siswa)->get(route('pengaduan.show', $p))->assertOk()
            ->assertSee('Proses perbaikan')->assertDontSee('RAHASIA-INTERNAL-TEKNISI');
    }

    // ------------------------------------------------------------------ skema database

    public function test_skema_workflow_tersedia(): void
    {
        foreach (['riwayat_status', 'penugasan_teknisi', 'bukti_perbaikan'] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "Tabel {$tabel} tidak ada");
        }
        foreach (['status', 'status_penanganan', 'teknisi_id', 'progres', 'diterima_pada', 'mulai_pada', 'diajukan_pada', 'selesai_pada', 'direvisi_pada', 'catatan_revisi'] as $kolom) {
            $this->assertTrue(Schema::hasColumn('pengaduan', $kolom), "Kolom pengaduan.{$kolom} tidak ada");
        }
    }
}
