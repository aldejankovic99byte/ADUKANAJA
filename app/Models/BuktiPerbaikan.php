<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuktiPerbaikan extends Model
{
    public const SEBELUM = 'sebelum';
    public const SESUDAH = 'sesudah';
    public const JENIS = [self::SEBELUM, self::SESUDAH];

    protected $table = 'bukti_perbaikan';

    protected $fillable = ['pengaduan_id', 'user_id', 'jenis', 'foto', 'keterangan'];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fotoUrl(): string
    {
        return asset('storage/'.$this->foto);
    }
}
