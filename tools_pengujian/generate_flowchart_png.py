import os
import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

html_content = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Flowchart Sistem ADUKANAJA</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap');
  
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #0f172a;
    color: #1e293b;
    padding: 30px;
    display: flex;
    justify-content: center;
  }
  
  .container {
    width: 1400px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    border: 1px solid #e2e8f0;
  }
  
  /* HEADER BANNER */
  .header {
    background: linear-gradient(135deg, #0A2463 0%, #1e3a8a 50%, #1d4ed8 100%);
    color: white;
    padding: 32px 40px;
    border-bottom: 4px solid #3b82f6;
    position: relative;
  }
  
  .badge-row {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
  }
  
  .badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: 1px solid rgba(255, 255, 255, 0.25);
  }
  
  .badge-accent {
    background: #38bdf8;
    color: #0f172a;
    border: none;
  }
  
  .title {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 6px;
  }
  
  .subtitle {
    font-size: 13.5px;
    color: #bfdbfe;
    line-height: 1.5;
  }
  
  /* LEGEND BAR */
  .legend-bar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
  }
  
  .legend-items {
    display: flex;
    gap: 24px;
    align-items: center;
  }
  
  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
  }
  
  .symbol-box {
    width: 24px;
    height: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid #475569;
    font-size: 9px;
    font-weight: 700;
  }
  .sym-term { border-radius: 8px; background: #e0e7ff; border-color: #4338ca; }
  .sym-proc { background: #f1f5f9; border-color: #334155; }
  .sym-dec  { width: 18px; height: 18px; transform: rotate(45deg); background: #fef3c7; border-color: #b45309; }
  .sym-io   { transform: skewX(-15deg); background: #e0f2fe; border-color: #0284c7; }
  
  .actor-tags {
    display: flex;
    gap: 16px;
  }
  
  .actor-tag {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 11.5px;
  }
  .dot { width: 10px; height: 10px; border-radius: 50%; }
  .dot-siswa { background: #2563eb; }
  .dot-admin { background: #7c3aed; }
  .dot-teknisi { background: #059669; }
  
  /* CANVAS DIAGRAM */
  .diagram-wrapper {
    padding: 30px 30px 40px 30px;
    background: #ffffff;
  }
  
  svg {
    width: 100%;
    height: auto;
    display: block;
  }
  
  /* SVG STYLES */
  .swimlane-header {
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.5px;
    fill: #ffffff;
  }
  
  .node-text-title {
    font-size: 11.5px;
    font-weight: 700;
    text-anchor: middle;
  }
  
  .node-text-sub {
    font-size: 9.5px;
    font-weight: 500;
    text-anchor: middle;
    fill: #64748b;
  }
  
  .conn-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
  }
  
  .footer-note {
    background: #f1f5f9;
    padding: 16px 40px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
  }
</style>
</head>
<body>

<div class="container">
  <!-- HEADER -->
  <div class="header">
    <div class="badge-row">
      <span class="badge badge-accent">Mapel KK-RPL</span>
      <span class="badge">Elemen: Pemrograman Teks, Grafik & Multimedia</span>
      <span class="badge">KUK: Algoritma Dijelaskan Cara Kerjanya</span>
    </div>
    <h1 class="title">FLOWCHART SISTEM PENGADUAN SARANA SEKOLAH (ADUKANAJA)</h1>
    <p class="subtitle">
      Visualisasi Algoritma State-Machine & Multi-Role Workflow: Siswa (Pengaju Kerusakan & Media), Admin (Verifikator & Otorisasi), Teknisi (Eksekutor & Dokumentasi Perbaikan).
    </p>
  </div>
  
  <!-- LEGEND -->
  <div class="legend-bar">
    <div class="legend-items">
      <span style="font-weight:700; color:#0f172a;">Simbol Standar ISO/ANSI:</span>
      <div class="legend-item"><span class="symbol-box sym-term"></span> Terminator (Mulai / Selesai)</div>
      <div class="legend-item"><span class="symbol-box sym-io"></span> Input / Output</div>
      <div class="legend-item"><span class="symbol-box sym-proc"></span> Proses</div>
      <div class="legend-item"><span class="symbol-box sym-dec"></span> Decision (Percabangan)</div>
    </div>
    <div class="actor-tags">
      <div class="actor-tag"><span class="dot dot-siswa"></span> Swimlane Siswa</div>
      <div class="actor-tag"><span class="dot dot-admin"></span> Swimlane Admin</div>
      <div class="actor-tag"><span class="dot dot-teknisi"></span> Swimlane Teknisi</div>
    </div>
  </div>

  <!-- DIAGRAM AREA -->
  <div class="diagram-wrapper">
    <svg viewBox="0 0 1340 1820" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <!-- Marker Arrows -->
        <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1 L 10 5 L 0 9 z" fill="#334155" />
        </marker>
        <marker id="arrow-green" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1 L 10 5 L 0 9 z" fill="#16a34a" />
        </marker>
        <marker id="arrow-red" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1 L 10 5 L 0 9 z" fill="#dc2626" />
        </marker>
        <marker id="arrow-blue" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1 L 10 5 L 0 9 z" fill="#2563eb" />
        </marker>

        <!-- Drop Shadows -->
        <filter id="shadow" x="-5%" y="-5%" width="110%" height="115%" filterUnits="userSpaceOnUse">
          <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#0f172a" flood-opacity="0.08"/>
        </filter>
        <filter id="shadow-dec" x="-10%" y="-10%" width="120%" height="120%" filterUnits="userSpaceOnUse">
          <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#0f172a" flood-opacity="0.10"/>
        </filter>
      </defs>

      <!-- ================= SWIMLANES ================= -->
      <!-- Swimlane 1: SISWA -->
      <rect x="20" y="20" width="410" height="1780" rx="14" fill="#f0f7ff" stroke="#bfdbfe" stroke-width="1.5" />
      <rect x="20" y="20" width="410" height="50" rx="14" fill="#2563eb" />
      <rect x="20" y="55" width="410" height="15" fill="#2563eb" />
      <text x="225" y="52" class="swimlane-header" text-anchor="middle">1. SISWA (PELAPOR SARANA)</text>

      <!-- Swimlane 2: ADMIN -->
      <rect x="465" y="20" width="410" height="1780" rx="14" fill="#faf5ff" stroke="#e9d5ff" stroke-width="1.5" />
      <rect x="465" y="20" width="410" height="50" rx="14" fill="#7c3aed" />
      <rect x="465" y="55" width="410" height="15" fill="#7c3aed" />
      <text x="670" y="52" class="swimlane-header" text-anchor="middle">2. ADMIN (VERIFIKASI & VALIDASI)</text>

      <!-- Swimlane 3: TEKNISI -->
      <rect x="910" y="20" width="410" height="1780" rx="14" fill="#f0fdf4" stroke="#bbf7d0" stroke-width="1.5" />
      <rect x="910" y="20" width="410" height="50" rx="14" fill="#059669" />
      <rect x="910" y="55" width="410" height="15" fill="#059669" />
      <text x="1115" y="52" class="swimlane-header" text-anchor="middle">3. TEKNISI (PENANGANAN LAPANGAN)</text>


      <!-- ================= STEP 1: START (SISWA) ================= -->
      <!-- Terminator: Mulai -->
      <rect x="155" y="95" width="140" height="42" rx="21" fill="#2563eb" filter="url(#shadow)"/>
      <text x="225" y="121" font-size="13" font-weight="800" fill="#ffffff" text-anchor="middle">MULAI</text>

      <!-- Arrow: Start -> Input Form -->
      <line x1="225" y1="137" x2="225" y2="175" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Input/Output: Form Pengaduan -->
      <polygon points="120,175 350,175 330,235 100,235" fill="#ffffff" stroke="#2563eb" stroke-width="2" filter="url(#shadow)" />
      <text x="225" y="200" class="node-text-title" fill="#1e3a8a">Input Data Pengaduan</text>
      <text x="225" y="218" class="node-text-sub">Kategori, Lokasi, Deskripsi, Tgl</text>

      <!-- Arrow: Input Form -> Upload Foto -->
      <line x1="225" y1="235" x2="225" y2="275" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Input/Output: Upload Foto Kerusakan (Multimedia) -->
      <polygon points="120,275 350,275 330,335 100,335" fill="#ffffff" stroke="#2563eb" stroke-width="2" filter="url(#shadow)" />
      <text x="225" y="300" class="node-text-title" fill="#1e3a8a">Unggah Foto Kerusakan</text>
      <text x="225" y="318" class="node-text-sub">Multimedia (JPG/PNG/WEBP < 2MB)</text>

      <!-- Arrow: Upload -> Decision Validasi -->
      <line x1="225" y1="335" x2="225" y2="375" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Decision 1: Validasi Form & File -->
      <polygon points="225,375 335,425 225,475 115,425" fill="#fef3c7" stroke="#b45309" stroke-width="2" filter="url(#shadow-dec)" />
      <text x="225" y="422" class="node-text-title" fill="#78350f">Data & File</text>
      <text x="225" y="437" class="node-text-title" fill="#78350f">Valid?</text>

      <!-- Decision 1 Branch NO -> Back to Input Form -->
      <path d="M 115 425 L 60 425 L 60 205 L 105 205" fill="none" stroke="#dc2626" stroke-width="2" stroke-dasharray="4,3" marker-end="url(#arrow-red)" />
      <text x="70" y="415" class="conn-label" fill="#dc2626">Tidak (Error)</text>

      <!-- Decision 1 Branch YES -> Process Simpan DB -->
      <line x1="225" y1="475" x2="225" y2="520" stroke="#16a34a" stroke-width="2" marker-end="url(#arrow-green)" />
      <text x="235" y="500" class="conn-label" fill="#16a34a">Ya</text>

      <!-- Process: Simpan Database & Generate Tiket -->
      <rect x="110" y="520" width="230" height="60" rx="8" fill="#ffffff" stroke="#334155" stroke-width="2" filter="url(#shadow)" />
      <text x="225" y="546" class="node-text-title" fill="#0f172a">Simpan ke DB & Tiket #00XX</text>
      <text x="225" y="564" class="node-text-sub">status: Pending | not_assigned</text>

      <!-- Cross-Lane Arrow: Siswa -> Admin (Notifikasi) -->
      <path d="M 340 550 L 550 550" fill="none" stroke="#2563eb" stroke-width="2.5" marker-end="url(#arrow-blue)" />
      <text x="445" y="540" class="conn-label" fill="#2563eb" text-anchor="middle">Notifikasi</text>


      <!-- ================= STEP 2: VERIFIKASI (ADMIN) ================= -->
      <!-- Process: Admin Meninjau -->
      <rect x="550" y="520" width="240" height="60" rx="8" fill="#ffffff" stroke="#7c3aed" stroke-width="2" filter="url(#shadow)" />
      <text x="670" y="546" class="node-text-title" fill="#5b21b6">Admin Membuka Pengaduan</text>
      <text x="670" y="564" class="node-text-sub">Cek Deskripsi, Lokasi & Foto</text>

      <!-- Arrow -> Decision 2 Verifikasi -->
      <line x1="670" y1="580" x2="670" y2="625" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Decision 2: Verifikasi Setujui/Tolak -->
      <polygon points="670,625 780,675 670,725 560,675" fill="#fef3c7" stroke="#b45309" stroke-width="2" filter="url(#shadow-dec)" />
      <text x="670" y="672" class="node-text-title" fill="#78350f">Pengaduan</text>
      <text x="670" y="687" class="node-text-title" fill="#78350f">Disetujui?</text>

      <!-- Decision 2 Branch NO -> Tolak Pengaduan -->
      <path d="M 560 675 L 485 675 L 485 750" fill="none" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow-red)" />
      <text x="515" y="665" class="conn-label" fill="#dc2626">Tolak</text>

      <!-- Process: Status Rejected -->
      <rect x="440" y="750" width="160" height="55" rx="8" fill="#fee2e2" stroke="#dc2626" stroke-width="1.5" filter="url(#shadow)" />
      <text x="520" y="774" class="node-text-title" fill="#991b1b">status: Rejected</text>
      <text x="520" y="792" class="node-text-sub" fill="#b91c1c">+ Catatan Penolakan</text>

      <!-- Rejected -> Notifikasi Siswa -> End Tolak -->
      <path d="M 440 780 L 320 780 L 320 830" fill="none" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow-red)" />
      <text x="365" y="772" class="conn-label" fill="#dc2626">Notifikasi</text>

      <!-- IO Siswa: Laporan Ditolak -->
      <polygon points="210,830 380,830 360,880 190,880" fill="#fee2e2" stroke="#dc2626" stroke-width="1.5" filter="url(#shadow)" />
      <text x="285" y="853" class="node-text-title" fill="#991b1b">Siswa Lihat Alasan</text>
      <text x="285" y="869" class="node-text-sub" fill="#b91c1c">Pengaduan Selesai (Ditolak)</text>

      <line x1="285" y1="880" x2="285" y2="915" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />
      <rect x="235" y="915" width="100" height="34" rx="17" fill="#64748b" filter="url(#shadow)"/>
      <text x="285" y="937" font-size="11" font-weight="700" fill="#ffffff" text-anchor="middle">SELESAI</text>

      <!-- Decision 2 Branch YES -> Setujui & Tugaskan Teknisi -->
      <line x1="670" y1="725" x2="670" y2="765" stroke="#16a34a" stroke-width="2" marker-end="url(#arrow-green)" />
      <text x="680" y="745" class="conn-label" fill="#16a34a">Setuju (Approved)</text>

      <!-- Process: Tugaskan Teknisi -->
      <rect x="550" y="765" width="240" height="60" rx="8" fill="#ffffff" stroke="#7c3aed" stroke-width="2" filter="url(#shadow)" />
      <text x="670" y="790" class="node-text-title" fill="#5b21b6">Pilih & Tugaskan Teknisi</text>
      <text x="670" y="808" class="node-text-sub">status_penanganan: Assigned</text>

      <!-- Cross-Lane Arrow: Admin -> Teknisi (Notifikasi Penugasan) -->
      <path d="M 790 795 L 995 795" fill="none" stroke="#7c3aed" stroke-width="2.5" marker-end="url(#arrow)" />
      <text x="890" y="785" class="conn-label" fill="#7c3aed" text-anchor="middle">Notifikasi Tugas</text>


      <!-- ================= STEP 3: TEKNISI ================= -->
      <!-- IO: Notifikasi Masuk Teknisi -->
      <polygon points="1010,765 1240,765 1220,825 990,825" fill="#ffffff" stroke="#059669" stroke-width="2" filter="url(#shadow)" />
      <text x="1115" y="790" class="node-text-title" fill="#065f46">Terima Notifikasi Tugas</text>
      <text x="1115" y="808" class="node-text-sub">Lihat Detail Kerusakan</text>

      <!-- Arrow -> Decision 3 Terima / Tolak -->
      <line x1="1115" y1="825" x2="1115" y2="870" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Decision 3: Teknisi Terima Tugas? -->
      <polygon points="1115,870 1225,920 1115,970 1005,920" fill="#fef3c7" stroke="#b45309" stroke-width="2" filter="url(#shadow-dec)" />
      <text x="1115" y="917" class="node-text-title" fill="#78350f">Teknisi</text>
      <text x="1115" y="932" class="node-text-title" fill="#78350f">Terima?</text>

      <!-- Decision 3 Branch NO -> Tolak Tugas -> Kembalikan ke Admin -->
      <path d="M 1005 920 L 890 920 L 890 815 L 795 815" fill="none" stroke="#dc2626" stroke-width="2" stroke-dasharray="4,3" marker-end="url(#arrow-red)" />
      <text x="915" y="910" class="conn-label" fill="#dc2626">Tolak (+alasan)</text>

      <!-- Decision 3 Branch YES -> Terima & Mulai Pengerjaan -->
      <line x1="1115" y1="970" x2="1115" y2="1015" stroke="#16a34a" stroke-width="2" marker-end="url(#arrow-green)" />
      <text x="1125" y="995" class="conn-label" fill="#16a34a">Terima (Accepted)</text>

      <!-- Process: Mulai Perbaikan -->
      <rect x="1000" y="1015" width="230" height="60" rx="8" fill="#ffffff" stroke="#059669" stroke-width="2" filter="url(#shadow)" />
      <text x="1115" y="1040" class="node-text-title" fill="#065f46">Mulai Pekerjaan Perbaikan</text>
      <text x="1115" y="1058" class="node-text-sub">status_penanganan: In Progress</text>

      <!-- Arrow -> Process Upload Bukti -->
      <line x1="1115" y1="1075" x2="1115" y2="1120" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- IO: Upload Foto Bukti Perbaikan -->
      <polygon points="1010,1120 1240,1120 1220,1180 990,1180" fill="#ffffff" stroke="#059669" stroke-width="2" filter="url(#shadow)" />
      <text x="1115" y="1145" class="node-text-title" fill="#065f46">Unggah Foto Bukti Selesai</text>
      <text x="1115" y="1163" class="node-text-sub">Multimedia (Foto Sesudah Perbaikan)</text>

      <!-- Arrow -> Decision 4 Foto Terunggah? -->
      <line x1="1115" y1="1180" x2="1115" y2="1225" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Decision 4: Foto Bukti Terunggah? -->
      <polygon points="1115,1225 1225,1275 1115,1325 1005,1275" fill="#fef3c7" stroke="#b45309" stroke-width="2" filter="url(#shadow-dec)" />
      <text x="1115" y="1272" class="node-text-title" fill="#78350f">Foto Sesudah</text>
      <text x="1115" y="1287" class="node-text-title" fill="#78350f">Terunggah?</text>

      <!-- Decision 4 Branch NO -> Wajib Upload Foto -->
      <path d="M 1225 1275 L 1280 1275 L 1280 1150 L 1235 1150" fill="none" stroke="#dc2626" stroke-width="2" stroke-dasharray="4,3" marker-end="url(#arrow-red)" />
      <text x="1235" y="1265" class="conn-label" fill="#dc2626">Belum (Ditolak Sistem)</text>

      <!-- Decision 4 Branch YES -> Ajukan Selesai -->
      <line x1="1115" y1="1325" x2="1115" y2="1370" stroke="#16a34a" stroke-width="2" marker-end="url(#arrow-green)" />
      <text x="1125" y="1350" class="conn-label" fill="#16a34a">Sudah</text>

      <!-- Process: Ajukan Konfirmasi Selesai -->
      <rect x="990" y="1370" width="250" height="60" rx="8" fill="#ffffff" stroke="#059669" stroke-width="2" filter="url(#shadow)" />
      <text x="1115" y="1395" class="node-text-title" fill="#065f46">Ajukan Konfirmasi Penyelesaian</text>
      <text x="1115" y="1413" class="node-text-sub">status: waiting_confirmation</text>

      <!-- Cross-Lane Arrow: Teknisi -> Admin (Validasi Hasil) -->
      <path d="M 990 1400 L 795 1400" fill="none" stroke="#059669" stroke-width="2.5" marker-end="url(#arrow)" />
      <text x="890" y="1390" class="conn-label" fill="#059669" text-anchor="middle">Minta Validasi</text>


      <!-- ================= STEP 4: VALIDASI AKHIR (ADMIN) ================= -->
      <!-- Process: Admin Cek Hasil -->
      <rect x="550" y="1370" width="240" height="60" rx="8" fill="#ffffff" stroke="#7c3aed" stroke-width="2" filter="url(#shadow)" />
      <text x="670" y="1395" class="node-text-title" fill="#5b21b6">Admin Validasi Hasil Lapangan</text>
      <text x="670" y="1413" class="node-text-sub">Bandingkan Foto Sebelum & Sesudah</text>

      <!-- Arrow -> Decision 5 Hasil Selesai? -->
      <line x1="670" y1="1430" x2="670" y2="1475" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Decision 5: Apakah Hasil Sesuai? -->
      <polygon points="670,1475 780,1525 670,1575 560,1525" fill="#fef3c7" stroke="#b45309" stroke-width="2" filter="url(#shadow-dec)" />
      <text x="670" y="1522" class="node-text-title" fill="#78350f">Hasil Perbaikan</text>
      <text x="670" y="1537" class="node-text-title" fill="#78350f">Tuntas & Sesuai?</text>

      <!-- Decision 5 Branch NO -> Minta Revisi -> Balik ke Teknisi -->
      <path d="M 780 1525 L 940 1525 L 940 1045 L 995 1045" fill="none" stroke="#d97706" stroke-width="2.5" stroke-dasharray="5,4" marker-end="url(#arrow)" />
      <text x="860" y="1515" class="conn-label" fill="#d97706" text-anchor="middle">Minta Revisi (+Catatan)</text>

      <!-- Decision 5 Branch YES -> Nyatakan Selesai (Completed) -->
      <line x1="670" y1="1575" x2="670" y2="1620" stroke="#16a34a" stroke-width="2" marker-end="url(#arrow-green)" />
      <text x="680" y="1600" class="conn-label" fill="#16a34a">Ya (Selesai)</text>

      <!-- Process: Final State Completed -->
      <rect x="545" y="1620" width="250" height="60" rx="8" fill="#ecfdf5" stroke="#16a34a" stroke-width="2" filter="url(#shadow)" />
      <text x="670" y="1645" class="node-text-title" fill="#065f46">Update status_penanganan: Completed</text>
      <text x="670" y="1663" class="node-text-sub" fill="#047857">Kunci Tiket & Catat Waktu Selesai</text>

      <!-- Cross-Lane Arrow: Admin -> Siswa (Notifikasi Selesai) -->
      <path d="M 545 1650 L 335 1650" fill="none" stroke="#16a34a" stroke-width="2.5" marker-end="url(#arrow-green)" />
      <text x="440" y="1640" class="conn-label" fill="#16a34a" text-anchor="middle">Notifikasi Selesai</text>


      <!-- ================= STEP 5: SELESAI (SISWA) ================= -->
      <!-- IO: Siswa Melihat Hasil -->
      <polygon points="120,1620 340,1620 320,1680 100,1680" fill="#ffffff" stroke="#2563eb" stroke-width="2" filter="url(#shadow)" />
      <text x="220" y="1645" class="node-text-title" fill="#1e3a8a">Lihat Status: Selesai</text>
      <text x="220" y="1663" class="node-text-sub">Cek Foto Hasil & Timeline Riwayat</text>

      <!-- Arrow -> Terminator Selesai Final -->
      <line x1="220" y1="1680" x2="220" y2="1725" stroke="#334155" stroke-width="2" marker-end="url(#arrow)" />

      <!-- Terminator: SELESAI -->
      <rect x="150" y="1725" width="140" height="42" rx="21" fill="#16a34a" filter="url(#shadow)"/>
      <text x="220" y="1751" font-size="13" font-weight="800" fill="#ffffff" text-anchor="middle">SELESAI</text>

    </svg>
  </div>

  <!-- FOOTER -->
  <div class="footer-note">
    <span>Diagram Alir Algoritma Sistem Pengaduan & Penanganan Sarana Sekolah (ADUKANAJA)</span>
    <span>SMK Rekayasa Perangkat Lunak &bull; Framework Laravel 12 &bull; Standar Diagram ISO/ANSI 5807</span>
  </div>
</div>

</body>
</html>
"""

html_path = os.path.abspath("flowchart_adukanaja.html")
with open(html_path, "w", encoding="utf-8") as f:
    f.write(html_content)

print(f"HTML saved to {html_path}")

from selenium.webdriver.chrome.service import Service

# Render to PNG using Selenium Chrome
chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--force-device-scale-factor=2")  # High DPI / 2x Retina quality
chrome_options.add_argument("--window-size=1500,2200")

driver_path = r"C:\Users\windows\.cache\selenium\chromedriver\win64\157.0.8103.78\chromedriver.exe"
service = Service(executable_path=driver_path)
driver = webdriver.Chrome(service=service, options=chrome_options)
try:
    driver.get(f"file:///{html_path.replace(os.sep, '/')}")
    time.sleep(2)  # Wait for webfonts to render
    
    # Locate container
    container = driver.find_element("css selector", ".container")
    
    # Output file paths
    png_root = os.path.abspath("flowchart_adukanaja.png")
    png_docs = os.path.abspath("docs/flowchart_adukanaja.png")
    
    container.screenshot(png_root)
    container.screenshot(png_docs)
    print(f"Screenshots saved successfully:\n1. {png_root}\n2. {png_docs}")
finally:
    driver.quit()
