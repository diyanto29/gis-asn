@extends('layouts.app')

@section('title', 'Manajemen User & Hak Akses RBAC')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen User & Hak Akses</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pengelolaan akun login Administrator BKPSDM dan Pegawai PNS beserta pemetaan profil kepegawaian.
            </p>
        </div>
        <div>
            <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i> Tambah User Baru
            </a>
        </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total User</div>
                <div class="text-xl font-extrabold text-slate-900">{{ number_format($totalUsers) }} <span class="text-xs font-normal text-slate-400">Akun</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Admin BKPSDM</div>
                <div class="text-xl font-extrabold text-indigo-600">{{ number_format($totalAdmin) }} <span class="text-xs font-normal text-slate-400">Admin</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Akun Pegawai PNS</div>
                <div class="text-xl font-extrabold text-emerald-600">{{ number_format($totalAsn) }} <span class="text-xs font-normal text-slate-400">PNS</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                <i data-lucide="link" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Terhubung Profil</div>
                <div class="text-xl font-extrabold text-teal-600">{{ number_format($totalLinkedAsn) }} <span class="text-xs font-normal text-slate-400">Relasi</span></div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 text-xs font-semibold">
            <span class="text-slate-400 px-2 flex items-center gap-1">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Role:
            </span>
            <a href="{{ route('users.index') }}" class="px-3 py-1.5 rounded-lg transition {{ !request('role') ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Semua ({{ number_format($totalUsers) }})
            </a>
            <a href="{{ route('users.index', ['role' => 'admin']) }}" class="px-3 py-1.5 rounded-lg transition {{ request('role') === 'admin' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Admin ({{ number_format($totalAdmin) }})
            </a>
            <a href="{{ route('users.index', ['role' => 'asn']) }}" class="px-3 py-1.5 rounded-lg transition {{ request('role') === 'asn' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                PNS ({{ number_format($totalAsn) }})
            </a>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-2">
            @if (request('role'))
                <input type="hidden" name="role" value="{{ request('role') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, atau email..." class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border w-56 focus:border-blue-500 focus:ring-blue-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-xs px-3.5 py-1.5 rounded-lg font-medium transition">Cari</button>
            @if (request()->hasAny(['search', 'role']))
                <a href="{{ route('users.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 border border-slate-200 rounded-lg">Reset</a>
            @endif
        </form>
    </div>

    <!-- Users DataTable -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 w-12 text-center no-sort">No</th>
                        <th class="px-5 py-3.5">Nama Pengguna &amp; NIP</th>
                        <th class="px-5 py-3.5">Email Login</th>
                        <th class="px-5 py-3.5 text-center">Role RBAC</th>
                        <th class="px-5 py-3.5">Profil PNS Terhubung</th>
                        <th class="px-5 py-3.5 text-center no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="table-row-standard group">
                            <td class="px-5 py-3.5 text-center font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg {{ $user->isAdmin() ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }} flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">
                                            {{ $user->name }}
                                            @if (auth()->id() === $user->id)
                                                <span class="ml-1 px-1.5 py-0.5 text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded">Anda</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-400 font-mono">{{ $user->nip ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-700">{{ $user->email }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($user->isAdmin())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i data-lucide="shield" class="w-3 h-3"></i> Admin BKPSDM
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="user" class="w-3 h-3"></i> Pegawai PNS
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                @if ($user->asn)
                                    <a href="{{ route('asn.show', $user->asn->id) }}" class="font-semibold text-blue-700 hover:underline">
                                        {{ $user->asn->nama_lengkap }}
                                    </a>
                                    <div class="text-[11px] text-slate-400">{{ $user->asn->unitSaatIni->nama_unit ?? '-' }}</div>
                                @else
                                    <span class="text-slate-400 italic">{{ $user->isAdmin() ? 'Akun Pengelola Sistem' : 'Belum terhubung' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('users.edit', $user->id) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit User / Reset Password">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    @if (auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Yakin ingin menghapus akun pengguna {{ $user->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus User">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
