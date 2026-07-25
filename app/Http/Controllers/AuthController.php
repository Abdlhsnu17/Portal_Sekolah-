<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login', ['profil' => SchoolProfile::current()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $berhasil = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (RuntimeException) {
            // Kata sandi tersimpan bukan hash bcrypt — biasanya karena baris user
            // disunting langsung lewat klien database. Jangan sampai jadi error 500.
            throw ValidationException::withMessages([
                'email' => 'Kata sandi akun ini tersimpan dalam format yang tidak valid. '
                    .'Minta administrator menyetel ulang kata sandi melalui menu Pengguna.',
            ]);
        }

        if (! $berhasil) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
