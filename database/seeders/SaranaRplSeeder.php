<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Sarana;
use Illuminate\Database\Seeder;

class SaranaRplSeeder extends Seeder
{
    /**
     * Menambahkan data Sarana & Fasilitas realistis khusus SMK jurusan Rekayasa Perangkat Lunak (RPL)
     * serta memperkaya kategori kerusakan fasilitas sekolah.
     */
    public function run(): void
    {
        // 1. Kategori Kerusakan (mempertahankan yang lama + menambah kategori esensial kejuruan RPL)
        $kategoriTambahan = [
            'Listrik',
            'Air',
            'Furnitur',
            'Sanitasi',
            'Elektronik',
            'Bangunan',
            'Jaringan & Internet',
            'Perangkat Komputer / Hardware',
            'Audio Visual & Multimedia',
        ];

        foreach ($kategoriTambahan as $namaKat) {
            Kategori::firstOrCreate(['nama' => $namaKat]);
        }

        // 2. Data Sarana / Fasilitas Realistis SMK RPL (berdasarkan zona & ruangan nyata di SMK RPL)
        $daftarSarana = [
            // ===== Fasilitas Bawaan Awal (Tetap dipertahankan agar relasi lama aman) =====
            ['Proyektor', 'Lab Komputer'],
            ['AC', 'Lab Komputer'],
            ['Kursi Kelas', 'Kelas XI RPL'],
            ['Papan Tulis', 'Kelas XII RPL B'],
            ['Stop Kontak', 'Perpustakaan'],
            ['Keran Wastafel', 'Toilet Lt.2'],

            // ===== LAB RPL 1: Software & Web Development =====
            ['PC Client Siswa Unit 01-18 (Core i5, 16GB RAM)', 'Lab RPL 1 (Software Dev)'],
            ['PC Client Siswa Unit 19-36 (Core i5, 16GB RAM)', 'Lab RPL 1 (Software Dev)'],
            ['PC Master / Komputer Instruktur Lab', 'Lab RPL 1 (Software Dev)'],
            ['Proyektor LCD Epson & Screen Layar Gantung', 'Lab RPL 1 (Software Dev)'],
            ['Switch Hub 24-Port Gigabit Cisco/D-Link', 'Lab RPL 1 (Software Dev)'],
            ['Access Point WiFi High-Density Ruang Praktik', 'Lab RPL 1 (Software Dev)'],
            ['AC Split Inverter 2 PK (Depan)', 'Lab RPL 1 (Software Dev)'],
            ['AC Split Inverter 2 PK (Belakang)', 'Lab RPL 1 (Software Dev)'],
            ['Stop Kontak Jalur Meja Komputer Siswa', 'Lab RPL 1 (Software Dev)'],
            ['Whiteboard Kaca / Glassboard Presentasi Kode', 'Lab RPL 1 (Software Dev)'],
            ['Kursi Putar Ergonomis Lab Komputer Siswa', 'Lab RPL 1 (Software Dev)'],
            ['Kabel Jaringan UTP Cat6 & Jack RJ45 Meja Praktik', 'Lab RPL 1 (Software Dev)'],

            // ===== LAB RPL 2: Mobile App, Database & IoT =====
            ['PC Workstation Siswa Unit 01-18 (Core i7, 16GB RAM)', 'Lab RPL 2 (Mobile & DB)'],
            ['PC Workstation Siswa Unit 19-36 (Core i7, 16GB RAM)', 'Lab RPL 2 (Mobile & DB)'],
            ['Smart TV Presentasi & Review Source Code 55"', 'Lab RPL 2 (Mobile & DB)'],
            ['Proyektor Plafon HDMI & Screen Otomatis', 'Lab RPL 2 (Mobile & DB)'],
            ['Switch Hub 24-Port D-Link Gigabit Rackmount', 'Lab RPL 2 (Mobile & DB)'],
            ['Trainer Kit IoT & Microcontroller Box (ESP32/Arduino)', 'Lab RPL 2 (Mobile & DB)'],
            ['AC Split Inverter 2 PK', 'Lab RPL 2 (Mobile & DB)'],
            ['Stop Kontak Dinding & Jalur Stabilizer', 'Lab RPL 2 (Mobile & DB)'],
            ['Kursi Hidrolik Siswa Lab RPL', 'Lab RPL 2 (Mobile & DB)'],
            ['Rak Wallmount 9U & Patch Panel LAN', 'Lab RPL 2 (Mobile & DB)'],

            // ===== RUANG TEACHING FACTORY (TeFa RPL / Software House SMK) =====
            ['Workstation PC High-End Desain UI/UX & Fullstack', 'Ruang TeFa Software House'],
            ['Monitor Dual Display 24 Inch IPS Frameless', 'Ruang TeFa Software House'],
            ['Pen Display / Drawing Tablet Desainer Digital', 'Ruang TeFa Software House'],
            ['Smart Interactive Whiteboard 65 Inch', 'Ruang TeFa Software House'],
            ['Meja Kolaboratif Rapat Scrum / Tim Agile', 'Ruang TeFa Software House'],
            ['Papan Kanban Kaca (Sprint & Task Board)', 'Ruang TeFa Software House'],
            ['AC Split 1.5 PK Daikin', 'Ruang TeFa Software House'],
            ['Sofa Tamu & Meja Presentasi Project Klien', 'Ruang TeFa Software House'],

            // ===== RUANG SERVER & NOC (Network Operation Center) SEKOLAH =====
            ['Rak Server Rackmount 19 Inch 42U Tertutup', 'Ruang Server & NOC'],
            ['Server Utama SIAKAD & Database (Dell PowerEdge)', 'Ruang Server & NOC'],
            ['UPS Online Rackmount 3000VA (APC Smart-UPS)', 'Ruang Server & NOC'],
            ['Core Router Gateway Internet (MikroTik Cloud Core CCR)', 'Ruang Server & NOC'],
            ['Managed Distribution Switch 48-Port Gigabit PoE', 'Ruang Server & NOC'],
            ['AC Server Room Dingin 24 Jam 1.5 PK', 'Ruang Server & NOC'],
            ['APAR Tabung Gas Clean Agent CO2 Ruang IT', 'Ruang Server & NOC'],
            ['IP Camera CCTV Monitoring Ruang Server', 'Ruang Server & NOC'],

            // ===== RUANG KELAS TEORI (X, XI, XII RPL) =====
            ['Meja & Kursi Siswa Standar Sekolah (36 Set)', 'Kelas X RPL A'],
            ['Papan Tulis Whiteboard Magnetik 120x240cm', 'Kelas X RPL A'],
            ['Kipas Angin Dinding Tornado Wall Fan', 'Kelas X RPL A'],
            ['Stop Kontak Dinding Charger Laptop Siswa', 'Kelas X RPL A'],

            ['Meja & Kursi Siswa Standar Sekolah (36 Set)', 'Kelas XI RPL A'],
            ['Proyektor Plafon Gantung & Kabel HDMI Wall Plate', 'Kelas XI RPL A'],
            ['Papan Tulis Whiteboard Magnetik 120x240cm', 'Kelas XI RPL A'],
            ['Stop Kontak Jalur Baris Belakang', 'Kelas XI RPL B'],
            ['Kipas Angin Dinding Tornado Wall Fan', 'Kelas XI RPL B'],

            ['Meja & Kursi Siswa Standar Sekolah (36 Set)', 'Kelas XII RPL A'],
            ['Proyektor Plafon Gantung & Layar Proyektor', 'Kelas XII RPL A'],
            ['AC Split 1.5 PK Ruang Teori', 'Kelas XII RPL B'],
            ['Speaker Dinding Pengumuman Sekolah', 'Kelas XII RPL B'],

            // ===== RUANG GURU & KETUA PROGRAM KEAHLIAN RPL =====
            ['PC Desktop All-in-One Guru RPL', 'Ruang Guru Kejuruan RPL'],
            ['Printer Laserjet Multifungsi (Print/Scan/Copy A4-F4)', 'Ruang Guru Kejuruan RPL'],
            ['AC Split 1.5 PK', 'Ruang Guru Kejuruan RPL'],
            ['Meja Kerja Guru & Kursi Kantor Putar', 'Ruang Guru Kejuruan RPL'],
            ['Dispenser Galon Air Minum Panas & Dingin', 'Ruang Guru Kejuruan RPL'],
            ['Lemari Besi Arsip & Berkas Uji Kompetensi (UKK)', 'Ruang Guru Kejuruan RPL'],

            // ===== PERPUSTAKAAN & RUANG LITERASI DIGITAL =====
            ['PC Terminal Pencarian OPAC / E-Library Siswa', 'Perpustakaan Lt.1'],
            ['Barcode Scanner Buku & Kartu Anggota', 'Perpustakaan Lt.1'],
            ['Stop Kontak Meja Belajar Mandiri Siswa', 'Perpustakaan Lt.1'],
            ['AC Split 2 PK Ruang Baca', 'Perpustakaan Lt.1'],
            ['Rak Buku Baja Koleksi Rekayasa Perangkat Lunak', 'Perpustakaan Lt.1'],

            // ===== SANITASI & FASILITAS UMUM GEDUNG RPL =====
            ['Keran Wastafel Cuci Tangan Otomatis', 'Toilet Siswa Gedung RPL Lt.1'],
            ['Kloset Duduk & Jet Shower Semprot', 'Toilet Siswa Gedung RPL Lt.1'],
            ['Pintu PVC & Slot Kunci Bilik Toilet', 'Toilet Siswa Gedung RPL Lt.1'],
            ['Keran Wastafel Cuci Tangan', 'Toilet Siswa Gedung RPL Lt.2'],
            ['Kloset Jongkok & Keran Bak Air Mandi', 'Toilet Siswa Gedung RPL Lt.2'],
            ['Pintu PVC & Slot Kunci Bilik Toilet', 'Toilet Siswa Gedung RPL Lt.2'],
            ['Toren Penampungan & Pompa Air Pendorong Otomatis', 'Rooftop Gedung RPL'],
            ['Lampu LED Koridor Gedung Lantai 1 & Lantai 2', 'Koridor Gedung RPL'],
        ];

        foreach ($daftarSarana as [$nama, $lokasi]) {
            Sarana::firstOrCreate([
                'nama' => $nama,
                'lokasi' => $lokasi,
            ]);
        }
    }
}
