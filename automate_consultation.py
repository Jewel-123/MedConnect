import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# --- Configuration ---
BASE_URL = "http://localhost/medconnect/login.php"
EMAIL = "arun@gmail.com"
PASSWORD = "Qwerty@123"
SYMPTOMS = "vomiting, fever"

def setup_driver():
    """Initializes the Chrome WebDriver."""
    chrome_options = Options()
    # chrome_options.add_argument("--headless")
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    driver.maximize_window()
    return driver

def run_automation():
    driver = setup_driver()
    wait = WebDriverWait(driver, 15)
    
    try:
        # 1. Login
        print(f"Step 1: Logging in as {EMAIL}...")
        driver.get(BASE_URL)
        wait.until(EC.presence_of_element_located((By.ID, "loginEmail"))).send_keys(EMAIL)
        driver.find_element(By.ID, "loginPassword").send_keys(PASSWORD)
        driver.find_element(By.CSS_SELECTOR, "#loginForm button[type='submit']").click()
        
        # 2. Navigate to Symptom Checker
        print("Step 2: Navigating to Patient Dashboard and Symptom Checker...")
        wait.until(EC.url_contains("patient_dashboard.php"))
        driver.get("http://localhost/medconnect/symptom_checker.php")
        
        # 3. Submit Symptoms
        print(f"Step 3: Submitting symptoms: {SYMPTOMS}...")
        # Step 1 of Symptom Checker
        wait.until(EC.visibility_of_element_located((By.ID, "symptomsText"))).send_keys(SYMPTOMS)
        driver.find_element(By.CSS_SELECTOR, "#step1 .btn-primary").click()
        
        # Step 2 of Symptom Checker (Severity)
        print("Selecting severity...")
        # Wait for step 2 to be active
        wait.until(EC.visibility_of_element_located((By.ID, "step2")))
        time.sleep(1) # Wait for animation
        # Click on the first severity option (Mild)
        severity_options = wait.until(EC.presence_of_all_elements_located((By.CLASS_NAME, "severity-option")))
        severity_options[0].click()
        driver.find_element(By.CSS_SELECTOR, "#step2 .btn-primary").click()
        
        # Step 3 of Symptom Checker (Submit)
        print("Finalizing analysis...")
        wait.until(EC.visibility_of_element_located((By.ID, "step3")))
        time.sleep(1)
        driver.find_element(By.CSS_SELECTOR, "#step3 .btn-primary").click()
        
        # 4. Book for Matching Specialty
        print("Step 4: Booking for matching specialty...")
        # Wait for the "Book Appointment" button in the result
        book_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Book Appointment')]")))
        book_btn.click()
        
        # 5. Select Doctor, Date, and Time
        print("Step 5: Selecting Doctor and Time Slot...")
        wait.until(EC.url_contains("appointment_booking.php"))
        
        # Select first available doctor card
        doctors = wait.until(EC.presence_of_all_elements_located((By.CLASS_NAME, "doctor-card")))
        doctors[0].click()
        
        # Select a date in the calendar (pick the first available non-disabled day)
        wait.until(EC.visibility_of_element_located((By.ID, "calendarSection")))
        time.sleep(1)
        days = driver.find_elements(By.CSS_SELECTOR, ".calendar-day:not(.disabled)")
        if not days:
            raise Exception("No available days in the current calendar month.")
        days[0].click()
        
        # Select first time slot
        print("Selecting time slot...")
        slots = wait.until(EC.visibility_of_all_elements_located((By.CLASS_NAME, "time-slot")))
        slots[0].click()
        
        # Confirm Appointment
        print("Confirming appointment...")
        confirm_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Confirm Appointment')]")))
        confirm_btn.click()
        
        # 6. Pay for Consultation
        print("Step 6: Proceeding to Payment...")
        wait.until(EC.url_contains("payment_gateway.php"))
        
        # Wait for the Pay button
        pay_btn = wait.until(EC.element_to_be_clickable((By.CLASS_NAME, "btn-pay")))
        print(f"Final Step: Clicking Pay button (Current Text: {pay_btn.text})...")
        pay_btn.click()
        
        # Check for success (The system simulates success/successState on verification)
        # Note: SuccessState is shown after payment processing
        print("Waiting for payment success...")
        wait.until(EC.visibility_of_element_located((By.ID, "successState")))
        print("\nSUCCESS: Entire consultation flow completed successfully!")
        time.sleep(5)
        
    except Exception as e:
        print(f"\nERROR occurred: {e}")
        # Take a screenshot for debugging
        driver.save_screenshot("automation_error.png")
        print("Screenshot saved as automation_error.png")
        
    finally:
        print("Closing browser...")
        driver.quit()

if __name__ == "__main__":
    run_automation()
