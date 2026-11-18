<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 2 — fondasi penugasan teknisi (aditif, tidak mengubah/menghapus data lama).
 *
 * Kolom ini menyimpan penugasan SAAT INI. Tabel riwayat (penugasan_teknisi, riwayat_status)
 * dirancang pada FASE 3.
 *
 * Idempotent: tiap kolom dicek dengan Schema::hasColumn() karena database lokal sebelumnya
 * sempat mengalami error "Duplicate column name 'teknisi_id'" (kolom sudah ada dari percobaan
 * terdahulu). Kolom yang sudah ada TIDAK diubah.
 *
 * Baris pengaduan lama otomatis status_penanganan = 'not_assigned'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pengaduan', 'teknisi_id')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('pengaduan', 'status_penanganan')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                // not_assigned | assigned | accepted | in_progress | waiting_confirmation | revision_required | completed
                $table->string('status_penanganan', 30)->default('not_assigned')->index();
            });
        }

        if (! Schema::hasColumn('pengaduan', 'ditugaskan_pada')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->timestamp('ditugaskan_pada')->nullable();
            });
        }

        if (! Schema::hasColumn('pengaduan', 'ditugaskan_oleh')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['ditugaskan_oleh', 'teknisi_id'] as $kolom) {
            if (Schema::hasColumn('pengaduan', $kolom)) {
                try {
                    Schema::table('pengaduan', fn (Blueprint $t) => $t->dropForeign([$kolom]));
                } catch (\Throwable $e) {
                    // foreign key tidak ada (mis. SQLite atau kolom lama tanpa FK) -> lanjut
                }
                Schema::table('pengaduan', fn (Blueprint $t) => $t->dropColumn($kolom));
            }
        }

        if (Schema::hasColumn('pengaduan', 'status_penanganan')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->dropIndex(['status_penanganan']);
                $table->dropColumn('status_penanganan');
            });
        }

        if (Schema::hasColumn('pengaduan', 'ditugaskan_pada')) {
            Schema::table('pengaduan', fn (Blueprint $t) => $t->dropColumn('ditugaskan_pada'));
        }
    }
};
