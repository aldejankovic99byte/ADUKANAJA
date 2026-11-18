<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 6 — notifikasi in-app dan komentar pengaduan (aditif; tidak menyentuh data lama).
 * Idempotent: tabel yang sudah ada tidak dibuat ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifikasi')) {
            Schema::create('notifikasi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('pengaduan_id')->nullable()->constrained('pengaduan')->cascadeOnDelete();
                $table->string('jenis', 30);
                $table->string('judul', 150);
                $table->text('pesan')->nullable();
                $table->string('url', 255)->nullable();
                $table->timestamp('dibaca_pada')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'dibaca_pada']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('komentar_pengaduan')) {
            Schema::create('komentar_pengaduan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('isi');
                $table->timestamps();
                $table->index(['pengaduan_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('komentar_pengaduan');
        Schema::dropIfExists('notifikasi');
    }
};
