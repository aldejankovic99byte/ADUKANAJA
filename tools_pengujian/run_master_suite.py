import os
import sys
import time
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

def snap(name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    driver.save_screenshot(path)
    print(f"  >>> [SAVED] {path} ({os.path.getsize(path)} bytes)", flush=True)

def click_js(el):
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", el)
    time.sleep(0.3)
    driver.execute_script("arguments[0].click();", el)

def logout():
    print("  Logging out...", flush=True)
    logout_forms = driver.find_elements(By.XPATH, "//form[contains(@action, 'logout')]")
    if logout_forms:
        driver.execute_script("arguments[0].submit();", logout_forms[0])
        time.sleep(2.5)
    if "/login" not in driver.current_url:
        driver.get(f"{BASE_URL}/login")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.NAME, "login")))
    time.sleep(1)

def login(user, pw):
    logout()
    print(f"  Logging in as {user}...", flush=True)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.clear()
    login_inp.send_keys(user)
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.clear()
    pw_inp.send_keys(pw)
    btn = driver.find_element(By.XPATH, "//button[@type='submit']")
    click_js(btn)
    time.sleep(3)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")

def scroll_top():
    driver.execute_script("window.scrollTo(0, 0);")
    time.sleep(0.5)

def scroll_mid():
    driver.execute_script("window.scrollTo(0, 350);")
    time.sleep(0.5)

tc5_url = None
tc5_id = None

# ==========================================
# TC001: Login dengan Email (Admin)
# ==========================================
try:
    print("\n=== TC001: Login dengan Email (Admin) ===", flush=True)
    driver.get(f"{BASE_URL}/login")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.NAME, "login")))
    driver.find_element(By.NAME, "login").send_keys("rudi@sch.id")
    driver.find_element(By.NAME, "password").send_keys("password")
    click_js(driver.find_element(By.XPATH, "//button[@type='submit']"))
    WebDriverWait(driver, 20).until(lambda d: "/admin" in d.current_url)
    time.sleep(2.5)
    scroll_top()
    snap("TC001")
except Exception as e:
    print("TC001 error:", e, flush=True)

# ==========================================
# TC002: Login dengan NIS (User/Siswa)
# ==========================================
try:
    print("\n=== TC002: Login dengan NIS (User/Siswa) ===", flush=True)
    logout()
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.clear()
    login_inp.send_keys("24090")
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.clear()
    pw_inp.send_keys("password")
    click_js(driver.find_element(By.XPATH, "//button[@type='submit']"))
    WebDriverWait(driver, 20).until(lambda d: "/beranda" in d.current_url)
    time.sleep(2.5)
    scroll_top()
    snap("TC002")
except Exception as e:
    print("TC002 error:", e, flush=True)

# ==========================================
# TC003: Validasi Login Password Salah
# ==========================================
try:
    print("\n=== TC003: Validasi Login Password Salah ===", flush=True)
    logout()
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.clear()
    login_inp.send_keys("aldejan@sch.id")
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.clear()
    pw_inp.send_keys("salah123")
    click_js(driver.find_element(By.XPATH, "//button[@type='submit']"))
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-error, .login-alert")))
    time.sleep(1)
    scroll_top()
    snap("TC003")
except Exception as e:
    print("TC003 error:", e, flush=True)

# ==========================================
# TC004: Beranda User (Ringkasan Pengaduan)
# ==========================================
try:
    print("\n=== TC004: Beranda User (Ringkasan Pengaduan) ===", flush=True)
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/beranda")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".card, table")))
    time.sleep(2)
    scroll_top()
    snap("TC004")
except Exception as e:
    print("TC004 error:", e, flush=True)

# ==========================================
# TC005: Ajukan Pengaduan Baru (Form Lengkap + Foto)
# ==========================================
try:
    print("\n=== TC005: Ajukan Pengaduan Baru (Form Lengkap + Foto) ===", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "kategori_id")))
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
    
    btn_kirim = driver.find_element(By.ID, "btn-kirim")
    click_js(btn_kirim)
    WebDriverWait(driver, 20).until(lambda d: "/pengaduan/" in d.current_url and "/ajukan" not in d.current_url)
    time.sleep(2)
    scroll_top()
    snap("TC005")
    tc5_url = driver.current_url
    tc5_id = tc5_url.rstrip("/").split("/")[-1].split("?")[0].split("#")[0]
    print(f"  Resulting ticket: {tc5_id} ({tc5_url})", flush=True)
