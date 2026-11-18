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
    time.sleep(2)
    driver.find_element(By.NAME, "login").send_keys("rudi@sch.id")
    driver.find_element(By.NAME, "password").send_keys("password")
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(3)
    
    driver.get("https://adukanaja.rf.gd/admin/verifikasi")
    time.sleep(3)
    tickets = driver.find_elements(By.CSS_SELECTOR, ".verif-ticket")
    print(f"Total tickets in verifikasi: {len(tickets)}")
    for t in tickets[:8]:
        print("Ticket:", t.text.replace("\n", " | "))
finally:
    driver.quit()
