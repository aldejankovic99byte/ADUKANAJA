<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit trail perubahan status pengaduan (hanya ditambah, tidak pernah diubah). */
class RiwayatStatus extends Model
{
    public const UPDATED_AT = null;

    public const PENGAJUAN = 'pengajuan';
    public const VERIFIKASI = 'verifikasi';
    public const PENANGANAN = 'penanganan';
    public const CATATAN = 'catatan';
    public const BUKTI = 'bukti';

    protected $table = 'riwayat_status';

    protected $fillable = ['pengaduan_id', 'jenis', 'dari', 'ke', 'user_id', 'catatan'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Judul singkat untuk tampilan timeline. */
    public function judul(): string
    {
        return match ($this->jenis) {
            self::PENGAJUAN => 'Pengaduan diajukan',
            self::VERIFIKASI => 'Verifikasi: '.ucfirst($this->ke),
            self::PENANGANAN => 'Penanganan: '.(Pengaduan::penangananLabels()[$this->ke] ?? $this->ke),
            self::CATATAN => 'Catatan pengerjaan',
            self::BUKTI => 'Bukti perbaikan diunggah',
            default => ucfirst($this->jenis),
        };
    }
}
