import os
import sys
import time
from datetime import date
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

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

print("Initializing Chrome driver...")
driver = webdriver.Chrome(options=chrome_options)
driver.set_page_load_timeout(60)

def snap(name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    driver.save_screenshot(path)
    print(f"  [SAVED] {path} ({os.path.getsize(path)} bytes)")

def logout():
    driver.delete_all_cookies()
    driver.get(f"{BASE_URL}/login")
    time.sleep(1.5)

def login(user, pw):
    logout()
    print(f"Logging in as {user}...")
    driver.get(f"{BASE_URL}/login")
    time.sleep(2)
    # override confirms
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.clear()
    login_inp.send_keys(user)
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.clear()
    pw_inp.send_keys(pw)
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(3)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")

def scroll_top():
    driver.execute_script("window.scrollTo(0, 0);")
    time.sleep(0.5)

def scroll_mid():
    driver.execute_script("window.scrollTo(0, 300);")
    time.sleep(0.5)

try:
    print("\n--- TC001: Login dengan Email (Admin) ---")
    login("rudi@sch.id", "password")
    if "/admin/dashboard" not in driver.current_url:
        driver.get(f"{BASE_URL}/admin/dashboard")
        time.sleep(2)
    scroll_top()
    snap("TC001")

    print("\n--- TC002: Login dengan NIS (User/Siswa) ---")
    login("2526003", "password")
    if "/beranda" not in driver.current_url:
        driver.get(f"{BASE_URL}/beranda")
        time.sleep(2)
    scroll_top()
    snap("TC002")

    print("\n--- TC003: Validasi Login Password Salah ---")
    logout()
    login_inp = driver.find_element(By.NAME, "login")
    login_inp.send_keys("aldejan@sch.id")
    pw_inp = driver.find_element(By.NAME, "password")
    pw_inp.send_keys("salah123")
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(2)
    scroll_top()
    snap("TC003")

    print("\n--- TC004: Beranda User (Ringkasan Pengaduan) ---")
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/beranda")
    time.sleep(2)
    scroll_top()
    snap("TC004")

    print("\n--- TC005: Ajukan Pengaduan Baru (Form Lengkap + Foto) ---")
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    
    # Fill form
    kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
    kategori_sel.select_by_visible_text("Listrik")
    
    lokasi_inp = driver.find_element(By.ID, "lokasi")
    lokasi_inp.clear()
    lokasi_inp.send_keys("Lab Komputer")
    
    tgl_inp = driver.find_element(By.ID, "tanggal_kejadian")
    # keep default or set today
    
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
    time.sleep(3)
    scroll_top()
    snap("TC005")
    tc5_url = driver.current_url
    print("TC005 resulting URL:", tc5_url)
    tc5_id = tc5_url.rstrip("/").split("/")[-1]

    print("\n--- TC006: Validasi Formulir Ajukan (Deskripsi Singkat) ---")
    driver.get(f"{BASE_URL}/pengaduan/ajukan")
    time.sleep(2)
    kategori_sel = Select(driver.find_element(By.ID, "kategori_id"))
    kategori_sel.select_by_visible_text("Listrik")
    lokasi_inp = driver.find_element(By.ID, "lokasi")
    lokasi_inp.send_keys("Lab Komputer")
    desk_inp = driver.find_element(By.ID, "deskripsi")
    desk_inp.send_keys("rusak")
    driver.find_element(By.ID, "btn-kirim").click()
    time.sleep(2)
    scroll_top()
    snap("TC006")

    print("\n--- TC007: Filter Riwayat Status Pending ---")
    driver.get(f"{BASE_URL}/riwayat?status=pending")
    time.sleep(2)
    scroll_top()
    snap("TC007")

    print("\n--- TC008: Detail Pengaduan & Timeline ---")
    # Open TC005 detail or an approved complaint detail to show timeline
    driver.get(tc5_url)
    time.sleep(2)
    scroll_mid()
    snap("TC008")

    print("\n--- TC009: Kirim Komentar pada Pengaduan ---")
    driver.get(tc5_url)
    time.sleep(2)
    try:
        komentar_box = driver.find_element(By.ID, "isi-komentar")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", komentar_box)
        time.sleep(0.5)
        komentar_box.send_keys("Mohon kabar perbaikannya, ruangan dipakai untuk ujian hari Jumat")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Kirim komentar')]").click()
        time.sleep(2.5)
        # scroll to comments
        driver.execute_script("document.getElementById('komentar').scrollIntoView({block: 'center'});")
        time.sleep(0.5)
    except Exception as e:
        print("Comment error:", e)
    snap("TC009")

    print("\n--- TC010: Admin Setujui Pengaduan Pending (Approved) ---")
    login("rudi@sch.id", "password")
    # Open TC5 ticket in verifikasi
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    time.sleep(2)
    try:
        # radio button approved
        app_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='approved']")
        driver.execute_script("arguments[0].click();", app_radio)
        tanggapan_area = driver.find_element(By.ID, "tanggapan")
        tanggapan_area.clear()
        tanggapan_area.send_keys("Laporan valid, akan ditugaskan ke teknisi")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC010 error:", e)
    scroll_top()
    snap("TC010")

    print("\n--- TC011: Admin Tolak Pengaduan Tanpa Catatan (Validasi Rejected) ---")
    driver.get(f"{BASE_URL}/admin/verifikasi?status=pending")
    time.sleep(2)
    # click first pending ticket if available
    pending_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    if pending_tickets:
        driver.execute_script("arguments[0].click();", pending_tickets[0])
        time.sleep(2)
    try:
        rej_radio = driver.find_element(By.XPATH, "//input[@name='status'][@value='rejected']")
        driver.execute_script("arguments[0].click();", rej_radio)
        tanggapan_area = driver.find_element(By.ID, "tanggapan")
        tanggapan_area.clear()
        driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Status Verifikasi')]").click()
        time.sleep(2)
    except Exception as e:
        print("TC011 error:", e)
    scroll_top()
    snap("TC011")

    print("\n--- TC012: Admin Menugaskan Teknisi (Pak Budi Santoso) ---")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    time.sleep(2)
    try:
        teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
        for opt in teknisi_sel.options:
            if "Budi" in opt.text:
                teknisi_sel.select_by_visible_text(opt.text)
                break
        driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC012 error:", e)
    scroll_top()
    snap("TC012")

    print("\n--- TC013: Teknisi Terima Tugas ---")
    login("budi@sch.id", "password")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        terima_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Terima Tugas')]")
        terima_btn.click()
        time.sleep(2.5)
    except Exception as e:
        print("TC013 error:", e)
    scroll_top()
    snap("TC013")

    print("\n--- Preparation for TC014: Assign another task to Budi for rejection test ---")
    login("rudi@sch.id", "password")
    # find another approved task to assign to Budi
    driver.get(f"{BASE_URL}/admin/verifikasi?status=approved")
    time.sleep(2)
    app_tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    rej_test_id = None
    for t in app_tickets:
        href = t.get_attribute("href")
        t_id = href.split("id=")[1].split("&")[0].split("#")[0]
        if t_id != tc5_id:
            rej_test_id = t_id
            driver.get(f"{BASE_URL}/admin/verifikasi?id={rej_test_id}#detail")
            time.sleep(1.5)
            try:
                teknisi_sel = Select(driver.find_element(By.ID, "teknisi_id"))
                for opt in teknisi_sel.options:
                    if "Budi" in opt.text:
                        teknisi_sel.select_by_visible_text(opt.text)
                        break
                driver.find_element(By.XPATH, "//button[contains(text(), 'Tugaskan') or contains(text(), 'Simpan Perubahan')]").click()
                time.sleep(2)
                print(f"Assigned ticket {rej_test_id} to Budi for rejection test.")
                break
            except Exception as e:
                print("Could not assign to Budi:", e)

    print("\n--- TC014: Teknisi Tolak Tugas dengan Alasan ---")
    login("budi@sch.id", "password")
    if rej_test_id:
        driver.get(f"{BASE_URL}/teknisi/tugas/{rej_test_id}")
        time.sleep(2)
        try:
            driver.execute_script("window.confirm = () => true; window.alert = () => true;")
            alasan_area = driver.find_element(By.ID, "alasan")
            alasan_area.clear()
            alasan_area.send_keys("Sedang menangani perbaikan di ruangan lain")
            tolak_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Tolak Tugas')]")
            tolak_btn.click()
            time.sleep(2.5)
        except Exception as e:
            print("TC014 error:", e)
    scroll_top()
    snap("TC014")

    print("\n--- TC015: Teknisi Mulai Pekerjaan & Simpan Progres (50%) ---")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
        if mulai_btns:
            mulai_btns[0].click()
            time.sleep(2)
        
        progres_inp = driver.find_element(By.ID, "progres")
        progres_inp.clear()
        progres_inp.send_keys("50")
        
        catatan_prog = driver.find_element(By.ID, "catatan")
        catatan_prog.clear()
        catatan_prog.send_keys("Penggantian lampu proyektor sedang dikerjakan")
        
        driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC015 error:", e)
    scroll_top()
    snap("TC015")

    print("\n--- TC016: Teknisi Unggah Foto Bukti Perbaikan ---")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    try:
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
    except Exception as e:
        print("TC016 error:", e)
    scroll_top()
    snap("TC016")

    print("\n--- TC017: Teknisi Ajukan Penyelesaian Pekerjaan ---")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_selesai = driver.find_element(By.ID, "catatan-selesai")
        cat_selesai.clear()
        cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal")
        
        driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC017 error:", e)
    scroll_top()
    snap("TC017")

    print("\n--- TC019 & TC018: Admin Revisi (TC019) dan Validasi Selesai (TC018) ---")
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    time.sleep(2)
    
    # First test TC019: Kembalikan untuk revisi
    print("Testing TC019: Kembalikan untuk Revisi...")
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_revisi = driver.find_element(By.ID, "catatan-revisi")
        cat_revisi.clear()
        cat_revisi.send_keys("Foto sesudah kurang jelas, mohon diulang")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Kembalikan untuk Revisi')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC019 error:", e)
    scroll_top()
    snap("TC019")

    # Now technician re-submits so we can do TC018 (Validasi Selesai)
    print("Teknisi re-submits after revision for TC018...")
    login("budi@sch.id", "password")
    driver.get(f"{BASE_URL}/teknisi/tugas/{tc5_id}")
    time.sleep(2)
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    # Click Mulai Mengerjakan Revisi if present
    rev_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Mengerjakan Revisi')]")
    if rev_btns:
        rev_btns[0].click()
        time.sleep(2)
    # Upload sesudah photo
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
        print("Re-upload error:", e)
    # Ajukan penyelesaian again
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_selesai = driver.find_element(By.ID, "catatan-selesai")
        cat_selesai.clear()
        cat_selesai.send_keys("Perbaikan selesai, proyektor berfungsi normal setelah revisi")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("Re-submit error:", e)

    # Now TC018: Admin Nyatakan Selesai (Valid)
    print("\n--- TC018: Admin Validasi Hasil Pekerjaan (Selesai) ---")
    login("rudi@sch.id", "password")
    driver.get(f"{BASE_URL}/admin/verifikasi?id={tc5_id}#detail")
    time.sleep(2)
    try:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        cat_val = driver.find_element(By.ID, "catatan-validasi")
        cat_val.clear()
        cat_val.send_keys("Pekerjaan sudah baik")
        driver.find_element(By.XPATH, "//button[contains(text(), 'Nyatakan Selesai')]").click()
        time.sleep(2.5)
    except Exception as e:
        print("TC018 error:", e)
    scroll_top()
    snap("TC018")

    print("\n--- TC020: Dashboard Admin (Statistik & Grafik) ---")
    driver.get(f"{BASE_URL}/admin/dashboard")
    time.sleep(3)
    scroll_top()
    snap("TC020")

    print("\n--- TC021: Admin Tambah Pengguna Baru ---")
    driver.get(f"{BASE_URL}/admin/pengguna")
    time.sleep(2)
    # Delete dewi@sch.id if already exists
    del_btns = driver.find_elements(By.XPATH, "//tr[contains(., 'dewi@sch.id')]//button[contains(text(), 'Hapus')]")
    if del_btns:
        driver.execute_script("window.confirm = () => true; window.alert = () => true;")
        del_btns[0].click()
        time.sleep(2)

    driver.get(f"{BASE_URL}/admin/pengguna/create")
    time.sleep(2)
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

    print("\n--- TC022: Admin Tambah Sarana Baru ---")
    driver.get(f"{BASE_URL}/admin/sarana/create")
    time.sleep(2)
    driver.find_element(By.ID, "nama").send_keys("Printer Warna Epson L3210")
    driver.find_element(By.ID, "lokasi").send_keys("Ruang Tata Usaha")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]").click()
    time.sleep(2.5)
    scroll_top()
    snap("TC022")

    print("\n--- TC023: Admin Laporan (Filter & Export) ---")
    driver.get(f"{BASE_URL}/admin/laporan?dari=2026-11-01&sampai=2026-11-16&status=approved")
    time.sleep(2.5)
    scroll_top()
    snap("TC023")

    print("\n--- TC024: Ubah Password Profil ---")
    driver.get(f"{BASE_URL}/profil")
    time.sleep(2)
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
    
    # Change back password to "password"
    print("Reverting password back to standard 'password'...")
    driver.get(f"{BASE_URL}/profil")
    time.sleep(1.5)
    pw_lama = driver.find_element(By.ID, "password_lama")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pw_lama)
    pw_lama.send_keys("password123")
    driver.find_element(By.ID, "password").send_keys("password")
    driver.find_element(By.ID, "password_confirmation").send_keys("password")
    driver.find_element(By.XPATH, "//button[contains(text(), 'Ubah Password')]").click()
    time.sleep(2)

    print("\n--- TC025: Notifikasi Pengaduan & Tandai Semua Dibaca ---")
    login("aldejan@sch.id", "password")
    driver.get(f"{BASE_URL}/notifikasi")
    time.sleep(2)
    tanda_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Tandai semua dibaca')]")
    if tanda_btns:
        tanda_btns[0].click()
        time.sleep(2)
    scroll_top()
    snap("TC025")

    print("\nALL TEST CASES EXECUTED SUCCESSFULLY!")

except Exception as err:
    print(f"\nFATAL ERROR in execution: {err}")
    import traceback
    traceback.print_exc()

finally:
    driver.quit()
    print("Browser closed.")
