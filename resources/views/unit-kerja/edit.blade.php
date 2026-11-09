@extends('layouts.app')

@section('title', 'Edit Unit Kerja: ' . $unit->nama_unit)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('unit-kerja.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Kuota Formasi & Unit Kerja</h1>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $unit->nama_unit }} ({{ $unit->kode_unit }})</p>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <form method="POST" action="{{ route('unit-kerja.update', $unit->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Unit Kerja</label>
                <input type="text" name="nama_unit" value="{{ old('nama_unit', $unit->nama_unit) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="kategori" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="Dinas" {{ $unit->kategori === 'Dinas' ? 'selected' : '' }}>Dinas</option>
                        <option value="Badan" {{ $unit->kategori === 'Badan' ? 'selected' : '' }}>Badan</option>
                        <option value="Kecamatan" {{ $unit->kategori === 'Kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                        <option value="UPTD" {{ $unit->kategori === 'UPTD' ? 'selected' : '' }}>UPTD / Rumah Sakit</option>
                        <option value="Sekolah" {{ $unit->kategori === 'Sekolah' ? 'selected' : '' }}>Sekolah</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 mb-1">Kuota Formasi Lowong (ABK)</label>
                    <input type="number" name="kuota_formasi_kosong" value="{{ old('kuota_formasi_kosong', $unit->kuota_formasi_kosong) }}" min="0" required class="w-full text-sm font-bold border border-indigo-300 rounded-lg p-2.5 bg-indigo-50/50 text-indigo-900 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bezetting Riil Saat Ini</label>
                    <input type="number" name="bezetting_saat_ini" value="{{ old('bezetting_saat_ini', $unit->bezetting_saat_ini) }}" min="0" class="w-full text-sm border border-slate-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kebutuhan Analisis Beban Kerja (ABK)</label>
                    <input type="number" name="kebutuhan_abk" value="{{ old('kebutuhan_abk', $unit->kebutuhan_abk) }}" min="0" class="w-full text-sm border border-slate-300 rounded-lg p-2.5">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude Kantor Absensi</label>
                    <input type="text" name="kantor_lat" value="{{ old('kantor_lat', $unit->kantor_lat) }}" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude Kantor Absensi</label>
                    <input type="text" name="kantor_lng" value="{{ old('kantor_lng', $unit->kantor_lng) }}" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Kantor</label>
                <textarea name="alamat_kantor" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2.5">{{ old('alamat_kantor', $unit->alamat_kantor) }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('unit-kerja.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

