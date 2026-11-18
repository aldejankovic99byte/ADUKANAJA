<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->foreignId('sarana_id')->nullable()->constrained('sarana')->nullOnDelete();
            $table->string('lokasi', 100);
            $table->date('tanggal_kejadian');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->string('status', 10)->default('pending')->index(); // pending | approved | rejected
            $table->text('tanggapan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};