except Exception as e:
    print("TC005 error:", e, flush=True)

# ==========================================
# TC006: Validasi Formulir Ajukan (Deskripsi Singkat)
# ==========================================
try:
    print("\n=== TC006: Validasi Formulir Ajukan (Deskripsi Singkat) ===", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "kategori_id")))
    kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
    kategori_sel.select_by_visible_text("Listrik")
    lokasi_inp = driver.find_element(By.ID, "lokasi")
    lokasi_inp.clear()
    lokasi_inp.send_keys("Lab Komputer")
    desk_inp = driver.find_element(By.ID, "deskripsi")
    desk_inp.clear()
    desk_inp.send_keys("rusak")
    btn_kirim = driver.find_element(By.ID, "btn-kirim")
    click_js(btn_kirim)
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".field-error, .has-error")))
    time.sleep(1)
    scroll_top()
    snap("TC006")
except Exception as e:
    print("TC006 error:", e, flush=True)

# ==========================================
# TC007: Filter Riwayat Status Pending
# ==========================================
try:
    print("\n=== TC007: Filter Riwayat Status Pending ===", flush=True)
    driver.get(f"{BASE_URL}/riwayat?status=pending")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, "table, .card")))
    time.sleep(2)
    scroll_top()
    snap("TC007")
except Exception as e:
    print("TC007 error:", e, flush=True)

# ==========================================
# TC008: Detail Pengaduan & Timeline
# ==========================================
try:
    print("\n=== TC008: Detail Pengaduan & Timeline ===", flush=True)
    # Open an approved complaint with rich timeline
    driver.get(f"{BASE_URL}/riwayat")
    time.sleep(2)
    # find detail link
    det_links = driver.find_elements(By.XPATH, "//a[contains(text(), 'Detail')]")
    if det_links:
        click_js(det_links[0])
        time.sleep(2)
    elif tc5_url:
        driver.get(tc5_url)
        time.sleep(2)
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".card, .timeline, .komentar")))
    scroll_mid()
    snap("TC008")
except Exception as e:
    print("TC008 error:", e, flush=True)

# ==========================================
# TC009: Kirim Komentar pada Pengaduan
# ==========================================
try:
    print("\n=== TC009: Kirim Komentar pada Pengaduan ===", flush=True)
    if tc5_url:
        driver.get(tc5_url)
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "isi-komentar")))
    komentar_box = driver.find_element(By.ID, "isi-komentar")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", komentar_box)
    time.sleep(0.5)
    komentar_box.send_keys("Mohon kabar perbaikannya, ruangan dipakai untuk ujian hari Jumat")
    btn_komentar = driver.find_element(By.XPATH, "//button[contains(text(), 'Kirim komentar')]")
    click_js(btn_komentar)
    time.sleep(2.5)
    try:
        driver.execute_script("document.getElementById('komentar').scrollIntoView({block: 'center'});")
        time.sleep(0.5)
    except:
        pass
    snap("TC009")
except Exception as e:
    print("TC009 error:", e, flush=True)

# ==========================================
# TC010: Admin Setujui Pengaduan Pending (Approved)
# ==========================================
try:
    print("\n=== TC010: Admin Setujui Pengaduan Pending (Approved) ===", flush=True)
    login("rudi@sch.id", "password")
    if tc5_id:
        driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    else:
        driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.XPATH, "//input[@name='status'][@value='approved']")))
    app_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='approved']")
    click_js(app_radio)
    tanggapan_area = driver.find_element(By.ID, "tanggapan")
    tanggapan_area.clear()
    tanggapan_area.send_keys("Laporan valid, akan ditugaskan ke teknisi")
    btn_simpan = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]")
    click_js(btn_simpan)
    time.sleep(2.5)
    scroll_top()
    snap("TC010")
except Exception as e:
    print("TC010 error:", e, flush=True)

