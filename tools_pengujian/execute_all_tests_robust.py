import os
import sys
import time
from datetime import date
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

sys.stdout.reconfigure(line_buffering=True)

BASE_URL = "https://adukanaja.rf.gd"
SCREENSHOT_DIR = os.path.join(os.getcwd(), "screenshots")
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

IMG_KERUSAKAN = os.path.abspath("kerusakan.jpg")
IMG_SESUDAH = os.path.abspath("sesudah.jpg")

chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--window-size=1280,850")
chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

print("Initializing Chrome driver...", flush=True)
driver = webdriver.Chrome(options=chrome_options)
driver.set_page_load_timeout(60)

def wait_el(by, val, timeout=20):
    return WebDriverWait(driver, timeout).until(EC.presence_of_element_located((by, val)))

def wait_click(by, val, timeout=20):
    el = WebDriverWait(driver, timeout).until(EC.element_to_be_clickable((by, val)))
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", el)
    time.sleep(0.3)
    return el

def snap(name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    driver.save_screenshot(path)
    print(f"  [SAVED] {path} ({os.path.getsize(path)} bytes)", flush=True)

def logout():
    driver.delete_all_cookies()
    driver.get(f"{BASE_URL}/login")
    time.sleep(1.5)

def login(user, pw):
    logout()
    print(f"Logging in as {user}...", flush=True)
    driver.get(f"{BASE_URL}/login")
    wait_el(By.NAME, "login")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.clear()
    login_inp.send_keys(user)
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.clear()
    pw_inp.send_keys(pw)
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(2.5)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")

def scroll_top():
    driver.execute_script("window.scrollTo(0, 0);")
    time.sleep(0.5)

def scroll_mid():
    driver.execute_script("window.scrollTo(0, 350);")
    time.sleep(0.5)

try:
    print("\n--- TC001: Login dengan Email (Admin) ---", flush=True)
    login("rudi@sch.id", "password")
    if "/admin/dashboard" not in driver.current_url:
        driver.get(f"{BASE_URL}/admin/dashboard")
    wait_el(By.CSS_SELECTOR, ".card, .stat, table")
    time.sleep(2)
    scroll_top()
    snap("TC001")

    print("\n--- TC002: Login dengan NIS (User/Siswa) ---", flush=True)
    login("2526003", "password")
    if "/beranda" not in driver.current_url:
        driver.get(f"{BASE_URL}/beranda")
    wait_el(By.CSS_SELECTOR, ".card, table")
    time.sleep(2)
    scroll_top()
    snap("TC002")

    print("\n--- TC003: Validasi Login Password Salah ---", flush=True)
    logout()
    wait_el(By.NAME, "login")
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.send_keys("aldejan@sch.id")
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.send_keys("salah123")
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    wait_el(By.CSS_SELECTOR, ".alert-error, .login-alert")
    time.sleep(1)
    scroll_top()
    snap("TC003")

    print("\n--- TC004: Beranda User (Ringkasan Pengaduan) ---", flush=True)
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/beranda")
    wait_el(By.CSS_SELECTOR, ".card, table")
    time.sleep(2)
    scroll_top()
    snap("TC004")

    print("\n--- TC005: Ajukan Pengaduan Baru (Form Lengkap + Foto) ---", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    wait_el(By.ID, "kategori_id")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    
    kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
    kategori_sel.select_by_visible_text("Listrik")
    
    lokasi_inp = driver.find_element(By.ID, "lokasi")
    lokasi_inp.clear()
    lokasi_inp.send_keys("Lab Komputer")
    
    sarana_sel = Select(driver.find_element(By.ID, "sarana_id"))
    try:
        sarana_sel.select_by_visible_text("Proyektor · Lab Komputer")
    except:
        sarana_sel.select_by_index(1)
        
    desk_inp = driver.find_element(By.ID, "deskripsi")
    desk_inp.clear()
    desk_inp.send_keys("Lampu proyektor tidak menyala saat dipakai pelajaran")
    
    foto_inp = driver.find_element(By.ID, "foto")
    foto_inp.send_keys(IMG_KERUSAKAN)
    time.sleep(1)
    
    driver.find_element(By.ID, "btn-kirim").click()
    WebDriverWait(driver, 20).until(lambda d: "/pengaduan/" in d.current_url and "/ajukan" not in d.current_url)
    time.sleep(2)
    scroll_top()
    snap("TC005")
    tc5_url = driver.current_url
    tc5_id = tc5_url.rstrip("/").split("/")[-1].split("?")[0].split("#")[0]
    print(f"TC005 Pengaduan ID: {tc5_id}, URL: {tc5_url}", flush=True)

    print("\n--- TC006: Validasi Formulir Ajukan (Deskripsi Singkat) ---", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    wait_el(By.ID, "kategori_id")
    kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
    kategori_sel.select_by_visible_text("Listrik")
    lokasi_inp = driver.find_element(By.ID, "lokasi")
    lokasi_inp.send_keys("Lab Komputer")
    desk_inp = driver.find_element(By.ID, "deskripsi")
    desk_inp.send_keys("rusak")
    driver.find_element(By.ID, "btn-kirim").click()
    wait_el(By.CSS_SELECTOR, ".field-error, .has-error")
    time.sleep(1)
    scroll_top()
    snap("TC006")

    print("\n--- TC007: Filter Riwayat Status Pending ---", flush=True)
    driver.get(f"{BASE_URL}/riwayat?status=pending")
    wait_el(By.CSS_SELECTOR, "table, .card")
    time.sleep(2)
    scroll_top()
    snap("TC007")

    print("\n--- TC008: Detail Pengaduan & Timeline ---", flush=True)
    driver.get(tc5_url)
    wait_el(By.CSS_SELECTOR, ".timeline, .komentar, .card")
    time.sleep(2)
    scroll_mid()
    snap("TC008")

    print("\n--- TC009: Kirim Komentar pada Pengaduan ---", flush=True)
    driver.get(tc5_url)
    wait_el(By.ID, "isi-komentar")
    komentar_box = driver.find_element(By.ID, "isi-komentar")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", komentar_box)
    time.sleep(0.5)
    komentar_box.send_keys("Mohon kabar perbaikannya, ruangan dipakai untuk ujian hari Jumat")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Kirim komentar')]").click()
    time.sleep(2.5)
    try:
        driver.execute_script("document.getElementById('komentar').scrollIntoView({block: 'center'});")
        time.sleep(0.5)
    except:
        pass
    snap("TC009")

    print("\n--- TC010: Admin Setujui Pengaduan Pending (Approved) ---", flush=True)
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    wait_el(By.XPATH, "//input[@name='status'][@value='approved']")
    app_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='approved']")
    driver.execute_script("arguments[0].click();", app_radio)
    tanggapan_area = driver.find_element(By.ID, "tanggapan")
    tanggapan_area.clear()
    tanggapan_area.send_keys("Laporan valid, akan ditugaskan ke teknisi")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC010")

    print("\n--- TC011: Admin Tolak Pengaduan Tanpa Catatan (Validasi Rejected) ---", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    wait_el(By.CSS_SELECTOR, ".verif-ticket, .card")
    time.sleep(1.5)
    pending_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    if pending_tickets:
        driver.execute_script("arguments[0].click();", pending_tickets[0])
        time.sleep(2)
    rej_radio = wait_el(By.XPATH, "//input[@name='status'][@value='rejected']")
    driver.execute_script("arguments[0].click();", rej_radio)
    tanggapan_area = driver.find_element(By.ID, "tanggapan")
    tanggapan_area.clear()
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]").click()
    wait_el(By.CSS_SELECTOR, ".field-error, .has-error")
    time.sleep(1)
    scroll_top()
    snap("TC011")

    print("\n--- TC012: Admin Menugaskan Teknisi (Pak Budi Santoso) ---", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    wait_el(By.ID, "teknisi_id")
    teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
    for opt in teknisi_sel.options:
        if "Budi" in opt.text:
            teknisi_sel.select_by_visible_text(opt.text)
            break
    driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC012")

    print("\n--- TC013: Teknisi Terima Tugas ---", flush=True)
    login("budi@sch.id", "password")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    wait_el(By.XPATH, "//button[contains(text(), 'Terima Tugas')]")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Terima Tugas')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC013")

    print("\n--- Preparation for TC014: Assign another task to Budi for rejection test ---", flush=True)
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
    wait_el(By.CSS_SELECTOR, ".verif-ticket")
    time.sleep(1.5)
    app_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    rej_test_id = None
    for t in app_tickets:
        href = t.get_attribute("href")
        t_id = href.split("id=")[1].split("&")[0].split("#")[0]
        if str(t_id) != str(tc5_id):
            rej_test_id = t_id
            driver.get(f"{BASE_URL}/admin/verifikasi?id={rej_test_id}#detail")
            time.sleep(2)
            try:
                teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
                for opt in teknisi_sel.options:
                    if "Budi" in opt.text:
                        teknisi_sel.select_by_visible_text(opt.text)
                        break
                driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]").click()
                time.sleep(2)
                print(f"Assigned ticket {rej_test_id} to Budi for rejection test.", flush=True)
                break
            except Exception as e:
                print("Could not assign to Budi:", e, flush=True)

    print("\n--- TC014: Teknisi Tolak Tugas dengan Alasan ---", flush=True)
    login("budi@sch.id", "password")
    if rej_test_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{rej_test_id}")
        wait_el(By.ID, "alasan")
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        alasan_area = driver.find_element(By.ID, "alasan")
        alasan_area.clear()
        alasan_area.send_keys("Sedang menangani perbaikan di ruangan lain")
        tolak_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Tolak Tugas')]")
        tolak_btn.click()
        time.sleep(2.5)
    scroll_top()
    snap("TC014")

    print("\n--- TC015: Teknisi Mulai Pekerjaan & Simpan Progres (50%) ---", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
    if mulai_btns:
        mulai_btns[0].click()
        time.sleep(2)
    
    wait_el(By.ID, "progres")
    progres_inp = driver.find_element(By.ID, "progres")
    progres_inp.clear()
    progres_inp.send_keys("50")
    
    catatan_prog = driver.find_element(By.ID, "catatan")
    catatan_prog.clear()
    catatan_prog.send_keys("Penggantian lampu proyektor sedang dikerjakan")
    
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC015")

    print("\n--- TC016: Teknisi Unggah Foto Bukti Perbaikan ---", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    wait_el(By.ID, "jenis")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    jenis_sel = Select(driver.find_element(By.ID, "jenis"))
    jenis_sel.select_by_value("sesudah")
    
    foto_upload = driver.find_element(By.ID, "foto")
    foto_upload.send_keys(IMG_SESUDAH)
    
    ket_inp = driver.find_element(By.ID, "keterangan")
    ket_inp.clear()
    ket_inp.send_keys("Proyektor sudah menyala normal")
    
    driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC016")

    print("\n--- TC017: Teknisi Ajukan Penyelesaian Pekerjaan ---", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    wait_el(By.ID, "catatan-selesai")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_selesai = driver.find_element(By.ID, "catatan-selesai")
    cat_selesai.clear()
    cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal")
    
    driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC017")

    print("\n--- TC019: Admin Kembalikan untuk Revisi ---", flush=True)
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    wait_el(By.ID, "catatan-revisi")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_revisi = driver.find_element(By.ID, "catatan-revisi")
    cat_revisi.clear()
    cat_revisi.send_keys("Foto sesudah kurang jelas, mohon diulang")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Kembalikan untuk Revisi')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC019")

    print("\nTeknisi re-submits after revision for TC018...", flush=True)
    login("budi@sch.id", "password")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    rev_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Mengerjakan Revisi')]")
    if rev_btns:
        rev_btns[0].click()
        time.sleep(2)
    try:
        jenis_sel = Select(driver.find_element(By.ID, "jenis"))
        jenis_sel.select_by_value("sesudah")
        foto_upload = driver.find_element(By.ID, "foto")
        foto_upload.send_keys(IMG_SESUDAH)
        ket_inp = driver.find_element(By.ID, "keterangan")
        ket_inp.clear()
        ket_inp.send_keys("Foto sesudah perbaikan baru")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("Re-upload error:", e, flush=True)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_selesai = driver.find_element(By.ID, "catatan-selesai")
        cat_selesai.clear()
        cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal setelah revisi")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("Re-submit error:", e, flush=True)

    print("\n--- TC018: Admin Validasi Hasil Pekerjaan (Selesai) ---", flush=True)
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    wait_el(By.ID, "catatan-validasi")
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_val = driver.find_element(By.ID, "catatan-validasi")
    cat_val.clear()
    cat_val.send_keys("Pekerjaan sudah baik")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Nyatakan Selesai')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC018")

    print("\n--- TC020: Dashboard Admin (Statistik & Grafik) ---", flush=True)
    driver.get(f"{BASE_URL}/admin/dashboard")
    wait_el(By.CSS_SELECTOR, ".card, .stat, table")
    time.sleep(3)
    scroll_top()
    snap("TC020")

    print("\n--- TC021: Admin Tambah Pengguna Baru ---", flush=True)
    driver.get(f"{BASE_URL}/admin/pengguna")
    wait_el(By.CSS_SELECTOR, "table, .card")
    time.sleep(1.5)
    del_btns = driver.find_elements(By.XPATH, "//tr[contains(., 'dewi@sch.id')]//button[contains(text(), 'Hapus')]")
    if del_btns:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        del_btns[0].click()
        time.sleep(2)

    driver.get(f"{BASE_URL}/admin/pengguna/create")
    wait_el(By.ID, "name")
    driver.find_element(By.ID, "name").send_keys("Dewi Anggraini")
    role_sel = Select(driver.find_element(By.ID, "role"))
    try:
        role_sel.select_by_value("user")
    except:
        role_sel.select_by_value("siswa")
    driver.find_element(By.ID, "email").send_keys("dewi@sch.id")
    driver.find_element(By.ID, "nis").send_keys("2526020")
    driver.find_element(By.ID, "kelas").send_keys("XI RPL A")
    driver.find_element(By.ID, "password").send_keys("password123")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC021")

    print("\n--- TC022: Admin Tambah Sarana Baru ---", flush=True)
    driver.get(f"{BASE_URL}/admin/sarana/create")
    wait_el(By.ID, "nama")
    driver.find_element(By.ID, "nama").send_keys("Printer Warna Epson L3210")
    driver.find_element(By.ID, "lokasi").send_keys("Ruang Tata Usaha")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC022")

    print("\n--- TC023: Admin Laporan (Filter & Export) ---", flush=True)
    driver.get(f"{BASE_URL}/admin/laporan?dari=2026-11-01&sampai=2026-11-16&status=approved")
    wait_el(By.CSS_SELECTOR, "table, .filters")
    time.sleep(2.5)
    scroll_top()
    snap("TC023")

    print("\n--- TC024: Ubah Password Profil ---", flush=True)
    driver.get(f"{BASE_URL}/profil")
    wait_el(By.ID, "password_lama")
    pw_lama = driver.find_element(By.ID, "password_lama")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
    time.sleep(0.5)
    pw_lama.send_keys("password")
    driver.find_element(By.ID, "password").send_keys("password123")
    driver.find_element(By.ID, "password_confirmation").send_keys("password123")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC024")
    
    print("Reverting password back to standard 'password'...", flush=True)
    driver.get(f"{BASE_URL}/profil")
    wait_el(By.ID, "password_lama")
    pw_lama = driver.find_element(By.ID, "password_lama")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
    pw_lama.send_keys("password123")
    driver.find_element(By.ID, "password").send_keys("password")
    driver.find_element(By.ID, "password_confirmation").send_keys("password")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]").click()
    time.sleep(2)

    print("\n--- TC025: Notifikasi Pengaduan & Tandai Semua Dibaca ---", flush=True)
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/notifikasi")
    wait_el(By.CSS_SELECTOR, ".card, .notif-list, .empty")
    time.sleep(1.5)
    tanda_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Tandai semua dibaca')]")
    if tanda_btns:
        tanda_btns[0].click()
        time.sleep(2)
    scroll_top()
    snap("TC025")

    print("\n>>> ALL TEST CASES TC001 TO TC025 EXECUTED AND CAPTURED SUCCESSFULLY! <<<", flush=True)

except Exception as err:
    print(f"\nFATAL ERROR in execution: {err}", flush=True)
    import traceback
    traceback.print_exc()

finally:
    driver.quit()
    print("Browser closed.", flush=True)
