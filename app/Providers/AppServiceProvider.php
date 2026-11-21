<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Gallery;
use App\Models\Registration;
use App\Models\SchoolProfile;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Observers\ActivityObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->batasiLajuPermintaan();

        foreach ([Registration::class, Student::class, Teacher::class, StaffMember::class,
            Attendance::class, Announcement::class, Gallery::class, SchoolProfile::class, User::class] as $model) {
            $model::observe(ActivityObserver::class);
        }

        View::composer(['layouts.public', 'layouts.admin'], function ($view) {
            $view->with([
                'tickerAnnouncements' => Announcement::published()->take(4)->get(),
                'tickerEnabled' => SiteSetting::getValue('ticker_enabled', '1') === '1',
                'tickerText' => SiteSetting::getValue(
                    'ticker_text',
                    'Portal sekolah — informasi akademik dan layanan sekolah dalam satu tempat.',
                ),
            ]);
        });

        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();
            $view->with([
                'headerNotifications' => $user?->notifications()->latest()->limit(8)->get() ?? collect(),
                'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
            ]);
        });
    }

    /**
     * Pembatas laju untuk jalur yang terbuka ke internet.
     *
     * Semua endpoint di bawah ini bisa diakses tanpa login, jadi tanpa pembatas
     * satu skrip bisa menebak kata sandi, membanjiri panitia dengan pendaftar
     * palsu, atau menyisir kombinasi nomor + email pada halaman cek status.
     */
    protected function batasiLajuPermintaan(): void
    {
        // Dibatasi per akun sekaligus per alamat IP: menebak satu kata sandi pada
        // banyak akun harus sama tertahannya dengan menebak banyak kata sandi
        // pada satu akun.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by((string) $request->ip()),
        ]);

        RateLimiter::for('pendaftaran', fn (Request $request) => Limit::perHour(5)->by((string) $request->ip()));

        RateLimiter::for('cek-status', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));

        RateLimiter::for('unggah-dokumen', fn (Request $request) => Limit::perMinute(20)->by((string) $request->ip()));
    }
}