# ==========================================
# TC011: Admin Tolak Pengaduan Tanpa Catatan (Validasi Rejected)
# ==========================================
try:
    print("\n=== TC011: Admin Tolak Pengaduan Tanpa Catatan (Validasi Rejected) ===", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".verif-ticket, .card")))
    time.sleep(1.5)
    pending_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    if pending_tickets:
        click_js(pending_tickets[0])
        time.sleep(2)
    rej_radio = WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.XPATH, "//input[@name='status'][@value='rejected']")))
    click_js(rej_radio)
    tanggapan_area = driver.find_element(By.ID, "tanggapan")
    tanggapan_area.clear()
    btn_simpan = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]")
    click_js(btn_simpan)
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".field-error, .has-error")))
    time.sleep(1)
    scroll_top()
    snap("TC011")
except Exception as e:
    print("TC011 error:", e, flush=True)

# ==========================================
# TC012: Admin Menugaskan Teknisi (Pak Budi Santoso)
# ==========================================
try:
    print("\n=== TC012: Admin Menugaskan Teknisi (Pak Budi Santoso) ===", flush=True)
    if tc5_id:
        driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    else:
        driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "teknisi_id")))
    teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
    for opt in teknisi_sel.options:
        if "Budi" in opt.text:
            teknisi_sel.select_by_visible_text(opt.text)
            break
    btn_tugas = driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]")
    click_js(btn_tugas)
    time.sleep(2.5)
    scroll_top()
    snap("TC012")
except Exception as e:
    print("TC012 error:", e, flush=True)

# ==========================================
# TC013: Teknisi Terima Tugas
# ==========================================
try:
    print("\n=== TC013: Teknisi Terima Tugas ===", flush=True)
    login("budi@sch.id", "password")
    if tc5_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    else:
        driver.get(f"{BASE_URL}/teknisi/tugas")
        t_link = WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'Detail')]")))
        click_js(t_link)
    terima_btn = WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.XPATH, "//button[contains(text(), 'Terima Tugas')]")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    click_js(terima_btn)
    time.sleep(2.5)
    scroll_top()
    snap("TC013")
except Exception as e:
    print("TC013 error:", e, flush=True)

# ==========================================
# Preparation for TC014: Assign another task to Budi for rejection test
# ==========================================
rej_test_id = None
try:
    print("\nAssigning another task to Budi for TC014 rejection test...", flush=True)
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".verif-ticket")))
    time.sleep(1.5)
    app_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
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
                btn_tugas = driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]")
                click_js(btn_tugas)
                time.sleep(2)
                print(f"  Assigned ticket {rej_test_id} to Budi for rejection test.", flush=True)
                break
            except Exception as e:
                print("Could not assign to Budi:", e, flush=True)
except Exception as e:
    print("Prep TC014 error:", e, flush=True)

# ==========================================
# TC014: Teknisi Tolak Tugas dengan Alasan
# ==========================================
try:
    print("\n=== TC014: Teknisi Tolak Tugas dengan Alasan ===", flush=True)
    login("budi@sch.id", "password")
    if rej_test_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{rej_test_id}")
        WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "alasan")))
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        alasan_area = driver.find_element(By.ID, "alasan")
        alasan_area.clear()
        alasan_area.send_keys("Sedang menangani perbaikan di ruangan lain")
        tolak_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Tolak Tugas')]")
        click_js(tolak_btn)
        time.sleep(2.5)
    scroll_top()
    snap("TC014")
except Exception as e:
    print("TC014 error:", e, flush=True)

# ==========================================
# TC015: Teknisi Mulai Pekerjaan & Simpan Progres (50%)
# ==========================================
try:
    print("\n=== TC015: Teknisi Mulai Pekerjaan & Simpan Progres (50%) ===", flush=True)
    if tc5_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    else:
        driver.get(f"{BASE_URL}/teknisi/tugas")
        click_js(driver.find_element(By.XPATH, "//a[contains(text(), 'Detail')]"))
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
    if mulai_btns:
        click_js(mulai_btns[0])
        time.sleep(2)
    
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "progres")))
    progres_inp = driver.find_element(By.ID, "progres")
    progres_inp.clear()
    progres_inp.send_keys("50")
    
    catatan_prog = driver.find_element(By.ID, "catatan")
    catatan_prog.clear()
    catatan_prog.send_keys("Penggantian lampu proyektor sedang dikerjakan")
    
    btn_prog = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]")
    click_js(btn_prog)
    time.sleep(2.5)
    scroll_top()
    snap("TC015")
except Exception as e:
    print("TC015 error:", e, flush=True)

