import uuid

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from tests.browser.base import SeleniumTestCase


class AuthenticationE2ETest(SeleniumTestCase):
    def test_login_gagal_lalu_login_admin_berhasil(self):
        self.open("/login")
        self.driver.find_element(By.ID, "email").send_keys("admin.e2e@sekolah.test")
        self.driver.find_element(By.ID, "password").send_keys("password-salah")
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        self.wait.until(EC.text_to_be_present_in_element((By.TAG_NAME, "body"), "Email atau kata sandi tidak sesuai"))

        self.driver.find_element(By.ID, "email").clear()
        self.driver.find_element(By.ID, "email").send_keys("admin.e2e@sekolah.test")
        self.driver.find_element(By.ID, "password").send_keys("E2E-Test-2026!")
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        self.wait.until(EC.url_contains("/admin"))
        self.assertIn("Aktivitas sekolah hari ini", self.driver.find_element(By.TAG_NAME, "body").text)
        self.assert_no_browser_errors()


class RegistrationE2ETest(SeleniumTestCase):
    def test_pendaftaran_baru_dapat_dicek_kembali(self):
        suffix = uuid.uuid4().hex[:10]
        email = f"calon.{suffix}@example.test"

        self.open("/pendaftaran")
        values = {
            "nama_lengkap": f"Calon Siswa {suffix}",
            "nisn": suffix,
            "tempat_lahir": "Jakarta",
            "tanggal_lahir": "2010-05-10",
            "asal_sekolah": "SMP E2E",
            "jurusan_pilihan": "MIPA",
            "email": email,
            "telepon": "081234567890",
            "alamat": "Jl. Pengujian No. 1",
            "nama_wali": "Wali E2E",
            "telepon_wali": "081298765432",
        }
        for field, value in values.items():
            self.driver.find_element(By.NAME, field).send_keys(value)
        Select(self.driver.find_element(By.NAME, "jenis_kelamin")).select_by_value("L")
        self.driver.find_element(By.CSS_SELECTOR, "form[action$='/pendaftaran']").submit()

        self.wait.until(EC.url_contains("/dokumen"))
        body = self.driver.find_element(By.TAG_NAME, "body").text
        self.assertIn("Pendaftaran berhasil dikirim", body)
        registration_number = self.driver.find_element(By.CSS_SELECTOR, ".tabular.mt-3.text-4xl").text.strip()
        self.assertRegex(registration_number, r"^PSB-\d{4}-\d{4,}$")

        self.open("/pendaftaran/cek-status")
        self.driver.find_element(By.NAME, "nomor").send_keys(registration_number)
        self.driver.find_element(By.NAME, "email").send_keys(email)
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        self.wait.until(EC.text_to_be_present_in_element((By.TAG_NAME, "body"), registration_number))
        result = self.driver.find_element(By.TAG_NAME, "body").text
        self.assertIn(f"Calon Siswa {suffix}", result)
        self.assertIn("SMP E2E", result)
        self.assertIn("menunggu", result.lower())
        self.assert_no_browser_errors()
