import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--window-size=1280,850")
chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

driver = webdriver.Chrome(options=chrome_options)
try:
    driver.get("https://adukanaja.rf.gd/login")
    time.sleep(2)
    driver.find_element(By.NAME, "login").send_keys("aldejan@sch.id")
    driver.find_element(By.NAME, "password").send_keys("password")
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(3)
    print("After login URL:", driver.current_url)
    
    driver.get("https://adukanaja.rf.gd/pengaduan/ajukan")
    time.sleep(3)
    print("Ajukan URL:", driver.current_url)
    print("Ajukan title:", driver.title)
    driver.save_screenshot("test_ajukan.png")
    
    # Check elements
    selects = driver.find_elements(By.TAG_NAME, "select")
    print("Selects count:", len(selects))
    for s in selects:
        print("Select:", s.get_attribute("id"), s.get_attribute("name"))
    
    inputs = driver.find_elements(By.TAG_NAME, "input")
    for i in inputs:
        print("Input:", i.get_attribute("id"), i.get_attribute("name"), i.get_attribute("type"))
        
finally:
    driver.quit()