# ==========================================
# TC016: Teknisi Unggah Foto Bukti Perbaikan
# ==========================================
try:
    print("\n=== TC016: Teknisi Unggah Foto Bukti Perbaikan ===", flush=True)
    if tc5_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "jenis")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    jenis_sel = Select(driver.find_element(By.ID, "jenis"))
    jenis_sel.select_by_value("sesudah")
    
    foto_upload = driver.find_element(By.ID, "foto")
    foto_upload.send_keys(IMG_SESUDAH)
    
    ket_inp = driver.find_element(By.ID, "keterangan")
    ket_inp.clear()
    ket_inp.send_keys("Proyektor sudah menyala normal")
    
    btn_upload = driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]")
    click_js(btn_upload)
    time.sleep(2.5)
    scroll_top()
    snap("TC016")
except Exception as e:
    print("TC016 error:", e, flush=True)

# ==========================================
# TC017: Teknisi Ajukan Penyelesaian Pekerjaan
# ==========================================
try:
    print("\n=== TC017: Teknisi Ajukan Penyelesaian Pekerjaan ===", flush=True)
    if tc5_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "catatan-selesai")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_selesai = driver.find_element(By.ID, "catatan-selesai")
    cat_selesai.clear()
    cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal")
    
    btn_ajukan = driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]")
    click_js(btn_ajukan)
    time.sleep(2.5)
    scroll_top()
    snap("TC017")
except Exception as e:
    print("TC017 error:", e, flush=True)

# ==========================================
# TC019: Admin Kembalikan untuk Revisi
# ==========================================
try:
    print("\n=== TC019: Admin Kembalikan untuk Revisi ===", flush=True)
    login("rudi@sch.id", "password")
    if tc5_id:
        driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "catatan-revisi")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_revisi = driver.find_element(By.ID, "catatan-revisi")
    cat_revisi.clear()
    cat_revisi.send_keys("Foto sesudah kurang jelas, mohon diulang")
    btn_revisi = driver.find_element(By.XPATH, "//button[contains(text(), 'Kembalikan untuk Revisi')]")
    click_js(btn_revisi)
    time.sleep(2.5)
    scroll_top()
    snap("TC019")
except Exception as e:
    print("TC019 error:", e, flush=True)

# ==========================================
# Re-submission by Teknisi after revision for TC018
# ==========================================
try:
    print("\nTeknisi re-submits after revision for TC018...", flush=True)
    login("budi@sch.id", "password")
    if tc5_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    rev_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Mengerjakan Revisi')]")
    if rev_btns:
        click_js(rev_btns[0])
        time.sleep(2)
    try:
        jenis_sel = Select(driver.find_element(By.ID, "jenis"))
        jenis_sel.select_by_value("sesudah")
        foto_upload = driver.find_element(By.ID, "foto")
        foto_upload.send_keys(IMG_SESUDAH)
        ket_inp = driver.find_element(By.ID, "keterangan")
        ket_inp.clear()
        ket_inp.send_keys("Foto sesudah perbaikan baru")
        btn_up = driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]")
        click_js(btn_up)
        time.sleep(2.5)
    except Exception as e:
        print("Re-upload error:", e, flush=True)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_selesai = driver.find_element(By.ID, "catatan-selesai")
        cat_selesai.clear()
        cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal setelah revisi")
        btn_ajukan = driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]")
        click_js(btn_ajukan)
        time.sleep(2.5)
    except Exception as e:
        print("Re-submit error:", e, flush=True)
except Exception as e:
    print("Re-submit exception:", e, flush=True)

# ==========================================
# TC018: Admin Validasi Hasil Pekerjaan (Selesai)
# ==========================================
try:
    print("\n=== TC018: Admin Validasi Hasil Pekerjaan (Selesai) ===", flush=True)
    login("rudi@sch.id", "password")
    if tc5_id:
        driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "catatan-validasi")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    cat_val = driver.find_element(By.ID, "catatan-validasi")
    cat_val.clear()
    cat_val.send_keys("Pekerjaan sudah baik")
    btn_selesai = driver.find_element(By.XPATH, "//button[contains(text(), 'Nyatakan Selesai')]")
    click_js(btn_selesai)
    time.sleep(2.5)
    scroll_top()
    snap("TC018")
except Exception as e:
    print("TC018 error:", e, flush=True)

