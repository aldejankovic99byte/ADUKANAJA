<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * FASE 1 — regression test konsistensi nama file/class/view/route.
 * Di Windows (XAMPP) huruf besar/kecil tidak dibedakan, tetapi di Linux (server produksi, CI) dibedakan,
 * sehingga ketidaksesuaian nama baru "meledak" saat deploy. Test ini mencegahnya.
 */
class StrukturProjectTest extends TestCase
{
    /** @return array<int, string> */
    private function files(string $dir, string $ext): array
    {
        $out = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), $ext)) {
                $out[] = $f->getPathname();
            }
        }
        sort($out);

        return $out;
    }

    public function test_nama_file_php_di_app_sama_dengan_nama_class(): void
    {
        foreach ($this->files(app_path(), '.php') as $path) {
            $src = file_get_contents($path);
            if (preg_match('/^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+(\w+)/m', $src, $m)) {
                $this->assertSame($m[1].'.php', basename($path), "Nama file tidak sama dengan nama class: {$path}");
            }
        }
    }

    public function test_tidak_ada_class_yang_dideklarasikan_dua_kali(): void
    {
        $seen = [];
        foreach ($this->files(app_path(), '.php') as $path) {
            $src = file_get_contents($path);
            $ns = preg_match('/^namespace\s+([^;]+);/m', $src, $n) ? $n[1] : '';
            if (preg_match('/^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+(\w+)/m', $src, $m)) {
                $fqcn = $ns.'\\'.$m[1];
                $this->assertArrayNotHasKey($fqcn, $seen, "Class {$fqcn} dideklarasikan di dua file: {$path} dan ".($seen[$fqcn] ?? ''));
                $seen[$fqcn] = $path;
            }
        }
    }

    public function test_nama_file_view_huruf_kecil_tanpa_spasi(): void
    {
        foreach ($this->files(resource_path('views'), '.blade.php') as $path) {
            $rel = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $path);
            $this->assertSame(strtolower($rel), $rel, "Nama view harus huruf kecil: {$rel}");
            $this->assertStringNotContainsString(' ', $rel, "Nama view tidak boleh mengandung spasi: {$rel}");
        }
    }

    public function test_semua_view_yang_dipanggil_controller_ada(): void
    {
        foreach ($this->files(app_path(), '.php') as $path) {
            preg_match_all("/view\(\s*'([a-z0-9_.\-]+)'/i", file_get_contents($path), $m);
            foreach ($m[1] as $name) {
                $this->assertTrue(View::exists($name), "View '{$name}' dipanggil di {$path} tetapi tidak ditemukan");
            }
        }
    }

    public function test_semua_route_yang_dipakai_di_view_terdaftar(): void
    {
        foreach ($this->files(resource_path('views'), '.blade.php') as $path) {
            if (basename($path) === 'welcome.blade.php') {
                continue; // halaman bawaan Laravel yang tidak dipakai (memeriksa Route::has('register') sendiri)
            }
            preg_match_all("/route\(\s*'([a-z0-9_.\-]+)'/i", file_get_contents($path), $m);
            foreach ($m[1] as $name) {
                $this->assertTrue(Route::has($name), "Route '{$name}' dipakai di {$path} tetapi tidak terdaftar");
            }
        }
    }

    public function test_semua_komponen_blade_yang_dipakai_ada(): void
    {
        foreach ($this->files(resource_path('views'), '.blade.php') as $path) {
            preg_match_all('/<x-([a-z0-9.\-]+)/i', file_get_contents($path), $m);
            foreach ($m[1] as $name) {
                $file = resource_path('views/components/'.str_replace('.', '/', $name).'.blade.php');
                $this->assertFileExists($file, "Komponen <x-{$name}> dipakai di {$path}");
            }
        }
    }

    public function test_view_pagination_kustom_terdaftar(): void
    {
        $this->assertTrue(View::exists('vendor.pagination.adukanaja'));
    }
}
