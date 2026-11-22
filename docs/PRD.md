# Product Requirements Document: Portal Sekolah

## 1. Ringkasan

Portal Sekolah adalah aplikasi web berbasis Laravel yang menyediakan website profil sekolah, penerimaan siswa baru (PSB), dan panel administrasi. Produk menghubungkan pengunjung, calon siswa, staf, guru, dan administrator dalam satu portal.

## 2. Tujuan Produk

- Menjadikan situs sebagai sumber informasi resmi sekolah.
- Memudahkan pendaftaran dan pengecekan status PSB secara daring.
- Membantu panitia memverifikasi data dan dokumen pendaftar.
- Memusatkan pengelolaan siswa, guru, konten, galeri, profil sekolah, dan pengguna.
- Menyediakan pemantauan operasional melalui dashboard admin yang responsif.
- Melindungi sesi dan akun pengelola melalui kontrol akses serta fitur keamanan.

## 3. Pengguna

| Pengguna | Kebutuhan utama |
| --- | --- |
| Pengunjung umum | Melihat profil sekolah, berita, agenda, galeri, dan informasi PSB |
| Calon siswa atau orang tua | Mendaftar, mengunggah dokumen, dan mengecek status |
| Staf sekolah | Mengelola pendaftaran, siswa, guru, berita, galeri, dan profil |
| Admin | Mengelola seluruh modul, termasuk pengguna dan pengaturan akun |
| Guru | Melihat jadwal/penugasan miliknya dan mengelola nilai serta absensi pada kelas/mapel yang ditugaskan |
| Siswa | Melihat jadwal, agenda belajar, nilai yang sudah diisi, dan absensi miliknya sendiri |

## 4. Scope Fitur

### 4.1 Website Publik

- Beranda, profil sekolah, daftar/detail berita, dan galeri.
- Ringkasan profil, statistik sekolah, berita terbaru, dan dokumentasi kegiatan.
- Halaman publik dapat diakses tanpa login.
- Navigasi responsif pada desktop dan perangkat mobile.

### 4.2 Penerimaan Siswa Baru

- Formulir pendaftaran dengan validasi server.
- Nomor pendaftaran otomatis dengan format `PSB-{tahun}-{nomor urut}`.
- Status pendaftaran: `menunggu`, `diverifikasi`, `diterima`, dan `ditolak`.
- Pengecekan status berdasarkan nomor pendaftaran dan email.
- Unggah, unduh, dan hapus dokumen pendaftaran.
- Catatan panitia yang dapat dilihat bersama status pendaftaran.
- Batas kepemilikan dokumen dijaga melalui bukti sesi setelah calon siswa melakukan pendaftaran atau pengecekan status.

### 4.3 Panel Admin

- Autentikasi login dan logout.
- Dashboard dengan statistik siswa aktif, guru aktif, pendaftar, pendaftar menunggu, konten, galeri, dan tingkat verifikasi.
- Daftar pendaftar terbaru dan konten terbaru.
- Sidebar responsif yang dapat diciutkan pada desktop.
- Header dengan tanggal, waktu, dan menu akun.
- Logo monogram pada bagian atas sidebar tanpa teks tambahan.

### 4.4 Manajemen Pendaftaran

- Daftar pendaftar dengan pencarian dan filter status.
- Halaman detail pendaftar beserta dokumen.
- Pembaruan status dan catatan verifikasi.
- Konversi pendaftar diterima menjadi data siswa.
- Penghapusan data pendaftaran beserta dokumen terkait.

### 4.5 Manajemen Data

- CRUD siswa.
- CRUD guru.
- CRUD berita, agenda, dan pengumuman.
- Unggah dan hapus foto galeri.
- Edit profil sekolah.
- CRUD pengguna yang dibatasi untuk role admin, termasuk penautan akun siswa ke satu profil siswa.

### 4.6 Akademik dan Portal Belajar

- Admin mengelola tahun ajaran, kelas, penempatan siswa, jadwal, penugasan guru, dan komponen penilaian.
- Guru melihat jadwal dan penugasan miliknya, serta mengisi nilai/absensi hanya pada kelas dan mata pelajaran yang ditugaskan.
- Siswa hanya dapat melihat data akademik dari profil siswa yang ditautkan ke akunnya.
- Wali hanya dapat melihat data siswa yang ditautkan ke akun wali dan tidak dapat mengubah nilai atau absensi.
- Materi digital, pengumpulan tugas, dan kuis interaktif belum termasuk modul yang tersedia.

### 4.6 Keamanan Akun

- Pengguna nonaktif tidak dapat masuk.
- Login gagal dibatasi dan menampilkan CAPTCHA setelah beberapa percobaan.
- Pengelolaan kata sandi dengan validasi kekuatan dan sesi lain dapat dicabut.
- Riwayat login perangkat dan audit keamanan tersedia di halaman keamanan akun.
- Dukungan TOTP autentikasi dua faktor dan kode pemulihan tersedia dalam implementasi.
- MFA dapat diaktifkan melalui konfigurasi `MFA_ENABLED=true`; admin diwajibkan menggunakan MFA saat fitur ini aktif.
- Header keamanan HTTP diterapkan pada seluruh respons.
- Admin dapat mengaktifkan atau menonaktifkan tulisan berjalan serta mengubah teksnya melalui menu pengaturan.

