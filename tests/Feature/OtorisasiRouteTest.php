<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Sarana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * FASE 5 — pengujian otorisasi OTOMATIS: seluruh route yang terdaftar dipindai dan diuji terhadap
 * guest + ketiga role. Route baru yang lupa diberi middleware role akan langsung terdeteksi.
 */
class OtorisasiRouteTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        // id 1,2,3 -> setiap route dengan parameter {x} diuji memakai id 1 yang benar-benar ada,
        // sehingga hasil 403 murni berasal dari middleware role (bukan 404 karena data tidak ada).
        foreach ([['admin', 'a@sch.id', null], ['siswa', 's@sch.id', '1001'], ['teknisi', 't@sch.id', null]] as [$role, $email, $nis]) {
            $this->users[$role] = User::create([
                'name' => ucfirst($role), 'email' => $email, 'nis' => $nis, 'role' => $role, 'password' => 'password123',
            ]);
        }

        $kategori = Kategori::create(['nama' => 'Listrik']);
        Sarana::create(['nama' => 'Proyektor', 'lokasi' => 'Lab']);

        (new Pengaduan())->forceFill([
            'user_id' => $this->users['siswa']->id, 'kategori_id' => $kategori->id, 'lokasi' => 'Lab',
            'tanggal_kejadian' => now()->toDateString(), 'deskripsi' => 'Lampu mati total di lab.',
            'status' => 'approved', 'teknisi_id' => $this->users['teknisi']->id, 'status_penanganan' => 'assigned',
        ])->save();
    }

    /** @return array<int, array{method: string, uri: string, raw: string, roles: array<int, string>}> */
    private function routeTerlindungi(): array
    {
        $hasil = [];

        foreach (Route::getRoutes() as $route) {
            $mw = $route->gatherMiddleware();
            $roles = [];
            foreach ($mw as $m) {
                if (is_string($m) && str_starts_with($m, 'role:')) {
                    $roles = array_merge($roles, explode(',', substr($m, 5)));
                }
            }

            if (! in_array('auth', $mw, true) && $roles === []) {
                continue; // route publik (login, halaman utama, dsb.)
            }

            $hasil[] = [
                'method' => collect($route->methods())->first(fn ($m) => $m !== 'HEAD'),
                'uri' => '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri()),
                'raw' => $route->uri(),
                'roles' => $roles,
            ];
        }

        return $hasil;
    }

    public function test_pemindaian_route_menemukan_route_yang_diharapkan(): void
    {
        $this->assertGreaterThan(35, count($this->routeTerlindungi()), 'Jumlah route terlindungi tidak wajar');
    }

    public function test_prefix_admin_teknisi_dan_siswa_selalu_dilindungi_role_yang_tepat(): void
    {
        $siswaUri = ['beranda', 'riwayat', 'pengaduan', 'pengaduan/ajukan', 'pengaduan/{pengaduan}'];
        $salah = [];

        foreach ($this->routeTerlindungi() as $r) {
            $diharapkan = match (true) {
                str_starts_with($r['raw'], 'admin/') => ['admin'],
                str_starts_with($r['raw'], 'teknisi/') => ['teknisi'],
                in_array($r['raw'], $siswaUri, true) => ['siswa'],
                default => null,
            };

            if ($diharapkan !== null && $r['roles'] !== $diharapkan) {
                $salah[] = "{$r['method']} /{$r['raw']} -> role [".implode(',', $r['roles']).'] seharusnya ['.implode(',', $diharapkan).']';
            }
        }

        $this->assertSame([], $salah, "Route tanpa/salah middleware role:\n".implode("\n", $salah));
    }

    public function test_guest_diarahkan_ke_login_di_semua_route_terlindungi(): void
    {
        $gagal = [];

        foreach ($this->routeTerlindungi() as $r) {
            $res = $this->call($r['method'], $r['uri']);
            if ($res->getStatusCode() !== 302 || ! str_ends_with((string) $res->headers->get('Location'), '/login')) {
                $gagal[] = "{$r['method']} {$r['uri']} -> {$res->getStatusCode()}";
            }
        }

        $this->assertSame([], $gagal, "Guest tidak diarahkan ke login:\n".implode("\n", $gagal));
    }

    public function test_role_yang_salah_selalu_mendapat_403(): void
    {
        $gagal = [];

        foreach ($this->routeTerlindungi() as $r) {
            if ($r['roles'] === []) {
                continue;
            }

            foreach ($this->users as $role => $user) {
                if (in_array($role, $r['roles'], true)) {
                    continue;
                }

                $res = $this->actingAs($user)->call($r['method'], $r['uri']);
                if ($res->getStatusCode() !== 403) {
                    $gagal[] = "{$role} {$r['method']} {$r['uri']} -> {$res->getStatusCode()} (seharusnya 403)";
                }
            }
        }

        $this->assertSame([], $gagal, "Role salah tidak ditolak:\n".implode("\n", $gagal));
    }

    public function test_halaman_get_untuk_role_yang_benar_tidak_menyebabkan_error_server(): void
    {
        $gagal = [];

        foreach ($this->routeTerlindungi() as $r) {
            if ($r['method'] !== 'GET') {
                continue;
            }

            foreach ($this->users as $role => $user) {
                if ($r['roles'] !== [] && ! in_array($role, $r['roles'], true)) {
                    continue;
                }

                $res = $this->actingAs($user)->call('GET', $r['uri']);
                if ($res->getStatusCode() >= 500) {
                    $gagal[] = "{$role} GET {$r['uri']} -> {$res->getStatusCode()}";
                }
            }
        }

        $this->assertSame([], $gagal, "Halaman error server:\n".implode("\n", $gagal));
    }
}
