<?php

namespace App\Http\Controllers;

use App\Models\LoginDevice;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Notifications\NewLoginNotification;
use App\Support\SecurityAudit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        $this->ensureCaptcha($request);

        return view('auth.login', [
            'profil' => SchoolProfile::current(),
            'captchaRequired' => $this->captchaRequired($request),
            'captchaQuestion' => $request->session()->get('login_captcha_question'),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($this->captchaRequired($request)) {
            $request->validate(['captcha' => ['required']]);
            if (! hash_equals((string) $request->session()->get('login_captcha_answer'), (string) $request->input('captcha'))) {
                $this->refreshCaptcha($request);
                throw ValidationException::withMessages(['captcha' => 'Jawaban verifikasi tidak tepat.']);
            }
        }

        try {
            $berhasil = Auth::validate($credentials);
        } catch (RuntimeException) {
            // Kata sandi tersimpan bukan hash bcrypt — biasanya karena baris user
            // disunting langsung lewat klien database. Jangan sampai jadi error 500.
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        if (! $berhasil) {
            RateLimiter::hit($this->failureKey($request), 900);
            SecurityAudit::record('login.failed', null, $request, ['email_hash' => hash('sha256', strtolower($credentials['email']))]);
            $this->ensureCaptcha($request);
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();
        if (! $user?->is_active) {
            RateLimiter::hit($this->failureKey($request), 900);
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->failureKey($request));
        $request->session()->forget(['login_captcha_answer', 'login_captcha_question', 'login_captcha_required']);
        if (config('security.mfa_enabled') && $user->hasTwoFactorAuthentication()) {
            $request->session()->put(['mfa_login_user' => $user->id, 'mfa_login_remember' => $request->boolean('remember') && ! $user->isAdmin()]);

            return redirect()->route('login.mfa');
        }

        Auth::login($user, $request->boolean('remember') && ! $user->isAdmin());

        return $this->finishLogin($request);
    }

    public function showMfa(Request $request)
    {
        abort_unless($request->session()->has('mfa_login_user'), 403);

        return view('auth.mfa', ['profil' => SchoolProfile::current()]);
    }

    public function verifyMfa(Request $request, Totp $totp)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = User::findOrFail($request->session()->get('mfa_login_user'));
        $input = strtoupper(trim((string) $request->input('code')));
        $valid = $totp->verify($user->two_factor_secret, $input);
        if (! $valid) {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($input, $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $valid = true;
                    break;
                }
            }
        }
        if (! $valid) {
            SecurityAudit::record('mfa.failed', $user, $request);
            throw ValidationException::withMessages(['code' => 'Kode autentikator atau pemulihan tidak valid.']);
        }
        $remember = (bool) $request->session()->pull('mfa_login_remember', false);
        $request->session()->forget('mfa_login_user');
        Auth::login($user, $remember);
        SecurityAudit::record('mfa.passed', $user, $request);

        return $this->finishLogin($request);
    }

    private function finishLogin(Request $request)
    {

        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        SecurityAudit::record('login.succeeded', Auth::user(), $request);
        $fingerprint = hash('sha256', (string) $request->userAgent().'|'.$request->ip());
        $device = LoginDevice::firstOrNew(['user_id' => Auth::id(), 'fingerprint' => $fingerprint]);
        $isNew = ! $device->exists;
        $device->fill(['ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'first_seen_at' => $device->first_seen_at ?? now(), 'last_seen_at' => now()])->save();
        if ($isNew && ! app()->environment('testing') && filter_var(Auth::user()->email, FILTER_VALIDATE_EMAIL)) {
            Auth::user()->notify(new NewLoginNotification((string) $request->ip(), (string) $request->userAgent()));
        }

        return redirect()->intended(Auth::user()->password_changed_at?->lt(now()->subYears(50)) ? route('security.index') : route('admin.dashboard'))
            ->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
    }

    public function logout(Request $request)
    {
        SecurityAudit::record('logout', $request->user(), $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    private function failureKey(Request $request): string
    {
        return 'login-fail:'.hash('sha256', strtolower((string) $request->input('email')).'|'.$request->ip());
    }

    private function captchaRequired(Request $request): bool
    {
        if (filled($request->input('email'))) {
            return RateLimiter::attempts($this->failureKey($request)) >= 5;
        }

        return (bool) $request->session()->get('login_captcha_required', false);
    }

    private function ensureCaptcha(Request $request): void
    {
        if ($this->captchaRequired($request)) {
            $request->session()->put('login_captcha_required', true);
            if (! $request->session()->has('login_captcha_answer')) {
                $this->refreshCaptcha($request);
            }
        }
    }

    private function refreshCaptcha(Request $request): void
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        $request->session()->put(['login_captcha_question' => "{$a} + {$b}", 'login_captcha_answer' => (string) ($a + $b)]);
    }
}
