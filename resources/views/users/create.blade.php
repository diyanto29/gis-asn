@extends('layouts.app')

@section('title', 'Tambah User Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Akun Pengguna</h1>
                <p class="text-xs text-slate-500 mt-0.5">Buat akun baru untuk Administrator BKPSDM atau Pegawai PNS.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-5">
            @csrf

            <!-- Role Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Role Hak Akses <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex flex-col p-4 border-2 rounded-xl cursor-pointer hover:border-indigo-500 transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30">
                        <input type="radio" name="role" value="admin" {{ old('role') === 'admin' ? 'checked' : '' }} onchange="toggleAsnSelect()" class="sr-only">
                        <span class="text-sm font-bold text-slate-900">Administrator BKPSDM</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">Akses penuh Master Data, Pembobotan TOPSIS, Formasi ABK, dan Manajemen User.</span>
                    </label>
                    <label class="flex flex-col p-4 border-2 rounded-xl cursor-pointer hover:border-emerald-500 transition has-checked:border-emerald-600 has-checked:bg-emerald-50/30">
                        <input type="radio" name="role" value="asn" {{ old('role', 'asn') === 'asn' ? 'checked' : '' }} onchange="toggleAsnSelect()" class="sr-only">
                        <span class="text-sm font-bold text-slate-900">Pegawai PNS</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">Akses profil pribadi, pemetaan domisili, dan simulasi mutasi mandiri.</span>
                    </label>
                </div>
                @error('role') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tautkan Profil PNS (khusus role asn) -->
            <div id="asnLinkSection">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tautkan ke Profil Data PNS (Opsional — otomatis isi Nama &amp; NIP)</label>
                <select name="asn_id" id="asnSelect" onchange="fillFromAsn(this)" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Pilih Profil PNS yang Belum Punya Akun --</option>
                    @foreach ($availableAsns as $a)
                        <option value="{{ $a->id }}"
                            data-nama="{{ $a->nama }}"
                            data-nip="{{ $a->nip }}"
                            {{ old('asn_id') == $a->id ? 'selected' : '' }}>
                            {{ $a->nip }} - {{ $a->nama_lengkap }} ({{ $a->unitSaatIni->nama_unit ?? '-' }})
                        </option>
                    @endforeach
                </select>
                @error('asn_id') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="nameInput" value="{{ old('name') }}" required placeholder="Nama lengkap pengguna" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">NIP (18 Digit)</label>
                    <input type="text" name="nip" id="nipInput" value="{{ old('nip') }}" placeholder="Contoh: 198501012010011001" class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('nip') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Login <span class="text-rose-500">*</span></label>
                <input type="email" name="email" id="emailInput" value="{{ old('email') }}" required placeholder="nama@bkpsdm.kabxyz.go.id" class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                @error('email') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('password') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="Ulangi kata sandi" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Simpan Akun User
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function toggleAsnSelect() {
        const role = document.querySelector('input[name="role"]:checked')?.value;
        const sec = document.getElementById('asnLinkSection');
        if (sec) sec.style.display = (role === 'asn') ? 'block' : 'none';
    }
    function fillFromAsn(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;
        document.getElementById('nameInput').value = opt.dataset.nama || '';
        document.getElementById('nipInput').value = opt.dataset.nip || '';
        if (opt.dataset.nip) {
            document.getElementById('emailInput').value = opt.dataset.nip + '@bkpsdm.kabxyz.go.id';
        }
    }
    document.addEventListener('DOMContentLoaded', toggleAsnSelect);
</script>
@endpush
