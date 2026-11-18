<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 3 — workflow penanganan (aditif; tidak menghapus / mengubah data lama).
 *
 *  - pengaduan            : + progres, diterima_pada, mulai_pada, diajukan_pada, selesai_pada, direvisi_pada, catatan_revisi
 *  - riwayat_status       : audit trail seluruh perubahan status (verifikasi & penanganan) + catatan pengerjaan
 *  - penugasan_teknisi    : riwayat penugasan teknisi per pengaduan
 *  - bukti_perbaikan      : foto bukti sebelum/sesudah perbaikan
 *
 * Idempotent (hasColumn / hasTable). Data lama di-backfill ke riwayat_status & penugasan_teknisi
 * hanya saat tabel tersebut BARU dibuat, sehingga menjalankan ulang tidak menggandakan data.
 * Kolom `pengaduan.status` tetap menjadi status VERIFIKASI (pending|approved|rejected);
 * `pengaduan.status_penanganan` (FASE 2) menjadi status PENANGANAN. Keduanya terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- kolom baru pada pengaduan ----------
        if (! Schema::hasColumn('pengaduan', 'progres')) {
            Schema::table('pengaduan', fn (Blueprint $t) => $t->unsignedTinyInteger('progres')->default(0));
        }
        foreach (['diterima_pada', 'mulai_pada', 'diajukan_pada', 'selesai_pada', 'direvisi_pada'] as $kolom) {
            if (! Schema::hasColumn('pengaduan', $kolom)) {
                Schema::table('pengaduan', fn (Blueprint $t) => $t->timestamp($kolom)->nullable());
            }
        }
        if (! Schema::hasColumn('pengaduan', 'catatan_revisi')) {
            Schema::table('pengaduan', fn (Blueprint $t) => $t->text('catatan_revisi')->nullable());
        }

        // ---------- riwayat_status ----------
        $riwayatBaru = ! Schema::hasTable('riwayat_status');
        if ($riwayatBaru) {
            Schema::create('riwayat_status', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
                $table->string('jenis', 20);              // pengajuan | verifikasi | penanganan | catatan | bukti
                $table->string('dari', 30)->nullable();
                $table->string('ke', 30);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('catatan')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['pengaduan_id', 'created_at']);
            });
        }

        // ---------- penugasan_teknisi ----------
        $penugasanBaru = ! Schema::hasTable('penugasan_teknisi');
        if ($penugasanBaru) {
            Schema::create('penugasan_teknisi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
                $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('aktif')->default(true);
                $table->timestamp('dilepas_pada')->nullable();
                $table->timestamps();
                $table->index(['pengaduan_id', 'aktif']);
            });
        }

        // ---------- bukti_perbaikan ----------
        if (! Schema::hasTable('bukti_perbaikan')) {
            Schema::create('bukti_perbaikan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('jenis', 10);              // sebelum | sesudah
                $table->string('foto');
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();
                $table->index(['pengaduan_id', 'jenis']);
            });
        }

        // ---------- backfill data lama (hanya jika tabel baru dibuat) ----------
        if ($riwayatBaru) {
            DB::table('pengaduan')->orderBy('id')->chunkById(200, function ($rows) use ($penugasanBaru) {
                foreach ($rows as $p) {
                    $riwayat = [[
                        'pengaduan_id' => $p->id, 'jenis' => 'pengajuan', 'dari' => null, 'ke' => 'pending',
                        'user_id' => $p->user_id, 'catatan' => null, 'created_at' => $p->created_at,
                    ]];

                    if ($p->status !== 'pending') {
                        $riwayat[] = [
                            'pengaduan_id' => $p->id, 'jenis' => 'verifikasi', 'dari' => 'pending', 'ke' => $p->status,
                            'user_id' => $p->diverifikasi_oleh, 'catatan' => $p->tanggapan,
                            'created_at' => $p->diverifikasi_pada ?? $p->updated_at,
                        ];
                    }

                    if ($p->teknisi_id !== null) {
                        $tgl = $p->ditugaskan_pada ?? $p->updated_at;
                        $riwayat[] = [
                            'pengaduan_id' => $p->id, 'jenis' => 'penanganan', 'dari' => 'not_assigned', 'ke' => 'assigned',
                            'user_id' => $p->ditugaskan_oleh, 'catatan' => 'Data awal (migrasi)', 'created_at' => $tgl,
                        ];
                        if (! in_array($p->status_penanganan, ['not_assigned', 'assigned'], true)) {
                            $riwayat[] = [
                                'pengaduan_id' => $p->id, 'jenis' => 'penanganan', 'dari' => 'assigned', 'ke' => $p->status_penanganan,
                                'user_id' => $p->teknisi_id, 'catatan' => 'Data awal (migrasi)', 'created_at' => $p->updated_at,
                            ];
                        }
                        if ($penugasanBaru) {
                            DB::table('penugasan_teknisi')->insert([
                                'pengaduan_id' => $p->id, 'teknisi_id' => $p->teknisi_id, 'ditugaskan_oleh' => $p->ditugaskan_oleh,
                                'aktif' => true, 'dilepas_pada' => null, 'created_at' => $tgl, 'updated_at' => $tgl,
                            ]);
                        }
                    }

                    DB::table('riwayat_status')->insert($riwayat);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_perbaikan');
        Schema::dropIfExists('penugasan_teknisi');
        Schema::dropIfExists('riwayat_status');

        foreach (['catatan_revisi', 'direvisi_pada', 'selesai_pada', 'diajukan_pada', 'mulai_pada', 'diterima_pada', 'progres'] as $kolom) {
            if (Schema::hasColumn('pengaduan', $kolom)) {
                Schema::table('pengaduan', fn (Blueprint $t) => $t->dropColumn($kolom));
            }
        }
    }
};
