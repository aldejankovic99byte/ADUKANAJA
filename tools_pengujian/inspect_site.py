import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--window-size=1280,900")
chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

driver = webdriver.Chrome(options=chrome_options)
try:
    driver.get("https://adukanaja.rf.gd/login")
    time.sleep(3)
    print("Page source len:", len(driver.page_source))
    # print form fields
    inputs = driver.find_elements(By.TAG_NAME, "input")
    for inp in inputs:
        print("Input:", inp.get_attribute("name"), inp.get_attribute("type"), inp.get_attribute("id"))
    
    # Try login as admin
    email_field = driver.find_element(By.NAME, "email") if driver.find_elements(By.NAME, "email") else driver.find_element(By.NAME, "login")
    email_field.send_keys("rudi@sch.id")
    pw_field = driver.find_element(By.NAME, "password")
    pw_field.send_keys("password")
    btn = driver.find_element(By.XPATH, "//button[@type='submit']")
    btn.click()
    time.sleep(3)
    print("After admin login URL:", driver.current_url)
    print("After admin login title:", driver.title)
finally:
    driver.quit()
