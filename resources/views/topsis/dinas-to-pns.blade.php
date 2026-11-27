@extends('layouts.app')

@section('title', 'Rekomendasi Mutasi: Dinas ke PNS (Mode 2)')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 mb-2">
                <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Mode 2 &bull; 4 Kriteria TOPSIS (Dinas &rarr; PNS)
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rekomendasi Mutasi: Dinas ke PNS</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pilih suatu Dinas / Unit Kerja untuk menampilkan dan merangking Pegawai PNS pada dinas tersebut berdasarkan 4 kriteria TOPSIS (Jarak, Masa Jabatan, Masa Kerja, dan Kebutuhan Pegawai Unit Saat Ini).
            </p>
        </div>
        <div class="flex items-center gap-2">
            {{-- HIDE SEMENTARA BOBOT KRITERIA SESUAI INSTRUKSI KLIEN --}}
            @if(false)
            <a href="{{ route('master-kriteria.index', ['mode' => 'dinas_to_pns']) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="sliders" class="w-4 h-4 text-blue-600"></i> Bobot Kriteria ({{ $kriteriaModels->count() }})
            </a>
            @endif
            <a href="{{ route('topsis.pns-to-dinas') }}" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Beralih ke PNS ➔ Dinas
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
                    <select name="unit_id" required class="searchable-select w-full text-sm rounded-xl border-slate-300 focus:border-blue-600 focus:ring-blue-600 p-2.5 border bg-white shadow-2xs" placeholder="-- Cari Nama Dinas / Unit Kerja --">
                        <option value="">-- Pilih salah satu Unit Kerja / Instansi --</option>
                        @foreach ($unitList as $u)
                            <option value="{{ $u->id }}" {{ (string)$u->id === (string)request('unit_id') ? 'selected' : '' }}>
                                {{ $u->nama_unit }} [{{ $u->kategori }}] - Bezetting: {{ $u->bezetting_saat_ini }} / ABK: {{ $u->kebutuhan_abk }} {{ $u->kuota_formasi_kosong > 0 ? "({$u->kuota_formasi_kosong} Lowong)" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 shrink-0 h-[42px]">
                    <i data-lucide="map-pin" class="w-4 h-4"></i> Tampilkan Peta & Kandidat
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
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Bezetting vs ABK (Agregat Formasi)</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    Bezetting: <strong>{{ $unitTujuanEval['bezetting'] ?? 0 }}</strong> &bull; ABK: <strong>{{ $unitTujuanEval['abk'] ?? 0 }}</strong>
                </span>
                <span class="text-slate-500 text-[11px]">Lowong: <strong>{{ $unitTujuanEval['lowong'] ?? 0 }}</strong> ({{ $unitTujuanEval['persentase'] ?? 0 }}%)</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Status Kebutuhan &amp; Formasi Terbuka</span>
                @if($unitTujuanEval)
                <span class="inline-block px-2 py-0.5 mt-0.5 rounded border font-bold text-[10px] {{ $unitTujuanEval['badge_class'] }}">
                    {{ $unitTujuanEval['status'] }} ({{ $unitTujuanEval['persentase'] }}%)
                </span>
                @endif
                <span class="text-[11px] text-slate-500 block mt-1">Formasi Lowong Terbuka: <strong>{{ $openFormasiUnit->count() }} Jabatan</strong> ({{ $openFormasiUnit->sum('kuota_lowong') }} kursi)</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Titik Koordinat Kantor</span>
                <span class="font-mono text-slate-700 block mt-0.5">Lat: {{ number_format($selectedUnit->kantor_lat, 5) }}</span>
                <span class="font-mono text-slate-700 block">Lng: {{ number_format($selectedUnit->kantor_lng, 5) }}</span>
            </div>
        </div>

        @if($filterSummary)
        <div class="p-3 rounded-xl {{ $filterSummary['lolos_syarat_mutlak'] > 0 ? 'bg-indigo-50/70 border-indigo-200 text-indigo-900' : 'bg-amber-50 border-amber-200 text-amber-900' }} border text-xs flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4 shrink-0"></i>
                <span>
                    <strong>Pegawai PNS di {{ $selectedUnit->nama_unit }}:</strong>
                    Total <strong>{{ $filterSummary['total_pns_unit'] }} PNS</strong> &rarr;
                    <strong>{{ $filterSummary['lolos_syarat_mutlak'] }} PNS lolos</strong> syarat mutlak Masa Jabatan &ge; {{ $filterSummary['min_masa_jabatan_tahun'] }} Thn
                    (Belum memenuhi &lt; {{ $filterSummary['min_masa_jabatan_tahun'] }} Thn: {{ $filterSummary['gugur_masa_jabatan'] }} PNS).
                </span>
            </div>
        </div>
        @endif
        @endif
    </div>

    <!-- Panel 4 Kriteria Aktif Mode 2 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i data-lucide="check-square" class="w-4 h-4 text-indigo-600"></i> 4 Kriteria Penilaian SPK Dinas ➔ PNS (Total Bobot: {{ round($kriteriaModels->sum('bobot') * 100) }}%)
            </div>
            <a href="{{ route('master-kriteria.index', ['mode' => 'dinas_to_pns']) }}" class="text-xs text-blue-700 hover:underline font-semibold flex items-center gap-1">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Atur Kriteria &amp; Subkriteria
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
    <!-- Peta Spasial Rekomendasi Dinas -> PNS (Show Map) -->
    <div id="mapSectionDinas" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="map" class="w-4 h-4 text-indigo-700"></i>
                <h3 class="font-bold text-slate-900 text-sm">Peta Sebaran Kandidat PNS — {{ $selectedUnit->nama_unit }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-700 inline-block"></span> Lokasi Dinas Tujuan</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-600 inline-block"></span> Top 10 Domisili Kandidat PNS</span>
            </div>
        </div>
        <div id="spkMapDinasToPns" style="height: 400px;" class="w-full relative z-0"></div>
    </div>

    <!-- Tabel Hasil Ranking DataTables 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i> Pemeringkatan Pegawai PNS pada {{ $selectedUnit->nama_unit }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pegawai PNS pada unit ini diurutkan menggunakan 4 kriteria TOPSIS: Jarak (C1), Masa Jabatan (C2), Masa Kerja (C3), dan Kebutuhan Pegawai Unit Saat Ini (C4).
                </p>
            </div>
            <div class="text-xs text-slate-500">
                Total PNS Lolos Syarat: <strong>{{ count($ranked) }}</strong> Pegawai
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12 no-sort">Rank</th>
                        <th class="px-4 py-3.5">Pegawai PNS</th>
                        <th class="px-4 py-3.5">Jabatan &amp; Kelas</th>
                        <th class="px-4 py-3.5 text-right">C1: Jarak</th>
                        <th class="px-4 py-3.5 text-center">C2: Masa Jabatan</th>
                        <th class="px-4 py-3.5 text-center">C3: Masa Kerja</th>
                        <th class="px-4 py-3.5 text-center">C4: Unit Saat Ini</th>
                        <th class="px-4 py-3.5 text-center font-mono font-bold text-indigo-900">Nilai V<sub>i</sub></th>
                        <th class="px-4 py-3.5 text-center no-sort">Aksi</th>
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
                                    {{ $item['asn']->nama ?? 'Pegawai PNS' }}
                                </div>
                                <div class="text-xs text-slate-400 font-mono">
                                    NIP. {{ $item['asn']->nip ?? '-' }} &bull; Gol. {{ $item['asn']->pangkat_golongan ?? '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-slate-800 text-xs">{{ $item['asn']->jabatan ?? '-' }}</div>
                                <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ ($item['asn']->jenis_jabatan ?? '') === 'fungsional' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $item['asn']->jenis_jabatan ?? 'Pelaksana' }}
                                    </span>
                                    @if ($item['asn']->masterJabatan?->kelas_jabatan)
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Kls {{ $item['asn']->masterJabatan->kelas_jabatan }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800 text-xs whitespace-nowrap">
                                {{ number_format($item['jarak_ke_dinas_km'], 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ number_format($item['masa_jabatan_tahun'], 1) }} Thn</div>
                                <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    &ge; {{ $item['min_masa_jabatan_tahun'] ?? 2 }} Thn
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ number_format($item['masa_kerja_tahun'], 1) }} Thn</div>
                                <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold {{ !empty($item['masa_kerja_keluar_ok']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ !empty($item['masa_kerja_keluar_ok']) ? 'Bisa Keluar (>'.$item['min_masa_kerja_keluar_tahun'].' Th)' : 'Dalam Daerah (<'.$item['min_masa_kerja_keluar_tahun'].' Th)' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs">
                                @php $ue = $item['unit_asal_eval'] ?? null; @endphp
                                @if ($ue)
                                    <span class="px-2 py-0.5 rounded-full font-bold border text-[10px] {{ $ue['badge_class'] }}">
                                        {{ $ue['status'] }} ({{ $ue['persentase'] }}%)
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Bez {{ $ue['bezetting'] }} / ABK {{ $ue['abk'] }} ({{ $ue['lowong'] }})
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-xs text-indigo-900">
                                {{ number_format($item['vi'], 4) }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                        onclick="focusPnsOnMap({{ $item['asn']->domisili_lat ?? 0 }}, {{ $item['asn']->domisili_lng ?? 0 }}, {{ json_encode($item['asn']->nama ?? 'Pegawai PNS') }}, {{ $item['rank'] }}, '{{ number_format($item['jarak_ke_dinas_km'], 2) }}', '{{ number_format($item['vi'], 4) }}')"
                                        class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 shadow-2xs"
                                        title="Tampilkan lokasi domisili PNS ini pada peta">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Peta
                                    </button>
                                    <a href="{{ route('topsis.pns-to-dinas', ['asn_id' => $item['id']]) }}"
                                        class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-700 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-700 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 shadow-2xs"
                                        title="Cari rekomendasi dinas tujuan untuk PNS ini (Mode 1)">
                                        <i data-lucide="compass" class="w-3.5 h-3.5"></i> Cari Dinas
                                    </a>
                                </div>

                                {{-- HIDE SEMENTARA TARIK PEGAWAI / BAPERJAKAT SESUAI INSTRUKSI KLIEN --}}
                                @if(false)
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
                                @endif
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

@if ($selectedUnit && count($ranked) > 0)
@push('scripts')
<script>
    let spkMap2, activeHighlightLine2, activeHighlightMarker2;
    const dinasLat = {{ $selectedUnit->kantor_lat ?? -6.32635 }};
    const dinasLng = {{ $selectedUnit->kantor_lng ?? 108.32042 }};

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('spkMapDinasToPns')) return;

        spkMap2 = L.map('spkMapDinasToPns').setView([dinasLat, dinasLng], 12);
        window.attachGmapBasemap(spkMap2);

        const bounds = [[dinasLat, dinasLng]];

        // 1. Marker Dinas Tujuan (Pin Google Maps Biru Gedung)
        L.marker([dinasLat, dinasLng], {
            icon: window.createGmapPinIcon({ color: '#1d4ed8', type: 'office', size: 'lg' })
        }).addTo(spkMap2).bindPopup(`<strong>Lokasi Dinas Tujuan</strong><br/>{{ addslashes($selectedUnit->nama_unit) }}`).openPopup();

        // 2. Top 10 Kandidat PNS (Emerald)
        @php
            $topPnsMap = collect($ranked)->take(10)->map(function($item) {
                return [
                    'rank' => $item['rank'],
                    'nama' => $item['asn']->nama ?? 'Pegawai PNS',
                    'lat' => (float) ($item['asn']->domisili_lat ?? 0),
                    'lng' => (float) ($item['asn']->domisili_lng ?? 0),
                    'jarak' => number_format($item['jarak_ke_dinas_km'], 2),
                    'vi' => number_format($item['vi'], 4),
                ];
            })->values();
        @endphp
        const topPns = @json($topPnsMap);

        topPns.forEach(p => {
            if (!p.lat || !p.lng) return;
            L.marker([p.lat, p.lng], {
                icon: window.createGmapPinIcon({
                    color: p.rank === 1 ? '#059669' : '#10b981',
                    type: 'rank',
                    label: '#' + p.rank,
                    size: p.rank === 1 ? 'lg' : 'md'
                })
            }).addTo(spkMap2).bindPopup(
                `<span style="font-weight:700;color:#059669;">Rank #${p.rank}</span><br/><strong>${p.nama}</strong><br/>Jarak ke Dinas: <strong>${p.jarak} km</strong> &bull; V<sub>i</sub>: ${p.vi}`
            );
            if (p.rank <= 3) {
                L.polyline([[dinasLat, dinasLng], [p.lat, p.lng]], {
                    color: '#10b981', weight: p.rank === 1 ? 3 : 1.5, dashArray: p.rank === 1 ? null : '4, 5', opacity: 0.75
                }).addTo(spkMap2);
                bounds.push([p.lat, p.lng]);
            }
        });

        if (bounds.length > 1) {
            spkMap2.fitBounds(bounds, { padding: [40, 40] });
        }

        // Auto-track rute jalan raya OSRM untuk Rank #1 saat peta dimuat
        if (topPns.length > 0 && topPns[0].lat && topPns[0].lng) {
            const first = topPns[0];
            drawOsrmTrack2(first.lat, first.lng, first.nama, first.rank, first.jarak, first.vi, false);
        }
    });

    function focusPnsOnMap(lat, lng, nama, rank, jarak, vi) {
        if (!spkMap2 || !lat || !lng) return;
        document.getElementById('mapSectionDinas').scrollIntoView({ behavior: 'smooth', block: 'center' });
        drawOsrmTrack2(lat, lng, nama, rank, jarak, vi, true);
    }

    function drawOsrmTrack2(lat, lng, nama, rank, jarak, vi, openPop) {
        if (activeHighlightLine2) spkMap2.removeLayer(activeHighlightLine2);
        if (activeHighlightMarker2) spkMap2.removeLayer(activeHighlightMarker2);

        // Fallback garis langsung sambil memuat rute jalan raya OSRM
        activeHighlightLine2 = L.polyline([[dinasLat, dinasLng], [lat, lng]], {
            color: '#4338ca', weight: 3, dashArray: '6, 6', opacity: 0.75
        }).addTo(spkMap2);

        activeHighlightMarker2 = L.marker([lat, lng], {
            icon: window.createGmapPinIcon({ color: '#ea4335', type: 'rank', label: '#' + rank, size: 'lg' })
        }).addTo(spkMap2).bindPopup(
            `<span style="font-weight:700;color:#4338ca;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Jarak Lurus: <strong>${jarak} km</strong> &bull; V<sub>i</sub>: ${vi}<br/><span style="color:#64748b;font-size:11px;">Melacak rute jalan raya OSRM...</span>`
        );

        if (openPop) {
            activeHighlightMarker2.openPopup();
            spkMap2.fitBounds([[dinasLat, dinasLng], [lat, lng]], { padding: [60, 60] });
        }

        fetch(`https://router.project-osrm.org/route/v1/driving/${lng},${lat};${dinasLng},${dinasLat}?overview=full&geometries=geojson`)
            .then(res => res.json())
            .then(data => {
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const km = (route.distance / 1000).toFixed(2);
                    const menit = Math.round(route.duration / 60);
                    const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);

                    if (activeHighlightLine2) spkMap2.removeLayer(activeHighlightLine2);
                    activeHighlightLine2 = L.polyline(coords, {
                        color: '#4338ca', weight: 4.5, opacity: 0.9
                    }).addTo(spkMap2);

                    activeHighlightMarker2.setPopupContent(
                        `<span style="font-weight:700;color:#4338ca;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Rute Jalan Riil (OSRM): <strong>${km} km</strong> (~${menit} mnt)<br/>Nilai V<sub>i</sub>: <strong>${vi}</strong>`
                    );
                    if (openPop) {
                        activeHighlightMarker2.openPopup();
                        spkMap2.fitBounds(activeHighlightLine2.getBounds(), { padding: [50, 50] });
                    }
                }
            })
            .catch(() => {
                activeHighlightMarker2.setPopupContent(
                    `<span style="font-weight:700;color:#4338ca;">Rank #${rank} (Terpilih)</span><br/><strong>${nama}</strong><br/>Jarak Spasial: <strong>${jarak} km</strong> &bull; V<sub>i</sub>: ${vi}`
                );
            });
    }
</script>
@endpush
@endif
