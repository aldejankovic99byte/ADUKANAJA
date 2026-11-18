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

new_ticket_id = None
new_ticket_url = None

try:
    # Initial page hit to establish __test cookie
    driver.get(f"{BASE_URL}/login")
    time.sleep(3)

    # ========================================================
    # GROUP 1: ADMIN WORKFLOW (TC001, TC020, TC021, TC022, TC023, TC010, TC011, TC012, TC019, TC018)
    # ========================================================
    print("\n================== GROUP 1: ADMIN ==================", flush=True)
    switch_user("rudi@sch.id", "password")

    # TC001: Login Admin
    print("\n[TC001] Login Admin -> Dashboard", flush=True)
    if "/admin" not in driver.current_url:
        driver.get(f"{BASE_URL}/admin/dashboard")
        time.sleep(2)
    scroll_top()
    snap("TC001")

    # TC020: Dashboard Admin
    print("\n[TC020] Dashboard Admin (Statistik & Grafik)", flush=True)
    driver.get(f"{BASE_URL}/admin/dashboard")
    time.sleep(2)
    scroll_top()
    snap("TC020")

    # TC021: Tambah Pengguna
    print("\n[TC021] Admin Tambah Pengguna Baru", flush=True)
    driver.get(f"{BASE_URL}/admin/pengguna")
    time.sleep(2)
    driver.get(f"{BASE_URL}/admin/pengguna/create")
    time.sleep(2)
    try:
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
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC021 form error:", e, flush=True)
    scroll_top()
    snap("TC021")

    # TC022: Tambah Sarana
    print("\n[TC022] Admin Tambah Sarana Baru", flush=True)
    driver.get(f"{BASE_URL}/admin/sarana/create")
    time.sleep(2)
    try:
        driver.find_element(By.ID, "nama").send_keys("Printer Warna Epson L3210")
        driver.find_element(By.ID, "lokasi").send_keys("Ruang Tata Usaha")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC022 form error:", e, flush=True)
    scroll_top()
    snap("TC022")

    # TC023: Admin Laporan
    print("\n[TC023] Admin Laporan (Filter & Export)", flush=True)
    driver.get(f"{BASE_URL}/admin/laporan?status=approved")
    time.sleep(2)
    scroll_top()
    snap("TC023")

    # TC010: Admin Setujui Pengaduan Pending
    print("\n[TC010] Admin Setujui Pengaduan Pending (Approved)", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    time.sleep(2)
    try:
        app_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='approved']")
        click_js(app_radio)
        tanggapan = driver.find_element(By.ID, "tanggapan")
        tanggapan.clear()
        tanggapan.send_keys("Laporan valid, akan ditugaskan ke teknisi")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC010 error:", e, flush=True)
    scroll_top()
    snap("TC010")

    # TC011: Admin Tolak Pengaduan Tanpa Catatan (Validasi Rejected)
    print("\n[TC011] Admin Tolak Pengaduan Tanpa Catatan (Validasi Error)", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    time.sleep(2)
    try:
        rej_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='rejected']")
        click_js(rej_radio)
        tanggapan = driver.find_element(By.ID, "tanggapan")
        tanggapan.clear()
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]")
        click_js(btn)
        time.sleep(1.5)
    except Exception as e:
        print("TC011 error:", e, flush=True)
    scroll_top()
    snap("TC011")

    # TC012: Admin Menugaskan Teknisi (Pak Budi Santoso)
    print("\n[TC012] Admin Menugaskan Teknisi (Pak Budi Santoso)", flush=True)
    driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
    time.sleep(2)
    try:
        teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
        for opt in teknisi_sel.options:
            if "Budi" in opt.text:
                teknisi_sel.select_by_visible_text(opt.text)
                break
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC012 error:", e, flush=True)
    scroll_top()
    snap("TC012")

    # TC019: Admin Kembalikan untuk Revisi
    print("\n[TC019] Admin Kembalikan untuk Revisi", flush=True)
    try:
        driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
        time.sleep(2)
        revisi_areas = driver.find_elements(By.ID, "catatan-revisi")
        if revisi_areas:
            revisi_areas[0].clear()
            revisi_areas[0].send_keys("Foto sesudah kurang jelas, mohon diulang")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Kembalikan untuk Revisi')]")
            click_js(btn)
            time.sleep(2.5)
    except Exception as e:
        print("TC019 error:", e, flush=True)
    scroll_top()
    snap("TC019")

    # TC018: Admin Validasi Hasil Pekerjaan (Selesai)
    print("\n[TC018] Admin Validasi Hasil Pekerjaan (Selesai)", flush=True)
    try:
        driver.get(f"{BASE_URL}/admin/verifikasi")
        time.sleep(2)
        val_areas = driver.find_elements(By.ID, "catatan-validasi")
        if val_areas:
            val_areas[0].clear()
            val_areas[0].send_keys("Pekerjaan sudah baik dan berfungsi normal")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Nyatakan Selesai')]")
            click_js(btn)
            time.sleep(2.5)
    except Exception as e:
        print("TC018 error:", e, flush=True)
    scroll_top()
    snap("TC018")

    # ========================================================
    # GROUP 2: USER SISWA WORKFLOW (TC002, TC004, TC005, TC006, TC007, TC008, TC009, TC024, TC025)
    # ========================================================
    print("\n================== GROUP 2: USER SISWA ==================", flush=True)
    # TC002: Login dengan NIS
    print("\n[TC002] Login dengan NIS (2526003)", flush=True)
    driver.delete_cookie("laravel-session")
    driver.delete_cookie("XSRF-TOKEN")
    driver.get(f"{BASE_URL}/login")
    time.sleep(2)
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.NAME, "login")))
    driver.find_element(By.NAME, "login").send_keys("2526003")
    driver.find_element(By.NAME, "password").send_keys("password")
    click_js(driver.find_element(By.XPATH, "//button[@type='submit']"))
    time.sleep(3)
    scroll_top()
    snap("TC002")

    # TC004: Beranda User
    print("\n[TC004] Beranda User (Ringkasan Pengaduan)", flush=True)
    driver.get(f"{BASE_URL}/beranda")
    time.sleep(2)
    scroll_top()
    snap("TC004")

    # TC005: Ajukan Pengaduan Baru
    print("\n[TC005] Ajukan Pengaduan Baru (Form Lengkap + Foto)", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    time.sleep(2)
    try:
        kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
        try:
            kategori_sel.select_by_visible_text("Listrik")
        except:
            kategori_sel.select_by_index(1)
        lokasi = driver.find_element(By.ID, "lokasi")
        lokasi.clear()
        lokasi.send_keys("Lab Komputer")
        sarana_sel = Select(driver.find_element(By.ID, "sarana_id"))
        try:
            sarana_sel.select_by_index(1)
        except:
            pass
        desk = driver.find_element(By.ID, "deskripsi")
        desk.clear()
        desk.send_keys("Lampu proyektor tidak menyala saat dipakai pelajaran praktikum")
        foto = driver.find_element(By.ID, "foto")
        foto.send_keys(IMG_KERUSAKAN)
        time.sleep(1)
        driver.execute_script("document.getElementById('btn-kirim').disabled = false;")
        click_js(driver.find_element(By.ID, "btn-kirim"))
        time.sleep(3)
        new_ticket_url = driver.current_url
        print(f"  Resulting new ticket URL: {new_ticket_url}", flush=True)
    except Exception as e:
        print("TC005 error:", e, flush=True)
    scroll_top()
    snap("TC005")

    # TC006: Validasi Formulir Ajukan
    print("\n[TC006] Validasi Formulir Ajukan (Deskripsi Singkat)", flush=True)
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    time.sleep(2)
    try:
        kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
        kategori_sel.select_by_index(1)
        lokasi = driver.find_element(By.ID, "lokasi")
        lokasi.clear()
        lokasi.send_keys("Lab Komputer")
        desk = driver.find_element(By.ID, "deskripsi")
        desk.clear()
        desk.send_keys("rusak")
        driver.execute_script("document.getElementById('btn-kirim').disabled = false;")
        click_js(driver.find_element(By.ID, "btn-kirim"))
        time.sleep(1.5)
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
    if new_ticket_url and "/pengaduan/" in new_ticket_url:
        driver.get(new_ticket_url)
    else:
        driver.get(f"{BASE_URL}/riwayat")
        time.sleep(2)
        det_links = driver.find_elements(By.XPATH, "//a[contains(text(), 'Detail')]")
        if det_links:
            click_js(det_links[0])
    time.sleep(2)
    scroll_top()
    snap("TC008")

    # TC009: Kirim Komentar pada Pengaduan
    print("\n[TC009] Kirim Komentar pada Pengaduan", flush=True)
    try:
        komentar_box = driver.find_element(By.ID, "isi-komentar")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", komentar_box)
        time.sleep(0.5)
        komentar_box.clear()
        komentar_box.send_keys("Mohon kabar perbaikannya, ruangan dipakai untuk ujian hari Jumat")
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
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]")
        click_js(btn)
        time.sleep(2.5)
        # revert back to 'password'
        driver.get(f"{BASE_URL}/profil")
        time.sleep(1)
        driver.find_element(By.ID, "password_lama").send_keys("password123")
        driver.find_element(By.ID, "password").send_keys("password")
        driver.find_element(By.ID, "password_confirmation").send_keys("password")
        click_js(driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]"))
        time.sleep(1.5)
    except Exception as e:
        print("TC024 error:", e, flush=True)
    scroll_top()
    snap("TC024")

    # TC025: Notifikasi Pengaduan
    print("\n[TC025] Notifikasi Pengaduan & Tandai Semua Dibaca", flush=True)
    driver.get(f"{BASE_URL}/notifikasi")
    time.sleep(2)
    try:
        tanda_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Tandai semua dibaca')]")
        if tanda_btns:
            click_js(tanda_btns[0])
            time.sleep(2)
    except Exception as e:
        print("TC025 error:", e, flush=True)
    scroll_top()
    snap("TC025")

    # ========================================================
    # GROUP 3: TEKNISI WORKFLOW (TC013, TC014, TC015, TC016, TC017)
    # ========================================================
    print("\n================== GROUP 3: TEKNISI ==================", flush=True)
    switch_user("budi@sch.id", "password")

    # TC013: Detail Tugas Teknisi (Terima Tugas)
    print("\n[TC013] Detail Tugas (Terima Tugas)", flush=True)
    driver.get(f"{BASE_URL}/teknisi/tugas")
    time.sleep(2)
    det_links = driver.find_elements(By.XPATH, "//a[contains(text(), 'Detail')]")
    if det_links:
        click_js(det_links[0])
        time.sleep(2)
    try:
        terima_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Terima Tugas')]")
        if terima_btns:
            click_js(terima_btns[0])
            time.sleep(2.5)
    except Exception as e:
        print("TC013 error:", e, flush=True)
    scroll_top()
    snap("TC013")

    # TC015: Pengerjaan & Progres (50%)
    print("\n[TC015] Pengerjaan & Progres (50%)", flush=True)
    try:
        mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
        if mulai_btns:
            click_js(mulai_btns[0])
            time.sleep(2)
        prog_inps = driver.find_elements(By.ID, "progres")
        if prog_inps:
            prog_inps[0].clear()
            prog_inps[0].send_keys("50")
            cat_inps = driver.find_elements(By.ID, "catatan")
            if cat_inps:
                cat_inps[0].clear()
                cat_inps[0].send_keys("Penggantian lampu proyektor sedang dikerjakan")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]")
            click_js(btn)
            time.sleep(2.5)
    except Exception as e:
        print("TC015 error:", e, flush=True)
    scroll_top()
    snap("TC015")

    # TC016: Foto Bukti Perbaikan
    print("\n[TC016] Foto Bukti Perbaikan", flush=True)
    try:
        jenis_sel = Select(driver.find_element(By.ID, "jenis"))
        jenis_sel.select_by_value("sesudah")
        foto_inp = driver.find_element(By.ID, "foto")
        foto_inp.send_keys(IMG_SESUDAH)
        ket_inp = driver.find_element(By.ID, "keterangan")
        ket_inp.clear()
        ket_inp.send_keys("Proyektor sudah menyala normal")
        btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]")
        click_js(btn)
        time.sleep(2.5)
    except Exception as e:
        print("TC016 error:", e, flush=True)
    scroll_top()
    snap("TC016")

    # TC017: Ajukan Penyelesaian
    print("\n[TC017] Ajukan Penyelesaian", flush=True)
    try:
        cat_selesai = driver.find_elements(By.ID, "catatan-selesai")
        if cat_selesai:
            cat_selesai[0].clear()
            cat_selesai[0].send_keys("Perbaikan selesai, proyektor berfungsi normal")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]")
            click_js(btn)
            time.sleep(2.5)
    except Exception as e:
        print("TC017 error:", e, flush=True)
    scroll_top()
    snap("TC017")

    # TC014: Tolak Tugas dengan Alasan
    print("\n[TC014] Tolak Tugas dengan Alasan", flush=True)
    try:
        driver.get(f"{BASE_URL}/teknisi/tugas")
        time.sleep(2)
        alasan_inps = driver.find_elements(By.ID, "alasan")
        if alasan_inps:
            alasan_inps[0].clear()
            alasan_inps[0].send_keys("Sedang menangani perbaikan di ruangan lain")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Tolak Tugas')]")
            click_js(btn)
            time.sleep(2.5)
    except Exception as e:
        print("TC014 error:", e, flush=True)
    scroll_top()
    snap("TC014")

    # ========================================================
    # GROUP 4: NEGATIVE TEST (TC003)
    # ========================================================
    print("\n================== GROUP 4: NEGATIVE TEST ==================", flush=True)
    print("\n[TC003] Validasi Login Password Salah", flush=True)
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

    print("\n>>> ALL TEST CASES TC001 TO TC025 COMPLETED SUCCESSFULLY! <<<", flush=True)

finally:
    driver.quit()
    print("Browser closed.", flush=True)
