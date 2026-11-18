import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

chrome_options = Options()
chrome_options.add_argument("--headless=new")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--window-size=1280,800")
chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

print("Starting Chrome...")
driver = webdriver.Chrome(options=chrome_options)
try:
    print("Navigating to https://adukanaja.rf.gd...")
    driver.get("https://adukanaja.rf.gd")
    time.sleep(5)
    print("Title:", driver.title)
    print("Current URL:", driver.current_url)
    driver.save_screenshot("test_rf_gd.png")
    print("Screenshot saved to test_rf_gd.png")
finally:
    driver.quit()
