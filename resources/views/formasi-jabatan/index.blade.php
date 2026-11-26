@extends('layouts.app')

@section('title', 'Master Formasi Jabatan & Tracing Riwayat ABK')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Formasi Jabatan & Rekam Jejak ABK</h1>
            <p class="text-sm text-slate-500 mt-1">
                Katalog formasi kebutuhan jabatan fungsional/struktural, kualifikasi linier, dan audit tracing multi-tahun.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('formasi-jabatan.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i> Buka Formasi Jabatan Baru
            </a>
        </div>
    </div>

    <!-- Tracing Year Navigation Tabs (Multi-Year Audit) -->
    <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 text-xs font-semibold">
            <span class="text-slate-400 px-2 flex items-center gap-1">
                <i data-lucide="history" class="w-3.5 h-3.5"></i> Tracing Tahun:
            </span>
            @foreach ($availableYears as $year)
                <a href="{{ route('formasi-jabatan.index', array_merge(request()->query(), ['tahun' => $year])) }}" class="px-3 py-1.5 rounded-lg transition {{ $selectedYear == $year ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Tahun {{ $year }} {{ $year == 2026 ? '(Aktif)' : '(Historis)' }}
                </a>
            @endforeach
            <a href="{{ route('formasi-jabatan.index', array_merge(request()->query(), ['tahun' => 'all'])) }}" class="px-3 py-1.5 rounded-lg transition {{ $selectedYear === 'all' ? 'bg-slate-800 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Semua Riwayat
            </a>
        </div>
        <div class="text-[11px] text-slate-400 font-mono pr-2">
            Periode Peninjauan: <strong>{{ $selectedYear === 'all' ? 'Sepanjang Riwayat' : 'Tahun Anggaran ' . $selectedYear }}</strong>
        </div>
    </div>

    <!-- Stat Cards Metric Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="briefcase" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Formasi</div>
                <div class="text-xl font-extrabold text-slate-900">{{ $totalFormasi }} <span class="text-xs font-normal text-slate-400">Posisi</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="door-open" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Formasi Terbuka</div>
                <div class="text-xl font-extrabold text-emerald-600">{{ $formasiTerbuka }} <span class="text-xs font-normal text-slate-400">Jabatan</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kursi Lowong</div>
                <div class="text-xl font-extrabold text-indigo-600">{{ $totalLowong }} <span class="text-xs font-normal text-slate-400">Kursi</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                <i data-lucide="archive" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Formasi Terisi / Tutup</div>
                <div class="text-xl font-extrabold text-slate-700">{{ $formasiTerisi }} <span class="text-xs font-normal text-slate-400">Riwayat</span></div>
            </div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('formasi-jabatan.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tahun" value="{{ $selectedYear }}">

                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama jabatan/kualifikasi..." class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border w-56">

                <select name="unit_id" class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border">
                    <option value="">Semua Unit Kerja</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_unit }}</option>
                    @endforeach
                </select>

                <select name="status" class="text-xs rounded-lg border-slate-300 px-3 py-1.5 border">
                    <option value="">Semua Status</option>
                    <option value="terbuka" {{ request('status') === 'terbuka' ? 'selected' : '' }}>Terbuka</option>
                    <option value="terisi" {{ request('status') === 'terisi' ? 'selected' : '' }}>Terisi</option>
                    <option value="ditutup" {{ request('status') === 'ditutup' ? 'selected' : '' }}>Ditutup</option>
                </select>

                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-xs px-3.5 py-1.5 rounded-lg transition font-medium">Filter</button>
                @if (request()->hasAny(['search', 'unit_id', 'status']))
                    <a href="{{ route('formasi-jabatan.index', ['tahun' => $selectedYear]) }}" class="text-xs text-slate-500 hover:text-slate-800 p-1.5 border rounded-lg">Reset</a>
                @endif
            </form>

            <div class="text-xs text-slate-500">
                Total: <strong>{{ count($formasiList) }}</strong> baris formasi jabatan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 w-24">Tahun</th>
                        <th class="px-5 py-3.5">Unit Kerja Sasaran</th>
                        <th class="px-5 py-3.5">Nama Jabatan & Kualifikasi</th>
                        <th class="px-5 py-3.5 text-center">Jenjang</th>
                        <th class="px-5 py-3.5 text-center">ABK / Bezetting</th>
                        <th class="px-5 py-3.5 text-center">Sisa Lowong</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-center no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($formasiList as $formasi)
                        <tr class="table-row-standard group">
                            <td class="px-5 py-3.5 font-mono text-xs">
                                <span class="px-2 py-1 bg-slate-100 text-slate-800 font-bold rounded border border-slate-200">
                                    {{ $formasi->tahun_anggaran }}
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1">{{ $formasi->periode }}</div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-900">
                                <div class="font-bold text-slate-900 group-hover:text-blue-700 transition">
                                    {{ $formasi->unitKerja->nama_unit ?? '-' }}
                                </div>
                                <span class="text-[11px] text-slate-400">{{ $formasi->unitKerja->kategori ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900">{{ $formasi->nama_jabatan }}</span>
                                    @if ($formasi->jenis_jabatan === 'struktural')
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 rounded">Struktural</span>
                                    @elseif ($formasi->jenis_jabatan === 'fungsional')
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 rounded">Fungsional</span>
                                    @elseif ($formasi->jenis_jabatan === 'pelaksana')
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">Pelaksana</span>
                                    @endif
                                    @if ($formasi->masterJabatan)
                                        <span class="px-1.5 py-0.5 text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200 rounded">
                                            {{ $formasi->masterJabatan->kode_jabatan }}{{ $formasi->masterJabatan->kelas_jabatan ? ' • Kls ' . $formasi->masterJabatan->kelas_jabatan : '' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">Syarat: {{ $formasi->kualifikasi_pendidikan ?? 'Kesesuaian rumpun ilmu' }}</div>
                                @if ($formasi->keterangan)
                                    <div class="text-[11px] text-slate-400 italic mt-0.5 max-w-sm truncate">{{ $formasi->keterangan }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center text-xs">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-medium rounded">
                                    {{ $formasi->jenjang_jabatan ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center text-xs font-mono">
                                <div>Kebutuhan: <strong>{{ $formasi->kebutuhan_abk }}</strong></div>
                                <div class="text-slate-400 text-[11px]">Bezetting: {{ $formasi->bezetting_riil }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($formasi->kuota_lowong > 0)
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 font-extrabold text-xs rounded-full">
                                        {{ $formasi->kuota_lowong }} Kursi
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-slate-100 text-slate-400 text-xs rounded-full">
                                        0 Kursi
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($formasi->status === 'terbuka')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Terbuka
                                    </span>
                                @elseif ($formasi->status === 'terisi')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Terisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Ditutup
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('formasi-jabatan.edit', $formasi->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Formasi">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('formasi-jabatan.destroy', $formasi->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus formasi {{ $formasi->nama_jabatan }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Formasi">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                Belum ada formasi jabatan yang terdaftar pada kriteria peninjauan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

