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
                Pilih seorang Pegawai PNS untuk menghitung ranking penempatan Dinas/Unit Kerja tujuan terbaik berdasarkan metode TOPSIS multi-kriteria.
            </p>
        </div>
        <div class="flex items-center gap-2">
            {{-- HIDE SEMENTARA BOBOT KRITERIA SESUAI INSTRUKSI KLIEN --}}
            @if(false)
            <a href="{{ route('master-kriteria.index', ['mode' => 'pns_to_dinas']) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="sliders" class="w-4 h-4 text-blue-600"></i> Bobot Kriteria ({{ $kriteriaModels->count() }})
            </a>
            @endif
            <a href="{{ route('topsis.dinas-to-pns') }}" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Beralih ke Dinas ➔ PNS
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

    <!-- Selector Pegawai PNS -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('topsis.pns-to-dinas') }}" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Pilih Pegawai PNS (Target Pemohon Rotasi) <span class="text-rose-500">*</span>
                    </label>
                    <select name="asn_id" required class="searchable-select w-full text-sm rounded-xl border-slate-300 focus:border-blue-600 focus:ring-blue-600 p-2.5 border bg-white shadow-2xs" placeholder="-- Cari Nama / NIP Pegawai PNS --">
                        <option value="">-- Pilih salah satu Pegawai PNS --</option>
                        @foreach ($asnList as $a)
                            <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('asn_id') ? 'selected' : '' }}>
                                {{ $a->nama }} (NIP. {{ $a->nip }}) - {{ $a->jabatan }} [{{ $a->unitSaatIni->nama_unit ?? 'Unit Asal' }}]
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 shrink-0 h-[42px]">
                    <i data-lucide="map-pin" class="w-4 h-4"></i> Tampilkan Peta & Ranking
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
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Jabatan, Jenis &amp; Kelas</span>
                <span class="font-semibold text-slate-800 block mt-0.5">{{ $selectedAsn->jabatan }}</span>
                <div class="flex flex-wrap items-center gap-1 mt-1">
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $selectedAsn->jenis_jabatan === 'fungsional' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ $selectedAsn->jenis_jabatan }}
                    </span>
                    @if($selectedAsn->masterJabatan?->kelas_jabatan)
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        Kelas {{ $selectedAsn->masterJabatan->kelas_jabatan }}
                    </span>
                    @endif
                </div>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Unit Kerja Saat Ini (C5 &bull; Hak Prerogatif)</span>
                <span class="font-bold text-blue-900 block mt-0.5">{{ $selectedAsn->unitSaatIni->nama_unit ?? '-' }}</span>
                @if($unitAsalEval)
                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="px-1.5 py-0.5 rounded border font-bold text-[10px] {{ $unitAsalEval['badge_class'] }}">
                        {{ $unitAsalEval['status'] }} ({{ $unitAsalEval['persentase'] }}%)
                    </span>
                    <span class="text-slate-500">Bez: <strong>{{ $unitAsalEval['bezetting'] }}</strong> / ABK: <strong>{{ $unitAsalEval['abk'] }}</strong> / Lowong: <strong>{{ $unitAsalEval['lowong'] }}</strong></span>
                </div>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Syarat Mutlak Masa Jabatan &amp; Masa Kerja</span>
                <div class="mt-0.5 flex items-center gap-1.5">
                    <span class="font-semibold text-slate-800">Jabatan: <strong>{{ number_format($kelayakan['masa_jabatan_tahun'] ?? 0, 1) }} Thn</strong></span>
                    @if($kelayakan && $kelayakan['masa_jabatan_ok'])
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Memenuhi (&ge;{{ $kelayakan['min_masa_jabatan_tahun'] }} Th)</span>
                    @else
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Belum (&lt;{{ $kelayakan['min_masa_jabatan_tahun'] ?? 2 }} Th)</span>
                    @endif
                </div>
                <span class="text-slate-500 text-[11px] block mt-0.5">Masa Kerja: <strong>{{ number_format($kelayakan['masa_kerja_tahun'] ?? 0, 1) }} Thn</strong> &bull; Jarak: {{ number_format($selectedAsn->jarak_ke_kantor_km, 1) }} km</span>
            </div>
        </div>

        @if($kelayakan && !$kelayakan['masa_jabatan_ok'])
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5">
            <i data-lucide="shield-alert" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
            <div>
                <div class="font-bold">Tidak Memenuhi Syarat Mutlak Sistem (Poin 3 — Masa Jabatan):</div>
                <div class="mt-0.5">{{ $kelayakan['alasan_tidak_layak'] }}</div>
            </div>
        </div>
        @elseif($filterSummary && $filterSummary['unit_cocok_formasi'] === 0)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
            <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
            <div>
                <div class="font-bold">Belum Ada Formasi Jabatan Cocok &amp; Tersedia di Unit Kerja Lain:</div>
                <div class="mt-0.5">Aturan matching aktif: <strong>{{ $filterSummary['aturan_match'] }}</strong> (kuota lowong &gt; 0). Tidak ada unit kerja lain yang saat ini memiliki formasi lowong yang sesuai.</div>
            </div>
        </div>
        @elseif($filterSummary)
        <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900 text-xs flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4 text-blue-700 shrink-0"></i>
                <span><strong>Filter Mutlak Aktif:</strong> {{ $filterSummary['aturan_match'] }} &bull; Kuota Lowong &gt; 0 &bull; Masa Jabatan &ge; {{ $kelayakan['min_masa_jabatan_tahun'] ?? 2 }} Thn</span>
            </div>
            <span class="px-2 py-0.5 rounded-full bg-blue-700 text-white font-bold text-[11px]">
                {{ $filterSummary['unit_cocok_formasi'] }} dari {{ $filterSummary['total_unit_lain'] }} Unit Lolos
            </span>
        </div>
        @endif
        @endif
    </div>

    {{-- HIDE SEMENTARA PANEL KRITERIA AKTIF SESUAI INSTRUKSI KLIEN --}}
    @if(false)
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
    @endif

    @if ($selectedAsn && count($ranked) > 0)
    <!-- Peta Spasial Rekomendasi PNS -> Dinas (Show Map) -->
    <div id="mapSection" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="map" class="w-4 h-4 text-blue-700"></i>
                <h3 class="font-bold text-slate-900 text-sm">Peta Rekomendasi Penempatan — {{ $selectedAsn->nama }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-600 inline-block"></span> Domisili PNS</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-slate-500 inline-block"></span> Kantor Asal</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-600 inline-block"></span> Top 10 Dinas Rekomendasi</span>
            </div>
        </div>
        <div id="spkMapPnsToDinas" style="height: 400px;" class="w-full relative z-0"></div>
    </div>

    <!-- Tabel Hasil Ranking DataTables 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i> Matriks Rekomendasi Penempatan Terbaik untuk {{ $selectedAsn->nama }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Hasil pemeringkatan Euclidean TOPSIS terhadap <strong>{{ count($ranked) }}</strong> alternatif Unit Kerja se-Kabupaten XYZ.
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
                        <th class="px-4 py-3.5 text-center no-sort">Peta</th>
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
                                @if (!empty($item['formasi_cocok']))
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @foreach ($item['formasi_cocok'] as $fl)
                                            <span class="px-2 py-0.5 rounded font-semibold bg-blue-50 text-blue-700 border border-blue-200 text-[10px]">
                                                {{ $fl }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-xs text-blue-900">
                                {{ number_format($item['vi'], 4) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button type="button"
                                    onclick="focusUnitOnMap({{ $item['unit']->kantor_lat ?? 0 }}, {{ $item['unit']->kantor_lng ?? 0 }}, {{ json_encode($item['unit']->nama_unit ?? 'Unit Kerja') }}, {{ $item['rank'] }}, '{{ number_format($item['jarak_baru_km'], 2) }}', '{{ number_format($item['vi'], 4) }}')"
                                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 hover:border-emerald-600 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 shadow-2xs"
                                    title="Tampilkan rute ke unit ini pada peta">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Lihat Peta
                                </button>

                                {{-- HIDE SEMENTARA AJUKAN SIDANG BAPERJAKAT SESUAI INSTRUKSI KLIEN --}}
                                @if(false)
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
                                @endif
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

@if ($selectedAsn && count($ranked) > 0)
@push('scripts')
<script>
    let spkMap1, activeHighlightLine1, activeHighlightMarker1;
    const pnsHomeLat = {{ $selectedAsn->domisili_lat ?? -6.32635 }};
    const pnsHomeLng = {{ $selectedAsn->domisili_lng ?? 108.32042 }};

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('spkMapPnsToDinas')) return;

        spkMap1 = L.map('spkMapPnsToDinas').setView([pnsHomeLat, pnsHomeLng], 12);
        window.attachGmapBasemap(spkMap1);

        const bounds = [[pnsHomeLat, pnsHomeLng]];

        // 1. Marker Rumah PNS (Pin Google Maps Merah)
        L.marker([pnsHomeLat, pnsHomeLng], {
            icon: window.createGmapPinIcon({ color: '#ea4335', type: 'home', size: 'lg' })
        }).addTo(spkMap1).bindPopup(`<strong>Domisili PNS</strong><br/>{{ addslashes($selectedAsn->nama) }}`).openPopup();

        // 2. Marker Kantor Asal (Pin Google Maps Slate)
        @if($selectedAsn->unitSaatIni && $selectedAsn->unitSaatIni->kantor_lat)
            const asalLat = {{ $selectedAsn->unitSaatIni->kantor_lat }};
            const asalLng = {{ $selectedAsn->unitSaatIni->kantor_lng }};
            L.marker([asalLat, asalLng], {
                icon: window.createGmapPinIcon({ color: '#64748b', type: 'office', size: 'md' })
            }).addTo(spkMap1).bindPopup(`<strong>Kantor Asal</strong><br/>{{ addslashes($selectedAsn->unitSaatIni->nama_unit) }}<br/>Jarak: {{ number_format($selectedAsn->jarak_ke_kantor_km, 2) }} km`);
            L.polyline([[pnsHomeLat, pnsHomeLng], [asalLat, asalLng]], {
                color: '#94a3b8', weight: 2, dashArray: '5, 6', opacity: 0.7
            }).addTo(spkMap1);
            bounds.push([asalLat, asalLng]);
        @endif

        // 3. Top 10 Rekomendasi Dinas (Emerald)
        @php
            $topUnitsMap = collect($ranked)->take(10)->map(function($item) {
                return [
                    'rank' => $item['rank'],
                    'nama' => $item['unit']->nama_unit ?? 'Unit Kerja',
                    'lat' => (float) ($item['unit']->kantor_lat ?? 0),
                    'lng' => (float) ($item['unit']->kantor_lng ?? 0),
                    'jarak' => number_format($item['jarak_baru_km'], 2),
                    'vi' => number_format($item['vi'], 4),
                ];
            })->values();
        @endphp
        const topUnits = @json($topUnitsMap);

        topUnits.forEach(u => {
            if (!u.lat || !u.lng) return;
            L.marker([u.lat, u.lng], {
                icon: window.createGmapPinIcon({
                    color: u.rank === 1 ? '#059669' : '#10b981',
                    type: 'rank',
                    label: '#' + u.rank,
                    size: u.rank === 1 ? 'lg' : 'md'
                })
            }).addTo(spkMap1).bindPopup(
                `<span style="font-weight:700;color:#059669;">Rank #${u.rank}</span><br/><strong>${u.nama}</strong><br/>Jarak: <strong>${u.jarak} km</strong> &bull; V<sub>i</sub>: ${u.vi}`
            );
            if (u.rank <= 3) {
                L.polyline([[pnsHomeLat, pnsHomeLng], [u.lat, u.lng]], {
                    color: '#10b981', weight: u.rank === 1 ? 3 : 1.5, dashArray: u.rank === 1 ? null : '4, 5', opacity: 0.75
                }).addTo(spkMap1);
                bounds.push([u.lat, u.lng]);
            }
        });

        if (bounds.length > 1) {
            spkMap1.fitBounds(bounds, { padding: [40, 40] });
        }

        // Auto-track rute jalan raya OSRM untuk Rank #1 saat peta dimuat
        if (topUnits.length > 0 && topUnits[0].lat && topUnits[0].lng) {
            const first = topUnits[0];
            drawOsrmTrack1(first.lat, first.lng, first.nama, first.rank, first.jarak, first.vi, false);
        }
    });

    function focusUnitOnMap(lat, lng, nama, rank, jarak, vi) {
        if (!spkMap1 || !lat || !lng) return;
        document.getElementById('mapSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
        drawOsrmTrack1(lat, lng, nama, rank, jarak, vi, true);
    }

    function drawOsrmTrack1(lat, lng, nama, rank, jarak, vi, openPop) {
        if (activeHighlightLine1) spkMap1.removeLayer(activeHighlightLine1);
        if (activeHighlightMarker1) spkMap1.removeLayer(activeHighlightMarker1);

        // Fallback garis langsung sambil memuat rute jalan raya OSRM
        activeHighlightLine1 = L.polyline([[pnsHomeLat, pnsHomeLng], [lat, lng]], {
            color: '#2563eb', weight: 3, dashArray: '6, 6', opacity: 0.75
        }).addTo(spkMap1);

        activeHighlightMarker1 = L.marker([lat, lng], {
            icon: window.createGmapPinIcon({ color: '#1d4ed8', type: 'rank', label: '#' + rank, size: 'lg' })
        }).addTo(spkMap1).bindPopup(
            `<span style="font-weight:700;color:#1d4ed8;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Jarak Lurus: <strong>${jarak} km</strong> &bull; V<sub>i</sub>: ${vi}<br/><span style="color:#64748b;font-size:11px;">Melacak rute jalan raya OSRM...</span>`
        );

        if (openPop) {
            activeHighlightMarker1.openPopup();
            spkMap1.fitBounds([[pnsHomeLat, pnsHomeLng], [lat, lng]], { padding: [60, 60] });
        }

        fetch(`https://router.project-osrm.org/route/v1/driving/${pnsHomeLng},${pnsHomeLat};${lng},${lat}?overview=full&geometries=geojson`)
            .then(res => res.json())
            .then(data => {
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const km = (route.distance / 1000).toFixed(2);
                    const menit = Math.round(route.duration / 60);
                    const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);

                    if (activeHighlightLine1) spkMap1.removeLayer(activeHighlightLine1);
                    activeHighlightLine1 = L.polyline(coords, {
                        color: '#2563eb', weight: 4.5, opacity: 0.9
                    }).addTo(spkMap1);

                    activeHighlightMarker1.setPopupContent(
                        `<span style="font-weight:700;color:#1d4ed8;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Rute Jalan Riil (OSRM): <strong>${km} km</strong> (~${menit} mnt)<br/>Nilai V<sub>i</sub>: <strong>${vi}</strong>`
                    );
                    if (openPop) {
                        activeHighlightMarker1.openPopup();
                        spkMap1.fitBounds(activeHighlightLine1.getBounds(), { padding: [50, 50] });
                    }
                }
            })
            .catch(() => {
                activeHighlightMarker1.setPopupContent(
                    `<span style="font-weight:700;color:#1d4ed8;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Jarak Spasial: <strong>${jarak} km</strong> &bull; V<sub>i</sub>: ${vi}`
                );
            });
    }
</script>
@endpush
@endif
