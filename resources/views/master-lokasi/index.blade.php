@extends('layouts.app')

@section('title', 'Master Data Lokasi & Wilayah Spasial')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Data Wilayah & Geospasial</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar kecamatan, titik koordinat pusat wilayah, dan referensi batas spasial Kabupaten Indramayu.</p>
        </div>
        <div>
            <a href="{{ route('master-lokasi.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i> Tambah Wilayah Baru
            </a>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('master-lokasi.index') }}" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode kecamatan..." class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border w-64">
                <button type="submit" class="bg-slate-800 text-white text-xs px-3.5 py-1.5 rounded-lg hover:bg-slate-900 transition">Cari</button>
                @if (request('search'))
                    <a href="{{ route('master-lokasi.index') }}" class="text-xs text-slate-500 hover:text-slate-800 p-1.5 border rounded-lg">Reset</a>
                @endif
            </form>
            <div class="text-xs text-slate-500 font-medium">
                Total: <strong>{{ count($lokasiList) }}</strong> Wilayah Kecamatan Terdaftar
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Kode Kemendagri</th>
                        <th class="px-5 py-3.5">Kecamatan</th>
                        <th class="px-5 py-3.5">Titik Koordinat Pusat</th>
                        <th class="px-5 py-3.5 text-center">Jumlah Desa</th>
                        <th class="px-5 py-3.5">Karakteristik Wilayah</th>
                        <th class="px-5 py-3.5 text-center no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lokasiList as $lokasi)
                        <tr class="table-row-standard group">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">
                                {{ $lokasi->kode_kecamatan }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 group-hover:text-blue-700 transition">{{ $lokasi->nama_kecamatan }}</div>
                                <span class="text-[11px] text-slate-400">Kabupaten Indramayu</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono text-slate-600">
                                <div>Lat: {{ number_format($lokasi->pusat_lat, 6) }}</div>
                                <div>Lng: {{ number_format($lokasi->pusat_lng, 6) }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-full">
                                    {{ $lokasi->jumlah_desa }} Desa
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 max-w-xs truncate">
                                {{ $lokasi->keterangan ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('master-lokasi.edit', $lokasi->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Data">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('master-lokasi.destroy', $lokasi->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data wilayah {{ $lokasi->nama_kecamatan }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada data wilayah / kecamatan yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

