import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

chrome_options = Options()
chrome_options.add_argument('--headless=new')
driver = webdriver.Chrome(options=chrome_options)
driver.get('https://adukanaja.rf.gd/login')
time.sleep(2)
driver.find_element(By.NAME, 'login').send_keys('rudi@sch.id')
driver.find_element(By.NAME, 'password').send_keys('password')
driver.find_element(By.XPATH, "//button[@type='submit']").click()
time.sleep(2)

driver.get('https://adukanaja.rf.gd/admin/pengguna/2/edit')
time.sleep(2)
form = driver.find_element(By.XPATH, "//form[contains(@action, '/admin/pengguna/')]")
nis_input = form.find_element(By.NAME, 'nis')
nis_input.clear()
nis_input.send_keys('2526003')
btn = form.find_element(By.XPATH, ".//button[@type='submit']")
driver.execute_script("arguments[0].click();", btn)
time.sleep(3)
print('After update URL:', driver.current_url)
for a in driver.find_elements(By.CSS_SELECTOR, '.alert, .field-error'):
    print('Alert:', a.text)
driver.quit()
