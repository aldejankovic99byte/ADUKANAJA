import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')

driver = webdriver.Chrome(options=chrome_options)
try:
    driver.get('https://adukanaja.rf.gd/login')
    time.sleep(3)
    driver.find_element(By.NAME, 'login').send_keys('rudi@sch.id')
    driver.find_element(By.NAME, 'password').send_keys('password')
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(3)
    driver.get('https://adukanaja.rf.gd/admin/pengguna?q=aldejan')
    time.sleep(3)
    edit_link = driver.find_element(By.XPATH, "//tr[contains(., 'aldejan@sch.id')]//a[contains(text(), 'Edit')]")
    print('Edit link:', edit_link.get_attribute('href'))
    edit_link.click()
    time.sleep(2)
    nis_field = driver.find_element(By.ID, 'nis')
    nis_field.clear()
    nis_field.send_keys('2526003')
    driver.find_element(By.XPATH, "//button[contains(text(), 'Simpan')]").click()
    time.sleep(3)
    print('Updated NIS to 2526003 successfully!')
finally:
    driver.quit()
