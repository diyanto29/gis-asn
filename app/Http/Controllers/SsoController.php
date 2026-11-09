<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\SsoToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Menangani autentikasi Single Sign-On (SSO) dari portal CodeIgniter eksisting.
     */
    public function handleSso(Request $request): RedirectResponse
    {
        $tokenString = $request->query('token');

        if (!$tokenString) {
            return redirect()->route('dashboard')
                ->with('error', 'Token autentikasi SSO tidak disertakan.');
        }

        // Cari token yang masih aktif dan belum kedaluwarsa
        $ssoToken = SsoToken::where('token', $tokenString)
            ->where('expired_at', '>', now())
            ->first();

        if (!$ssoToken) {
            return redirect()->route('dashboard')
                ->with('error', 'Tiket SSO tidak sah atau sesi Anda telah kedaluwarsa (maks. 2 menit). Silakan ulangi dari portal kepegawaian.');
        }

        $nip = $ssoToken->nip;
        $role = $ssoToken->role ?? 'asn';

        // Langsung hapus token (One-Time Ephemeral Token / Anti-Replay)
        $ssoToken->delete();

        // Cari nama dari ASN Master jika ada
        $asn = AsnMaster::where('nip', $nip)->first();
        $nama = $asn ? $asn->nama_lengkap : "Pegawai {$nip}";

        // Login atau buat sesi pengguna Laravel
        $user = User::firstOrCreate(
            ['email' => "{$nip}@bkpsdm.local"],
            [
                'name' => $nama,
                'password' => bcrypt(Str::random(24)),
            ]
        );

        Auth::login($user);

        // Jika ASN yang login, bisa diarahkan langsung ke halaman profilnya
        if ($role === 'asn' && $asn) {
            return redirect()->route('asn.show', $asn->id)
                ->with('success', "Selamat datang, {$nama}. Sesi Anda berhasil terhubung melalui SSO Portal Kepegawaian.");
        }

        return redirect()->route('dashboard')
            ->with('success', "Autentikasi SSO berhasil sebagai {$role}.");
    }
}