# ==========================================
# TC020: Dashboard Admin (Statistik & Grafik)
# ==========================================
try:
    print("\n=== TC020: Dashboard Admin (Statistik & Grafik) ===", flush=True)
    driver.get(f"{BASE_URL}/admin/dashboard")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".card, .stat, table")))
    time.sleep(3)
    scroll_top()
    snap("TC020")
except Exception as e:
    print("TC020 error:", e, flush=True)

# ==========================================
# TC021: Admin Tambah Pengguna Baru
# ==========================================
try:
    print("\n=== TC021: Admin Tambah Pengguna Baru ===", flush=True)
    driver.get(f"{BASE_URL}/admin/pengguna")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, "table, .card")))
    time.sleep(1.5)
    del_btns = driver.find_elements(By.XPATH, "//tr[contains(., 'dewi@sch.id')]//button[contains(text(), 'Hapus')]")
    if del_btns:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        click_js(del_btns[0])
        time.sleep(2)

    driver.get(f"{BASE_URL}/admin/pengguna/create")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "name")))
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
    btn_simpan = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]")
    click_js(btn_simpan)
    time.sleep(2.5)
    scroll_top()
    snap("TC021")
except Exception as e:
    print("TC021 error:", e, flush=True)

# ==========================================
# TC022: Admin Tambah Sarana Baru
# ==========================================
try:
    print("\n=== TC022: Admin Tambah Sarana Baru ===", flush=True)
    driver.get(f"{BASE_URL}/admin/sarana/create")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "nama")))
    driver.find_element(By.ID, "nama").send_keys("Printer Warna Epson L3210")
    driver.find_element(By.ID, "lokasi").send_keys("Ruang Tata Usaha")
    btn_simpan = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]")
    click_js(btn_simpan)
    time.sleep(2.5)
    scroll_top()
    snap("TC022")
except Exception as e:
    print("TC022 error:", e, flush=True)

# ==========================================
# TC023: Admin Laporan (Filter & Export)
# ==========================================
try:
    print("\n=== TC023: Admin Laporan (Filter & Export) ===", flush=True)
    driver.get(f"{BASE_URL}/admin/laporan?dari=2026-11-01&sampai=2026-11-16&status=approved")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, "table, .filters")))
    time.sleep(2.5)
    scroll_top()
    snap("TC023")
except Exception as e:
    print("TC023 error:", e, flush=True)

# ==========================================
# TC024: Ubah Password Profil
# ==========================================
try:
    print("\n=== TC024: Ubah Password Profil ===", flush=True)
    driver.get(f"{BASE_URL}/profil")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "password_lama")))
    pw_lama = driver.find_element(By.ID, "password_lama")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
    time.sleep(0.5)
    pw_lama.send_keys("password")
    driver.find_element(By.ID, "password").send_keys("password123")
    driver.find_element(By.ID, "password_confirmation").send_keys("password123")
    btn_pw = driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]")
    click_js(btn_pw)
    time.sleep(2.5)
    scroll_top()
    snap("TC024")
    
    print("Reverting password back to standard 'password'...", flush=True)
    driver.get(f"{BASE_URL}/profil")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.ID, "password_lama")))
    pw_lama = driver.find_element(By.ID, "password_lama")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
    pw_lama.send_keys("password123")
    driver.find_element(By.ID, "password").send_keys("password")
    driver.find_element(By.ID, "password_confirmation").send_keys("password")
    btn_pw = driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]")
    click_js(btn_pw)
    time.sleep(2)
except Exception as e:
    print("TC024 error:", e, flush=True)

# ==========================================
# TC025: Notifikasi Pengaduan & Tandai Semua Dibaca
# ==========================================
try:
    print("\n=== TC025: Notifikasi Pengaduan & Tandai Semua Dibaca ===", flush=True)
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/notifikasi")
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".card, .notif-list, .empty")))
    time.sleep(1.5)
    tanda_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Tandai semua dibaca')]")
    if tanda_btns:
        click_js(tanda_btns[0])
        time.sleep(2)
    scroll_top()
    snap("TC025")
except Exception as e:
    print("TC025 error:", e, flush=True)

print("\n>>> ALL TEST CASES TC001 TO TC025 SCRIPT COMPLETE! <<<", flush=True)
driver.quit()
print("Browser closed.", flush=True)
