from selenium.webdriver.common.by import By

from tests.browser.base import SeleniumTestCase


class PublicSmokeTest(SeleniumTestCase):
    def test_halaman_publik_utama_dapat_dibuka(self):
        pages = {
            "/": "Portal Sekolah E2E",
            "/profil": "Profil Sekolah",
            "/berita": "Berita",
            "/galeri": "Galeri",
            "/pendaftaran": "Formulir Pendaftaran",
            "/pendaftaran/cek-status": "Cek Status Pendaftaran",
            "/login": "Selamat datang kembali",
        }

        for path, expected_text in pages.items():
            with self.subTest(path=path):
                self.open(path)
                self.assertIn(expected_text, self.driver.find_element(By.TAG_NAME, "body").text)
                self.assert_no_browser_errors()

    def test_panel_admin_mengarahkan_pengunjung_ke_login(self):
        self.open("/admin")
        self.assertTrue(self.driver.current_url.endswith("/login"))
        self.assertIn("Selamat datang kembali", self.driver.find_element(By.TAG_NAME, "body").text)
