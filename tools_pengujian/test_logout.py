import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

chrome_options = Options()
chrome_options.add_argument('--headless=new')
driver = webdriver.Chrome(options=chrome_options)
driver.get('https://adukanaja.rf.gd/login')
time.sleep(3)
driver.find_element(By.NAME, 'login').send_keys('rudi@sch.id')
driver.find_element(By.NAME, 'password').send_keys('password')
driver.find_element(By.XPATH, "//button[@type='submit']").click()
time.sleep(3)
print('Logged in as Rudi, URL:', driver.current_url)

# Now log out via form submission
logout_forms = driver.find_elements(By.XPATH, "//form[contains(@action, 'logout')]")
print('Logout form count:', len(logout_forms))
if logout_forms:
    driver.execute_script("arguments[0].submit();", logout_forms[0])
    time.sleep(3)
    print('After logout URL:', driver.current_url)
    print('Title:', driver.title)

driver.quit()
