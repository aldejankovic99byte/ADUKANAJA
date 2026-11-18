import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')

driver = webdriver.Chrome(options=chrome_options)
try:
    driver.get('https://adukanaja.rf.gd/login')
    WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.NAME, 'login')))
    driver.find_element(By.NAME, 'login').send_keys('rudi@sch.id')
    driver.find_element(By.NAME, 'password').send_keys('password')
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    WebDriverWait(driver, 20).until(lambda d: '/admin' in d.current_url)

    driver.get('https://adukanaja.rf.gd/admin/pengguna/2/edit')
    nis_el = WebDriverWait(driver, 20).until(EC.presence_of_element_located((By.NAME, 'nis')))
    nis_el.clear()
    nis_el.send_keys('2526003')
    btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]")
    driver.execute_script("arguments[0].click();", btn)
    time.sleep(3)
    print('Aldejan NIS successfully updated to 2526003!')
finally:
    driver.quit()
