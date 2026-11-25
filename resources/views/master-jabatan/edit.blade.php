@extends('layouts.app')

@section('title', 'Edit Master Jabatan PNS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('master-jabatan.index') }}" class="hover:text-blue-600 transition">Master Referensi Jabatan</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Edit Jabatan</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Perbarui Referensi Jabatan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Perubahan nama jabatan otomatis mensinkronkan data profil PNS yang terhubung.</p>
        </div>
        <a href="{{ route('master-jabatan.index') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/60">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="edit" class="w-5 h-5 text-blue-600"></i>
                Formulir Perubahan Nomenklatur
            </h2>
            <p class="text-xs text-slate-500 mt-1">ID Referensi: #{{ $jabatan->id }} | Terhubung ke: <strong>{{ $jabatan->asns()->count() }} Pegawai PNS</strong></p>
        </div>

        <form action="{{ route('master-jabatan.update', $jabatan->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Pilihan Utama: Jenis Jabatan (Pelaksana / Fungsional / Struktural) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                    Klasifikasi / Jenis Jabatan <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer hover:border-emerald-500 transition has-checked:border-emerald-600 has-checked:bg-emerald-50/30">
                        <input type="radio" name="jenis_jabatan" value="pelaksana" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) === 'pelaksana' ? 'checked' : '' }} class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Jabatan Pelaksana</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <span class="text-[11px] text-slate-500">Tugas teknis operasional administratif.</span>
                    </label>

                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer hover:border-indigo-500 transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30">
                        <input type="radio" name="jenis_jabatan" value="fungsional" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) === 'fungsional' ? 'checked' : '' }} class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Jabatan Fungsional</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        </div>
                        <span class="text-[11px] text-slate-500">Keahlian / keterampilan profesi tertentu.</span>
                    </label>

                    <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer hover:border-amber-500 transition has-checked:border-amber-600 has-checked:bg-amber-50/30">
                        <input type="radio" name="jenis_jabatan" value="struktural" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) === 'struktural' ? 'checked' : '' }} class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Jabatan Struktural</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        </div>
                        <span class="text-[11px] text-slate-500">Kepemimpinan manajerial unit instansi.</span>
                    </label>
                </div>
                @error('jenis_jabatan')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nama & Kode Jabatan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Jabatan Standar <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan', $jabatan->nama_jabatan) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('nama_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kode Jabatan
                    </label>
                    <input type="text" name="kode_jabatan" value="{{ old('kode_jabatan', $jabatan->kode_jabatan) }}" class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('kode_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Jenjang & Rumpun Jabatan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Jabatan</label>
                    <input type="text" name="jenjang_jabatan" value="{{ old('jenjang_jabatan', $jabatan->jenjang_jabatan) }}" placeholder="Contoh: Ahli Pertama, Ahli Muda, Terampil..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('jenjang_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rumpun Jabatan</label>
                    <input type="text" name="rumpun_jabatan" value="{{ old('rumpun_jabatan', $jabatan->rumpun_jabatan) }}" placeholder="Contoh: Komputer, Administrasi, Medis..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('rumpun_jabatan')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Kualifikasi Pendidikan Linier -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kualifikasi Pendidikan Linier</label>
                <textarea name="kualifikasi_pendidikan" rows="3" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('kualifikasi_pendidikan', $jabatan->kualifikasi_pendidikan) }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk validasi kesesuaian formasi linier pada modul rekomendasi mutasi.</p>
                @error('kualifikasi_pendidikan')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('master-jabatan.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition inline-flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Perbarui Nomenklatur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
