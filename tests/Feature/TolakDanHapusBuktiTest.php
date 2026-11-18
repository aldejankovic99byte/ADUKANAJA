<?php

namespace Tests\Feature;

use App\Exceptions\TransisiTidakValid;
use App\Models\BuktiPerbaikan;
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
 * FASE 6 — teknisi menolak tugas (assigned -> not_assigned) dan menghapus foto bukti.
 */
class TolakDanHapusBuktiTest extends TestCase
{
    use RefreshDatabase;

    private PengaduanWorkflow $wf;
    private User $admin;
    private User $siswa;
    private User $teknisi;
    private User $teknisi2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->wf = app(PengaduanWorkflow::class);
        $mk = fn (string $role, string $email, ?string $nis = null) => User::create(['name' => ucfirst($role).' '.$email, 'email' => $email, 'nis' => $nis, 'role' => $role, 'password' => 'password123']);
        $this->admin = $mk('admin', 'admin@sch.id');
        $this->siswa = $mk('siswa', 's@sch.id', '1001');
        $this->teknisi = $mk('teknisi', 't1@sch.id');
        $this->teknisi2 = $mk('teknisi', 't2@sch.id');
    }

    private function pengaduan(): Pengaduan
    {
        $k = Kategori::firstOrCreate(['nama' => 'Listrik']);
        $p = new Pengaduan();
        $p->forceFill([
            'user_id' => $this->siswa->id, 'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.', 'status' => 'approved',
        ])->save();

        return $p->fresh();
    }

    private function foto(string $n = 'a.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($n, 400, 300);
    }

    // ------------------------------------------------------------------ tolak tugas

    public function test_teknisi_menolak_tugas_lewat_http(): void
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);

        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk()
            ->assertSee('Tolak tugas')->assertSee('Alasan penolakan');

        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.tolak', $p->id), ['alasan' => 'Sedang cuti sampai minggu depan'])
            ->assertRedirect(route('teknisi.tugas.index'))->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('not_assigned', $p->status_penanganan);
        $this->assertNull($p->teknisi_id);
        $this->assertNull($p->ditugaskan_pada);
        $this->assertNull($p->ditugaskan_oleh);
        $this->assertSame('approved', $p->status, 'status verifikasi tidak berubah');

        // penugasan lama ditutup, bukan dihapus (jejak audit)
        $this->assertSame(1, $p->penugasan()->count());
        $lama = $p->penugasan()->first();
        $this->assertFalse($lama->aktif);
        $this->assertNotNull($lama->dilepas_pada);

        // riwayat memuat alasan & pelaku
        $r = $p->riwayat()->where('jenis', 'penanganan')->latest('id')->first();
        $this->assertSame('assigned', $r->dari);
        $this->assertSame('not_assigned', $r->ke);
        $this->assertSame($this->teknisi->id, $r->user_id);
        $this->assertStringContainsString('Sedang cuti sampai minggu depan', $r->catatan);

        // admin diberi tahu dengan alasan; teknisi tidak lagi melihat tugasnya
        $n = Notifikasi::where('user_id', $this->admin->id)->where('jenis', 'tugas_ditolak')->firstOrFail();
        $this->assertStringContainsString('Sedang cuti', $n->pesan);
        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertNotFound();
        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.index'))->assertOk()->assertDontSee("/teknisi/tugas/{$p->id}", false); // tidak ada baris tugas
    }

    public function test_setelah_ditolak_admin_dapat_menugaskan_ulang_ke_teknisi_lain(): void
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $this->wf->tolak($p, $this->teknisi, 'Tidak punya alat yang sesuai');

        $this->actingAs($this->admin)->put(route('admin.verifikasi.tugaskan', $p), ['teknisi_id' => $this->teknisi2->id])->assertSessionHas('success');

        $p->refresh();
        $this->assertSame($this->teknisi2->id, $p->teknisi_id);
        $this->assertSame('assigned', $p->status_penanganan);
        $this->assertSame(2, $p->penugasan()->count());
        $this->assertSame(1, $p->penugasan()->where('aktif', true)->count());
        $this->actingAs($this->teknisi2)->get(route('teknisi.tugas.show', $p->id))->assertOk();
    }

    public function test_alasan_penolakan_wajib_dan_dibatasi(): void
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $url = route('teknisi.tugas.tolak', $p->id);

        $this->actingAs($this->teknisi)->post($url, [])->assertSessionHasErrors('alasan');
        $this->actingAs($this->teknisi)->post($url, ['alasan' => 'abc'])->assertSessionHasErrors('alasan');
        $this->actingAs($this->teknisi)->post($url, ['alasan' => str_repeat('a', 501)])->assertSessionHasErrors('alasan');

        $this->assertSame('assigned', $p->fresh()->status_penanganan);
        $this->assertSame($this->teknisi->id, $p->fresh()->teknisi_id);
    }

    public function test_tugas_tidak_bisa_ditolak_setelah_diterima_atau_oleh_orang_lain(): void
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);

        // teknisi lain -> 404; admin/siswa -> 403
        $this->actingAs($this->teknisi2)->post(route('teknisi.tugas.tolak', $p->id), ['alasan' => 'Bukan tugas saya'])->assertNotFound();
        $this->actingAs($this->admin)->post(route('teknisi.tugas.tolak', $p->id), ['alasan' => 'Alasan cukup panjang'])->assertForbidden();
        $this->actingAs($this->siswa)->post(route('teknisi.tugas.tolak', $p->id), ['alasan' => 'Alasan cukup panjang'])->assertForbidden();
        $this->assertSame($this->teknisi->id, $p->fresh()->teknisi_id);

        // setelah diterima tidak bisa ditolak lagi
        $this->wf->terima($p, $this->teknisi);
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.tolak', $p->id), ['alasan' => 'Berubah pikiran'])
            ->assertRedirect(route('teknisi.tugas.show', $p->id))->assertSessionHas('error');
        $this->assertSame('accepted', $p->fresh()->status_penanganan);

        $this->expectException(TransisiTidakValid::class);
        $this->wf->tolak($p, $this->teknisi, 'Berubah pikiran');
    }

    public function test_tombol_tolak_hanya_tampil_saat_status_ditugaskan(): void
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $this->wf->terima($p, $this->teknisi);

        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk()->assertDontSee('Tolak tugas');
    }

    // ------------------------------------------------------------------ hapus foto bukti

    private function siapKerja(): Pengaduan
    {
        $p = $this->pengaduan();
        $this->wf->tugaskan($p, $this->teknisi, $this->admin);
        $this->wf->terima($p, $this->teknisi);
        $this->wf->mulai($p, $this->teknisi);

        return $p->fresh();
    }

    public function test_teknisi_menghapus_foto_bukti_dan_file_fisik_ikut_terhapus(): void
    {
        $p = $this->siapKerja();
        $this->wf->tambahBukti($p, $this->teknisi, 'sebelum', $this->foto(), null);
        $bukti = $p->bukti()->firstOrFail();
        Storage::disk('public')->assertExists($bukti->foto);

        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk()->assertSee('Hapus foto');

        $this->actingAs($this->teknisi)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))
            ->assertRedirect(route('teknisi.tugas.show', $p->id))->assertSessionHas('success');

        $this->assertModelMissing($bukti);
        Storage::disk('public')->assertMissing($bukti->foto);
        $r = $p->riwayat()->where('jenis', 'bukti')->latest('id')->first();
        $this->assertStringContainsString('dihapus', $r->catatan);
    }

    public function test_hapus_bukti_hanya_untuk_tugas_milik_sendiri_dan_bukti_yang_sesuai(): void
    {
        $p = $this->siapKerja();
        $this->wf->tambahBukti($p, $this->teknisi, 'sebelum', $this->foto(), null);
        $bukti = $p->bukti()->firstOrFail();

        // teknisi lain, admin, siswa
        $this->actingAs($this->teknisi2)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))->assertForbidden();
        $this->actingAs($this->siswa)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))->assertForbidden();

        // bukti milik tugas lain tidak bisa dihapus lewat tugas sendiri (IDOR)
        $lain = $this->pengaduan();
        $this->wf->tugaskan($lain, $this->teknisi2, $this->admin);
        $this->wf->terima($lain, $this->teknisi2);
        $this->wf->mulai($lain, $this->teknisi2);
        $this->wf->tambahBukti($lain, $this->teknisi2, 'sebelum', $this->foto('b.jpg'), null);
        $buktiLain = $lain->bukti()->firstOrFail();

        $this->actingAs($this->teknisi)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $buktiLain->id]))->assertNotFound();
        $this->assertModelExists($bukti);
        $this->assertModelExists($buktiLain);
        Storage::disk('public')->assertExists($buktiLain->foto);
    }

    public function test_bukti_tidak_bisa_dihapus_setelah_diajukan_selesai(): void
    {
        $p = $this->siapKerja();
        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto(), null);
        $this->wf->ajukanPenyelesaian($p, $this->teknisi, null);
        $bukti = $p->bukti()->firstOrFail();

        $this->actingAs($this->teknisi)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))->assertSessionHas('error');

        $this->assertModelExists($bukti);
        Storage::disk('public')->assertExists($bukti->foto);
        $this->actingAs($this->teknisi)->get(route('teknisi.tugas.show', $p->id))->assertOk()->assertDontSee('Hapus foto');
    }

    public function test_setelah_bukti_sesudah_dihapus_pengajuan_penyelesaian_ditolak(): void
    {
        $p = $this->siapKerja();
        $this->wf->tambahBukti($p, $this->teknisi, 'sesudah', $this->foto(), null);
        $bukti = $p->bukti()->firstOrFail();

        $this->actingAs($this->teknisi)->delete(route('teknisi.tugas.bukti.hapus', [$p->id, $bukti->id]))->assertSessionHas('success');
        $this->actingAs($this->teknisi)->post(route('teknisi.tugas.ajukan', $p->id))->assertSessionHas('error');

        $this->assertSame('in_progress', $p->fresh()->status_penanganan);
        $this->assertSame(0, BuktiPerbaikan::count());
    }

    public function test_bukti_milik_admin_atau_pengaduan_lain_menolak_di_lapisan_service(): void
    {
        $p = $this->siapKerja();
        $lain = $this->pengaduan();
        $buktiLain = BuktiPerbaikan::create(['pengaduan_id' => $lain->id, 'user_id' => $this->teknisi2->id, 'jenis' => 'sebelum', 'foto' => 'bukti/x.jpg']);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->wf->hapusBukti($p, $this->teknisi, $buktiLain);
    }
}
