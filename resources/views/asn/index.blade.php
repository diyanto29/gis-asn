@extends('layouts.app')

@section('title', 'Data Kepegawaian & Domisili Spasial PNS')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Data Pegawai & Pemetaan Domisili PNS</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar seluruh PNS, koordinat tempat tinggal, unit kerja, riwayat TMT, dan beban jarak harian.</p>
        </div>
        @if (auth()->user()->isAdmin())
            <div>
                <a href="{{ route('asn.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i> Tambah Pegawai PNS
                </a>
            </div>
        @endif
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('asn.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Nama / NIP / Jabatan</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NIP, jabatan..." class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Jabatan</label>
                <select name="jenis_jabatan" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
                    <option value="">Semua Jenis</option>
                    <option value="fungsional" {{ request('jenis_jabatan') === 'fungsional' ? 'selected' : '' }}>Fungsional</option>
                    <option value="pelaksana" {{ request('jenis_jabatan') === 'pelaksana' ? 'selected' : '' }}>Pelaksana</option>
                    <option value="struktural" {{ request('jenis_jabatan') === 'struktural' ? 'selected' : '' }}>Struktural</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Filter Zona Jarak</label>
                <select name="zona" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
                    <option value="">Semua Zona</option>
                    <option value="dekat" {{ request('zona') === 'dekat' ? 'selected' : '' }}>Dekat (&lt; 5 km)</option>
                    <option value="sedang" {{ request('zona') === 'sedang' ? 'selected' : '' }}>Sedang (5 - 20 km)</option>
                    <option value="jauh" {{ request('zona') === 'jauh' ? 'selected' : '' }}>Jauh (&gt; 20 km)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Unit Kerja</label>
                <select name="unit_id" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
                    <option value="">Semua Unit Kerja</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_unit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-medium text-sm py-2 px-4 rounded-lg transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filter
                </button>
                <a href="{{ route('asn.index') }}" class="p-2 border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg transition" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 w-12 text-center no-sort">No</th>
                        <th class="px-5 py-3.5">Pegawai</th>
                        <th class="px-5 py-3.5">Jabatan / Golongan</th>
                        <th class="px-5 py-3.5">Unit Kerja Saat Ini</th>
                        <th class="px-5 py-3.5">Alamat Domisili</th>
                        <th class="px-5 py-3.5 text-right">Jarak (Km)</th>
                        <th class="px-5 py-3.5 text-center">Zona</th>
                        <th class="px-5 py-3.5 text-center no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($asns as $index => $asn)
                        <tr class="table-row-standard group">
                            <td class="px-5 py-3.5 text-xs text-slate-400 font-mono text-center">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('asn.show', $asn->id) }}" class="font-bold text-slate-900 group-hover:text-blue-700 transition">
                                    {{ $asn->nama_lengkap }}
                                </a>
                                <div class="text-xs text-slate-400 font-mono">{{ $asn->nip }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900 text-xs">{{ $asn->jabatan }}</div>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    @if ($asn->jenis_jabatan === 'fungsional')
                                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-semibold rounded">Fungsional</span>
                                    @elseif ($asn->jenis_jabatan === 'pelaksana')
                                        <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold rounded">Pelaksana</span>
                                    @else
                                        <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-semibold rounded">Struktural</span>
                                    @endif
                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded">{{ $asn->pangkat_golongan }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                    TMT Jbt: {{ $asn->tgl_jabatan ? $asn->tgl_jabatan->format('d/m/Y') : '-' }} &bull; MK: {{ $asn->masa_kerja_tahun }} Thn
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-800 text-xs">
                                <div>{{ $asn->unitSaatIni->nama_unit ?? '-' }}</div>
                                @if ($asn->formasiJabatan)
                                    <div class="text-[10px] text-teal-700 mt-0.5 font-mono">
                                        Formasi {{ $asn->formasiJabatan->tahun_anggaran }} &bull; {{ ucfirst($asn->formasiJabatan->status) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                @if ($asn->domisili_lat)
                                    <div class="font-medium text-slate-700">
                                        {{ $asn->kelurahan_desa ? $asn->kelurahan_desa . ', ' : '' }}Kec. {{ $asn->kecamatan ?? '-' }}
                                    </div>
                                    @if ($asn->alamat_detail)
                                        <div class="text-[10px] text-slate-400 truncate max-w-[180px]" title="{{ $asn->alamat_detail }}">{{ $asn->alamat_detail }}</div>
                                    @endif
                                    <div class="text-[10px] text-slate-400 font-mono">{{ number_format($asn->domisili_lat, 4) }}, {{ number_format($asn->domisili_lng, 4) }}</div>
                                @else
                                    <span class="text-rose-500 italic">Belum ada titik koordinat</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold {{ $asn->zona_jarak === 'jauh' ? 'text-rose-600' : ($asn->zona_jarak === 'sedang' ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ $asn->jarak_ke_kantor_km ? number_format($asn->jarak_ke_kantor_km, 2) . ' km' : '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($asn->zona_jarak === 'jauh')
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-xs rounded-full font-semibold">Jauh</span>
                                @elseif ($asn->zona_jarak === 'sedang')
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs rounded-full font-semibold">Sedang</span>
                                @elseif ($asn->zona_jarak === 'dekat')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs rounded-full font-semibold">Dekat</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('asn.show', $asn->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Detail Pegawai">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('asn.edit', $asn->id) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit Profil Pegawai">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('asn.destroy', $asn->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pegawai {{ $asn->nama }} beserta akun loginnya?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Pegawai">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">Tidak ada data pegawai yang cocok dengan kriteria filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

