import os
import unittest

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait


class SeleniumTestCase(unittest.TestCase):
    base_url = os.environ.get("E2E_BASE_URL", "http://127.0.0.1:9515").rstrip("/")

    def setUp(self):
        options = Options()
        if os.environ.get("E2E_HEADLESS", "1") != "0":
            options.add_argument("--headless=new")
        options.add_argument("--window-size=1440,1000")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-search-engine-choice-screen")
        options.set_capability("goog:loggingPrefs", {"browser": "SEVERE"})

        self.driver = webdriver.Chrome(options=options)
        self.driver.set_page_load_timeout(20)
        self.wait = WebDriverWait(self.driver, 10)

    def tearDown(self):
        # Tidak mengambil screenshot/video agar tidak ada gambar hasil test yang tertinggal.
        if hasattr(self, "driver"):
            self.driver.quit()

    def open(self, path="/"):
        self.driver.get(f"{self.base_url}{path}")

    def assert_no_browser_errors(self):
        errors = self.driver.get_log("browser")
        self.assertEqual([], errors, f"JavaScript error ditemukan: {errors}")
