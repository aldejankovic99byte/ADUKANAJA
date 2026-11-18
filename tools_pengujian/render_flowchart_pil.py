import os
import math
from PIL import Image, ImageDraw, ImageFont

def create_flowchart_png():
    width = 1600
    height = 2550
    
    # Create image with dark slate background
    img = Image.new("RGB", (width, height), "#0f172a")
    draw = ImageDraw.Draw(img)
    
    # Fonts
    font_bold = "C:/Windows/Fonts/segoeuib.ttf"
    font_regular = "C:/Windows/Fonts/segoeui.ttf"
    font_semibold = "C:/Windows/Fonts/segoeuisl.ttf"
    
    f_title = ImageFont.truetype(font_bold, 30)
    f_badge = ImageFont.truetype(font_bold, 13)
    f_sub = ImageFont.truetype(font_regular, 16)
    f_lane_hdr = ImageFont.truetype(font_bold, 18)
    f_node_title = ImageFont.truetype(font_bold, 14)
    f_node_sub = ImageFont.truetype(font_regular, 12)
    f_decision = ImageFont.truetype(font_bold, 13)
    f_label = ImageFont.truetype(font_bold, 12)
    f_footer = ImageFont.truetype(font_regular, 13)
    f_box_hdr = ImageFont.truetype(font_bold, 15)
    f_box_txt = ImageFont.truetype(font_regular, 13)

    # 1. White Canvas Card
    card_x1, card_y1 = 30, 30
    card_x2, card_y2 = width - 30, height - 30
    draw.rounded_rectangle([card_x1, card_y1, card_x2, card_y2], radius=24, fill="#ffffff", outline="#cbd5e1", width=2)
    
    # 2. Header Banner (Navy Gradient effect)
    hdr_h = 130
    draw.rounded_rectangle([card_x1, card_y1, card_x2, card_y1 + hdr_h], radius=24, fill="#0A2463")
    draw.rectangle([card_x1, card_y1 + hdr_h - 24, card_x2, card_y1 + hdr_h], fill="#0A2463")
    draw.line([card_x1, card_y1 + hdr_h, card_x2, card_y1 + hdr_h], fill="#2563eb", width=4)
    
    # Badges
    badges = [
        ("MAPEL: KK-RPL", "#38bdf8", "#0f172a"),
        ("ELEMEN: PEMROGRAMAN TEKS, GRAFIK & MULTIMEDIA", "#1e3a8a", "#ffffff"),
        ("FOKUS: KUK ALGORITMA DIJELASKAN CARA KERJANYA", "#1e3a8a", "#ffffff")
    ]
    cur_bx = card_x1 + 40
    for txt, bg_col, fg_col in badges:
        bbox = f_badge.getbbox(txt)
        bw = bbox[2] - bbox[0] + 20
        draw.rounded_rectangle([cur_bx, card_y1 + 18, cur_bx + bw, card_y1 + 42], radius=12, fill=bg_col)
        draw.text((cur_bx + 10, card_y1 + 22), txt, font=f_badge, fill=fg_col)
        cur_bx += bw + 12

    # Title & Subtitle
    draw.text((card_x1 + 40, card_y1 + 52), "FLOWCHART SISTEM PENGADUAN SARANA SEKOLAH (ADUKANAJA)", font=f_title, fill="#ffffff")
    draw.text((card_x1 + 40, card_y1 + 94), "Visualisasi Standar ISO/ANSI: Logika Validasi Multimedia, State Workflow 3 Role, dan Transaksi Audit Trail Database", font=f_sub, fill="#93c5fd")

    # 3. Legend Bar
    leg_y = card_y1 + hdr_h + 12
    draw.rectangle([card_x1 + 40, leg_y, card_x2 - 40, leg_y + 44], fill="#f8fafc", outline="#e2e8f0", width=1)
    
    # Symbols in Legend
    leg_x = card_x1 + 55
    draw.text((leg_x, leg_y + 13), "Simbol Standar ISO/ANSI:", font=f_label, fill="#0f172a")
    leg_x += 165
    
    # Terminator symbol
    draw.rounded_rectangle([leg_x, leg_y + 12, leg_x + 30, leg_y + 32], radius=10, fill="#dbeafe", outline="#1d4ed8", width=1)
    draw.text((leg_x + 36, leg_y + 13), "Terminator (Mulai/Selesai)", font=f_footer, fill="#334155")
    leg_x += 205
    
    # IO Parallelogram
    draw.polygon([(leg_x + 5, leg_y + 12), (leg_x + 30, leg_y + 12), (leg_x + 25, leg_y + 32), (leg_x, leg_y + 32)], fill="#e0e7ff", outline="#4338ca", width=1)
    draw.text((leg_x + 36, leg_y + 13), "Input / Output (Data & Foto)", font=f_footer, fill="#334155")
    leg_x += 225

    # Process Rectangle
    draw.rectangle([leg_x, leg_y + 12, leg_x + 30, leg_y + 32], fill="#f1f5f9", outline="#334155", width=1)
    draw.text((leg_x + 36, leg_y + 13), "Proses (Database / Eksekusi)", font=f_footer, fill="#334155")
    leg_x += 225

    # Decision Diamond
    draw.polygon([(leg_x + 15, leg_y + 10), (leg_x + 30, leg_y + 22), (leg_x + 15, leg_y + 34), (leg_x, leg_y + 22)], fill="#fef3c7", outline="#b45309", width=1)
    draw.text((leg_x + 36, leg_y + 13), "Decision (Percabangan Kondisi)", font=f_footer, fill="#334155")

    # 4. Swimlanes Setup
    top_lane_y = leg_y + 58
    lane_bottom_y = card_y2 - 260
    
    l1_x1, l1_x2 = card_x1 + 40, card_x1 + 510     # Siswa (w=470)
    l2_x1, l2_x2 = card_x1 + 535, card_x1 + 1005   # Admin (w=470)
    l3_x1, l3_x2 = card_x1 + 1030, card_x2 - 40    # Teknisi (w=470)
    
    lanes = [
        (l1_x1, l1_x2, "1. SISWA (PELAPOR KERUSAKAN)", "#EFF6FF", "#BFDBFE", "#2563EB", 275),
        (l2_x1, l2_x2, "2. ADMIN (VERIFIKASI & OTORISASI)", "#FAF5FF", "#E9D5FF", "#7C3AED", 770),
        (l3_x1, l3_x2, "3. TEKNISI (PENANGANAN LAPANGAN)", "#F0FDF4", "#BBF7D0", "#059669", 1265)
    ]
    
    for lx1, lx2, ltitle, lbg, lborder, lhdr_col, lcenter in lanes:
        # Lane Body
        draw.rounded_rectangle([lx1, top_lane_y, lx2, lane_bottom_y], radius=16, fill=lbg, outline=lborder, width=2)
        # Lane Header
        draw.rounded_rectangle([lx1, top_lane_y, lx2, top_lane_y + 44], radius=16, fill=lhdr_col)
        draw.rectangle([lx1, top_lane_y + 22, lx2, top_lane_y + 44], fill=lhdr_col)
        
        # Header text
        bbox = f_lane_hdr.getbbox(ltitle)
        tw = bbox[2] - bbox[0]
        draw.text((lcenter - tw // 2, top_lane_y + 12), ltitle, font=f_lane_hdr, fill="#ffffff")

    # Helper Functions for Shapes
    def draw_terminator(cx, cy, w, h, text, bg="#2563eb", fg="#ffffff"):
        x1, y1 = cx - w//2, cy - h//2
        x2, y2 = cx + w//2, cy + h//2
        draw.rounded_rectangle([x1, y1, x2, y2], radius=h//2, fill=bg, outline="#1e293b", width=2)
        bbox = f_node_title.getbbox(text)
        tw = bbox[2] - bbox[0]
        th = bbox[3] - bbox[1]
        draw.text((cx - tw//2, cy - th//2 - 2), text, font=f_node_title, fill=fg)
        return (x1, y1, x2, y2)

    def draw_process(cx, cy, w, h, title, sub, border_col="#334155", bg="#ffffff"):
        x1, y1 = cx - w//2, cy - h//2
        x2, y2 = cx + w//2, cy + h//2
        draw.rounded_rectangle([x1, y1, x2, y2], radius=8, fill=bg, outline=border_col, width=2)
        bbox1 = f_node_title.getbbox(title)
        bbox2 = f_node_sub.getbbox(sub)
        tw1 = bbox1[2] - bbox1[0]
        tw2 = bbox2[2] - bbox2[0]
        draw.text((cx - tw1//2, cy - 18), title, font=f_node_title, fill="#0f172a")
        draw.text((cx - tw2//2, cy + 4), sub, font=f_node_sub, fill="#475569")
        return (x1, y1, x2, y2)

    def draw_io(cx, cy, w, h, title, sub, border_col="#2563eb", bg="#ffffff"):
        x1, y1 = cx - w//2, cy - h//2
        x2, y2 = cx + w//2, cy + h//2
        skew = 18
        points = [(x1 + skew, y1), (x2, y1), (x2 - skew, y2), (x1, y2)]
        draw.polygon(points, fill=bg, outline=border_col, width=2)
        bbox1 = f_node_title.getbbox(title)
        bbox2 = f_node_sub.getbbox(sub)
        tw1 = bbox1[2] - bbox1[0]
        tw2 = bbox2[2] - bbox2[0]
        draw.text((cx - tw1//2, cy - 18), title, font=f_node_title, fill="#1e3a8a")
        draw.text((cx - tw2//2, cy + 4), sub, font=f_node_sub, fill="#475569")
        return (x1, y1, x2, y2)

    def draw_decision(cx, cy, w, h, title1, title2):
        x1, y1 = cx - w//2, cy - h//2
        x2, y2 = cx + w//2, cy + h//2
        points = [(cx, y1), (x2, cy), (cx, y2), (x1, cy)]
        draw.polygon(points, fill="#fef3c7", outline="#b45309", width=2)
        bbox1 = f_decision.getbbox(title1)
        tw1 = bbox1[2] - bbox1[0]
        draw.text((cx - tw1//2, cy - 15), title1, font=f_decision, fill="#78350f")
        if title2:
            bbox2 = f_decision.getbbox(title2)
            tw2 = bbox2[2] - bbox2[0]
            draw.text((cx - tw2//2, cy + 3), title2, font=f_decision, fill="#78350f")
        return (x1, y1, x2, y2)

    def draw_arrow(p1, p2, col="#334155", width=2, arrow_size=8):
        x1, y1 = p1
        x2, y2 = p2
        draw.line([p1, p2], fill=col, width=width)
        # Calculate arrowhead
        dx = x2 - x1
        dy = y2 - y1
        angle = math.atan2(dy, dx)
        a1 = angle + math.pi * 5 / 6
        a2 = angle - math.pi * 5 / 6
        tip1 = (x2 + arrow_size * math.cos(a1), y2 + arrow_size * math.sin(a1))
        tip2 = (x2 + arrow_size * math.cos(a2), y2 + arrow_size * math.sin(a2))
        draw.polygon([p2, tip1, tip2], fill=col)

    c1 = 275  # Center Lane 1 (Siswa)
    c2 = 770  # Center Lane 2 (Admin)
    c3 = 1265 # Center Lane 3 (Teknisi)

    # ================= 1. SISWA FLOW =================
    # Mulai
    t_start = draw_terminator(c1, 315, 140, 46, "MULAI", bg="#2563eb")
    
    # Arrow Mulai -> Input Form
    draw_arrow((c1, 338), (c1, 375))
    
    # Input Form
    io_form = draw_io(c1, 405, 330, 60, "Input Form Pengaduan", "Kategori, Lokasi, Deskripsi, Tanggal")
    draw_arrow((c1, 435), (c1, 475))

    # Input Media Foto
    io_media = draw_io(c1, 505, 330, 60, "Unggah Foto Kerusakan", "Multimedia: JPG/PNG/WEBP (Maks 2MB)")
    draw_arrow((c1, 535), (c1, 575))

    # Decision 1: Validasi
    dec1 = draw_decision(c1, 620, 200, 90, "Data & File", "Valid?")
    
    # Decision 1 -> Tidak (Loop back)
    draw.line([(c1 - 100, 620), (c1 - 145, 620), (c1 - 145, 405), (c1 - 165, 405)], fill="#dc2626", width=2)
    draw_arrow((c1 - 145, 405), (c1 - 165, 405), col="#dc2626")
    draw.text((c1 - 140, 602), "Tidak (Error)", font=f_label, fill="#dc2626")

    # Decision 1 -> Ya (Process Simpan DB)
    draw_arrow((c1, 665), (c1, 710), col="#16a34a")
    draw.text((c1 + 10, 678), "Ya", font=f_label, fill="#16a34a")

    # Process Simpan DB & Nomor Tiket
    p_simpan = draw_process(c1, 745, 340, 68, "Simpan DB & Generate Tiket", "status: pending | not_assigned", border_col="#2563eb")

    # Cross Arrow: Siswa -> Admin (Notifikasi)
    draw_arrow((c1 + 170, 745), (c2 - 170, 745), col="#2563eb", width=2)
    draw.text((505, 725), "Notifikasi Laporan Baru", font=f_label, fill="#2563eb")

    # ================= 2. ADMIN FLOW =================
    # Process Admin Review
    p_rev = draw_process(c2, 745, 340, 68, "Admin Meninjau Pengaduan", "Validasi Fisik & Deskripsi Kerusakan", border_col="#7c3aed")
    draw_arrow((c2, 779), (c2, 825))

    # Decision 2: Disetujui?
    dec2 = draw_decision(c2, 875, 210, 96, "Pengaduan", "Disetujui?")

    # Decision 2 -> Tolak (Rejected)
    draw.line([(c2 - 105, 875), (565, 875), (565, 930)], fill="#dc2626", width=2)
    draw_arrow((565, 875), (565, 930), col="#dc2626")
    draw.text((580, 855), "Tolak", font=f_label, fill="#dc2626")

    # Process Tolak
    p_reject = draw_process(565, 965, 200, 62, "status: Rejected", "Wajib Berikan Alasan", border_col="#dc2626", bg="#fee2e2")
    
    # Arrow Tolak -> Notifikasi Siswa
    draw.line([(565, 996), (565, 1025), (c1, 1025)], fill="#dc2626", width=2)
    draw_arrow((565, 1025), (c1, 1045), col="#dc2626")
    draw.text((400, 1007), "Notifikasi Ditolak", font=f_label, fill="#dc2626")

    # IO Siswa: Laporan Ditolak
    io_tolak = draw_io(c1, 1075, 310, 60, "Siswa Membaca Alasan", "Pengaduan Diarsipkan (Selesai)")
    draw_arrow((c1, 1105), (c1, 1145))
    draw_terminator(c1, 1165, 150, 40, "SELESAI (DITOLAK)", bg="#64748b")

    # Decision 2 -> Setuju (Tugaskan Teknisi)
    draw_arrow((c2, 923), (c2, 970), col="#16a34a")
    draw.text((c2 + 10, 938), "Setuju (Approved)", font=f_label, fill="#16a34a")

    # Process Tugaskan Teknisi
    p_tugas = draw_process(c2, 1005, 340, 68, "Tugaskan Teknisi Spesialis", "status_penanganan: assigned", border_col="#7c3aed")

    # Cross Arrow: Admin -> Teknisi
    draw_arrow((c2 + 170, 1005), (c3 - 170, 1005), col="#7c3aed", width=2)
    draw.text((995, 985), "Notifikasi Penugasan", font=f_label, fill="#7c3aed")

    # ================= 3. TEKNISI FLOW =================
    # IO Notifikasi Teknisi
    io_tek = draw_io(c3, 1005, 330, 60, "Terima Notifikasi Tugas", "Melihat Lokasi & Foto Kerusakan", border_col="#059669")
    draw_arrow((c3, 1035), (c3, 1075))

    # Decision 3: Teknisi Terima?
    dec3 = draw_decision(c3, 1125, 200, 92, "Teknisi", "Terima Tugas?")

    # Decision 3 -> Tolak (Kembalikan ke Admin)
    draw.line([(c3 - 100, 1125), (1050, 1125), (1050, 1025), (c2 + 170, 1025)], fill="#dc2626", width=2)
    draw_arrow((1050, 1025), (c2 + 170, 1025), col="#dc2626")
    draw.text((1055, 1105), "Tolak (+Alasan)", font=f_label, fill="#dc2626")

    # Decision 3 -> Terima (In Progress)
    draw_arrow((c3, 1171), (c3, 1220), col="#16a34a")
    draw.text((c3 + 10, 1188), "Terima (Accepted)", font=f_label, fill="#16a34a")

    # Process Mulai Perbaikan
    p_prog = draw_process(c3, 1255, 340, 68, "Mulai Pekerjaan Fisik", "status_penanganan: in_progress", border_col="#059669")
    draw_arrow((c3, 1289), (c3, 1335))

    # IO Upload Bukti Foto Sesudah
    io_bukti = draw_io(c3, 1365, 340, 62, "Unggah Foto Bukti Perbaikan", "Multimedia: Foto Sesudah Perbaikan", border_col="#059669")
    draw_arrow((c3, 1396), (c3, 1440))

    # Decision 4: Foto Bukti Terunggah?
    dec4 = draw_decision(c3, 1485, 210, 90, "Foto Sesudah", "Terunggah?")

    # Decision 4 -> Belum (Ditolak Sistem)
    draw.line([(c3 + 105, 1485), (c3 + 145, 1485), (c3 + 145, 1365), (c3 + 170, 1365)], fill="#dc2626", width=2)
    draw_arrow((c3 + 145, 1365), (c3 + 170, 1365), col="#dc2626")
    draw.text((c3 + 110, 1465), "Belum", font=f_label, fill="#dc2626")

    # Decision 4 -> Sudah
    draw_arrow((c3, 1530), (c3, 1575), col="#16a34a")
    draw.text((c3 + 10, 1545), "Sudah", font=f_label, fill="#16a34a")

    # Process Ajukan Penyelesaian
    p_selesai = draw_process(c3, 1610, 340, 68, "Ajukan Konfirmasi Selesai", "status: waiting_confirmation", border_col="#059669")

    # Cross Arrow: Teknisi -> Admin
    draw_arrow((c3 - 170, 1610), (c2 + 170, 1610), col="#059669", width=2)
    draw.text((1000, 1590), "Minta Validasi Hasil", font=f_label, fill="#059669")

    # ================= 4. VALIDASI AKHIR (ADMIN) =================
    # Process Admin Cek Hasil
    p_val = draw_process(c2, 1610, 340, 68, "Admin Evaluasi Bukti", "Bandingkan Foto Sebelum & Sesudah", border_col="#7c3aed")
    draw_arrow((c2, 1644), (c2, 1690))

    # Decision 5: Hasil Sesuai?
    dec5 = draw_decision(c2, 1740, 220, 98, "Hasil Perbaikan", "Tuntas & Sesuai?")

    # Decision 5 -> Minta Revisi (Balik ke Teknisi)
    draw.line([(c2 + 110, 1740), (1050, 1740), (1050, 1260), (c3 - 170, 1260)], fill="#d97706", width=2)
    draw_arrow((1050, 1260), (c3 - 170, 1260), col="#d97706")
    draw.text((975, 1720), "Minta Revisi (+Catatan)", font=f_label, fill="#d97706")

    # Decision 5 -> Selesai (Completed)
    draw_arrow((c2, 1789), (c2, 1835), col="#16a34a")
    draw.text((c2 + 10, 1805), "Ya (Tuntas)", font=f_label, fill="#16a34a")

    # Process Final Completed
    p_comp = draw_process(c2, 1870, 350, 70, "Update status: Completed", "Kunci Tiket & Catat Waktu Selesai", border_col="#16a34a", bg="#ecfdf5")

    # Cross Arrow: Admin -> Siswa (Notifikasi Selesai)
    draw.line([(c2 - 175, 1870), (c1 + 170, 1870)], fill="#16a34a", width=2)
    draw_arrow((c2 - 175, 1870), (c1 + 170, 1870), col="#16a34a")
    draw.text((490, 1850), "Notifikasi Selesai ke Siswa", font=f_label, fill="#16a34a")

    # ================= 5. SELESAI (SISWA) =================
    # IO Siswa Menerima Hasil
    io_fin = draw_io(c1, 1870, 340, 68, "Siswa Melihat Hasil Tuntas", "Cek Foto Perbaikan & Timeline Selesai", border_col="#16a34a")
    draw_arrow((c1, 1904), (c1, 1950))

    # Terminator Selesai
    draw_terminator(c1, 1975, 160, 48, "SELESAI (SUKSES)", bg="#16a34a")

    # ================= 6. BOTTOM ARCHITECTURE BOX =================
    box_y1 = lane_bottom_y + 15
    box_y2 = card_y2 - 25
    draw.rounded_rectangle([card_x1 + 40, box_y1, card_x2 - 40, box_y2], radius=14, fill="#f8fafc", outline="#cbd5e1", width=2)
    
    # Inner Header
    draw.rectangle([card_x1 + 40, box_y1, card_x2 - 40, box_y1 + 38], fill="#1e293b")
    draw.text((card_x1 + 60, box_y1 + 10), "ANALISIS KUK ALGORITMA — ARSITEKTUR KONTROL & STATE TRANSITION ENGINE (LARAVEL 12)", font=f_box_hdr, fill="#38bdf8")
    
    # Content Columns
    col1_x = card_x1 + 60
    col2_x = card_x1 + 540
    col3_x = card_x1 + 1020
    ty = box_y1 + 48
    
    # Col 1: Validasi & Multimedia
    draw.text((col1_x, ty), "1. Logika Validasi & Multimedia:", font=f_node_title, fill="#0f172a")
    draw.text((col1_x, ty + 22), "• Form Request Sanitasi (Required, string, dates).", font=f_box_txt, fill="#334155")
    draw.text((col1_x, ty + 42), "• Filter MIME: JPG, PNG, WEBP (Batas < 2MB).", font=f_box_txt, fill="#334155")
    draw.text((col1_x, ty + 62), "• Hash Obfuscation: Nama file dienkripsi acak.", font=f_box_txt, fill="#334155")
    draw.text((col1_x, ty + 82), "• Anti-Malware: Penolakan mutlak ekstensi .php.", font=f_box_txt, fill="#334155")

    # Col 2: State Machine Workflow
    draw.text((col2_x, ty), "2. State Transition (PengaduanWorkflow):", font=f_node_title, fill="#0f172a")
    draw.text((col2_x, ty + 22), "• Pemisahan status verifikasi & penanganan.", font=f_box_txt, fill="#334155")
    draw.text((col2_x, ty + 42), "• Matriks Transisi Ketat (Pengaduan::TRANSISI).", font=f_box_txt, fill="#334155")
    draw.text((col2_x, ty + 62), "• Database Lock (lockForUpdate) anti race condition.", font=f_box_txt, fill="#334155")
    draw.text((col2_x, ty + 82), "• Wajib Foto Bukti sebelum ajukan selesai.", font=f_box_txt, fill="#334155")

    # Col 3: Integritas & Otorisasi
    draw.text((col3_x, ty), "3. Audit Trail & Keamanan Otorisasi:", font=f_node_title, fill="#0f172a")
    draw.text((col3_x, ty + 22), "• RiwayatStatus atomik mencatat aktor & waktu.", font=f_box_txt, fill="#334155")
    draw.text((col3_x, ty + 42), "• NotifikasiService terintegrasi di tiap transisi.", font=f_box_txt, fill="#334155")
    draw.text((col3_x, ty + 62), "• Multi-role middleware & PengaduanPolicy.", font=f_box_txt, fill="#334155")
    draw.text((col3_x, ty + 82), "• Teruji 173 automated unit & feature tests.", font=f_box_txt, fill="#334155")

    # Save to Root and Docs
    out_root = os.path.abspath("flowchart_adukanaja.png")
    out_docs = os.path.abspath("docs/flowchart_adukanaja.png")
    
    img.save(out_root, format="PNG", dpi=(300, 300))
    img.save(out_docs, format="PNG", dpi=(300, 300))
    print(f"Flowchart PNG generated successfully:\n-> {out_root}\n-> {out_docs}")

if __name__ == "__main__":
    create_flowchart_png()
