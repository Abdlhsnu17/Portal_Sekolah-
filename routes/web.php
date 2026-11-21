<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceCheckInController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
*/

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/profil', 'profil')->name('profil');
    Route::get('/berita', 'berita')->name('berita');
    Route::get('/berita/{slug}', 'beritaDetail')->name('berita.detail');
    Route::get('/galeri', 'galeri')->name('galeri');
});

/*
|--------------------------------------------------------------------------
| Penerimaan Siswa Baru
|--------------------------------------------------------------------------
*/

Route::prefix('pendaftaran')->name('pendaftaran.')->controller(RegistrationController::class)->group(function () {
    Route::get('/', 'create')->name('form');
    Route::post('/', 'store')->middleware('throttle:pendaftaran')->name('store');
    Route::match(['get', 'post'], '/cek-status', 'cekStatus')->middleware('throttle:cek-status')->name('status');

    /*
     * Berkas pendaftaran. Nomor pendaftaran berurutan dan mudah ditebak, jadi
     * setiap rute di bawah ini menuntut bukti kepemilikan yang tersimpan di sesi
     * (lihat EnsurePendaftarTerverifikasi) — bukan sekadar nomor yang benar.
     */
    Route::middleware('pendaftar')->group(function () {
        Route::get('/{nomor}/dokumen', 'dokumen')->name('dokumen');
        Route::post('/{nomor}/dokumen', 'unggahDokumen')->middleware('throttle:unggah-dokumen')->name('dokumen.unggah');
        Route::get('/{nomor}/dokumen/{dokumen}', 'unduhDokumen')->name('dokumen.unduh');
        Route::delete('/{nomor}/dokumen/{dokumen}', 'hapusDokumen')->name('dokumen.hapus');
    });
});

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/login/verifikasi', [AuthController::class, 'showMfa'])->name('login.mfa');
    Route::post('/login/verifikasi', [AuthController::class, 'verifyMfa'])->middleware('throttle:login')->name('login.mfa.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('pemberitahuan')->name('notifications.')->middleware(['auth', 'aktif'])->group(function () {
    Route::post('/baca-semua', [NotificationController::class, 'readAll'])->name('read-all');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::get('/{notification}', [NotificationController::class, 'read'])->name('read');
});

Route::prefix('keamanan')->name('security.')->middleware(['auth', 'aktif'])->group(function () {
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::put('/password', [SecurityController::class, 'updatePassword'])->name('password');
    Route::post('/mfa', [SecurityController::class, 'beginMfa'])->name('mfa.begin');
    Route::post('/mfa/konfirmasi', [SecurityController::class, 'confirmMfa'])->name('mfa.confirm');
    Route::delete('/mfa', [SecurityController::class, 'disableMfa'])->name('mfa.disable');
    Route::delete('/sessions', [SecurityController::class, 'destroyOtherSessions'])->name('sessions.destroy');
});

Route::prefix('kehadiran')->name('attendance.')->middleware(['auth', 'aktif', 'mfa'])->group(function () {
    Route::get('/', [AttendanceCheckInController::class, 'create'])->name('check-in');
    Route::post('/', [AttendanceCheckInController::class, 'store'])->middleware('throttle:10,1')->name('store');
});

/*
|--------------------------------------------------------------------------
| Panel Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'aktif', 'mfa'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Pendaftaran
    Route::get('/pendaftaran', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'show'])->name('registrations.show');
    Route::get('/pendaftaran/{registration}/dokumen/{dokumen}', [Admin\RegistrationController::class, 'unduhDokumen'])->name('registrations.dokumen');
    Route::put('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'update'])->name('registrations.update');
    Route::post('/pendaftaran/{registration}/jadikan-siswa', [Admin\RegistrationController::class, 'terima'])->name('registrations.terima');
    Route::delete('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');

    // Siswa
    Route::resource('students', Admin\StudentController::class)->except('show');

    // Guru
    Route::resource('teachers', Admin\TeacherController::class)->except('show');

    // Tenaga kependidikan (khusus admin)
    Route::resource('staff', Admin\StaffMemberController::class)->except('show')->middleware('admin');
    Route::post('/staff/akun-guru', [Admin\StaffMemberController::class, 'createTeacherAccounts'])->middleware('admin')->name('staff.teacher-accounts');

    // Absensi pegawai (khusus admin)
    Route::get('attendances/export', [Admin\AttendanceController::class, 'export'])->name('attendances.export')->middleware('admin');
    Route::get('attendances/{attendance}/face', [Admin\AttendanceController::class, 'facePhoto'])->name('attendances.face')->middleware('admin');
    Route::put('attendances/{attendance}/face-review', [Admin\AttendanceController::class, 'reviewFace'])->name('attendances.face-review')->middleware('admin');
    Route::resource('attendances', Admin\AttendanceController::class)->except('show')->middleware('admin');

    // Berita & pengumuman
    Route::resource('announcements', Admin\AnnouncementController::class)->except('show');

    // Galeri
    Route::get('/galeri', [Admin\GalleryController::class, 'index'])->name('galleries.index');
    Route::post('/galeri', [Admin\GalleryController::class, 'store'])->name('galleries.store');
    Route::delete('/galeri/{gallery}', [Admin\GalleryController::class, 'destroy'])->name('galleries.destroy');

    // Profil sekolah
    Route::get('/profil-sekolah', [Admin\SchoolProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil-sekolah', [Admin\SchoolProfileController::class, 'update'])->name('profile.update');

    // Pengaturan khusus admin
    Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('settings.edit')->middleware('admin');
    Route::put('/pengaturan', [Admin\SettingController::class, 'update'])->name('settings.update')->middleware('admin');

    // Manajemen pengguna (khusus admin)
    Route::resource('users', Admin\UserController::class)->except('show')->middleware('admin');
});
