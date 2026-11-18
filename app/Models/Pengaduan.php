<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public const STATUS = [self::PENDING, self::APPROVED, self::REJECTED];

    // Status penanganan (terpisah dari status verifikasi di atas). Transisi lengkap dibangun di FASE 3.
    public const NOT_ASSIGNED = 'not_assigned';
    public const ASSIGNED = 'assigned';
    public const ACCEPTED = 'accepted';
    public const IN_PROGRESS = 'in_progress';
    public const WAITING_CONFIRMATION = 'waiting_confirmation';
    public const REVISION_REQUIRED = 'revision_required';
    public const COMPLETED = 'completed';

    public const PENANGANAN = [
        self::NOT_ASSIGNED, self::ASSIGNED, self::ACCEPTED, self::IN_PROGRESS,
        self::WAITING_CONFIRMATION, self::REVISION_REQUIRED, self::COMPLETED,
    ];

    /**
     * Transisi status penanganan yang diizinkan (satu-satunya sumber kebenaran; dipakai PengaduanWorkflow).
     * Status verifikasi (pending|approved|rejected) dikelola terpisah pada kolom `status`.
     */
    public const TRANSISI = [
        self::NOT_ASSIGNED => [self::ASSIGNED],
        self::ASSIGNED => [self::ASSIGNED, self::ACCEPTED, self::NOT_ASSIGNED], // NOT_ASSIGNED = teknisi menolak tugas
        self::ACCEPTED => [self::IN_PROGRESS],
        self::IN_PROGRESS => [self::IN_PROGRESS, self::WAITING_CONFIRMATION],
        self::WAITING_CONFIRMATION => [self::COMPLETED, self::REVISION_REQUIRED],
        self::REVISION_REQUIRED => [self::IN_PROGRESS],
        self::COMPLETED => [],
    ];

    /** Penanganan yang masih menunggu tindakan teknisi. */
    public const PENANGANAN_PERLU_TINDAKAN = [self::ASSIGNED, self::REVISION_REQUIRED];

    /** Penanganan yang sedang dikerjakan teknisi. */
    public const PENANGANAN_DIKERJAKAN = [self::ACCEPTED, self::IN_PROGRESS, self::REVISION_REQUIRED];

    protected $fillable = [
        'user_id', 'kategori_id', 'sarana_id', 'lokasi', 'tanggal_kejadian',
        'deskripsi', 'foto', 'status', 'tanggapan', 'diverifikasi_oleh', 'diverifikasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kejadian' => 'date',
            'diverifikasi_pada' => 'datetime',
            'ditugaskan_pada' => 'datetime',
            'diterima_pada' => 'datetime',
            'mulai_pada' => 'datetime',
            'diajukan_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'direvisi_pada' => 'datetime',
        ];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function sarana(): BelongsTo
    {
        return $this->belongsTo(Sarana::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class)->orderBy('created_at')->orderBy('id');
    }

    public function bukti(): HasMany
    {
        return $this->hasMany(BuktiPerbaikan::class)->orderBy('created_at')->orderBy('id');
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(KomentarPengaduan::class)->orderBy('created_at')->orderBy('id');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanTeknisi::class)->orderBy('id');
    }

    /** Alias bernama jelas: status verifikasi = kolom `status` (pending|approved|rejected). */
    public function getStatusVerifikasiAttribute(): string
    {
        return (string) $this->status;
    }

    public function bolehKe(string $penanganan): bool
    {
        return in_array($penanganan, self::TRANSISI[$this->status_penanganan] ?? [], true);
    }

    /** Teknisi yang saat ini bertanggung jawab atas pengaduan ini. */
    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function penugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    /** Nomor tiket seperti di wireframe: #0012 */
    public function kode(): string
    {
        return '#'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function scopeStatus($query, mixed $status)
    {
        return in_array($status, self::STATUS, true) ? $query->where('status', $status) : $query;
    }

    public static function penangananLabels(): array
    {
        return [
            self::NOT_ASSIGNED => 'Belum ditugaskan',
            self::ASSIGNED => 'Ditugaskan',
            self::ACCEPTED => 'Diterima',
            self::IN_PROGRESS => 'Sedang dikerjakan',
            self::WAITING_CONFIRMATION => 'Menunggu validasi',
            self::REVISION_REQUIRED => 'Perlu revisi',
            self::COMPLETED => 'Selesai',
        ];
    }

    public function penangananLabel(): string
    {
        return self::penangananLabels()[$this->status_penanganan] ?? ucfirst((string) $this->status_penanganan);
    }

    public function scopePenanganan($query, mixed $penanganan)
    {
        return in_array($penanganan, self::PENANGANAN, true) ? $query->where('status_penanganan', $penanganan) : $query;
    }

    /** Hanya pengaduan yang ditugaskan ke teknisi tertentu (dasar pencegahan IDOR di sisi teknisi). */
    public function scopeUntukTeknisi($query, User $teknisi)
    {
        return $query->where('teknisi_id', $teknisi->id);
    }

    /**
     * Tetapkan teknisi. Memakai forceFill agar kolom penugasan tidak perlu masuk $fillable
     * (menutup risiko mass-assignment dari input request).
     */
    public function tugaskanKe(User $teknisi, User $oleh): void
    {
        $this->forceFill([
            'teknisi_id' => $teknisi->id,
            'status_penanganan' => self::ASSIGNED,
            'ditugaskan_pada' => now(),
            'ditugaskan_oleh' => $oleh->id,
        ])->save();
    }
}
