@extends('layouts.app')

@section('title', 'Rekomendasi Mutasi: PNS ke Dinas (Mode 1)')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 mb-2">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Mode 1 &bull; Pegawai Mencari Kantor
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rekomendasi Mutasi: PNS ke Dinas</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pilih seorang Pegawai ASN untuk menghitung ranking penempatan Dinas/Unit Kerja tujuan terbaik berdasarkan metode TOPSIS multi-kriteria.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('master-kriteria.index', ['mode' => 'pns_to_dinas']) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="sliders" class="w-4 h-4 text-blue-600"></i> Bobot Kriteria ({{ $kriteriaModels->count() }})
            </a>
            <a href="{{ route('topsis.dinas-to-pns') }}" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Beralih ke Mode 2 (Dinas ke PNS)
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

    <!-- Selector Pegawai ASN -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('topsis.pns-to-dinas') }}" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Pilih Pegawai ASN (Target Pemohon Rotasi) <span class="text-rose-500">*</span>
                    </label>
                    <select name="asn_id" required class="w-full text-sm rounded-xl border-slate-300 focus:border-blue-600 focus:ring-blue-600 p-2.5 border bg-white shadow-2xs">
                        <option value="">-- Pilih salah satu dari 500 Pegawai ASN --</option>
                        @foreach ($asnList as $a)
                            <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('asn_id') ? 'selected' : '' }}>
                                {{ $a->nama }} (NIP. {{ $a->nip }}) - {{ $a->jabatan }} [{{ $a->unitSaatIni->nama_unit ?? 'Unit Asal' }}]
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 shrink-0">
                    <i data-lucide="calculator" class="w-4 h-4"></i> Hitung Rekomendasi TOPSIS
                </button>
            </div>
        </form>

        @if ($selectedAsn)
        <!-- Profil Pegawai Terpilih -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Nama & NIP Pegawai</span>
                <span class="font-bold text-slate-900 text-sm block mt-0.5">{{ $selectedAsn->nama }}</span>
                <span class="font-mono text-slate-500 text-[11px]">{{ $selectedAsn->nip }} &bull; Gol. {{ $selectedAsn->pangkat_golongan }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Jabatan & Jenis</span>
                <span class="font-semibold text-slate-800 block mt-0.5">{{ $selectedAsn->jabatan }}</span>
                <span class="inline-block px-2 py-0.5 mt-1 rounded text-[10px] font-bold uppercase {{ $selectedAsn->jenis_jabatan === 'fungsional' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($selectedAsn->jenis_jabatan === 'struktural' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-700') }}">
                    {{ $selectedAsn->jenis_jabatan }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Unit Kerja Asal & Jarak Riil</span>
                <span class="font-bold text-blue-900 block mt-0.5">{{ $selectedAsn->unitSaatIni->nama_unit ?? '-' }}</span>
                <span class="text-slate-600 text-[11px]">Jarak saat ini: <strong>{{ number_format($selectedAsn->jarak_ke_kantor_km, 2) }} km</strong></span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Masa Jabatan & Masa Kerja</span>
                <span class="font-semibold text-slate-800 block mt-0.5">Masa Jabatan: {{ $selectedAsn->tgl_jabatan ? date('d/m/Y', strtotime($selectedAsn->tgl_jabatan)) : '-' }}</span>
                <span class="text-slate-500 text-[11px]">Masa Kerja: {{ $selectedAsn->masa_kerja_tahun }} Tahun (TMT CPNS {{ $selectedAsn->tgl_cpns ? date('Y', strtotime($selectedAsn->tgl_cpns)) : '-' }})</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Panel Kriteria Aktif (Foto 2) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i data-lucide="check-square" class="w-4 h-4 text-emerald-600"></i> Kriteria Penilaian SPK PNS ➔ Dinas (Total Bobot: {{ round($kriteriaModels->sum('bobot') * 100) }}%)
            </div>
            <a href="{{ route('master-kriteria.index', ['mode' => 'pns_to_dinas']) }}" class="text-xs text-blue-700 hover:underline font-semibold flex items-center gap-1">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Ubah Bobot
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            @foreach ($kriteriaModels as $k)
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <span class="font-mono font-bold text-xs text-blue-900">{{ $k->kode_kriteria }}</span>
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

    @if ($selectedAsn && count($ranked) > 0)
    <!-- Tabel Hasil Ranking DataTables 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i> Matriks Rekomendasi Penempatan Terbaik untuk {{ $selectedAsn->nama }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Hasil pemeringkatan Euclidean TOPSIS terhadap <strong>{{ count($ranked) }}</strong> alternatif Unit Kerja se-Kabupaten Indramayu.
                </p>
            </div>
            <div class="text-xs text-slate-500">
                Peringkat Tertinggi: Nilai Preferensi <strong>V<sub>i</sub> Terbesar</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12 no-sort">Rank</th>
                        <th class="px-4 py-3.5">Unit Kerja Tujuan</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5 text-right">Jarak Baru</th>
                        <th class="px-4 py-3.5 text-right">Hemat Jarak</th>
                        <th class="px-4 py-3.5 text-center">Kebutuhan Staf</th>
                        <th class="px-4 py-3.5 text-center">Formasi Linier</th>
                        <th class="px-4 py-3.5 text-center font-mono font-bold text-blue-900">Nilai V<sub>i</sub></th>
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
                            <td class="px-4 py-3.5 font-bold text-slate-900 group-hover:text-blue-700 transition">
                                {{ $item['unit']->nama_unit ?? 'Unit Kerja' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded font-medium">
                                    {{ $item['unit']->kategori ?? 'OPD' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800 text-xs">
                                {{ number_format($item['jarak_baru_km'], 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-xs {{ $item['hemat_jarak_km'] > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $item['hemat_jarak_km'] > 0 ? '+' . number_format($item['hemat_jarak_km'], 2) . ' km' : '0.00 km' }}
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs">
                                @if ($item['kebutuhan_staf'] > 0)
                                    <span class="px-2 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $item['kebutuhan_staf'] }} Kursi
                                    </span>
                                @else
                                    <span class="text-slate-400">Cukup (0)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs">
                                @if ($item['has_formasi'] > 0)
                                    <span class="px-2 py-0.5 rounded font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Tersedia
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-xs text-blue-900">
                                {{ number_format($item['vi'], 4) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <form method="POST" action="{{ route('topsis.simpan') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="id_asn" value="{{ $selectedAsn->id }}">
                                    <input type="hidden" name="id_unit_tujuan" value="{{ $item['id'] }}">
                                    <input type="hidden" name="jarak_baru_km" value="{{ $item['jarak_baru_km'] }}">
                                    <input type="hidden" name="selisih_hemat_jarak_km" value="{{ $item['hemat_jarak_km'] }}">
                                    <input type="hidden" name="nilai_preferensi_v" value="{{ $item['vi'] }}">
                                    <input type="hidden" name="ranking" value="{{ $item['rank'] }}">
                                    <input type="hidden" name="catatan" value="Rekomendasi Mode 1 (PNS to Dinas): Penempatan ideal {{ $selectedAsn->nama }} ke {{ $item['unit']->nama_unit ?? '' }}.">

                                    <button type="submit" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg text-xs font-semibold transition flex items-center gap-1 mx-auto shadow-2xs" title="Ajukan Usulan ini ke Sidang Baperjakat">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i> Ajukan Sidang
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif (request()->filled('asn_id'))
        <div class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-400">
            <i data-lucide="info" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
            Tidak ditemukan alternatif unit kerja yang memenuhi kriteria koordinat untuk pegawai ini.
        </div>
    @endif

</div>
@endsection
