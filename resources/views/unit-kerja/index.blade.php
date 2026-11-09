@extends('layouts.app')

@section('title', 'Master Unit Kerja & Formasi ABK')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Unit Kerja & Formasi (ABK / Bezetting)</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar instansi, koordinat kantor absensi, dan kuota kebutuhan mutasi masuk.</p>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('unit-kerja.index') }}" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama unit..." class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border">
                <select name="kategori" class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border">
                    <option value="">Semua Kategori</option>
                    <option value="Dinas" {{ request('kategori') === 'Dinas' ? 'selected' : '' }}>Dinas</option>
                    <option value="Badan" {{ request('kategori') === 'Badan' ? 'selected' : '' }}>Badan</option>
                    <option value="Kecamatan" {{ request('kategori') === 'Kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                    <option value="UPTD" {{ request('kategori') === 'UPTD' ? 'selected' : '' }}>UPTD / Rumah Sakit</option>
                </select>
                <button type="submit" class="bg-slate-800 text-white text-xs px-3 py-1.5 rounded-lg">Cari</button>
            </form>
            <div class="text-xs text-slate-500">
                *Hanya unit dengan <strong>Kuota Formasi &gt; 0</strong> yang dapat dijadikan tujuan rotasi mutasi.
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Kode & Nama Unit</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5">Titik Koordinat Kantor</th>
                        <th class="px-5 py-3.5 text-center">Bezetting Riil</th>
                        <th class="px-5 py-3.5 text-center">Kebutuhan ABK</th>
                        <th class="px-5 py-3.5 text-center">Kuota Lowong</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $unit)
                        <tr class="table-row-standard group">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 group-hover:text-blue-700 transition">{{ $unit->nama_unit }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $unit->kode_unit }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $unit->alamat_kantor ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded font-medium">{{ $unit->kategori }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono text-slate-600">
                                <div>Lat: {{ number_format($unit->kantor_lat, 5) }}</div>
                                <div>Lng: {{ number_format($unit->kantor_lng, 5) }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-center font-medium text-slate-800">
                                {{ $unit->bezetting_saat_ini }} org
                            </td>
                            <td class="px-5 py-3.5 text-center font-medium text-slate-800">
                                {{ $unit->kebutuhan_abk }} org
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($unit->kuota_formasi_kosong > 0)
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold">
                                        {{ $unit->kuota_formasi_kosong }} Formasi
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-medium">
                                        Penuh
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <a href="{{ route('unit-kerja.edit', $unit->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg inline-block transition" title="Edit Unit & Kuota Formasi">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-400">Tidak ada data unit kerja.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($units->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $units->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

