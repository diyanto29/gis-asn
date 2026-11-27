<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Menampilkan daftar seluruh akun pengguna (Admin BKPSDM & Pegawai PNS).
     */
    public function index(Request $request): View
    {
        $query = User::with('asn.unitSaatIni');

        if ($request->filled('role') && in_array($request->role, ['admin', 'asn'], true)) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $totalUsers = User::count();
        $totalAdmin = User::where('role', 'admin')->count();
        $totalAsn = User::where('role', 'asn')->count();
        $totalLinkedAsn = User::whereNotNull('asn_id')->count();

        return view('users.index', compact(
            'users',
            'totalUsers',
            'totalAdmin',
            'totalAsn',
            'totalLinkedAsn'
        ));
    }

    /**
     * Menampilkan formulir penambahan akun pengguna baru.
     */
    public function create(): View
    {
        $linkedAsnIds = User::whereNotNull('asn_id')->pluck('asn_id')->toArray();
        $availableAsns = AsnMaster::with('unitSaatIni')
            ->whereNotIn('id', $linkedAsnIds)
            ->orderBy('nama')
            ->get();

        return view('users.create', compact('availableAsns'));
    }

    /**
     * Menyimpan akun pengguna baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'nip' => 'nullable|string|max:30|unique:users,nip',
            'email' => 'required|email|max:150|unique:users,email',
            'role' => 'required|in:admin,asn',
            'asn_id' => 'nullable|exists:asn_master,id|unique:users,asn_id',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validated['role'] === 'admin') {
            $validated['asn_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna {$validated['name']} ({$validated['role']}) berhasil ditambahkan.");
    }

    /**
     * Menampilkan formulir edit akun pengguna.
     */
    public function edit(int $id): View
    {
        $user = User::with('asn')->findOrFail($id);

        $linkedAsnIds = User::whereNotNull('asn_id')
            ->where('id', '!=', $user->id)
            ->pluck('asn_id')
            ->toArray();

        $availableAsns = AsnMaster::with('unitSaatIni')
            ->whereNotIn('id', $linkedAsnIds)
            ->orderBy('nama')
            ->get();

        return view('users.edit', compact('user', 'availableAsns'));
    }

    /**
     * Memperbarui data akun pengguna dan/atau kata sandi.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'nip' => 'nullable|string|max:30|unique:users,nip,' . $user->id,
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,asn',
            'asn_id' => 'nullable|exists:asn_master,id|unique:users,asn_id,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Cegah admin terakhir turun role menjadi asn
        if ($user->isAdmin() && $validated['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withInput()->with('error', 'Tidak dapat mengubah role: minimal harus ada 1 akun Administrator BKPSDM aktif.');
        }

        if ($validated['role'] === 'admin') {
            $validated['asn_id'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Menghapus akun pengguna (dengan proteksi akun sendiri & admin terakhir).
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun yang sedang aktif login.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('users.index')
                ->with('error', 'Gagal menghapus: minimal harus tersisa 1 akun Administrator BKPSDM.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna {$name} berhasil dihapus.");
    }
}
