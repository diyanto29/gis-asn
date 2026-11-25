@extends('layouts.app')

@section('title', 'Master Referensi Jabatan Standar PNS')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Referensi Jabatan PNS</h1>
            <p class="text-sm text-slate-500 mt-1">
                Katalog baku nomenklatur jabatan (Pelaksana & Fungsional) untuk standarisasi profil PNS dan validasi formasi linier TOPSIS.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('master-jabatan.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i> Tambah Referensi Jabatan
            </a>
        </div>
    </div>

    <!-- Stat Cards Metric Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="briefcase" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Jabatan</div>
                <div class="text-xl font-extrabold text-slate-900">{{ number_format($totalJabatan) }} <span class="text-xs font-normal text-slate-400">Nomenklatur</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Jabatan Pelaksana</div>
                <div class="text-xl font-extrabold text-emerald-600">{{ number_format($totalPelaksana) }} <span class="text-xs font-normal text-slate-400">Posisi</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="award" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Jabatan Fungsional</div>
                <div class="text-xl font-extrabold text-indigo-600">{{ number_format($totalFungsional) }} <span class="text-xs font-normal text-slate-400">Profesi</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="shield" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Jabatan Struktural</div>
                <div class="text-xl font-extrabold text-amber-600">{{ number_format($totalStruktural) }} <span class="text-xs font-normal text-slate-400">Eselon</span></div>
            </div>
        </div>
    </div>

    <!-- Filter Tab Bar -->
    <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 text-xs font-semibold">
            <span class="text-slate-400 px-2 flex items-center gap-1">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Klasifikasi:
            </span>
            <a href="{{ route('master-jabatan.index') }}" class="px-3 py-1.5 rounded-lg transition {{ !request('jenis') || request('jenis') === 'all' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Semua ({{ number_format($totalJabatan) }})
            </a>
            <a href="{{ route('master-jabatan.index', ['jenis' => 'pelaksana']) }}" class="px-3 py-1.5 rounded-lg transition {{ request('jenis') === 'pelaksana' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Pelaksana ({{ number_format($totalPelaksana) }})
            </a>
            <a href="{{ route('master-jabatan.index', ['jenis' => 'fungsional']) }}" class="px-3 py-1.5 rounded-lg transition {{ request('jenis') === 'fungsional' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Fungsional ({{ number_format($totalFungsional) }})
            </a>
            <a href="{{ route('master-jabatan.index', ['jenis' => 'struktural']) }}" class="px-3 py-1.5 rounded-lg transition {{ request('jenis') === 'struktural' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                Struktural ({{ number_format($totalStruktural) }})
            </a>
        </div>
        <div class="text-[11px] text-slate-400 font-mono pr-2">
            Standarisasi: <strong>PermenPANRB / BKN</strong>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-slate-400"></i>
                Daftar Nomenklatur Master Jabatan
            </h2>
            <span class="text-xs text-slate-500 font-medium">Tersinkronisasi dengan {{ number_format($jabatanList->sum('asns_count')) }} Pegawai PNS</span>
        </div>

        <div class="p-4">
            <table id="masterJabatanTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Kode Jabatan</th>
                        <th>Nama Jabatan Standar</th>
                        <th>Jenis Jabatan</th>
                        <th>Jenjang / Rumpun</th>
                        <th class="text-center">PNS Terhubung</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jabatanList as $index => $item)
                        <tr>
                            <td class="text-center font-mono text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td>
                                <span class="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ $item->kode_jabatan ?? 'JBT-' . str_pad($item->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $item->nama_jabatan }}</div>
                                @if($item->kualifikasi_pendidikan)
                                    <div class="text-[11px] text-slate-400 truncate max-w-md">{{ $item->kualifikasi_pendidikan }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($item->jenis_jabatan === 'fungsional')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Fungsional
                                    </span>
                                @elseif ($item->jenis_jabatan === 'pelaksana')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Pelaksana
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Struktural
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-xs text-slate-700 font-medium">{{ $item->jenjang_jabatan ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $item->rumpun_jabatan ?? 'Umum' }}</div>
                            </td>
                            <td class="text-center">
                                @if ($item->asns_count > 0)
                                    <span class="inline-flex items-center gap-1 font-bold text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                                        <i data-lucide="user" class="w-3 h-3"></i> {{ $item->asns_count }} PNS
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('master-jabatan.edit', $item->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Data">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                    <form action="{{ route('master-jabatan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus master jabatan ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition {{ $item->asns_count > 0 ? 'opacity-40 cursor-not-allowed' : '' }}" title="Hapus Data" {{ $item->asns_count > 0 ? 'disabled' : '' }}>
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($('#masterJabatanTable').length) {
            new DataTable('#masterJabatanTable', {
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50, 100],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari nama jabatan, kode...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ jabatan",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ditemukan data yang cocok",
                    paginate: {
                        first: "«",
                        previous: "‹",
                        next: "›",
                        last: "»"
                    }
                },
                columnDefs: [
                    { orderable: false, targets: 'no-sort' }
                ]
            });
        }
    });
</script>
@endpush
@endsection
