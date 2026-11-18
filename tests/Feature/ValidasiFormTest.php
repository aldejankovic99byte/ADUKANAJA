<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Sarana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 5 — validasi server-side untuk setiap form. Tiap kasus tidak valid harus: ditolak dengan error
 * pada field yang tepat DAN tidak menyimpan apa pun ke database.
 */
class ValidasiFormTest extends TestCase
{
    use RefreshDatabase;

    /** Cek error pada field; jika gagal, catat nama kasusnya agar mudah ditelusuri. */
    private function pastikanError($res, string $field, string $kasus, array &$gagal): void
    {
        try {
            $res->assertSessionHasErrors($field);
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $gagal[] = "{$kasus} (seharusnya error pada '{$field}')";
        }
    }

    private function user(string $role, string $email, ?string $nis = null): User
    {
        return User::create([
            'name' => ucfirst($role).' Uji', 'email' => $email, 'nis' => $nis, 'role' => $role, 'password' => 'password123',
        ]);
    }

    // ------------------------------------------------------------------ pengaduan (siswa)

    private function payloadPengaduan(Kategori $k, array $ubah = []): array
    {
        return array_merge([
            'kategori_id' => $k->id, 'lokasi' => 'Lab Komputer', 'tanggal_kejadian' => now()->toDateString(),
            'deskripsi' => 'Stop kontak korslet dan berbau gosong.',
        ], $ubah);
    }

    public function test_pengaduan_valid_tersimpan_dengan_status_awal_yang_benar(): void
    {
        Storage::fake('public');
        $siswa = $this->user('siswa', 's@sch.id', '1001');
        $k = Kategori::create(['nama' => 'Listrik']);

        $this->actingAs($siswa)->post('/pengaduan', $this->payloadPengaduan($k, ['foto' => UploadedFile::fake()->image('a.jpg')]))
            ->assertSessionHasNoErrors();

        $p = Pengaduan::firstOrFail();
        $this->assertSame($siswa->id, $p->user_id);
        $this->assertSame('pending', $p->status);
        $this->assertSame('not_assigned', $p->status_penanganan);
        $this->assertNull($p->teknisi_id);
    }

