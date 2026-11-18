<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_TEKNISI = 'teknisi';

    /** Alias kompatibilitas dengan role siswa */
    public const ROLE_SISWA = 'siswa';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_USER,
        self::ROLE_TEKNISI,
        self::ROLE_SISWA,
    ];

    protected $fillable = ['name', 'nis', 'kelas', 'email', 'role', 'foto', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Pengaduan yang DIAJUKAN user ini (role siswa). */
    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    /** Pengaduan yang DITUGASKAN ke user ini (role teknisi). */
    public function tugas(): HasMany
    {
        return $this->hasMany(Pengaduan::class, 'teknisi_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER || $this->role === self::ROLE_SISWA;
    }

    public function isSiswa(): bool
    {
        return $this->isUser();
    }

    public function isTeknisi(): bool
    {
        return $this->role === self::ROLE_TEKNISI;
    }

    public function hasRole(string ...$roles): bool
    {
        foreach ($roles as $r) {
            if ($r === $this->role) {
                return true;
            }
            if (($r === self::ROLE_USER || $r === self::ROLE_SISWA) && $this->isUser()) {
                return true;
            }
        }

        return false;
    }

    /** Nama route halaman utama sesuai role. Role tak dikenal -> login (aman, tidak memicu redirect loop). */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_TEKNISI => 'teknisi.dashboard',
            self::ROLE_USER, self::ROLE_SISWA => 'beranda',
            default => 'login',
        };
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_USER => 'User (Siswa / Guru)',
            self::ROLE_TEKNISI => 'Petugas Teknisi',
            self::ROLE_ADMIN => 'Admin / Petugas',
        ];
    }

    public function roleLabel(): string
    {
        if ($this->isUser()) {
            return self::roleLabels()[self::ROLE_USER];
        }

        return self::roleLabels()[$this->role] ?? ucfirst((string) $this->role);
    }

    /** URL foto profil (relatif ke alamat yang sedang dibuka, jadi tidak tergantung APP_URL). */
    public function fotoUrl(): ?string
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    public function inisial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
