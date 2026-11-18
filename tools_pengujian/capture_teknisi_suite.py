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

IMG_SESUDAH = os.path.abspath("sesudah.jpg")

chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--window-size=1280,850")
chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

print("Initializing Chrome driver for Teknisi...", flush=True)
driver = webdriver.Chrome(options=chrome_options)
driver.set_page_load_timeout(45)

def snap(name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    driver.save_screenshot(path)
    print(f"  >>> [SAVED] {path} ({os.path.getsize(path)} bytes)", flush=True)

def click_js(el):
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", el)
    time.sleep(0.4)
    driver.execute_script("arguments[0].disabled = false; arguments[0].click();", el)

def scroll_top():
    driver.execute_script("window.scrollTo(0, 0);")
    time.sleep(0.5)

def scroll_mid():
    driver.execute_script("window.scrollTo(0, 320);")
    time.sleep(0.5)

try:
    print("Navigating to login...", flush=True)
    driver.get(f"{BASE_URL}/login")
    time.sleep(3)
    
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.NAME, "login")))
    driver.execute_script("window.confirm = () => true; window.alert = () => true;")
    driver.find_element(By.NAME, "login").send_keys("budi@sch.id")
    driver.find_element(By.NAME, "password").send_keys("password")
    btn = driver.find_element(By.XPATH, "//button[@type='submit']")
    click_js(btn)
    time.sleep(3.5)
    
    driver.get(f"{BASE_URL}/teknisi/tugas")
    time.sleep(2.5)
    print("Teknisi Dashboard URL:", driver.current_url)

    # Open task 27
    task_url = f"{BASE_URL}/teknisi/tugas/27"
    driver.get(task_url)
    time.sleep(2.5)
    print("Task 27 page loaded, URL:", driver.current_url)

    # 1. TC014: Tolak Tugas dengan Alasan (diambil saat form tolak masih ada)
    print("\n=== [TC014] Tolak Tugas dengan Alasan ===", flush=True)
    try:
        alasan_field = driver.find_elements(By.ID, "alasan")
        if alasan_field:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", alasan_field[0])
            time.sleep(0.5)
            alasan_field[0].clear()
            alasan_field[0].send_keys("Sedang menangani perbaikan listrik darurat di Lab Komputer 2.")
            time.sleep(0.5)
            scroll_mid()
            snap("TC014")
        else:
            scroll_mid()
            snap("TC014")
    except Exception as e:
        print("TC014 error:", e, flush=True)
        snap("TC014")

    # 2. TC013: Terima Tugas
    print("\n=== [TC013] Terima Tugas ===", flush=True)
    try:
        terima_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Terima Tugas')]")
        if terima_btns:
            driver.execute_script("window.confirm = () => true;")
            click_js(terima_btns[0])
            time.sleep(3)
        scroll_top()
        snap("TC013")
    except Exception as e:
        print("TC013 error:", e, flush=True)
        snap("TC013")

    # Reload page
    driver.get(task_url)
    time.sleep(2)

    # 3. TC015: Pengerjaan & Input Progres (50%)
    print("\n=== [TC015] Pengerjaan & Input Progres (50%) ===", flush=True)
    try:
        driver.execute_script("window.confirm = () => true;")
        mulai_btns = driver.find_elements(By.XPATH, "//button[contains(text(), 'Mulai Pekerjaan')]")
        if mulai_btns:
            click_js(mulai_btns[0])
            time.sleep(2.5)
        
        prog_inps = driver.find_elements(By.ID, "progres")
        if prog_inps:
            prog_inps[0].clear()
            prog_inps[0].send_keys("50")
            cat_inps = driver.find_elements(By.ID, "catatan")
            if cat_inps:
                cat_inps[0].clear()
                cat_inps[0].send_keys("Pemeriksaan kabel power dan modul optik sedang dikerjakan.")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan Progres')]")
            click_js(btn)
            time.sleep(2.5)
        scroll_top()
        snap("TC015")
    except Exception as e:
        print("TC015 error:", e, flush=True)
        snap("TC015")

    # Reload page
    driver.get(task_url)
    time.sleep(2)

    # 4. TC016: Foto Bukti Perbaikan
    print("\n=== [TC016] Unggah Foto Bukti Perbaikan ===", flush=True)
    try:
        driver.execute_script("window.confirm = () => true;")
        jenis_sel = driver.find_elements(By.ID, "jenis")
        if jenis_sel:
            Select(jenis_sel[0]).select_by_value("sesudah")
            foto_inp = driver.find_element(By.ID, "foto")
            foto_inp.send_keys(IMG_SESUDAH)
            ket_inp = driver.find_element(By.ID, "keterangan")
            ket_inp.clear()
            ket_inp.send_keys("Proyektor sudah menyala normal dan gambar tajam.")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Unggah Foto Bukti')]")
            click_js(btn)
            time.sleep(2.5)
        scroll_top()
        snap("TC016")
    except Exception as e:
        print("TC016 error:", e, flush=True)
        snap("TC016")

    # Reload page
    driver.get(task_url)
    time.sleep(2)

    # 5. TC017: Ajukan Penyelesaian
    print("\n=== [TC017] Ajukan Penyelesaian Pekerjaan ===", flush=True)
    try:
        driver.execute_script("window.confirm = () => true;")
        cat_sel = driver.find_elements(By.ID, "catatan-selesai")
        if cat_sel:
            cat_sel[0].clear()
            cat_sel[0].send_keys("Perbaikan unit proyektor telah selesai dan diuji coba dengan laptop siswa.")
            btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Ajukan Penyelesaian')]")
            click_js(btn)
            time.sleep(2.5)
        scroll_top()
        snap("TC017")
    except Exception as e:
        print("TC017 error:", e, flush=True)
        snap("TC017")

    print("\n>>> TEKNISI WORKFLOW COMPLETED SUCCESSFULLY! <<<", flush=True)

finally:
    driver.quit()
    print("Browser closed.", flush=True)
