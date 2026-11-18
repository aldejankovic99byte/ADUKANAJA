<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\KomentarPengaduan;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\PenugasanTeknisi;
use App\Models\RiwayatStatus;
use App\Models\Sarana;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Pengguna (password semua akun demo: "password") ----------
        $admin = User::create([
            'name' => 'Pak Rudi', 'email' => 'rudi@sch.id', 'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);

        $siswa = collect([
            ['Aldejan Kovic Putra Sulash', 'aldejan@sch.id', '2526003', 'XII RPL B'],
            ['Ayu Ramadhani', 'ayu@sch.id', '2526011', 'XII RPL A'],
            ['Bima Setiawan', 'bima@sch.id', '2526012', 'XI RPL B'],
            ['Citra Dewi', 'citra@sch.id', '2526013', 'XI TKJ A'],
        ])->map(fn ($s) => User::create([
            'name' => $s[0], 'email' => $s[1], 'nis' => $s[2], 'kelas' => $s[3],
            'role' => User::ROLE_SISWA, 'password' => 'password',
        ]));

        // ---------- Kategori & Sarana ----------
        $kategori = collect(['Listrik', 'Air', 'Furnitur', 'Sanitasi', 'Elektronik', 'Bangunan'])
            ->mapWithKeys(fn ($n) => [$n => Kategori::create(['nama' => $n])]);

        $sarana = collect([
            ['Proyektor', 'Lab Komputer'], ['AC', 'Lab Komputer'], ['Kursi Kelas', 'Kelas XI RPL'],
            ['Papan Tulis', 'Kelas XII RPL B'], ['Stop Kontak', 'Perpustakaan'], ['Keran Wastafel', 'Toilet Lt.2'],
        ])->map(fn ($s) => Sarana::create(['nama' => $s[0], 'lokasi' => $s[1]]));

        // Tambahkan sarana & kategori komprehensif kejuruan SMK RPL
        $this->call(SaranaRplSeeder::class);

        // ---------- Contoh pengaduan (supaya grafik & laporan langsung terisi) ----------
        $contoh = [
            // [siswa, kategori, sarana idx|null, lokasi, deskripsi, status, hari yang lalu, tanggapan]
            [1, 'Listrik', 4, 'Perpustakaan', 'Stop kontak dekat meja baca korslet dan berbau gosong saat dipakai mengisi daya.', 'approved', 150, 'Sudah diganti oleh petugas listrik.'],
            [2, 'Furnitur', 2, 'Kelas XI RPL', 'Dua kursi di baris belakang patah di bagian sandaran.', 'approved', 128, 'Kursi sudah diperbaiki.'],
            [3, 'Sanitasi', 5, 'Toilet Lt.2', 'Keran wastafel bocor terus dan lantai jadi licin.', 'rejected', 110, 'Laporan ganda, sudah ditangani pada laporan sebelumnya.'],
            [0, 'Elektronik', 0, 'Lab Komputer', 'Proyektor tidak menampilkan gambar, lampu indikator berkedip merah.', 'approved', 95, 'Lampu proyektor diganti.'],
            [1, 'Bangunan', null, 'Koridor Gedung B', 'Plafon di depan ruang guru bocor dan hampir roboh.', 'approved', 80, 'Plafon sudah ditambal.'],
            [2, 'Air', null, 'Kantin', 'Air kran cuci tangan tidak mengalir sejak Senin.', 'approved', 62, 'Pipa sudah dibersihkan.'],
            [3, 'Listrik', 1, 'Lab Komputer', 'AC mati dan mengeluarkan bunyi berdecit.', 'rejected', 45, 'Mohon sertakan foto kerusakan agar dapat diverifikasi.'],
            [0, 'Furnitur', 3, 'Kelas XII RPL B', 'Papan tulis mulai terkelupas sehingga tulisan sulit dibaca.', 'approved', 33, 'Sedang dijadwalkan penggantian.'],
            [1, 'Elektronik', 0, 'Lab Komputer', 'Kabel HDMI proyektor longgar sehingga layar sering putus.', 'approved', 20, 'Kabel sudah diganti.'],
            [2, 'Sanitasi', 5, 'Toilet Lt.2', 'Pintu bilik toilet tidak bisa dikunci.', 'pending', 9, null],
            [0, 'Listrik', null, 'Lab Komputer', 'Lampu di barisan tengah lab mati dua buah.', 'pending', 5, null],
            [3, 'Furnitur', 2, 'Kelas XI RPL', 'Meja guru goyang karena kaki meja patah.', 'pending', 2, null],
            [1, 'Elektronik', null, 'Kelas XII RPL B', 'Speaker kelas tidak bersuara.', 'pending', 0, null],
        ];

        foreach ($contoh as [$s, $kat, $sar, $lokasi, $desk, $status, $hari, $tanggapan]) {
            $tgl = now()->subDays($hari);

            Pengaduan::create([
                'user_id' => $siswa[$s]->id,
                'kategori_id' => $kategori[$kat]->id,
                'sarana_id' => $sar === null ? null : $sarana[$sar]->id,
                'lokasi' => $lokasi,
                'tanggal_kejadian' => $tgl->copy()->subDay()->toDateString(),
                'deskripsi' => $desk,
                'status' => $status,
                'tanggapan' => $tanggapan,
                'diverifikasi_oleh' => $status === 'pending' ? null : $admin->id,
                'diverifikasi_pada' => $status === 'pending' ? null : $tgl->copy()->addDay(),
                'created_at' => $tgl,
                'updated_at' => $tgl,
            ]);
        }

        // ---------- Teknisi demo + penugasan contoh (FASE 2) ----------
        $this->call(TeknisiDemoSeeder::class);
        $teknisi = User::where('role', User::ROLE_TEKNISI)->orderBy('id')->get();

        $disetujui = Pengaduan::where('status', Pengaduan::APPROVED)->orderBy('id')->get();
        $urutan = [
            Pengaduan::COMPLETED, Pengaduan::COMPLETED, Pengaduan::COMPLETED, Pengaduan::COMPLETED,
            Pengaduan::WAITING_CONFIRMATION, Pengaduan::IN_PROGRESS, Pengaduan::ASSIGNED,
        ];

        foreach ($disetujui as $i => $p) {
            $p->forceFill([
                'teknisi_id' => $teknisi[$i % $teknisi->count()]->id,
                'status_penanganan' => $urutan[$i] ?? Pengaduan::ASSIGNED,
                'ditugaskan_pada' => $p->created_at->copy()->addDays(2),
                'ditugaskan_oleh' => $admin->id,
                'progres' => match ($urutan[$i] ?? Pengaduan::ASSIGNED) {
                    Pengaduan::COMPLETED, Pengaduan::WAITING_CONFIRMATION => 100,
                    Pengaduan::IN_PROGRESS => 50,
                    default => 0,
                },
                'selesai_pada' => ($urutan[$i] ?? null) === Pengaduan::COMPLETED ? $p->created_at->copy()->addDays(6) : null,
            ])->save();
        }

        // ---------- Riwayat status & penugasan contoh (FASE 3) ----------
        $catat = function (Pengaduan $p, string $jenis, ?string $dari, string $ke, ?int $oleh, $waktu, ?string $catatan = null) {
            (new RiwayatStatus())->forceFill([
                'pengaduan_id' => $p->id, 'jenis' => $jenis, 'dari' => $dari, 'ke' => $ke,
                'user_id' => $oleh, 'catatan' => $catatan, 'created_at' => $waktu,
            ])->save();
        };

        foreach (Pengaduan::orderBy('id')->get() as $p) {
            $catat($p, RiwayatStatus::PENGAJUAN, null, Pengaduan::PENDING, $p->user_id, $p->created_at);

            if ($p->status !== Pengaduan::PENDING) {
                $catat($p, RiwayatStatus::VERIFIKASI, Pengaduan::PENDING, $p->status, $p->diverifikasi_oleh, $p->diverifikasi_pada, $p->tanggapan);
            }

            if ($p->teknisi_id) {
                (new PenugasanTeknisi())->forceFill([
                    'pengaduan_id' => $p->id, 'teknisi_id' => $p->teknisi_id, 'ditugaskan_oleh' => $p->ditugaskan_oleh,
                    'aktif' => true, 'created_at' => $p->ditugaskan_pada, 'updated_at' => $p->ditugaskan_pada,
                ])->save();

                $catat($p, RiwayatStatus::PENANGANAN, Pengaduan::NOT_ASSIGNED, Pengaduan::ASSIGNED, $p->ditugaskan_oleh, $p->ditugaskan_pada, 'Data contoh');

                if ($p->status_penanganan !== Pengaduan::ASSIGNED) {
                    $catat($p, RiwayatStatus::PENANGANAN, Pengaduan::ASSIGNED, $p->status_penanganan, $p->teknisi_id,
                        $p->selesai_pada ?? $p->ditugaskan_pada->copy()->addDays(2), 'Data contoh');
                }
            }
        }

        // ---------- Komentar & notifikasi contoh (FASE 6) ----------
        $contoh = Pengaduan::where('status_penanganan', Pengaduan::IN_PROGRESS)->first()
            ?? Pengaduan::where('status', Pengaduan::APPROVED)->first();

        if ($contoh) {
            foreach ([
                [$contoh->pelapor, 'Apakah perbaikannya bisa selesai sebelum Jumat? Ruangannya dipakai untuk ujian.'],
                [$contoh->teknisi ?? $teknisi[0], 'Siap, kami usahakan Kamis sore. Bahan sudah dipesan.'],
                [$admin, 'Terima kasih. Mohon kabari bila ada kendala.'],
            ] as [$penulis, $isi]) {
                KomentarPengaduan::create(['pengaduan_id' => $contoh->id, 'user_id' => $penulis->id, 'isi' => $isi]);
            }
        }

        foreach (Pengaduan::where('status', Pengaduan::APPROVED)->take(3)->get() as $p) {
            Notifikasi::create([
                'user_id' => $p->user_id, 'pengaduan_id' => $p->id, 'jenis' => 'verifikasi',
                'judul' => "Pengaduan {$p->kode()} disetujui", 'pesan' => 'Laporan Anda disetujui dan akan segera ditugaskan ke teknisi.',
                'url' => route('pengaduan.show', $p->id, false),
            ]);
        }

        foreach (Pengaduan::where('status_penanganan', Pengaduan::WAITING_CONFIRMATION)->get() as $p) {
            Notifikasi::create([
                'user_id' => $admin->id, 'pengaduan_id' => $p->id, 'jenis' => 'menunggu_validasi',
                'judul' => "Menunggu validasi {$p->kode()}", 'pesan' => 'Teknisi mengajukan penyelesaian pekerjaan.',
                'url' => route('admin.verifikasi', ['id' => $p->id], false),
            ]);
        }
    }
}
