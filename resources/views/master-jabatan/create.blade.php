@extends('layouts.app')

@section('title', 'Tambah Master Jabatan PNS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('master-jabatan.index') }}" class="hover:text-blue-600 transition">Master Referensi Jabatan</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Tambah Baru</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Referensi Jabatan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftarkan nomenklatur jabatan baru untuk standardisasi data PNS & Formasi.</p>
        </div>
        <a href="{{ route('master-jabatan.index') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/60">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="briefcase" class="w-5 h-5 text-blue-600"></i>
                Formulir Nomenklatur Jabatan
            </h2>
            <p class="text-xs text-slate-500 mt-1">Lengkapi data jabatan sesuai ketentuan MenPAN-RB dan kebutuhan unit instansi.</p>
        </div>

        <form action="{{ route('master-jabatan.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Pilihan Utama: Jenis Jabatan (Hanya 2: Jabatan Pelaksana & Jabatan Fungsional) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                    Pilihan Jenis Jabatan <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer hover:border-emerald-500 transition has-checked:border-emerald-600 has-checked:bg-emerald-50/30">
                        <input type="radio" name="jenis_jabatan" value="pelaksana" {{ old('jenis_jabatan', 'pelaksana') === 'pelaksana' ? 'checked' : '' }} onchange="toggleJenjangJabatan()" class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Jabatan Pelaksana</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <span class="text-[11px] text-slate-500">Tugas teknis operasional administratif (tanpa jenjang jabatan).</span>
                    </label>

                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer hover:border-indigo-500 transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30">
                        <input type="radio" name="jenis_jabatan" value="fungsional" {{ old('jenis_jabatan') === 'fungsional' ? 'checked' : '' }} onchange="toggleJenjangJabatan()" class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Jabatan Fungsional</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        </div>
                        <span class="text-[11px] text-slate-500">Keahlian / keterampilan profesi tertentu (memiliki jenjang jabatan).</span>
                    </label>
                </div>
                @error('jenis_jabatan')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nama Jabatan, Kelas Jabatan (1-12), & Jenjang Jabatan (khusus Fungsional) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Jabatan Standar <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan') }}" required placeholder="Contoh: Pranata Komputer Ahli Pertama" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('nama_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kelas Jabatan (1 - 12) <span class="text-rose-500">*</span>
                    </label>
                    <select name="kelas_jabatan" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Kelas Jabatan --</option>
                        @for ($kls = 1; $kls <= 12; $kls++)
                            <option value="{{ $kls }}" {{ (string)old('kelas_jabatan') === (string)$kls ? 'selected' : '' }}>Kelas {{ $kls }}</option>
                        @endfor
                    </select>
                    @error('kelas_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div id="jenjangJabatanWrapper">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Jabatan (Khusus Jabatan Fungsional)</label>
                @php
                    $jenjangOptions = ['Pemula', 'Terampil', 'Mahir', 'Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama'];
                @endphp
                <select name="jenjang_jabatan" id="jenjangJabatanSelect" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Pilih Jenjang Jabatan --</option>
                    @foreach ($jenjangOptions as $jOpt)
                        <option value="{{ $jOpt }}" {{ old('jenjang_jabatan') === $jOpt ? 'selected' : '' }}>{{ $jOpt }}</option>
                    @endforeach
                </select>
                @error('jenjang_jabatan')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kualifikasi Pendidikan Linier -->
            @php
                $tingkatPendOptions = [
                    'SLTA / SMA Sederajat',
                    'Diploma III (D-III)',
                    'Diploma IV (D-IV)',
                    'S-1 / Sarjana',
                    'Profesi',
                    'S-2 / Magister',
                    'S-3 / Doktor',
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Pendidikan (Kualifikasi Linier)</label>
                    <select name="tingkat_pendidikan" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Tingkat Pendidikan --</option>
                        @foreach ($tingkatPendOptions as $tp)
                            <option value="{{ $tp }}" {{ old('tingkat_pendidikan') === $tp ? 'selected' : '' }}>{{ $tp }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pendidikan / Program Studi</label>
                    <input type="text" name="nama_pendidikan" value="{{ old('nama_pendidikan') }}" placeholder="Contoh: Teknik Informatika / Administrasi Publik" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <p class="text-[11px] text-slate-400 -mt-3">Digunakan untuk validasi kesesuaian formasi linier pada modul rekomendasi mutasi.</p>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('master-jabatan.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition inline-flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Nomenklatur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleJenjangJabatan() {
        const jenis = document.querySelector('input[name="jenis_jabatan"]:checked')?.value;
        const wrap = document.getElementById('jenjangJabatanWrapper');
        const sel = document.getElementById('jenjangJabatanSelect');
        if (!wrap || !sel) return;
        if (jenis === 'fungsional') {
            wrap.classList.remove('hidden');
            sel.disabled = false;
        } else {
            wrap.classList.add('hidden');
            sel.value = '';
            sel.disabled = true;
        }
    }
    document.addEventListener('DOMContentLoaded', toggleJenjangJabatan);
</script>
@endpush
