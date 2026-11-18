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
        @if (auth()->user()->isAdmin())
            <div>
                <a href="{{ route('unit-kerja.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i> Tambah Unit Kerja Baru
                </a>
            </div>
        @endif
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('unit-kerja.index') }}" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama unit..." class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border bg-white focus:ring-2 focus:ring-blue-600">
                <select name="kategori" class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border bg-white focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua Kategori</option>
                    <option value="Dinas" {{ request('kategori') === 'Dinas' ? 'selected' : '' }}>Dinas</option>
                    <option value="Badan" {{ request('kategori') === 'Badan' ? 'selected' : '' }}>Badan</option>
                    <option value="Kecamatan" {{ request('kategori') === 'Kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                    <option value="UPTD" {{ request('kategori') === 'UPTD' ? 'selected' : '' }}>UPTD / Rumah Sakit</option>
                </select>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-xs px-3.5 py-1.5 rounded-lg transition font-medium">Cari</button>
                @if(request()->filled('search') || request()->filled('kategori'))
                    <a href="{{ route('unit-kerja.index') }}" class="text-xs text-slate-500 hover:text-slate-800 p-1.5 border rounded-lg bg-white">Reset</a>
                @endif
            </form>
            <div class="text-xs text-slate-500">
                Total: <strong>{{ count($units) }}</strong> Unit Kerja Terdaftar
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Kode & Nama Unit</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5">Titik Koordinat Kantor</th>
                        <th class="px-5 py-3.5 text-center">Bezetting Riil</th>
                        <th class="px-5 py-3.5 text-center">Kebutuhan ABK</th>
                        <th class="px-5 py-3.5 text-center">Kuota Lowong</th>
                        <th class="px-5 py-3.5 text-center no-sort">Aksi</th>
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
                                @if (auth()->user()->isAdmin())
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('unit-kerja.edit', $unit->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg inline-block transition" title="Edit Unit & Kuota Formasi">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('unit-kerja.destroy', $unit->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus unit kerja {{ $unit->nama_unit }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Unit Kerja">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">Info Publik</span>
                                @endif
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
    </div>

</div>
@endsection

