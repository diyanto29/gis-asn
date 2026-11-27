@extends('layouts.app')

@section('title', 'Buka Formasi Jabatan Baru')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('formasi-jabatan.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buka Formasi Jabatan Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Mendaftarkan kebutuhan ABK jabatan pada unit kerja. Bezetting riil, kuota lowong, dan status dihitung otomatis dari pemangku PNS.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl overflow-hidden">
        <form method="POST" action="{{ route('formasi-jabatan.store') }}" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Sasaran <span class="text-rose-500">*</span></label>
                    <select name="id_unit_kerja" required class="searchable-select w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Unit Kerja / Kantor --</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" {{ old('id_unit_kerja') == $u->id ? 'selected' : '' }}>
                                {{ $u->nama_unit }} ({{ $u->kategori }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_unit_kerja')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                    <input type="number" name="tahun_anggaran" value="{{ old('tahun_anggaran', 2026) }}" min="2020" max="2035" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('tahun_anggaran')
                        <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Referensi Master Jabatan <span class="text-rose-500">*</span></label>
                <select name="id_master_jabatan" required class="searchable-select w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Pilih Jabatan dari Master Referensi Jabatan --</option>
                    @foreach ($masterJabatans as $mj)
                        <option value="{{ $mj->id }}" {{ old('id_master_jabatan') == $mj->id ? 'selected' : '' }}>
                            [{{ $mj->jenis_jabatan === 'fungsional' ? 'Fungsional' : 'Pelaksana' }}] {{ $mj->nama_jabatan }}{{ $mj->jenis_jabatan === 'fungsional' && $mj->jenjang_jabatan ? ' — ' . $mj->jenjang_jabatan : '' }}{{ $mj->kelas_jabatan ? ' (Kelas ' . $mj->kelas_jabatan . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Nama, jenis, jenjang, dan kualifikasi pendidikan diambil otomatis dari Data Referensi Jabatan.</p>
                @error('id_master_jabatan')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kebutuhan ABK (Jumlah Kursi) <span class="text-rose-500">*</span></label>
                <input type="number" name="kebutuhan_abk" value="{{ old('kebutuhan_abk', 2) }}" min="1" required class="w-full sm:w-48 text-sm font-mono font-bold border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                <p class="text-[11px] text-slate-400 mt-1">Bezetting riil akan dihitung otomatis dari jumlah PNS pemangku jabatan ini pada unit kerja sasaran, dan Kuota Lowong = Kebutuhan ABK − Bezetting.</p>
                @error('kebutuhan_abk')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('formasi-jabatan.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Simpan &amp; Buka Formasi
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