## 5. Role dan Hak Akses

| Role | Hak akses |
| --- | --- |
| Admin | Seluruh modul, termasuk manajemen pengguna |
| Staf | Modul operasional pendaftaran, siswa, guru, konten, galeri, dan profil sekolah; tidak dapat mengakses pengaturan/admin-only, data pengguna, akademik khusus, keuangan, perpustakaan, aset, atau laporan |
| Guru | Portal pengajaran, absensi pegawai, dan input nilai/absensi siswa hanya untuk kelas/mapel penugasan; tidak dapat membuka panel operasional admin |
| Siswa | Portal belajar baca-saja untuk jadwal, agenda penilaian, nilai yang sudah diisi, dan absensi miliknya sendiri |
| Wali | Portal baca-saja untuk data siswa yang ditautkan ke akun wali |
| Guest | Website publik, PSB, cek status, dan akses dokumen yang terhubung dengan sesi kepemilikan |

## 6. Kebutuhan UI/UX

- Tampilan publik resmi, informatif, dan mudah digunakan.
- Panel admin padat, konsisten, dan mendukung pekerjaan berulang.
- Data tabel mendukung pencarian/filter dan tetap dapat dibaca pada layar kecil.
- Form memiliki validasi dan pesan kesalahan yang jelas.
- Status ditampilkan bersama label teks, bukan hanya warna.
- Navigasi, sidebar, header, dan tombol memiliki state responsif yang konsisten.

## 7. Kebutuhan Teknis

- Backend: PHP `^8.3` dan Laravel `^13.8`.
- Frontend: Blade, Tailwind CSS `^4`, Alpine.js, dan Vite.
- Database: MySQL atau database lain yang didukung Laravel melalui migrasi.
- Upload disimpan melalui Laravel Storage.
- Build produksi menghasilkan aset di `public/build`.
- Seeder menyediakan data demo untuk pengujian lokal dan testing.
- Pengujian browser Selenium mendukung mode `smoke`, `e2e`, dan `all`.

## 8. Data Utama

- `users`: akun admin, staf, guru, wali, dan siswa.
- `school_profiles`: informasi profil sekolah.
- `students`: data siswa beserta tautan opsional ke akun siswa.
- `teachers`: data guru.
- `registrations`: data pendaftar PSB.
- `registration_documents`: dokumen pendaftar.
- `announcements`: berita, agenda, dan pengumuman.
- `galleries`: dokumentasi galeri.
- `security_audit_logs`: catatan kejadian keamanan.
- `login_devices`: perangkat atau sidik jari login.

## 9. Acceptance Criteria

- Pengunjung dapat membuka seluruh halaman publik tanpa login.
- Calon siswa dapat mendaftar dan memperoleh nomor pendaftaran.
- Calon siswa dapat mengecek status menggunakan nomor dan email.
- Calon siswa dapat mengunggah dokumen setelah memperoleh bukti kepemilikan sesi.
- Pengguna aktif dapat login dan pengunjung/akun nonaktif tidak dapat masuk ke panel.
- Admin dan staf dapat mencari serta memfilter pendaftar.
- Admin dan staf dapat mengubah status serta memberi catatan pendaftar.
- Pendaftar diterima dapat dikonversi menjadi data siswa.
- Admin dan staf dapat mengelola siswa, guru, berita, galeri, dan profil sekolah.
- Admin dapat mengelola pengguna.
- Guru tidak dapat membuka panel admin dan hanya dapat mengisi nilai/absensi untuk kelas/mapel penugasannya.
- Siswa hanya dapat membuka portal untuk profil siswa yang ditautkan ke akunnya.
- Wali hanya dapat melihat data siswa yang ditautkan ke akunnya.
- Dashboard menampilkan data yang sesuai dengan database.
- Pengguna dapat mengubah kata sandi dan mencabut sesi lain.
- `npm run build` berhasil menghasilkan `public/build/manifest.json`.
- `php artisan view:cache` dapat mengompilasi seluruh template Blade.
- Suite Selenium smoke/E2E dapat dijalankan pada lingkungan testing.

## 10. Batasan Saat Ini

- Notifikasi email/WhatsApp untuk perubahan status belum tersedia.
- Pembayaran daring/biaya pendaftaran belum terintegrasi; modul keuangan saat ini hanya mencatat tagihan dan pembayaran.
- MFA tersedia tetapi dinonaktifkan secara default hingga diaktifkan melalui konfigurasi.
- Materi digital, pengumpulan tugas, dan kuis interaktif belum tersedia.

## 11. Prioritas Berikutnya

1. Perluas ekspor CSV menjadi Excel/PDF.
2. Notifikasi email atau WhatsApp untuk perubahan status pendaftaran.
3. Materi belajar, pengumpulan tugas, dan kuis untuk portal guru/siswa.
4. Integrasi pembayaran daring untuk pendaftaran dan tagihan sekolah.
5. Aktivasi MFA terstruktur beserta pemulihan akun dan proses dukungan.
