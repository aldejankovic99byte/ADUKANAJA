<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sarana extends Model
{
    protected $table = 'sarana';

    protected $fillable = ['nama', 'lokasi'];

    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function label(): string
    {
        return $this->nama.' — '.$this->lokasi;
    }
}