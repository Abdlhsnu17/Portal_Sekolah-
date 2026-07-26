# Product Requirements Document: Portal Sekolah

## 1. Ringkasan

Portal Sekolah adalah aplikasi web untuk membantu sekolah mempublikasikan informasi resmi dan mengelola operasional data dasar sekolah. Produk ini menggabungkan website publik, sistem penerimaan siswa baru, serta panel admin untuk staf dan administrator.

## 2. Tujuan Produk

- Menyediakan satu portal resmi untuk informasi sekolah.
- Memudahkan calon siswa melakukan pendaftaran secara daring.
- Memudahkan panitia PSB memverifikasi data dan dokumen pendaftar.
- Memusatkan pengelolaan data siswa, guru, berita, galeri, profil sekolah, dan pengguna.
- Memberikan dashboard admin yang informatif dan profesional untuk pemantauan operasional.

## 3. Pengguna

| Pengguna | Kebutuhan Utama |
| --- | --- |
| Pengunjung umum | Melihat profil sekolah, berita, agenda, dan galeri |
| Calon siswa/orang tua | Mendaftar PSB, mengunggah dokumen, dan mengecek status |
| Staf sekolah | Mengelola pendaftaran, siswa, guru, konten, dan galeri |
| Admin | Mengelola seluruh data termasuk pengguna dan profil sekolah |
| Guru | Akses panel sesuai kebutuhan pengembangan berikutnya |

## 4. Scope Fitur

### 4.1 Website Publik

- Beranda dengan ringkasan profil, berita terbaru, galeri, dan statistik sekolah.
- Halaman profil sekolah berisi identitas, visi, misi, sejarah, dan kontak.
- Halaman berita dan detail berita.
- Halaman galeri dengan dokumentasi kegiatan, fasilitas, prestasi, dan ekstrakurikuler.
- Navigasi responsif untuk desktop dan mobile.

### 4.2 Penerimaan Siswa Baru

- Formulir pendaftaran calon siswa.
- Nomor pendaftaran otomatis dengan format `PSB-{tahun}-{urut}`.
- Status pendaftaran: `menunggu`, `diverifikasi`, `diterima`, dan `ditolak`.
- Halaman cek status berdasarkan nomor pendaftaran.
- Unggah dan hapus dokumen pendaftaran.
- Catatan status dari panitia untuk calon siswa.

### 4.3 Panel Admin

- Login dan logout pengguna.
- Dashboard dengan:
  - jumlah siswa aktif,
  - jumlah guru aktif,
  - jumlah pendaftar,
  - jumlah pendaftar menunggu,
  - jumlah konten,
  - jumlah galeri,
  - tingkat verifikasi,
  - pendaftar terbaru,
  - konten terbaru.
- Sidebar admin responsif dan dapat diciutkan di layar desktop.
- Header admin dengan informasi pengguna aktif.

### 4.4 Manajemen Pendaftaran

- Daftar pendaftar dengan filter status dan pencarian.
- Detail pendaftar.
- Update status dan catatan pendaftar.
- Konversi pendaftar diterima menjadi data siswa.
- Hapus data pendaftaran.

### 4.5 Manajemen Data Master

- CRUD siswa.
- CRUD guru.
- CRUD berita, agenda, dan pengumuman.
- Upload dan hapus foto galeri.
- Edit profil sekolah.
- CRUD pengguna khusus admin.

## 5. Role dan Hak Akses

| Role | Hak Akses |
| --- | --- |
| Admin | Semua modul, termasuk manajemen pengguna |
| Staf | Modul admin utama selain manajemen pengguna |
| Guru | Disiapkan sebagai role aplikasi, akses detail dapat dikembangkan berikutnya |
| Guest | Halaman publik, form PSB, cek status, dan unggah dokumen sesuai nomor pendaftaran |

## 6. Kebutuhan UI/UX

- Tampilan publik harus terasa resmi, informatif, dan mudah digunakan.
- Panel admin harus terasa profesional, padat, dan efisien untuk pekerjaan berulang.
- Tabel data harus mudah dipindai, mendukung pencarian/filter, dan tetap nyaman di layar kecil.
- Form harus jelas, konsisten, dan memiliki state validasi.
- Warna status tidak boleh berdiri sendiri; setiap status tetap harus memiliki label teks.
- Dashboard harus menonjolkan data penting tanpa terlihat seperti halaman pemasaran.

## 7. Kebutuhan Teknis

- Backend menggunakan Laravel.
- Frontend menggunakan Blade, Tailwind CSS, Alpine.js, dan Vite.
- Database menggunakan migrasi Laravel.
- File upload disimpan melalui Laravel Storage.
- Asset production harus dibuild ke `public/build`.
- Seeder menyediakan data demo agar aplikasi bisa langsung diuji.

## 8. Data Utama

- `users`: pengguna admin/staf/guru.
- `school_profiles`: profil sekolah.
- `students`: data siswa.
- `teachers`: data guru.
- `registrations`: data pendaftar PSB.
- `registration_documents`: dokumen pendaftaran.
- `announcements`: berita, agenda, dan pengumuman.
- `galleries`: foto galeri.

## 9. Acceptance Criteria

- Pengunjung dapat membuka halaman publik tanpa login.
- Calon siswa dapat mendaftar dan memperoleh nomor pendaftaran.
- Calon siswa dapat mengecek status dengan nomor pendaftaran.
- Calon siswa dapat mengunggah dokumen setelah mendaftar.
- Admin/staf dapat login ke panel admin.
- Admin/staf dapat memfilter dan mencari pendaftar.
- Admin/staf dapat mengubah status pendaftar dan memberi catatan.
- Admin/staf dapat mengelola siswa, guru, berita, galeri, dan profil sekolah.
- Admin dapat mengelola pengguna.
- Dashboard menampilkan statistik sesuai data database.
- `npm run build` menghasilkan `public/build/manifest.json`.
- Blade template dapat dikompilasi tanpa error.

## 10. Batasan Saat Ini

- Notifikasi email/WhatsApp belum tersedia.
- Payment atau biaya pendaftaran belum tersedia.
- Role guru sudah tersedia di model, tetapi area kerja khusus guru belum dibuat.
- Export data ke Excel/PDF belum tersedia.
- Audit log perubahan data belum tersedia.

## 11. Prioritas Pengembangan Berikutnya

1. Export pendaftar, siswa, dan guru ke Excel/PDF.
2. Notifikasi email atau WhatsApp untuk perubahan status pendaftaran.
3. Audit log untuk perubahan data penting.
4. Area khusus guru.
5. Pengaturan periode PSB dan kuota jurusan.
6. Preview dokumen pendaftar di panel admin.
