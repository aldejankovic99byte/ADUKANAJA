<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = ['user_id', 'pengaduan_id', 'jenis', 'judul', 'pesan', 'url', 'dibaca_pada'];

    protected function casts(): array
    {
        return ['dibaca_pada' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function scopeBelumDibaca($query)
    {
        return $query->whereNull('dibaca_pada');
    }

    public function sudahDibaca(): bool
    {
        return $this->dibaca_pada !== null;
    }

    public function tandaiDibaca(): void
    {
        if (! $this->sudahDibaca()) {
            $this->forceFill(['dibaca_pada' => now()])->save();
        }
    }
}
