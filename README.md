# Portal Sekolah

Portal Sekolah adalah aplikasi web berbasis Laravel untuk mengelola profil sekolah, berita, galeri, data siswa, data guru, dan penerimaan siswa baru (PSB). Aplikasi ini menyediakan halaman publik untuk pengunjung serta panel admin untuk staf sekolah.

## Fitur Utama

- Halaman publik: beranda, profil sekolah, berita, detail berita, dan galeri.
- Penerimaan siswa baru: formulir pendaftaran, cek status, unggah dokumen, dan penghapusan dokumen.
- Panel admin: dashboard operasional, manajemen pendaftaran, siswa, guru, berita, galeri, profil sekolah, dan pengguna.
- Role pengguna: `admin`, `staf`, dan `guru`.
- Dashboard profesional dengan ringkasan statistik, status verifikasi PSB, pendaftar terbaru, dan konten terbaru.
- Seed data demo untuk profil sekolah, akun, siswa, guru, konten, galeri, dan pendaftar.

## Teknologi

- PHP `^8.3`
- Laravel `^13.8`
- MySQL atau database lain yang didukung Laravel
- Vite `^8`
- Tailwind CSS `^4`
- Alpine.js

## Instalasi Lokal

1. Install dependency PHP:

```bash
composer install
```

2. Install dependency frontend:

```bash
npm install
```

3. Buat file environment dan app key:

```bash
cp .env.example .env
php artisan key:generate
```

4. Atur koneksi database di `.env`, lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

5. Buat symlink storage untuk file upload:

```bash
php artisan storage:link
```

6. Build asset frontend:

```bash
npm run build
```

7. Jalankan server:

```bash
php artisan serve
```

Akses aplikasi di `http://127.0.0.1:8000`.

## Mode Development

Untuk menjalankan server Laravel dan Vite secara terpisah:

```bash
php artisan serve
npm run dev
```

Atau gunakan script gabungan dari Composer:

```bash
composer run dev
```

## Akun Demo

Seeder hanya membuat akun contoh pada lingkungan `local`/`testing` apabila
`SEED_ADMIN_PASSWORD` dan `SEED_STAFF_PASSWORD` diisi. Gunakan password kuat dan
jangan pernah mengaktifkan akun demo di production.

Untuk production, gunakan HTTPS dan konfigurasi minimum berikut:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Panel admin tersedia di:

```text
/admin
```

## Struktur Modul

| Modul | Route Utama | Deskripsi |
| --- | --- | --- |
| Publik | `/`, `/profil`, `/berita`, `/galeri` | Informasi sekolah untuk pengunjung |
| Pendaftaran | `/pendaftaran` | Form PSB dan unggah dokumen |
| Cek Status | `/pendaftaran/cek-status` | Pengecekan status pendaftaran |
| Dashboard Admin | `/admin` | Statistik dan aktivitas terbaru |
| Pendaftaran Admin | `/admin/pendaftaran` | Verifikasi, catatan, penerimaan, dan penghapusan pendaftar |
| Siswa | `/admin/students` | CRUD data siswa |
| Guru | `/admin/teachers` | CRUD data guru |
| Berita | `/admin/announcements` | CRUD berita, agenda, dan pengumuman |
| Galeri | `/admin/galeri` | Upload dan kelola foto galeri |
| Profil Sekolah | `/admin/profil-sekolah` | Edit informasi sekolah |
| Pengguna | `/admin/users` | Manajemen pengguna khusus admin |

## Catatan Asset Vite

Jika muncul error:

```text
Vite manifest not found at: public/build/manifest.json
```

jalankan:

```bash
npm run build
```

Vite versi saat ini meminta Node.js `20.19+` atau `22.12+`. Jika build memberi warning versi Node, upgrade Node ke versi yang sesuai.

## Testing

Suite lama telah diganti dengan pengujian browser Selenium. Siapkan dependensi:

```bash
python3 -m pip install -r requirements-e2e.txt
```

Google Chrome/Chromium harus tersedia. Driver browser dikelola otomatis oleh
Selenium Manager. Jalankan suite sesuai kebutuhan:

```bash
npm run test:smoke
npm run test:e2e
npm run test:selenium
```

Runner memakai database SQLite sementara, menjalankan server Laravel sendiri,
dan menghapus database setelah selesai. Pengujian tidak mengambil screenshot
atau video, sehingga tidak ada gambar hasil test lama yang tersimpan.

## Dokumen Produk

Dokumen Product Requirements Document tersedia di [docs/PRD.md](docs/PRD.md).