    public function test_pengaduan_tidak_valid_ditolak_per_field(): void
    {
        Storage::fake('public');
        $siswa = $this->user('siswa', 's@sch.id', '1001');
        $k = Kategori::create(['nama' => 'Listrik']);

        $kasus = [
            'kategori kosong' => [['kategori_id' => ''], 'kategori_id'],
            'kategori tidak ada' => [['kategori_id' => 9999], 'kategori_id'],
            'sarana tidak ada' => [['sarana_id' => 9999], 'sarana_id'],
            'lokasi kosong' => [['lokasi' => ''], 'lokasi'],
            'lokasi terlalu panjang' => [['lokasi' => str_repeat('a', 101)], 'lokasi'],
            'tanggal kosong' => [['tanggal_kejadian' => ''], 'tanggal_kejadian'],
            'tanggal bukan tanggal' => [['tanggal_kejadian' => 'bukan-tanggal'], 'tanggal_kejadian'],
            'tanggal di masa depan' => [['tanggal_kejadian' => now()->addDay()->toDateString()], 'tanggal_kejadian'],
            'deskripsi kosong' => [['deskripsi' => ''], 'deskripsi'],
            'deskripsi terlalu pendek' => [['deskripsi' => str_repeat('a', 9)], 'deskripsi'],
            'deskripsi terlalu panjang' => [['deskripsi' => str_repeat('a', 2001)], 'deskripsi'],
            'foto gif' => [['foto' => UploadedFile::fake()->image('a.gif')], 'foto'],
            'foto pdf' => [['foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'foto'],
            'foto > 2MB' => [['foto' => UploadedFile::fake()->image('b.jpg')->size(3000)], 'foto'],
        ];

        $gagal = [];
        foreach ($kasus as $nama => [$ubah, $field]) {
            $this->pastikanError($this->actingAs($siswa)->post('/pengaduan', $this->payloadPengaduan($k, $ubah)), $field, $nama, $gagal);
        }
        $this->assertSame([], $gagal, "Kasus tidak tertolak:\n".implode("\n", $gagal));

        $this->assertSame(0, Pengaduan::count(), 'Kasus tidak valid tidak boleh menyimpan data');
        $this->assertSame([], Storage::disk('public')->allFiles(), 'Kasus tidak valid tidak boleh meninggalkan file');
    }

    // ------------------------------------------------------------------ kategori & sarana (admin)

    public function test_validasi_kategori(): void
    {
        $admin = $this->user('admin', 'a@sch.id');
        Kategori::create(['nama' => 'Listrik']);

        $gagal = [];
        foreach ([[['nama' => ''], 'kosong'], [['nama' => 'Listrik'], 'duplikat'], [['nama' => str_repeat('a', 61)], 'terlalu panjang']] as [$data, $nama]) {
            $this->pastikanError($this->actingAs($admin)->post('/admin/kategori', $data), 'nama', "Kategori {$nama}", $gagal);
        }
        $this->assertSame([], $gagal, "Kasus tidak tertolak:\n".implode("\n", $gagal));
        $this->assertSame(1, Kategori::count());

        // nama sendiri tetap boleh saat edit; nama kategori lain tidak
        $lain = Kategori::create(['nama' => 'Meubel']);
        $this->actingAs($admin)->put("/admin/kategori/{$lain->id}", ['nama' => 'Meubel'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/kategori/{$lain->id}", ['nama' => 'Listrik'])->assertSessionHasErrors('nama');
    }

    public function test_validasi_sarana(): void
    {
        $admin = $this->user('admin', 'a@sch.id');

        foreach ([
            [['nama' => '', 'lokasi' => 'Lab'], 'nama'],
            [['nama' => 'Proyektor', 'lokasi' => ''], 'lokasi'],
            [['nama' => str_repeat('a', 101), 'lokasi' => 'Lab'], 'nama'],
            [['nama' => 'Proyektor', 'lokasi' => str_repeat('a', 101)], 'lokasi'],
        ] as [$data, $field]) {
            $this->actingAs($admin)->post('/admin/sarana', $data)->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Sarana::count());
    }

    // ------------------------------------------------------------------ pengguna (admin)

    public function test_validasi_pengguna(): void
    {
        $admin = $this->user('admin', 'a@sch.id');
        $this->user('siswa', 'ada@sch.id', '1001');
        $dasar = ['name' => 'Baru', 'email' => 'baru@sch.id', 'role' => 'siswa', 'nis' => '2002', 'password' => 'rahasia123'];

        $kasus = [
            'nama kosong' => [['name' => ''], 'name'],
            'nama > 100' => [['name' => str_repeat('a', 101)], 'name'],
            'email bukan email' => [['email' => 'bukan-email'], 'email'],
            'email duplikat' => [['email' => 'ada@sch.id'], 'email'],
            'role tidak dikenal' => [['role' => 'superadmin'], 'role'],
            'nis duplikat' => [['nis' => '1001'], 'nis'],
            'nis kosong untuk siswa' => [['nis' => ''], 'nis'],
            'password kosong' => [['password' => ''], 'password'],
            'password < 8' => [['password' => 'pendek'], 'password'],
        ];

        $gagal = [];
        foreach ($kasus as $nama => [$ubah, $field]) {
            $this->pastikanError($this->actingAs($admin)->post('/admin/pengguna', array_merge($dasar, $ubah)), $field, $nama, $gagal);
        }
        $this->assertSame([], $gagal, "Kasus tidak tertolak:\n".implode("\n", $gagal));

        $this->assertDatabaseMissing('users', ['email' => 'baru@sch.id']);
        $this->assertSame(2, User::count()); // admin + 1 siswa awal

        $this->actingAs($admin)->post('/admin/pengguna', $dasar)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'baru@sch.id', 'role' => 'siswa']);
    }

    // ------------------------------------------------------------------ profil

    public function test_validasi_profil_dan_password(): void
    {
        Storage::fake('public');
        $this->user('siswa', 'lain@sch.id', '2002');
        $u = $this->user('siswa', 'saya@sch.id', '1001');

        $this->actingAs($u)->put('/profil', ['name' => '', 'email' => 'saya@sch.id'])->assertSessionHasErrors('name');
        $this->actingAs($u)->put('/profil', ['name' => 'X', 'email' => 'lain@sch.id'])->assertSessionHasErrors('email');
        $this->actingAs($u)->put('/profil', ['name' => 'X', 'email' => 'bukan-email'])->assertSessionHasErrors('email');

        // ubah password
        $this->actingAs($u)->put('/profil/password', ['password_lama' => 'salah', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
            ->assertSessionHasErrors('password_lama');
        $this->actingAs($u)->put('/profil/password', ['password_lama' => 'password123', 'password' => 'barubaru1', 'password_confirmation' => 'tidaksama'])
            ->assertSessionHasErrors('password');
        $this->actingAs($u)->put('/profil/password', ['password_lama' => 'password123', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');

        // foto profil
        $this->actingAs($u)->post('/profil/foto', [])->assertSessionHasErrors('foto');
        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('foto');
        $this->actingAs($u)->post('/profil/foto', ['foto' => UploadedFile::fake()->image('b.jpg')->size(3000)])->assertSessionHasErrors('foto');

        $this->assertTrue(Hash::check('password123', $u->fresh()->password), 'Password tidak boleh berubah pada kasus gagal');
        $this->assertSame('saya@sch.id', $u->fresh()->email);
    }

    // ------------------------------------------------------------------ login

    public function test_validasi_login(): void
    {
        $this->post('/login', ['login' => '', 'password' => 'x'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'a@sch.id', 'password' => ''])->assertSessionHasErrors('password');
        $this->post('/login', [])->assertSessionHasErrors(['login', 'password']);
        $this->assertGuest();
    }
}
