# Portal Sekolah

## Webhook pembayaran

Set `PAYMENT_WEBHOOK_SECRET` pada environment produksi, lalu arahkan provider pembayaran ke `POST /integrasi/pembayaran/webhook`. Header `X-Payment-Signature` berisi HMAC-SHA256 dari raw request body menggunakan secret tersebut. Payload minimal:

```json
{"external_reference":"PAY-20261128-ABCDEFGH","status":"paid","amount":150000,"paid_at":"2026-11-28T10:00:00+07:00"}
```

Status yang diterima adalah `paid`, `failed`, atau `expired`. Callback `paid` bersifat idempoten: pengiriman ulang tidak membuat pembayaran ganda.

Portal Sekolah adalah aplikasi web Laravel untuk publikasi informasi sekolah, penerimaan siswa baru (PSB), dan administrasi operasional. Aplikasi menyediakan halaman publik serta panel admin untuk admin dan staf.

## Fitur Terbaru

- Website publik untuk profil sekolah, berita, agenda, dan galeri.
- Pendaftaran siswa baru beserta nomor pendaftaran otomatis `PSB-{tahun}-{nomor urut}`.
- Pengecekan status PSB menggunakan nomor pendaftaran dan email.
- Unggah, unduh, dan hapus dokumen pendaftar dengan kontrol kepemilikan sesi.
- Dashboard admin responsif dengan statistik dan aktivitas sekolah terbaru.
- Sidebar admin yang dapat diciutkan pada layar desktop; bagian atas sidebar menampilkan logo saja.
- Manajemen pendaftaran, siswa, guru, berita, galeri, profil sekolah, dan pengguna.
- Konversi pendaftar dengan status diterima menjadi data siswa.
- Login dengan CAPTCHA setelah percobaan gagal berulang dan batasan pengguna nonaktif.
- Pengaturan kata sandi, pencabutan sesi lain, audit login, serta dukungan TOTP/MFA.
- Header keamanan HTTP pada seluruh respons.
- Pengaturan tulisan berjalan khusus admin melalui `/admin/pengaturan`.

## Teknologi

- PHP `^8.3`
- Laravel `^13.8`
- MySQL atau database lain yang didukung Laravel
- Blade
- Tailwind CSS `^4`
- Alpine.js
- Vite `^6`
- Selenium untuk pengujian browser

## Persyaratan

- PHP 8.3 atau lebih baru beserta ekstensi yang dibutuhkan Laravel.
- Composer.
- Node.js `20.19+` atau `22.12+`.
- npm.
- Database yang didukung Laravel.
- Google Chrome/Chromium untuk pengujian Selenium.

## Instalasi Lokal

1. Install dependency PHP:

```bash
composer install
```

2. Buat file environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Install dependency frontend:

```bash
npm install
```

4. Atur koneksi database pada `.env`, lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

5. Buat symlink storage:

```bash
php artisan storage:link
```

6. Build aset dan jalankan aplikasi:

```bash
npm run build
php artisan serve
```

Aplikasi dapat diakses di `http://127.0.0.1:8000`.

## Mode Development

Jalankan Laravel dan Vite secara terpisah:

```bash
php artisan serve
npm run dev
```

Atau jalankan seluruh proses development melalui Composer:

```bash
composer run dev
```

## Akun dan Data Demo

Seeder membuat akun dan data demo untuk lingkungan lokal/testing. Pengaturan password demo disediakan melalui variabel environment:

```env
SEED_ADMIN_PASSWORD=
SEED_STAFF_PASSWORD=
```

Gunakan password kuat dan jangan mengaktifkan akun demo pada production.

Panel admin:

```text
/admin
```

## Konfigurasi Keamanan

Contoh minimum untuk production:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
MFA_ENABLED=false
```

Fitur autentikasi dua faktor sudah tersedia. Aktifkan MFA pada akun yang siap menggunakan autentikator TOTP:

```env
MFA_ENABLED=true
```

Dengan MFA aktif, administrator wajib mengaktifkan MFA sebelum dapat membuka panel admin.

## Struktur Modul

| Modul | Route utama | Deskripsi |
| --- | --- | --- |
| Publik | `/`, `/profil`, `/berita`, `/galeri` | Informasi resmi untuk pengunjung |
| Pendaftaran | `/pendaftaran` | Formulir PSB dan unggah dokumen |
| Cek status | `/pendaftaran/cek-status` | Pengecekan status pendaftaran |
| Autentikasi | `/login` | Login, MFA, dan logout |
| Keamanan akun | `/keamanan` | Kata sandi, MFA, sesi, dan audit keamanan |
| Dashboard admin | `/admin` | Ringkasan operasional khusus admin |
| Dashboard staf | `/staf/dashboard` | Pendaftaran dan ringkasan data yang tersedia untuk staf |
| Dashboard guru | `/pengajaran-saya` | Penugasan, jadwal, penilaian, dan absensi kelas |
| Dashboard wali | `/portal-wali` | Ringkasan siswa tertaut, absensi, nilai, dan tagihan |
| Dashboard siswa | `/portal-siswa` | Kelas, jadwal, nilai, agenda belajar, dan absensi |
| Jadwal guru | `/pengajaran-saya/jadwal` | Jadwal lengkap kelas dan mata pelajaran guru |
| Jadwal siswa | `/portal-siswa/jadwal` | Jadwal lengkap untuk kelas aktif siswa |
| Catatan siswa | `/pengajaran-saya/catatan-siswa` | Guru membuat catatan mandiri; draf privat, catatan terbit tampil ke siswa dan wali tertaut |
| Catatan saya | `/portal-siswa/catatan` | Siswa membaca catatan yang diterbitkan guru |
| Catatan siswa wali | `/portal-wali/catatan` | Wali membaca catatan terbit untuk siswa yang tertaut |
| Pendaftaran admin | `/admin/pendaftaran` | Verifikasi dan konversi pendaftar |
| Siswa | `/admin/students` | CRUD data siswa |
| Guru | `/admin/teachers` | CRUD data guru |
| Berita | `/admin/announcements` | CRUD berita, agenda, dan pengumuman |
| Galeri | `/admin/galeri` | Unggah dan kelola foto galeri |
| Profil sekolah | `/admin/profil-sekolah` | Edit informasi sekolah |
| Pengguna | `/admin/users` | Manajemen pengguna khusus admin |
| Pengaturan | `/admin/pengaturan` | Pengaturan tulisan berjalan, khusus admin |

## Testing

Install dependency runner Selenium:

```bash
python3 -m pip install -r requirements-e2e.txt
```

Jalankan suite:

```bash
npm run test:smoke
npm run test:e2e
npm run test:selenium
```

Runner menggunakan database SQLite sementara, server Laravel sendiri, dan Chrome/Chromium. Database testing dihapus setelah suite selesai.

Perintah validasi dasar:

```bash
php artisan view:cache
npm run build
```

## Troubleshooting Vite

Jika muncul pesan:

```text
Vite manifest not found at: public/build/manifest.json
```

jalankan:

```bash
npm run build
```

Pastikan Node.js memenuhi persyaratan versi yang tercantum di atas.

## Dokumen Produk

Product Requirements Document terbaru tersedia di [docs/PRD.md](docs/PRD.md).
