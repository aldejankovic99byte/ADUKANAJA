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
driver.set_page_load_timeout(45)

def snap(name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    driver.save_screenshot(path)
    print(f"  >>> [SAVED] {path} ({os.path.getsize(path)} bytes)", flush=True)

def click_js(el):
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", el)
    time.sleep(0.3)
    driver.execute_script("arguments[0].disabled = false; arguments[0].click();", el)

def scroll_top():
    driver.execute_script("window.scrollTo(0, 0);")
    time.sleep(0.5)

def scroll_mid():
    driver.execute_script("window.scrollTo(0, 300);")
    time.sleep(0.5)

def switch_user(user, pw):
    print(f"\n--- Switching user to {user} ---", flush=True)
    driver.delete_cookie("laravel-session")
    driver.delete_cookie("XSRF-TOKEN")
    driver.get(f"{BASE_URL}/login")
    time.sleep(2)
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.NAME, "login")))
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
    print(f"  Logged in, current URL: {driver.current_url}", flush=True)

try:
    # Warm up session and cookies
    driver.get(f"{BASE_URL}/login")
    time.sleep(3)

    # ========================================================
    # 1. USER SISWA WORKFLOW (TC002, TC004, TC005, TC006, TC007, TC008, TC009, TC024, TC025)
    # ========================================================
    print("\n================== USER SISWA ==================", flush=True)
    # TC002: Login dengan NIS
    print("\n[TC002] Login dengan NIS (24090)", flush=True)
    switch_user("24090", "password")
    scroll_top()
    snap("TC002")

    # TC004: Beranda User
    print("\n[TC004] Beranda User (Ringkasan Pengaduan)", flush=True)
    driver.get(f"{BASE_URL}/beranda")
    time.sleep(2)
    scroll_top()
    snap("TC004")

    # TC005: Form Ajukan Pengaduan Terisi Lengkap + Foto
    print("\n[TC005] Ajukan Pengaduan Form Terisi Lengkap", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    time.sleep(2)
    try:
        kat_sel = Select(driver.find_element(By.ID, "kategori_id"))
        kat_sel.select_by_index(1)
        lok_inp = driver.find_element(By.ID, "lokasi")
        lok_inp.clear()
        lok_inp.send_keys("Lab Komputer 1")
        desk_inp = driver.find_element(By.ID, "deskripsi")
        desk_inp.clear()
        desk_inp.send_keys("Lampu proyektor tidak menyala saat digunakan untuk praktikum.")
        foto_inp = driver.find_element(By.ID, "foto")
        foto_inp.send_keys(IMG_KERUSAKAN)
        time.sleep(1)
    except Exception as e:
        print("TC005 fill error:", e, flush=True)
    scroll_top()
    snap("TC005")

    # TC006: Validasi Formulir Ajukan (Deskripsi Singkat)
    print("\n[TC006] Validasi Formulir Ajukan (Deskripsi Singkat)", flush=True)
    try:
        desk_inp = driver.find_element(By.ID, "deskripsi")
        desk_inp.clear()
        desk_inp.send_keys("rusak")
        btn = driver.find_element(By.ID, "btn-kirim")
        driver.execute_script("arguments[0].disabled = false;", btn)
        click_js(btn)
        time.sleep(2)
    except Exception as e:
        print("TC006 error:", e, flush=True)
    scroll_top()
    snap("TC006")

    # TC007: Filter Riwayat Status Pending
    print("\n[TC007] Filter Riwayat Status Pending", flush=True)
    driver.get(f"{BASE_URL}/riwayat?status=pending")
    time.sleep(2)
    scroll_top()
    snap("TC007")

    # TC008: Detail Pengaduan & Timeline
    print("\n[TC008] Detail Pengaduan & Timeline", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/22")
    time.sleep(2)
    scroll_top()
    snap("TC008")

    # TC009: Kirim Komentar pada Pengaduan
    print("\n[TC009] Kirim Komentar pada Pengaduan", flush=True)
    try:
        driver.get(f"{BASE_URL}/pengaduan/22")
        time.sleep(2)
        komentar_box = driver.find_element(By.ID, "isi-komentar")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", komentar_box)
        time.sleep(0.5)
        komentar_box.clear()
        komentar_box.send_keys("Mohon info perkiraan perbaikannya, ruangan akan digunakan ujian hari Jumat.")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Kirim komentar')]")
        click_js(btn)
        time.sleep(2.5)
        driver.execute_script("var c = document.getElementById('komentar'); if(c) c.scrollIntoView({block: 'center'});")
        time.sleep(0.5)
    except Exception as e:
        print("TC009 error:", e, flush=True)
    snap("TC009")

    # TC024: Profil Ubah Password
    print("\n[TC024] Profil Ubah Password", flush=True)
    driver.get(f"{BASE_URL}/profil")
    time.sleep(2)
    try:
        pw_lama = driver.find_element(By.ID, "password_lama")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
        time.sleep(0.5)
        pw_lama.send_keys("password")
        driver.find_element(By.ID, "password").send_keys("password123")
        driver.find_element(By.ID, "password_confirmation").send_keys("password123")
    except Exception as e:
        print("TC024 fill error:", e, flush=True)
    scroll_top()
    snap("TC024")

    # TC025: Notifikasi Pengaduan
    print("\n[TC025] Notifikasi Pengaduan", flush=True)
    driver.get(f"{BASE_URL}/notifikasi")
    time.sleep(2)
    try:
        tanda_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Tandai semua dibaca')]")
        if tanda_btns:
            click_js(tanda_btns[0])
            time.sleep(1.5)
    except Exception as e:
        print("TC025 error:", e, flush=True)
    scroll_top()
    snap("TC025")

    # ========================================================
    # 2. TEKNISI WORKFLOW (TC013, TC014, TC015, TC016, TC017)
    # ========================================================
    print("\n================== TEKNISI WORKFLOW ==================", flush=True)
    switch_user("budi@sch.id", "password")

    # TC014: Tolak Tugas dengan Alasan (Diambil sebelum tugas diterima)
    print("\n[TC014] Tolak Tugas dengan Alasan", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/27")
    time.sleep(2)
    try:
        alasan = driver.find_element(By.ID, "alasan")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", alasan)
        time.sleep(0.5)
        alasan.clear()
        alasan.send_keys("Sedang menangani perbaikan listrik mendesak di ruang lab sebelah.")
        time.sleep(0.5)
    except Exception as e:
        print("TC014 error:", e, flush=True)
    scroll_mid()
    snap("TC014")

    # TC013: Detail Tugas (Terima Tugas)
    print("\n[TC013] Terima Tugas", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/27")
    time.sleep(2)
    try:
        terima_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Terima Tugas')]")
        click_js(terima_btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC013 click error:", e, flush=True)
    scroll_top()
    snap("TC013")

    # TC015: Pengerjaan & Progres (50%)
    print("\n[TC015] Pengerjaan & Input Progres (50%)", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/27")
    time.sleep(2)
    try:
        mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
        if mulai_btns:
            click_js(mulai_btns[0])
            time.sleep(2)
        prog = driver.find_element(By.ID, "progres")
        prog.clear()
        prog.send_keys("50")
        cat = driver.find_element(By.ID, "catatan")
        cat.clear()
        cat.send_keys("Penggantian komponen kabel dan lampu proyektor sedang berlangsung.")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC015 error:", e, flush=True)
    scroll_top()
    snap("TC015")

    # TC016: Foto Bukti Perbaikan
    print("\n[TC016] Unggah Foto Bukti Perbaikan", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/27")
    time.sleep(2)
    try:
        jenis = Select(driver.find_element(By.ID, "jenis"))
        jenis.select_by_value("sesudah")
        foto = driver.find_element(By.ID, "foto")
        foto.send_keys(IMG_SESUDAH)
        ket = driver.find_element(By.ID, "keterangan")
        ket.clear()
        ket.send_keys("Proyektor sudah selesai diperbaiki dan menyala normal.")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC016 error:", e, flush=True)
    scroll_top()
    snap("TC016")

    # TC017: Ajukan Penyelesaian
    print("\n[TC017] Ajukan Penyelesaian", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas/27")
    time.sleep(2)
    try:
        cat_sel = driver.find_element(By.ID, "catatan-selesai")
        cat_sel.clear()
        cat_sel.send_keys("Perbaikan sarana proyektor selesai dikerjakan, siap diuji coba.")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC017 error:", e, flush=True)
    scroll_top()
    snap("TC017")

    # ========================================================
    # 3. NEGATIVE TEST (TC003)
    # ========================================================
    print("\n================== NEGATIVE LOGIN TEST (TC003) ==================", flush=True)
    driver.delete_cookie("laravel-session")
    driver.delete_cookie("XSRF-TOKEN")
    driver.get(f"{BASE_URL}/login")
    time.sleep(2)
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.NAME, "login")))
    driver.find_element(By.NAME, "login").send_keys("aldejan@sch.id")
    driver.find_element(By.NAME, "password").send_keys("salah123")
    click_js(driver.find_element(By.XPATH, "//button[@type='submit']"))
    time.sleep(2)
    scroll_top()
    snap("TC003")

    print("\n>>> ALL REMAINING TEST CASES CAPTURED SUCCESSFULLY! <<<", flush=True)

finally:
    driver.quit()
    print("Browser closed.", flush=True)
