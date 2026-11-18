import os
import openpyxl
from openpyxl.styles import PatternFill, Font, Alignment, Border, Side
from openpyxl.drawing.image import Image as OpenpyxlImage
from PIL import Image as PILImage

TEMPLATE_PATH = r"C:\Users\windows\.gemini\antigravity\brain\77d095f4-b654-4756-accf-c76f010bf736\.user_uploaded\media_1794892503785_d9eb7060.xlsx"
OUTPUT_PATH = r"c:\xampp\htdocs\Sistem_Pengaduan_Sarana_Sekolah\Laporan_Pengujian_ADUKANAJA.xlsx"
SCREENSHOT_DIR = r"c:\xampp\htdocs\Sistem_Pengaduan_Sarana_Sekolah\screenshots"

def create_report():
    print("Loading base workbook...", flush=True)
    wb = openpyxl.load_workbook(TEMPLATE_PATH)
    
    # Common styles
    font_family = "Segoe UI"
    navy_fill = PatternFill(start_color="0A2463", end_color="0A2463", fill_type="solid")
    sub_navy_fill = PatternFill(start_color="1E3A8A", end_color="1E3A8A", fill_type="solid")
    header_font = Font(name=font_family, size=11, bold=True, color="FFFFFF")
    meta_title_font = Font(name=font_family, size=11, bold=True, color="0A2463")
    meta_val_font = Font(name=font_family, size=11, bold=False, color="1E293B")
    data_font = Font(name=font_family, size=10, color="0F172A")
    check_font = Font(name=font_family, size=14, bold=True, color="16A34A")
    
    thin_side = Side(border_style="thin", color="CBD5E1")
    cell_border = Border(left=thin_side, right=thin_side, top=thin_side, bottom=thin_side)
    
    align_center = Alignment(horizontal="center", vertical="center", wrap_text=True)
    align_top_center = Alignment(horizontal="center", vertical="top", wrap_text=True)
    align_top_left = Alignment(horizontal="left", vertical="top", wrap_text=True)
    
    # -------------------------------------------------------------
    # 1. Format Sheet "Test Scenario"
    # -------------------------------------------------------------
    if "Test Scenario" in wb.sheetnames:
        ws_ts = wb["Test Scenario"]
        ws_ts.views.sheetView[0].showGridLines = True
        
        # Meta info rows 1-3
        for r in range(1, 4):
            ws_ts.row_dimensions[r].height = 22
            ws_ts.cell(r, 1).font = meta_title_font
            ws_ts.cell(r, 3).font = meta_val_font
            
        # Header row 5
        ws_ts.row_dimensions[5].height = 28
        for col_idx in range(1, 5):
            c = ws_ts.cell(5, col_idx)
            c.fill = navy_fill
            c.font = header_font
            c.alignment = align_center
            c.border = cell_border
            
        # Data rows
        col_widths_ts = {1: 14, 2: 26, 3: 26, 4: 80}
        for col_idx, width in col_widths_ts.items():
            ws_ts.column_dimensions[openpyxl.utils.get_column_letter(col_idx)].width = width
            
        for r in range(6, ws_ts.max_row + 1):
            ts_id = ws_ts.cell(r, 1).value
            if not ts_id:
                continue
            ws_ts.row_dimensions[r].height = 42
            is_even = (r % 2 == 0)
            row_fill = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid") if is_even else None
            
            for col_idx in range(1, 5):
                cell = ws_ts.cell(r, col_idx)
                cell.font = data_font
                cell.border = cell_border
                if row_fill:
                    cell.fill = row_fill
                if col_idx == 1:
                    cell.alignment = align_top_center
                    cell.font = Font(name=font_family, size=10, bold=True, color="0A2463")
                else:
                    cell.alignment = align_top_left

    # -------------------------------------------------------------
    # 2. Format Sheet "Test Case"
    # -------------------------------------------------------------
    if "Test Case" in wb.sheetnames:
        ws_tc = wb["Test Case"]
        ws_tc.views.sheetView[0].showGridLines = True
        
        # Meta info
        for r in range(1, 4):
            ws_tc.row_dimensions[r].height = 22
            ws_tc.cell(r, 1).font = meta_title_font
            ws_tc.cell(r, 3).font = meta_val_font
            
        # Header rows 5 and 6
        ws_tc.row_dimensions[5].height = 24
        ws_tc.row_dimensions[6].height = 22
        
        for col_idx in range(1, 13):
            c5 = ws_tc.cell(5, col_idx)
            c6 = ws_tc.cell(6, col_idx)
            
            c5.fill = navy_fill
            c5.font = header_font
            c5.alignment = align_center
            c5.border = cell_border
            
            c6.fill = sub_navy_fill if col_idx in [9, 10] else navy_fill
            c6.font = header_font
            c6.alignment = align_center
            c6.border = cell_border

        # Set Column widths
        tc_widths = {
            1: 13,  # TC ID
            2: 16,  # TS ID
            3: 22,  # Halaman
            4: 22,  # Fitur
            5: 24,  # Sub Fitur
            6: 40,  # Action
            7: 32,  # Data Uji
            8: 46,  # Respon yang Diharapkan
            9: 10,  # Sukses
            10: 10, # Gagal
            11: 48, # Tampilan Gambar
            12: 38  # Keterangan
        }
        for col_idx, width in tc_widths.items():
            ws_tc.column_dimensions[openpyxl.utils.get_column_letter(col_idx)].width = width

        # Iterate over test cases
        for r in range(7, ws_tc.max_row + 1):
            tc_id = ws_tc.cell(r, 1).value
            if not tc_id or not str(tc_id).strip().startswith("TC"):
                continue
            
            tc_str = str(tc_id).strip()
            ws_tc.row_dimensions[r].height = 150  # ample height for 180px thumbnail
            
            is_even = (r % 2 == 0)
            row_fill = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid") if is_even else None
            
            for col_idx in range(1, 13):
                cell = ws_tc.cell(r, col_idx)
                cell.font = data_font
                cell.border = cell_border
                if row_fill:
                    cell.fill = row_fill
                
                if col_idx in [1, 2]:
                    cell.alignment = align_top_center
                    cell.font = Font(name=font_family, size=10, bold=True, color="0A2463")
                elif col_idx in [9, 10]:
                    cell.alignment = align_top_center
                    if cell.value == "✓":
                        cell.font = check_font
                else:
                    cell.alignment = align_top_left

            # Embed screenshot into Column K (col 11)
            img_path = os.path.join(SCREENSHOT_DIR, f"{tc_str}.png")
            if os.path.exists(img_path) and os.path.getsize(img_path) > 0:
                try:
                    pil_img = PILImage.open(img_path)
                    orig_w, orig_h = pil_img.size
                    
                    # Target thumbnail size: max width 330px, max height 185px
                    max_w, max_h = 330, 185
                    ratio = min(max_w / orig_w, max_h / orig_h)
                    thumb_w = int(orig_w * ratio)
                    thumb_h = int(orig_h * ratio)
                    
                    xl_img = OpenpyxlImage(img_path)
                    xl_img.width = thumb_w
                    xl_img.height = thumb_h
                    
                    cell_ref = f"K{r}"
                    ws_tc.add_image(xl_img, cell_ref)
                    ws_tc.cell(r, 11).value = None # Clear placeholder text
                    print(f"  [EMBEDDED] {tc_str} into {cell_ref} ({thumb_w}x{thumb_h}px)", flush=True)
                except Exception as img_err:
                    print(f"  [WARN] Could not embed {tc_str}: {img_err}", flush=True)
            else:
                print(f"  [MISSING] Screenshot not found: {img_path}", flush=True)

    print("Saving report to:", OUTPUT_PATH, flush=True)
    wb.save(OUTPUT_PATH)
    print("Report successfully saved!", flush=True)

if __name__ == "__main__":
    create_report()
