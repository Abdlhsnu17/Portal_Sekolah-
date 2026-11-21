<?php

namespace App\Http\Controllers;

use App\Notifications\SecurityEventNotification;
use App\Support\SecurityAudit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SecurityController extends Controller
{
    public function index(Request $request, Totp $totp)
    {
        $secret = $request->session()->get('mfa_setup_secret');

        return view('auth.security', [
            'user' => $request->user(),
            'sessions' => DB::table('sessions')->where('user_id', $request->user()->id)
                ->latest('last_activity')->get(),
            'audits' => DB::table('security_audit_logs')->where('user_id', $request->user()->id)
                ->latest('created_at')->limit(15)->get(),
            'mfaSecret' => $secret,
            'mfaUri' => $secret ? $totp->uri($secret, $request->user()->email) : null,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
        $this->deleteOtherSessions($request);
        SecurityAudit::record('password.changed', $request->user(), $request);
        if (! app()->environment('testing')) {
            $request->user()->notify(new SecurityEventNotification('Kata sandi akun Anda baru saja diubah.', (string) $request->ip()));
        }

        return back()->with('success', 'Kata sandi diperbarui dan sesi lain telah dikeluarkan.');
    }

    public function beginMfa(Request $request, Totp $totp)
    {
        abort_unless(config('security.mfa_enabled'), 404);
        $request->validate(['current_password' => ['required', 'current_password']]);
        $request->session()->put('mfa_setup_secret', $totp->secret());

        return back()->with('success', 'Rahasia MFA dibuat. Tambahkan ke aplikasi autentikator lalu konfirmasi kode.');
    }

    public function confirmMfa(Request $request, Totp $totp)
    {
        abort_unless(config('security.mfa_enabled'), 404);
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = (string) $request->session()->get('mfa_setup_secret');
        if (! $secret || ! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Kode autentikator tidak valid atau kedaluwarsa.']);
        }

        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))->all();
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $codes),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('mfa_setup_secret');
        $request->session()->flash('recovery_codes', $codes);
        SecurityAudit::record('mfa.enabled', $request->user(), $request);

        return back()->with('success', 'Autentikasi dua faktor aktif. Simpan kode pemulihan di tempat aman.');
    }

    public function disableMfa(Request $request)
    {
        abort_unless(config('security.mfa_enabled'), 404);
        if ($request->user()->isAdmin()) {
            return back()->withErrors(['mfa' => 'MFA wajib untuk administrator dan tidak dapat dinonaktifkan.']);
        }
        $request->validate(['current_password' => ['required', 'current_password']]);
        $request->user()->forceFill([
            'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
        ])->save();
        SecurityAudit::record('mfa.disabled', $request->user(), $request);

        return back()->with('success', 'Autentikasi dua faktor dinonaktifkan.');
    }

    public function destroyOtherSessions(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $this->deleteOtherSessions($request);
        SecurityAudit::record('sessions.other_revoked', $request->user(), $request);

        return back()->with('success', 'Semua sesi lain telah dikeluarkan.');
    }

    private function deleteOtherSessions(Request $request): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())->delete();
        }
    }
}
