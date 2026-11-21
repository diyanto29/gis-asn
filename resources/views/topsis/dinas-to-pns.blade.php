@extends('layouts.app')

@section('title', 'Rekomendasi Mutasi: Dinas ke PNS (Mode 2)')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 mb-2">
                <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Mode 2 &bull; Dinas Mencari Pegawai
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rekomendasi Mutasi: Dinas ke PNS</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pilih suatu Dinas atau Unit Kerja yang membutuhkan aparatur untuk menghitung peringkat Pegawai PNS se-kabupaten yang paling tepat ditarik ke dinas tersebut.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('master-kriteria.index', ['mode' => 'dinas_to_pns']) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="sliders" class="w-4 h-4 text-blue-600"></i> Bobot Kriteria ({{ $kriteriaModels->count() }})
            </a>
            <a href="{{ route('topsis.pns-to-dinas') }}" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Beralih ke Mode 1 (PNS ke Dinas)
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <div class="font-medium">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <div class="font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Selector Dinas / Unit Kerja -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('topsis.dinas-to-pns') }}" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Pilih Dinas / Unit Kerja (Target yang Membutuhkan Pegawai) <span class="text-rose-500">*</span>
                    </label>
                    <select name="unit_id" required class="w-full text-sm rounded-xl border-slate-300 focus:border-blue-600 focus:ring-blue-600 p-2.5 border bg-white shadow-2xs">
                        <option value="">-- Pilih salah satu Unit Kerja / Instansi --</option>
                        @foreach ($unitList as $u)
                            <option value="{{ $u->id }}" {{ (string)$u->id === (string)request('unit_id') ? 'selected' : '' }}>
                                {{ $u->nama_unit }} [{{ $u->kategori }}] - Bezetting: {{ $u->bezetting_saat_ini }} / ABK: {{ $u->kebutuhan_abk }} {{ $u->kuota_formasi_kosong > 0 ? "({$u->kuota_formasi_kosong} Lowong)" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 shrink-0">
                    <i data-lucide="search" class="w-4 h-4"></i> Cari Kandidat Terbaik
                </button>
            </div>
        </form>

        @if ($selectedUnit)
        <!-- Profil Unit Kerja Terpilih -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Nama Unit Kerja</span>
                <span class="font-bold text-slate-900 text-sm block mt-0.5">{{ $selectedUnit->nama_unit }}</span>
                <span class="inline-block px-2 py-0.5 mt-1 rounded text-[10px] font-bold bg-slate-200 text-slate-800">
                    {{ $selectedUnit->kategori }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Kapasitas ABK vs Bezetting</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    Bezetting: <strong>{{ $selectedUnit->bezetting_saat_ini }} orang</strong>
                </span>
                <span class="text-slate-500 text-[11px]">Kebutuhan ABK: {{ $selectedUnit->kebutuhan_abk }} orang</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Status Kebutuhan Pegawai</span>
                @if ($selectedUnit->kuota_formasi_kosong > 0 || $selectedUnit->bezetting_saat_ini < $selectedUnit->kebutuhan_abk)
                    <span class="font-bold text-rose-700 block mt-0.5 flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-600"></i>
                        Kekurangan {{ max($selectedUnit->kuota_formasi_kosong, $selectedUnit->kebutuhan_abk - $selectedUnit->bezetting_saat_ini) }} Staf
                    </span>
                    <span class="text-[11px] text-slate-500">Sangat butuh mutasi masuk</span>
                @else
                    <span class="font-semibold text-emerald-700 block mt-0.5">Kapasitas Cukup / Terpenuhi</span>
                    <span class="text-[11px] text-slate-500">Bezetting seimbang</span>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Titik Koordinat Kantor</span>
                <span class="font-mono text-slate-700 block mt-0.5">Lat: {{ number_format($selectedUnit->kantor_lat, 5) }}</span>
                <span class="font-mono text-slate-700 block">Lng: {{ number_format($selectedUnit->kantor_lng, 5) }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Panel Kriteria Aktif Mode 2 (Foto 2) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i data-lucide="check-square" class="w-4 h-4 text-emerald-600"></i> Kriteria Penilaian SPK Dinas ➔ PNS (Total Bobot: {{ round($kriteriaModels->sum('bobot') * 100) }}%)
            </div>
            <a href="{{ route('master-kriteria.index', ['mode' => 'dinas_to_pns']) }}" class="text-xs text-blue-700 hover:underline font-semibold flex items-center gap-1">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Ubah Bobot
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach ($kriteriaModels as $k)
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="font-mono font-bold text-xs text-indigo-900">{{ $k->kode_kriteria }}</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ $k->atribut === 'cost' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                            {{ $k->atribut }}
                        </span>
                    </div>
                    <div class="font-semibold text-slate-800 text-[11px] mt-1 truncate" title="{{ $k->nama_kriteria }}">{{ $k->nama_kriteria }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Bobot: <strong>{{ round($k->bobot * 100) }}%</strong></div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($selectedUnit && count($ranked) > 0)
    <!-- Tabel Hasil Ranking DataTables 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i> Rekomendasi Kandidat Pegawai PNS untuk {{ $selectedUnit->nama_unit }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Kandidat diurutkan berdasarkan kedekatan domisili ke kantor ini, kematangan masa jabatan, masa kerja, dan keamanan kondisi unit kerja asal.
                </p>
            </div>
            <div class="text-xs text-slate-500">
                Total Kandidat Teranalisis: <strong>{{ count($ranked) }}</strong> Pegawai
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12 no-sort">Rank</th>
                        <th class="px-4 py-3.5">Pegawai ASN</th>
                        <th class="px-4 py-3.5">Jabatan & Jenis</th>
                        <th class="px-4 py-3.5">Unit Kerja Asal</th>
                        <th class="px-4 py-3.5 text-right">Jarak Rumah ke Dinas</th>
                        <th class="px-4 py-3.5 text-center">Masa Jabatan / Kerja</th>
                        <th class="px-4 py-3.5 text-center">Kondisi Unit Asal</th>
                        <th class="px-4 py-3.5 text-center font-mono font-bold text-indigo-900">Nilai V<sub>i</sub></th>
                        <th class="px-4 py-3.5 text-center no-sort">Aksi Sidang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($ranked as $item)
                        <tr class="table-row-standard group">
                            <td class="px-4 py-3.5 text-center font-bold">
                                @if ($item['rank'] === 1)
                                    <span class="w-6 h-6 rounded-full bg-amber-500 text-white inline-flex items-center justify-center font-extrabold text-xs shadow-xs">1</span>
                                @elseif ($item['rank'] === 2)
                                    <span class="w-6 h-6 rounded-full bg-slate-400 text-white inline-flex items-center justify-center font-bold text-xs">2</span>
                                @elseif ($item['rank'] === 3)
                                    <span class="w-6 h-6 rounded-full bg-amber-700 text-white inline-flex items-center justify-center font-bold text-xs">3</span>
                                @else
                                    <span class="text-xs text-slate-500 font-mono">{{ $item['rank'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900 group-hover:text-blue-700 transition">
                                    {{ $item['asn']->nama ?? 'Pegawai ASN' }}
                                </div>
                                <div class="text-xs text-slate-400 font-mono">
                                    NIP. {{ $item['asn']->nip ?? '-' }} &bull; Gol. {{ $item['asn']->pangkat_golongan ?? '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-slate-800 text-xs">{{ $item['asn']->jabatan ?? '-' }}</div>
                                <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ ($item['asn']->jenis_jabatan ?? '') === 'fungsional' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $item['asn']->jenis_jabatan ?? 'Pelaksana' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <div class="font-medium text-slate-700">{{ $item['asn']->unitSaatIni->nama_unit ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item['asn']->unit_kerja_induk ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800 text-xs">
                                {{ number_format($item['jarak_ke_dinas_km'], 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs">
                                <div>Jabatan: <strong>{{ number_format($item['masa_jabatan_tahun'], 1) }} Thn</strong></div>
                                <div class="text-[10px] text-slate-400">Kerja: {{ number_format($item['masa_kerja_tahun'], 0) }} Thn</div>
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs">
                                @if ($item['kondisi_unit_asal_skor'] >= 3)
                                    <span class="px-2 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Unit asal kelebihan staf, sangat aman dimutasi">
                                        Surplus Staf
                                    </span>
                                @elseif ($item['kondisi_unit_asal_skor'] == 2)
                                    <span class="px-2 py-0.5 rounded-full font-medium bg-slate-100 text-slate-700">
                                        Cukup (Normal)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Unit asal juga kekurangan staf">
                                        Kurang Staf
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-xs text-indigo-900">
                                {{ number_format($item['vi'], 4) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <form method="POST" action="{{ route('topsis.simpan') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="id_asn" value="{{ $item['id'] }}">
                                    <input type="hidden" name="id_unit_tujuan" value="{{ $selectedUnit->id }}">
                                    <input type="hidden" name="jarak_baru_km" value="{{ $item['jarak_ke_dinas_km'] }}">
                                    <input type="hidden" name="selisih_hemat_jarak_km" value="{{ $item['hemat_jarak_km'] }}">
                                    <input type="hidden" name="nilai_preferensi_v" value="{{ $item['vi'] }}">
                                    <input type="hidden" name="ranking" value="{{ $item['rank'] }}">
                                    <input type="hidden" name="catatan" value="Rekomendasi Mode 2 (Dinas to PNS): Penarikan {{ $item['asn']->nama ?? '' }} ke {{ $selectedUnit->nama_unit }}.">

                                    <button type="submit" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg text-xs font-semibold transition flex items-center gap-1 mx-auto shadow-2xs" title="Ajukan Kandidat ini ke Sidang Baperjakat">
                                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Tarik Pegawai
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif (request()->filled('unit_id'))
        <div class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-400">
            <i data-lucide="info" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
            Tidak ditemukan kandidat pegawai PNS di luar unit ini yang memenuhi kriteria domisili.
        </div>
    @endif

</div>
@endsection
