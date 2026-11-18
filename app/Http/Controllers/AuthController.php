<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login sistem mandiri SIMUTASI-GIS.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses autentikasi pengguna (Mendukung Login via NIP atau Email).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'NIP atau Alamat Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Tentukan login menggunakan NIP atau Email
        $loginType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'nip';

        if (Auth::attempt([$loginType => $loginInput, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $roleLabel = ($user->role === 'admin') ? 'Administrator BKPSDM' : 'Pegawai ASN';
            $targetRoute = $user->isAsn() ? route('asn.simulasi-mandiri') : route('dashboard');

            return redirect()->intended($targetRoute)
                ->with('success', "Selamat datang kembali, {$user->name}! Anda login sebagai {$roleLabel}.");
        }

        return back()->withInput($request->only('login', 'remember'))->withErrors([
            'login' => 'NIP / Email atau kata sandi yang Anda masukkan tidak valid.',
        ]);
    }

    /**
     * Mengakhiri sesi pengguna (Logout).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Sesi Anda telah berhasil diakhiri.');
    }
}

