<?php

namespace App\Services;

use App\Exceptions\TransisiTidakValid;
use App\Models\BuktiPerbaikan;
use App\Models\Pengaduan;
use App\Models\RiwayatStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Satu-satunya pintu perubahan status pengaduan. Controller TIDAK mengubah status langsung.
 *
 * Alur penanganan (status verifikasi harus approved):
 *   not_assigned -> assigned -> accepted -> in_progress -> waiting_confirmation -> completed
 *                                              ^                    |
 *                                              +-- revision_required +   (admin mengembalikan dengan catatan)
 *
 * Setiap method: (1) mengunci baris pengaduan dalam transaksi, (2) memeriksa pelaku (role & kepemilikan),
 * (3) memeriksa transisi + syarat, (4) menyimpan perubahan, (5) mencatat riwayat_status.
 */
class PengaduanWorkflow
{
    public const MAKS_BUKTI = 12;

    public function __construct(private NotifikasiService $notif)
    {
    }

    // ------------------------------------------------------------------ pengajuan & verifikasi

    public function pengajuan(Pengaduan $pengaduan, User $siswa): void
    {
        $this->catat($pengaduan, RiwayatStatus::PENGAJUAN, null, Pengaduan::PENDING, $siswa);

        $pengaduan->loadMissing('kategori');
        $this->notif->keAdmin(
            $pengaduan, 'pengaduan_baru', "Pengaduan baru {$pengaduan->kode()}",
            "{$siswa->name} melaporkan {$pengaduan->kategori?->nama} di {$pengaduan->lokasi}.", $siswa
        );
    }

