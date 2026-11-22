<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AttendanceCheckInController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianPortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\TeachingController;
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
Route::get('/portal-wali', [GuardianPortalController::class, 'index'])->middleware(['auth', 'aktif'])->name('guardian.index');
Route::get('/portal-siswa', [StudentPortalController::class, 'index'])->middleware(['auth', 'aktif', 'mfa'])->name('student.index');

Route::prefix('pemberitahuan')->name('notifications.')->middleware(['auth', 'aktif'])->group(function () {
    Route::post('/baca-semua', [NotificationController::class, 'readAll'])->name('read-all');
    Route::delete('/hapus-semua', [NotificationController::class, 'destroyAll'])->name('destroy-all');
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
    Route::get('/riwayat', [AttendanceCheckInController::class, 'history'])->name('history');
    Route::post('/', [AttendanceCheckInController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::post('/pulang', [AttendanceCheckInController::class, 'checkOut'])->middleware('throttle:10,1')->name('check-out');
    Route::put('/koreksi', [AttendanceCheckInController::class, 'correct'])->middleware('throttle:5,1')->name('correct');
    Route::post('/pengajuan', [AttendanceCheckInController::class, 'requestAbsence'])->middleware('throttle:5,1')->name('request');
});

// Guru hanya dapat melihat penugasan miliknya sendiri.
Route::get('/pengajaran-saya', [TeachingController::class, 'index'])
    ->middleware(['auth', 'aktif', 'mfa'])->name('teaching.index');

Route::prefix('pengajaran-saya')->name('teaching.')->middleware(['auth', 'aktif', 'mfa'])->group(function () {
    Route::get('/absensi-siswa', [TeachingController::class, 'attendance'])->name('attendance');
    Route::post('/absensi-siswa', [TeachingController::class, 'storeAttendance'])->name('attendance.store');
    Route::get('/penilaian/{assessment}/nilai', [TeachingController::class, 'scores'])->name('assessments.scores');
    Route::put('/penilaian/{assessment}/nilai', [TeachingController::class, 'storeScores'])->name('assessments.scores.store');
});

/*
|--------------------------------------------------------------------------
| Panel Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'aktif', 'mfa', 'internal'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Pendaftaran
    Route::get('/pendaftaran', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/pengaturan-psb', [Admin\AdmissionSettingController::class, 'index'])->middleware('admin')->name('admission-settings.index');
    Route::post('/pengaturan-psb/periode', [Admin\AdmissionSettingController::class, 'storePeriod'])->middleware('admin')->name('admission-periods.store');
    Route::put('/pengaturan-psb/periode/{admissionPeriod}/aktifkan', [Admin\AdmissionSettingController::class, 'activatePeriod'])->middleware('admin')->name('admission-periods.activate');
    Route::post('/pengaturan-psb/jalur', [Admin\AdmissionSettingController::class, 'storePath'])->middleware('admin')->name('admission-paths.store');
    Route::get('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'show'])->name('registrations.show');
    Route::get('/pendaftaran/{registration}/dokumen/{dokumen}', [Admin\RegistrationController::class, 'unduhDokumen'])->name('registrations.dokumen');
    Route::get('/pendaftaran/{registration}/dokumen/{dokumen}/pratinjau', [Admin\RegistrationController::class, 'previewDokumen'])->name('registrations.dokumen.preview');
    Route::put('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'update'])->name('registrations.update');
    Route::post('/pendaftaran/{registration}/jadikan-siswa', [Admin\RegistrationController::class, 'terima'])->name('registrations.terima');
    Route::delete('/pendaftaran/{registration}', [Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');

    // Siswa
    Route::resource('students', Admin\StudentController::class)->except('show');

    // Guru
    Route::resource('teachers', Admin\TeacherController::class)->except('show');

    // Kurikulum dan penugasan mengajar (khusus admin)
    Route::get('akademik/struktur', [Admin\AcademicStructureController::class, 'index'])->middleware('admin')->name('academic-structure.index');
    Route::post('akademik/tahun-ajaran', [Admin\AcademicStructureController::class, 'storeYear'])->middleware('admin')->name('academic-years.store');
    Route::put('akademik/tahun-ajaran/{academicYear}', [Admin\AcademicStructureController::class, 'updateYear'])->middleware('admin')->name('academic-years.update');
    Route::put('akademik/tahun-ajaran/{academicYear}/aktifkan', [Admin\AcademicStructureController::class, 'activateYear'])->middleware('admin')->name('academic-years.activate');
    Route::delete('akademik/tahun-ajaran/{academicYear}', [Admin\AcademicStructureController::class, 'destroyYear'])->middleware('admin')->name('academic-years.destroy');
    Route::post('akademik/kelas', [Admin\AcademicStructureController::class, 'storeClass'])->middleware('admin')->name('school-classes.store');
    Route::put('akademik/kelas/{schoolClass}', [Admin\AcademicStructureController::class, 'updateClass'])->middleware('admin')->name('school-classes.update');
    Route::delete('akademik/kelas/{schoolClass}', [Admin\AcademicStructureController::class, 'destroyClass'])->middleware('admin')->name('school-classes.destroy');
    Route::post('akademik/penempatan', [Admin\AcademicStructureController::class, 'enroll'])->middleware('admin')->name('student-enrollments.store');
    Route::put('akademik/penempatan/{studentEnrollment}', [Admin\AcademicStructureController::class, 'updateEnrollment'])->middleware('admin')->name('student-enrollments.update');
    Route::delete('akademik/penempatan/{studentEnrollment}', [Admin\AcademicStructureController::class, 'destroyEnrollment'])->middleware('admin')->name('student-enrollments.destroy');
    Route::get('akademik/jadwal', [Admin\ClassScheduleController::class, 'index'])->middleware('admin')->name('class-schedules.index');
    Route::post('akademik/jadwal', [Admin\ClassScheduleController::class, 'store'])->middleware('admin')->name('class-schedules.store');
    Route::put('akademik/jadwal/{classSchedule}', [Admin\ClassScheduleController::class, 'update'])->middleware('admin')->name('class-schedules.update');
    Route::delete('akademik/jadwal/{classSchedule}', [Admin\ClassScheduleController::class, 'destroy'])->middleware('admin')->name('class-schedules.destroy');
    Route::get('akademik/absensi-siswa', [Admin\StudentAttendanceController::class, 'index'])->middleware('admin')->name('student-attendances.index');
    Route::post('akademik/absensi-siswa', [Admin\StudentAttendanceController::class, 'store'])->middleware('admin')->name('student-attendances.store');
    Route::get('akademik/penilaian', [Admin\AssessmentController::class, 'index'])->middleware('admin')->name('assessments.index');
    Route::post('akademik/penilaian', [Admin\AssessmentController::class, 'store'])->middleware('admin')->name('assessments.store');
    Route::put('akademik/penilaian/{assessment}', [Admin\AssessmentController::class, 'update'])->middleware('admin')->name('assessments.update');
    Route::delete('akademik/penilaian/{assessment}', [Admin\AssessmentController::class, 'destroy'])->middleware('admin')->name('assessments.destroy');
    Route::get('akademik/penilaian/{assessment}/nilai', [Admin\AssessmentController::class, 'scores'])->middleware('admin')->name('assessments.scores');
    Route::put('akademik/penilaian/{assessment}/nilai', [Admin\AssessmentController::class, 'storeScores'])->middleware('admin')->name('assessments.scores.store');
    Route::get('akademik/rapor', [Admin\StudentReportController::class, 'index'])->middleware('admin')->name('student-reports.index');
    Route::get('laporan', [Admin\ReportExportController::class, 'index'])->middleware('admin')->name('reports.index');
    Route::get('laporan/siswa', [Admin\ReportExportController::class, 'students'])->middleware('admin')->name('reports.students');
    Route::get('laporan/guru', [Admin\ReportExportController::class, 'teachers'])->middleware('admin')->name('reports.teachers');
    Route::get('laporan/pendaftar', [Admin\ReportExportController::class, 'registrations'])->middleware('admin')->name('reports.registrations');
    Route::get('laporan/absensi-siswa', [Admin\ReportExportController::class, 'attendances'])->middleware('admin')->name('reports.attendances');
    Route::get('laporan/nilai', [Admin\ReportExportController::class, 'scores'])->middleware('admin')->name('reports.scores');
    Route::get('keuangan', [Admin\FinanceController::class, 'index'])->middleware('admin')->name('finance.index');
    Route::post('keuangan/tagihan', [Admin\FinanceController::class, 'storeInvoice'])->middleware('admin')->name('finance.invoices.store');
    Route::post('keuangan/tagihan/{invoice}/pembayaran', [Admin\FinanceController::class, 'storePayment'])->middleware('admin')->name('finance.payments.store');
    Route::get('perpustakaan', [Admin\LibraryController::class, 'index'])->middleware('admin')->name('library.index');
    Route::post('perpustakaan/buku', [Admin\LibraryController::class, 'storeBook'])->middleware('admin')->name('library.books.store');
    Route::post('perpustakaan/peminjaman', [Admin\LibraryController::class, 'storeLoan'])->middleware('admin')->name('library.loans.store');
    Route::put('perpustakaan/peminjaman/{libraryLoan}/kembali', [Admin\LibraryController::class, 'returnLoan'])->middleware('admin')->name('library.loans.return');
    Route::get('aset', [Admin\AssetController::class, 'index'])->middleware('admin')->name('assets.index');
    Route::post('aset', [Admin\AssetController::class, 'store'])->middleware('admin')->name('assets.store');
    Route::put('aset/{asset}', [Admin\AssetController::class, 'update'])->middleware('admin')->name('assets.update');
    Route::delete('aset/{asset}', [Admin\AssetController::class, 'destroy'])->middleware('admin')->name('assets.destroy');
    Route::get('penugasan-mengajar', [Admin\TeachingAssignmentController::class, 'index'])->middleware('admin')->name('teaching-assignments.index');
    Route::post('penugasan-mengajar/mapel', [Admin\TeachingAssignmentController::class, 'storeSubject'])->middleware('admin')->name('subjects.store');
    Route::post('penugasan-mengajar', [Admin\TeachingAssignmentController::class, 'store'])->middleware('admin')->name('teaching-assignments.store');
    Route::delete('penugasan-mengajar/{teachingAssignment}', [Admin\TeachingAssignmentController::class, 'destroy'])->middleware('admin')->name('teaching-assignments.destroy');

    // Tenaga kependidikan (khusus admin)
    Route::get('staff/aktifkan-absensi-saya', [Admin\StaffMemberController::class, 'createOwnAttendanceProfile'])->middleware('admin')->name('staff.activate-attendance.create');
    Route::post('staff/aktifkan-absensi-saya', [Admin\StaffMemberController::class, 'storeOwnAttendanceProfile'])->middleware('admin')->name('staff.activate-attendance.store');
    Route::resource('staff', Admin\StaffMemberController::class)->except('show')->middleware('admin');
    Route::post('/staff/akun-guru', [Admin\StaffMemberController::class, 'createTeacherAccounts'])->middleware('admin')->name('staff.teacher-accounts');

    // Absensi pegawai (khusus admin)
    Route::get('attendances/export', [Admin\AttendanceController::class, 'export'])->name('attendances.export')->middleware('admin');
    Route::get('attendances/{attendance}/face', [Admin\AttendanceController::class, 'facePhoto'])->name('attendances.face')->middleware('admin');
    Route::put('attendances/{attendance}/face-review', [Admin\AttendanceController::class, 'reviewFace'])->name('attendances.face-review')->middleware('admin');
    Route::put('attendances/{attendance}/request-review', [Admin\AttendanceController::class, 'reviewRequest'])->name('attendances.request-review')->middleware('admin');
    Route::get('attendances/{attendance}/request-evidence', [Admin\AttendanceController::class, 'requestEvidence'])->name('attendances.request-evidence')->middleware('admin');
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
    Route::get('wali', [Admin\GuardianController::class, 'index'])->middleware('admin')->name('guardians.index');
    Route::post('wali/{guardian}/siswa', [Admin\GuardianController::class, 'link'])->middleware('admin')->name('guardians.link');
});
