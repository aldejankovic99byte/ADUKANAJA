<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenugasanTeknisi extends Model
{
    protected $table = 'penugasan_teknisi';

    protected $fillable = ['pengaduan_id', 'teknisi_id', 'ditugaskan_oleh', 'aktif', 'dilepas_pada'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'dilepas_pada' => 'datetime'];
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function penugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }
}