    /** Verifikasi admin: pending|approved|rejected. Status yang tidak berubah tidak menambah riwayat. */
    public function verifikasi(Pengaduan $pengaduan, User $admin, string $status, ?string $tanggapan): void
    {
        $this->wajibAdmin($admin);

        if (! in_array($status, Pengaduan::STATUS, true)) {
            throw new TransisiTidakValid('Status verifikasi tidak dikenal.');
        }

        DB::transaction(function () use ($pengaduan, $admin, $status, $tanggapan) {
            $p = $this->kunci($pengaduan);

            // Pengaduan yang sudah ditugaskan harus tetap approved (kalau tidak, teknisi memegang tugas dari laporan ditolak/pending)
            if ($p->teknisi_id !== null && $status !== Pengaduan::APPROVED) {
                throw new TransisiTidakValid("Pengaduan {$p->kode()} sudah ditugaskan ke teknisi sehingga status verifikasinya tidak dapat diubah.");
            }

            $dari = $p->status;
            $p->update([
                'status' => $status,
                'tanggapan' => $tanggapan,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);

            if ($dari !== $status) {
                $this->catat($p, RiwayatStatus::VERIFIKASI, $dari, $status, $admin, $tanggapan);

                if ($status === Pengaduan::APPROVED) {
                    $this->notif->kePelapor($p, 'verifikasi', "Pengaduan {$p->kode()} disetujui", 'Laporan Anda disetujui dan akan segera ditugaskan ke teknisi.', $admin);
                } elseif ($status === Pengaduan::REJECTED) {
                    $this->notif->kePelapor($p, 'verifikasi', "Pengaduan {$p->kode()} ditolak", $tanggapan ?: 'Laporan Anda tidak dapat diproses.', $admin);
                }
            }
        });

        $pengaduan->refresh();
    }

    // ------------------------------------------------------------------ penugasan (admin)

    public function tugaskan(Pengaduan $pengaduan, User $teknisi, User $admin): void
    {
        $this->wajibAdmin($admin);

        if (! $teknisi->isTeknisi()) {
            throw new TransisiTidakValid('Pengguna yang dipilih bukan teknisi.');
        }

        DB::transaction(function () use ($pengaduan, $teknisi, $admin) {
            $p = $this->kunci($pengaduan);

            if ($p->status !== Pengaduan::APPROVED) {
                throw new TransisiTidakValid('Hanya pengaduan yang sudah disetujui yang dapat ditugaskan ke teknisi.');
            }
            if (! $p->bolehKe(Pengaduan::ASSIGNED)) {
                throw new TransisiTidakValid('Pengaduan sudah dalam proses penanganan sehingga teknisinya tidak dapat diganti.');
            }

            $dari = $p->status_penanganan;

            $p->penugasan()->where('aktif', true)->update(['aktif' => false, 'dilepas_pada' => now()]);
            $p->tugaskanKe($teknisi, $admin);
            $p->penugasan()->create([
                'teknisi_id' => $teknisi->id, 'ditugaskan_oleh' => $admin->id, 'aktif' => true,
            ]);

            $this->catat($p, RiwayatStatus::PENANGANAN, $dari, Pengaduan::ASSIGNED, $admin, "Ditugaskan kepada {$teknisi->name}");

            $this->notif->ke($teknisi, $p, 'tugas_baru', "Tugas baru {$p->kode()}", "{$p->kategori?->nama} di {$p->lokasi}. Silakan terima atau tolak tugas ini.", $admin);
            $this->notif->kePelapor($p, 'penugasan', "Teknisi ditugaskan untuk {$p->kode()}", "{$teknisi->name} akan menangani pengaduan Anda.", $admin);
        });

        $pengaduan->refresh();
    }

    // ------------------------------------------------------------------ pekerjaan (teknisi)

    public function terima(Pengaduan $pengaduan, User $teknisi): void
    {
        $this->ubahOlehTeknisi($pengaduan, $teknisi, Pengaduan::ACCEPTED, function (Pengaduan $p) {
            $p->forceFill(['diterima_pada' => now()])->save();
        }, 'Tugas diterima');
    }

    /** Mulai bekerja (dari accepted) atau mulai mengerjakan revisi (dari revision_required). */
    public function mulai(Pengaduan $pengaduan, User $teknisi): void
    {
        $this->ubahOlehTeknisi($pengaduan, $teknisi, Pengaduan::IN_PROGRESS, function (Pengaduan $p) {
            $revisi = $p->status_penanganan === Pengaduan::REVISION_REQUIRED;
            $p->forceFill([
                'mulai_pada' => $p->mulai_pada ?? now(),
                'progres' => $revisi ? min((int) $p->progres, 90) : (int) $p->progres,
            ])->save();
        }, null, fn (Pengaduan $p) => $p->status_penanganan === Pengaduan::REVISION_REQUIRED ? 'Mulai mengerjakan revisi' : 'Pekerjaan dimulai',
            function (Pengaduan $p, string $dari) use ($teknisi) {
                if ($dari === Pengaduan::ACCEPTED) {
                    $this->notif->kePelapor($p, 'perbaikan', "Perbaikan {$p->kode()} dimulai", "{$teknisi->name} mulai mengerjakan perbaikan.", $teknisi);
                }
            });
    }

    /** Perbarui progres (0-99) dan/atau catatan pengerjaan. Progres tidak boleh turun. */
    public function perbaruiProgres(Pengaduan $pengaduan, User $teknisi, int $progres, ?string $catatan): void
    {
        $this->wajibTeknisi($teknisi);

        DB::transaction(function () use ($pengaduan, $teknisi, $progres, $catatan) {
            $p = $this->kunciMilikTeknisi($pengaduan, $teknisi);

            if ($p->status_penanganan !== Pengaduan::IN_PROGRESS) {
                throw new TransisiTidakValid('Progres hanya dapat diperbarui saat pekerjaan sedang dikerjakan.');
            }
            if ($progres < 0 || $progres > 99) {
                throw new TransisiTidakValid('Progres harus antara 0 dan 99 (100% diisi saat mengajukan penyelesaian).');
            }
            if ($progres < (int) $p->progres) {
                throw new TransisiTidakValid("Progres tidak boleh lebih kecil dari progres sebelumnya ({$p->progres}%).");
            }

            $p->forceFill(['progres' => $progres])->save();

            $teks = "Progres {$progres}%".($catatan ? ': '.$catatan : '');
            $this->catat($p, RiwayatStatus::CATATAN, Pengaduan::IN_PROGRESS, Pengaduan::IN_PROGRESS, $teknisi, $teks);
        });

        $pengaduan->refresh();
    }

    public function tambahBukti(Pengaduan $pengaduan, User $teknisi, string $jenis, UploadedFile $foto, ?string $keterangan): BuktiPerbaikan
    {
        $this->wajibTeknisi($teknisi);

        if (! in_array($jenis, BuktiPerbaikan::JENIS, true)) {
            throw new TransisiTidakValid('Jenis bukti harus "sebelum" atau "sesudah".');
        }

        $path = null;

        try {
            return DB::transaction(function () use ($pengaduan, $teknisi, $jenis, $foto, $keterangan, &$path) {
                $p = $this->kunciMilikTeknisi($pengaduan, $teknisi);

                if (! in_array($p->status_penanganan, [Pengaduan::ACCEPTED, Pengaduan::IN_PROGRESS], true)) {
                    throw new TransisiTidakValid('Bukti hanya dapat diunggah setelah tugas diterima dan sebelum diajukan selesai.');
                }
                if ($p->bukti()->count() >= self::MAKS_BUKTI) {
                    throw new TransisiTidakValid('Jumlah foto bukti maksimal '.self::MAKS_BUKTI.' per pengaduan.');
                }

                $path = $foto->store('bukti', 'public');

                $bukti = $p->bukti()->create([
                    'user_id' => $teknisi->id, 'jenis' => $jenis, 'foto' => $path, 'keterangan' => $keterangan,
                ]);

                $this->catat($p, RiwayatStatus::BUKTI, $p->status_penanganan, $p->status_penanganan, $teknisi,
                    'Foto bukti '.$jenis.' perbaikan'.($keterangan ? ': '.$keterangan : ''));

                return $bukti;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path); // jangan tinggalkan file yatim jika transaksi gagal
            }
            throw $e;
        }
    }

    /** Ajukan penyelesaian: wajib ada foto bukti "sesudah" (setelah revisi: yang baru). */
    public function ajukanPenyelesaian(Pengaduan $pengaduan, User $teknisi, ?string $catatan): void
    {
        $this->ubahOlehTeknisi($pengaduan, $teknisi, Pengaduan::WAITING_CONFIRMATION, function (Pengaduan $p) {
            $adaBukti = $p->bukti()
                ->where('jenis', BuktiPerbaikan::SESUDAH)
                ->when($p->direvisi_pada, fn ($q, $t) => $q->where('created_at', '>=', $t))
                ->exists();

            if (! $adaBukti) {
                throw new TransisiTidakValid($p->direvisi_pada
                    ? 'Unggah foto bukti "sesudah" yang baru (setelah revisi) sebelum mengajukan penyelesaian.'
                    : 'Unggah minimal satu foto bukti "sesudah" perbaikan sebelum mengajukan penyelesaian.');
            }

            $p->forceFill(['progres' => 100, 'diajukan_pada' => now()])->save();
        }, $catatan ? 'Pengajuan penyelesaian: '.$catatan : 'Pengajuan penyelesaian', null, function (Pengaduan $p) use ($teknisi) {
            $this->notif->keAdmin($p, 'menunggu_validasi', "Menunggu validasi {$p->kode()}", "{$teknisi->name} mengajukan penyelesaian pekerjaan.", $teknisi);
        });
    }

    // ------------------------------------------------------------------ validasi akhir (admin)

    public function validasi(Pengaduan $pengaduan, User $admin, ?string $catatan): void
    {
        $this->ubahOlehAdmin($pengaduan, $admin, Pengaduan::COMPLETED, function (Pengaduan $p) {
            $p->forceFill(['selesai_pada' => now(), 'catatan_revisi' => null])->save();
        }, $catatan ?: 'Hasil pekerjaan divalidasi', function (Pengaduan $p) use ($admin) {
            $this->notif->kePelapor($p, 'selesai', "Pengaduan {$p->kode()} selesai", 'Perbaikan sudah selesai dan dinyatakan baik oleh admin.', $admin);
            $this->notif->keTeknisi($p, 'selesai', "Pekerjaan {$p->kode()} divalidasi", 'Hasil pekerjaan Anda dinyatakan selesai. Terima kasih!', $admin);
        });
    }

    public function mintaRevisi(Pengaduan $pengaduan, User $admin, string $catatan): void
    {
        if (trim($catatan) === '') {
            throw new TransisiTidakValid('Catatan revisi wajib diisi agar teknisi tahu apa yang harus diperbaiki.');
        }

        $this->ubahOlehAdmin($pengaduan, $admin, Pengaduan::REVISION_REQUIRED, function (Pengaduan $p) use ($catatan) {
            $p->forceFill(['catatan_revisi' => $catatan, 'direvisi_pada' => now()])->save();
        }, $catatan, function (Pengaduan $p) use ($admin, $catatan) {
            $this->notif->keTeknisi($p, 'revisi', "Revisi diminta untuk {$p->kode()}", $catatan, $admin);
        });
    }

    // ------------------------------------------------------------------ tolak tugas & hapus bukti (teknisi)

    /** Teknisi menolak tugas sebelum menerimanya: tugas kembali ke antrean admin dengan alasan tercatat. */
    public function tolak(Pengaduan $pengaduan, User $teknisi, string $alasan): void
    {
        $this->wajibTeknisi($teknisi);
        $alasan = trim($alasan);

        if (mb_strlen($alasan) < 5) {
            throw new TransisiTidakValid('Alasan penolakan wajib diisi (minimal 5 karakter).');
        }

        DB::transaction(function () use ($pengaduan, $teknisi, $alasan) {
            $p = $this->kunciMilikTeknisi($pengaduan, $teknisi);

            if ($p->status_penanganan !== Pengaduan::ASSIGNED) {
                throw new TransisiTidakValid('Tugas hanya dapat ditolak sebelum diterima.');
            }
            $this->pastikanTransisi($p, Pengaduan::NOT_ASSIGNED);

            $p->penugasan()->where('aktif', true)->update(['aktif' => false, 'dilepas_pada' => now()]);
            $p->forceFill([
                'teknisi_id' => null, 'status_penanganan' => Pengaduan::NOT_ASSIGNED,
                'ditugaskan_pada' => null, 'ditugaskan_oleh' => null, 'progres' => 0,
            ])->save();

            $this->catat($p, RiwayatStatus::PENANGANAN, Pengaduan::ASSIGNED, Pengaduan::NOT_ASSIGNED, $teknisi, "Ditolak oleh teknisi: {$alasan}");
            $this->notif->keAdmin($p, 'tugas_ditolak', "{$teknisi->name} menolak tugas {$p->kode()}", $alasan, $teknisi);
        });

        $pengaduan->refresh();
    }

    /** Hapus foto bukti milik tugas sendiri selama belum diajukan selesai. File fisik dihapus setelah transaksi berhasil. */
    public function hapusBukti(Pengaduan $pengaduan, User $teknisi, BuktiPerbaikan $bukti): void
    {
        $this->wajibTeknisi($teknisi);
        $path = null;

        DB::transaction(function () use ($pengaduan, $teknisi, $bukti, &$path) {
            $p = $this->kunciMilikTeknisi($pengaduan, $teknisi);

            if ((int) $bukti->pengaduan_id !== (int) $p->id) {
                throw new AuthorizationException('Foto bukti ini bukan bagian dari tugas Anda.');
            }
            if (! in_array($p->status_penanganan, [Pengaduan::ACCEPTED, Pengaduan::IN_PROGRESS], true)) {
                throw new TransisiTidakValid('Foto bukti hanya dapat dihapus sebelum penyelesaian diajukan.');
            }

            $path = $bukti->foto;
            $jenis = $bukti->jenis;
            $bukti->delete();

            $this->catat($p, RiwayatStatus::BUKTI, $p->status_penanganan, $p->status_penanganan, $teknisi, "Foto bukti {$jenis} dihapus");
        });

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    // ------------------------------------------------------------------ internal

    private function ubahOlehTeknisi(Pengaduan $pengaduan, User $teknisi, string $ke, callable $terapkan, ?string $catatan, ?callable $catatanDinamis = null, ?callable $setelah = null): void
    {
        $this->wajibTeknisi($teknisi);

        DB::transaction(function () use ($pengaduan, $teknisi, $ke, $terapkan, $catatan, $catatanDinamis, $setelah) {
            $p = $this->kunciMilikTeknisi($pengaduan, $teknisi);
            $this->pastikanTransisi($p, $ke);

            $dari = $p->status_penanganan;
            $teks = $catatanDinamis ? $catatanDinamis($p) : $catatan;

            $terapkan($p);
            $p->forceFill(['status_penanganan' => $ke])->save();
            $this->catat($p, RiwayatStatus::PENANGANAN, $dari, $ke, $teknisi, $teks);

            if ($setelah) {
                $setelah($p, $dari);
            }
        });

        $pengaduan->refresh();
    }

    private function ubahOlehAdmin(Pengaduan $pengaduan, User $admin, string $ke, callable $terapkan, ?string $catatan, ?callable $setelah = null): void
    {
        $this->wajibAdmin($admin);

        DB::transaction(function () use ($pengaduan, $admin, $ke, $terapkan, $catatan, $setelah) {
            $p = $this->kunci($pengaduan);
            $this->pastikanTransisi($p, $ke);

            $dari = $p->status_penanganan;
            $terapkan($p);
            $p->forceFill(['status_penanganan' => $ke])->save();
            $this->catat($p, RiwayatStatus::PENANGANAN, $dari, $ke, $admin, $catatan);

            if ($setelah) {
                $setelah($p);
            }
        });

        $pengaduan->refresh();
    }

    private function pastikanTransisi(Pengaduan $p, string $ke): void
    {
        if ($p->status !== Pengaduan::APPROVED) {
            throw new TransisiTidakValid('Pengaduan belum disetujui sehingga belum dapat ditangani.');
        }

        if (! $p->bolehKe($ke)) {
            $dari = Pengaduan::penangananLabels()[$p->status_penanganan] ?? (string) $p->status_penanganan;
            $tujuan = Pengaduan::penangananLabels()[$ke] ?? $ke;
            throw new TransisiTidakValid("Perubahan status dari \"{$dari}\" ke \"{$tujuan}\" tidak diizinkan.");
        }
    }

    /** Ambil ulang baris pengaduan dengan kunci tulis agar dua permintaan bersamaan tidak saling menimpa. */
    private function kunci(Pengaduan $pengaduan): Pengaduan
    {
        return Pengaduan::query()->lockForUpdate()->findOrFail($pengaduan->id);
    }

    private function kunciMilikTeknisi(Pengaduan $pengaduan, User $teknisi): Pengaduan
    {
        $p = $this->kunci($pengaduan);

        if ((int) $p->teknisi_id !== (int) $teknisi->id) {
            throw new AuthorizationException('Tugas ini tidak ditugaskan kepada Anda.');
        }

        return $p;
    }

    private function wajibAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Hanya admin yang dapat melakukan tindakan ini.');
        }
    }

    private function wajibTeknisi(User $user): void
    {
        if (! $user->isTeknisi()) {
            throw new AuthorizationException('Hanya teknisi yang dapat melakukan tindakan ini.');
        }
    }

    private function catat(Pengaduan $p, string $jenis, ?string $dari, string $ke, ?User $oleh, ?string $catatan = null): RiwayatStatus
    {
        return RiwayatStatus::create([
            'pengaduan_id' => $p->id, 'jenis' => $jenis, 'dari' => $dari, 'ke' => $ke,
            'user_id' => $oleh?->id, 'catatan' => $catatan,
        ]);
    }
}
