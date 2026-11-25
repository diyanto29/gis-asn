@extends('layouts.app')

@section('title', 'Simulasi Rekomendasi Penempatan Mandiri (Tahap 2)')

@section('content')
<div class="space-y-6">

    <!-- Header Profil Pegawai & Status Kelayakan BKN -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-700 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
                    {{ strtoupper(substr($asn->nama, 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900">{{ $asn->nama_lengkap }}</h2>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            NIP: {{ $asn->nip }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusKomitmen['badge_color'] }} border">
                            {{ $statusKomitmen['status_label'] }}
                        </span>
                        @if($asn->status_permohonan_mutasi)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-300 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            Sudah Ada Usulan Mutasi
                        </span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-y-1 gap-x-4">
                        <span><strong>Jabatan:</strong> {{ $asn->jabatan }} ({{ $asn->pangkat_golongan }})</span>
                        <span><strong>Kantor Saat Ini:</strong> {{ $asn->unitSaatIni->nama_unit ?? '-' }}</span>
                        <span><strong>Jarak Lama:</strong> <span class="text-rose-600 font-bold">{{ $asn->jarak_ke_kantor_km }} km</span> ({{ ucfirst($asn->zona_jarak) }})</span>
                    </div>
                </div>
            </div>

            <!-- Jika Admin yang login, bisa beralih simulasi antar-ASN -->
            @if(Auth::user()->isAdmin() && $asnList->count() > 1)
            <div class="flex items-center gap-2 self-start lg:self-center shrink-0">
                <form action="{{ route('asn.simulasi-mandiri') }}" method="GET" class="flex items-center gap-2">
                    <label for="asn_id" class="text-xs font-semibold text-slate-600 whitespace-nowrap">Pilih PNS:</label>
                    <select name="asn_id" id="asn_id" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-xl px-3 py-1.5 bg-slate-50 font-medium focus:ring-2 focus:ring-blue-600">
                        @foreach($asnList as $p)
                            <option value="{{ $p->id }}" {{ $p->id == $asn->id ? 'selected' : '' }}>
                                {{ $p->nama }} ({{ $p->nip }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif

        </div>

        @if($statusKomitmen['is_bound'] && $statusKomitmen['is_locked_external'])
        <div class="mt-4 p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2.5">
            <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold">Ketentuan Komitmen Ikatan Pengabdian BKN:</span>
                <span>{{ $statusKomitmen['notice'] }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Layout Split View: Peta GIS & Tabel Rekomendasi TOPSIS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Peta GIS Rute Riil OSRM (7 Kolom di Desktop) -->
        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                        <i data-lucide="map" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Peta Spasial Rute OSRM ke Unit Berformasi</h3>
                        <p class="text-[11px] text-slate-400">Jalur fisik jalan raya dari domisili ke unit rekomendasi teratas</p>
                    </div>
                </div>
                <div id="routeStatus" class="text-[11px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                    Peta Aktif
                </div>
            </div>

            <div id="simulasiMap" class="w-full h-[460px] bg-slate-100 z-10"></div>

            <!-- Legenda Peta -->
            <div class="p-3 bg-white border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-600">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-blue-600 inline-block border border-white shadow-xs"></span>
                        Rumah Domisili PNS
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-slate-400 inline-block"></span>
                        Kantor Lama ({{ $asn->jarak_ke_kantor_km }} km)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-600 inline-block"></span>
                        Kantor Rekomendasi (ABK > 0)
                    </span>
                </div>
                <div class="text-slate-400 font-mono text-[10px]">
                    OSRM Driving Route Engine
                </div>
            </div>
        </div>

        <!-- Tabel Perangkingan TOPSIS Unit Kerja (5 Kolom di Desktop) -->
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Rekomendasi Penempatan Optimal</h3>
                    <p class="text-[11px] text-slate-400">Diurutkan berdasarkan nilai preferensi TOPSIS (Vi)</p>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ count($ranked) }} Unit Berformasi
                </span>
            </div>

            <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                @forelse($ranked as $item)
                    @php
                        $unit = $item['alternative'];
                        $isTop = ($item['rank'] === 1);
                    @endphp
                    <div class="p-4 hover:bg-slate-50/80 transition {{ $isTop ? 'bg-blue-50/30' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 {{ $isTop ? 'bg-amber-500 text-white shadow-xs shadow-amber-500/30' : 'bg-slate-200 text-slate-700' }}">
                                    #{{ $item['rank'] }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm leading-tight">
                                        {{ $unit['nama'] }}
                                    </h4>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $unit['kategori'] }} &bull; Sisa Kuota: <span class="font-bold text-emerald-700">{{ $unit['kuota'] }} Formasi</span>
                                    </div>
                                    @if (!empty($unit['formasi_tersedia']))
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach ($unit['formasi_tersedia'] as $fNama)
                                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-semibold rounded border border-blue-100 flex items-center gap-1">
                                                    <i data-lucide="briefcase" class="w-2.5 h-2.5 text-blue-500"></i> {{ $fNama }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-extrabold text-blue-700 font-mono">
                                    Vi = {{ $item['v_value'] }}
                                </div>
                                <div class="text-[10px] text-slate-400">Skor TOPSIS</div>
                            </div>
                        </div>

                        <!-- Metrik Jarak & Penghematan -->
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="text-[10px] text-slate-400">Jarak Baru</div>
                                <div class="font-bold text-slate-800">{{ $unit['c1_jarak'] }} km</div>
                            </div>
                            <div class="p-2 bg-emerald-50/60 rounded-lg border border-emerald-100">
                                <div class="text-[10px] text-emerald-600">Efisiensi Jarak</div>
                                <div class="font-bold text-emerald-700">+{{ $unit['c3_hemat'] }} km hemat</div>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="mt-3 flex items-center justify-end gap-2">
                            <button type="button" 
                                onclick="focusRoute({{ $unit['kantor_lat'] }}, {{ $unit['kantor_lng'] }}, '{{ addslashes($unit['nama']) }}', {{ $unit['c1_jarak'] }})"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium transition flex items-center gap-1.5">
                                <i data-lucide="navigation" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Lihat Rute</span>
                            </button>

                            <button type="button"
                                onclick="openModalAjukan({{ $unit['id'] }}, '{{ addslashes($unit['nama']) }}', {{ $item['rank'] }}, {{ $item['v_value'] }}, {{ $unit['c1_jarak'] }}, {{ $unit['c3_hemat'] }}, {{ $unit['kuota'] }})"
                                class="px-3 py-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Ajukan Mutasi</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs">
                        <i data-lucide="alert-circle" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        {{ $message ?? 'Tidak ditemukan alternatif unit kerja berformasi.' }}
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Modal Dialog Pengajuan Permohonan Mutasi Mandiri -->
<div id="modalAjukan" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 transition-all transform animate-modal-pop">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-900 text-sm sm:text-base flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-blue-700"></i>
                Konfirmasi Permohonan Mutasi
            </h3>
            <button onclick="closeModalAjukan()" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="{{ route('asn.simulasi-mandiri.ajukan') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="asn_id" value="{{ $asn->id }}">
            <input type="hidden" name="unit_tujuan_id" id="modalUnitId">
            <input type="hidden" name="nilai_preferensi_v" id="modalVi">
            <input type="hidden" name="ranking" id="modalRank">

            <!-- Detail Unit Tujuan & Metrik -->
            <div class="p-3.5 bg-gradient-to-br from-blue-50/80 to-indigo-50/60 rounded-xl border border-blue-200/80 text-xs">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-slate-500 text-[11px]">Unit Kerja Tujuan:</div>
                        <div class="font-extrabold text-blue-900 text-sm mt-0.5" id="modalUnitNama">-</div>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-700 text-white shadow-2xs" id="modalRankBadge">Rank #1</span>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5" id="modalViText">Vi = 0.000</div>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-blue-100 flex items-center justify-between text-[11px] text-slate-600">
                    <span>Jarak Baru: <strong id="modalJarakBaruText">-</strong></span>
                    <span>Efisiensi: <strong class="text-emerald-700 font-bold" id="modalHematText">-</strong></span>
                    <span>Formasi: <strong id="modalKuotaText">-</strong></span>
                </div>
            </div>

            <!-- AI Smart Reason Assistant Header -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="alasan_mutasi" class="block text-xs font-semibold text-slate-700">
                        Alasan Permohonan Mutasi <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" onclick="generateAiDraft('jarak')" 
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-[11px] font-bold shadow-xs transition group">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 group-hover:rotate-12 transition-transform"></i>
                        <span>✨ Tulis Otomatis via AI</span>
                    </button>
                </div>

                <!-- AI Context Preset Pills -->
                <div class="mb-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="text-slate-400 text-[10px] font-semibold">Pilih Gaya Draf:</span>
                    <button type="button" onclick="generateAiDraft('jarak')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 border border-slate-200 text-slate-600 transition flex items-center gap-1">
                        🚗 Efisiensi Jarak
                    </button>
                    <button type="button" onclick="generateAiDraft('keluarga')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 border border-slate-200 text-slate-600 transition flex items-center gap-1">
                        👨‍👩‍👧 Domisili Keluarga
                    </button>
                    <button type="button" onclick="generateAiDraft('formasi')" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200 border border-slate-200 text-slate-600 transition flex items-center gap-1">
                        📋 Formasi ABK
                    </button>
                </div>

                <div class="relative">
                    <textarea name="alasan_mutasi" id="alasan_mutasi" rows="4" required
                        placeholder="Ketik alasan permohonan mutasi Anda di sini atau klik '✨ Tulis Otomatis via AI' untuk menyusun kalimat resmi berbasis data rute..."
                        class="w-full text-xs p-3 bg-slate-50/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition leading-relaxed"></textarea>
                </div>
                
                <div id="aiFeedbackBadge" class="hidden mt-1.5 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                    <span>Draf alasan resmi berhasil disusun oleh AI berdasarkan data rute & formasi! Anda bebas mengedit kalimatnya.</span>
                </div>
                
                <p class="text-[11px] text-slate-400 mt-1">
                    Alasan ini akan dilampirkan langsung ke lembar berkas pertimbangan sidang Baperjakat BKPSDM.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModalAjukan()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-700 hover:bg-blue-800 rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Kirim ke Baperjakat</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let map;
    let routePolyline = null;

    const homeLat = {{ $asn->domisili_lat ?? -6.326350 }};
    const homeLng = {{ $asn->domisili_lng ?? 108.324200 }};
    const officeLat = {{ $asn->unitSaatIni->kantor_lat ?? -6.326350 }};
    const officeLng = {{ $asn->unitSaatIni->kantor_lng ?? 108.324200 }};

    const alternatives = @json($ranked);

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        if (alternatives.length > 0) {
            const topUnit = alternatives[0].alternative;
            focusRoute(topUnit.kantor_lat, topUnit.kantor_lng, topUnit.nama, topUnit.c1_jarak);
        }
    });

    function initMap() {
        map = L.map('simulasiMap').setView([homeLat, homeLng], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Marker Domisili ASN (Rumah)
        const homeIcon = L.divIcon({
            className: 'custom-home-marker',
            html: `
                <div class="relative w-8 h-8 rounded-full bg-blue-700 text-white flex items-center justify-center shadow-md border-2 border-white">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        L.marker([homeLat, homeLng], { icon: homeIcon })
            .addTo(map)
            .bindPopup(`<strong>Rumah Domisili:</strong><br>{{ addslashes($asn->nama) }}`);

        // Marker Kantor Saat Ini
        const oldOfficeIcon = L.divIcon({
            className: 'custom-office-marker',
            html: `
                <div class="relative w-7 h-7 rounded-full bg-slate-500 text-white flex items-center justify-center shadow-md border-2 border-white">
                    <i data-lucide="building" class="w-3.5 h-3.5"></i>
                </div>
            `,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        L.marker([officeLat, officeLng], { icon: oldOfficeIcon })
            .addTo(map)
            .bindPopup(`<strong>Kantor Lama:</strong><br>{{ addslashes($asn->unitSaatIni->nama_unit ?? '') }}<br>Jarak: {{ $asn->jarak_ke_kantor_km }} km`);

        // Marker Unit-Unit Rekomendasi
        alternatives.forEach(item => {
            const u = item.alternative;
            const isRank1 = (item.rank === 1);
            const colorClass = isRank1 ? 'bg-emerald-600' : 'bg-slate-700';

            const unitIcon = L.divIcon({
                className: 'custom-unit-marker',
                html: `
                    <div class="relative w-7 h-7 rounded-full ${colorClass} text-white flex items-center justify-center shadow-md border-2 border-white font-extrabold text-[11px]">
                        #${item.rank}
                    </div>
                `,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            L.marker([u.kantor_lat, u.kantor_lng], { icon: unitIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="text-xs">
                        <strong>#${item.rank} ${u.nama}</strong><br>
                        Kuota Lowong: ${u.kuota} Formasi<br>
                        Jarak Baru: ${u.c1_jarak} km<br>
                        Efisiensi: +${u.c3_hemat} km hemat<br>
                        <strong>Nilai Vi: ${item.v_value}</strong>
                    </div>
                `);
        });

        lucide.createIcons();
    }

    // Menggambar Garis Rute Riil OSRM dari Rumah ke Kantor Rekomendasi
    function focusRoute(destLat, destLng, destName, distanceKm) {
        document.getElementById('routeStatus').innerHTML = `🚗 Menghitung rute ke ${destName}...`;

        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${homeLng},${homeLat};${destLng},${destLat}?overview=full&geometries=geojson`;

        fetch(osrmUrl)
            .then(res => res.json())
            .then(data => {
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const realDistanceKm = (route.distance / 1000).toFixed(1);
                    const durationMins = Math.round(route.duration / 60);

                    if (routePolyline) {
                        map.removeLayer(routePolyline);
                    }

                    routePolyline = L.geoJSON(route.geometry, {
                        style: {
                            color: '#2563eb',
                            weight: 5,
                            opacity: 0.85
                        }
                    }).addTo(map);

                    map.fitBounds(routePolyline.getBounds(), { padding: [40, 40] });

                    document.getElementById('routeStatus').innerHTML = `
                        🚗 <strong>${realDistanceKm} km</strong> (${durationMins} mnt via jalan raya)
                    `;
                } else {
                    fallbackLine(destLat, destLng, distanceKm);
                }
            })
            .catch(() => {
                fallbackLine(destLat, destLng, distanceKm);
            });
    }

    function fallbackLine(destLat, destLng, distanceKm) {
        if (routePolyline) {
            map.removeLayer(routePolyline);
        }
        routePolyline = L.polyline([[homeLat, homeLng], [destLat, destLng]], {
            color: '#059669',
            dashArray: '6, 8',
            weight: 3
        }).addTo(map);

        map.fitBounds(routePolyline.getBounds(), { padding: [30, 30] });
        document.getElementById('routeStatus').innerHTML = `📏 Jarak: ${distanceKm} km`;
    }

    let currentModalContext = {
        unitId: null,
        unitNama: '',
        rank: 1,
        vi: 0.0,
        jarakBaru: 0.0,
        hematJarak: 0.0,
        kuota: 0
    };

    const asnContext = {
        nama: "{{ addslashes($asn->nama) }}",
        jabatan: "{{ addslashes($asn->jabatan) }}",
        kantorAsal: "{{ addslashes($asn->unitSaatIni->nama_unit ?? 'Unit Kerja Asal') }}",
        jarakLama: {{ $asn->jarak_ke_kantor_km ?? 0 }},
        domisiliKecamatan: "{{ addslashes($asn->kecamatan ?? 'Indramayu') }}",
        domisiliDesa: "{{ addslashes($asn->kelurahan_desa ?? '') }}"
    };

    function openModalAjukan(unitId, unitNama, rank, vi, jarakBaru, hematJarak, kuota) {
        currentModalContext = { unitId, unitNama, rank, vi, jarakBaru, hematJarak, kuota };

        document.getElementById('modalUnitId').value = unitId;
        document.getElementById('modalUnitNama').innerText = unitNama;
        document.getElementById('modalRank').value = rank;
        document.getElementById('modalRankBadge').innerText = 'Rank #' + rank;
        document.getElementById('modalVi').value = vi;
        document.getElementById('modalViText').innerText = 'Vi = ' + Number(vi).toFixed(4);
        document.getElementById('modalJarakBaruText').innerText = jarakBaru + ' km';
        document.getElementById('modalHematText').innerText = '+' + hematJarak + ' km hemat';
        document.getElementById('modalKuotaText').innerText = kuota + ' Formasi';

        document.getElementById('aiFeedbackBadge').classList.add('hidden');
        document.getElementById('modalAjukan').classList.remove('hidden');
        lucide.createIcons();
    }

    function closeModalAjukan() {
        document.getElementById('modalAjukan').classList.add('hidden');
    }

    // Generator Draf Alasan Mutasi Kontekstual Berbasis AI & Data Riil
    function generateAiDraft(style) {
        const ctx = currentModalContext;
        let draft = "";

        if (style === 'keluarga') {
            draft = `Permohonan mutasi diajukan untuk mendekatkan penugasan kedinasan dengan domisili tempat tinggal keluarga di wilayah ${asnContext.domisiliDesa ? asnContext.domisiliDesa + ', ' : ''}Kecamatan ${asnContext.domisiliKecamatan}. Dengan rute tempuh baru sejauh ${ctx.jarakBaru} km menuju ${ctx.unitNama} (memangkas perjalanan sejauh ${ctx.hematJarak} km dari kantor lama), saya dapat lebih optimal menjaga keseimbangan keluarga sekaligus menjalankan tanggung jawab kedinasan sebagai ${asnContext.jabatan} secara berdedikasi tinggi.`;
        } else if (style === 'formasi') {
            draft = `Permohonan mutasi diajukan guna mengisi kebutuhan formasi lowong Analisis Beban Kerja (ABK) yang tersedia sebanyak ${ctx.kuota} formasi di ${ctx.unitNama}. Berdasarkan hasil pemeringkatan sistem SPK, rekomendasi penempatan ini menempati Peringkat #${ctx.rank} (Nilai Preferensi Vi = ${ctx.vi}) yang sangat selaras dengan kualifikasi dan pengalaman jabatan saya sebagai ${asnContext.jabatan}, serta memberikan efisiensi jarak tempuh sebesar ${ctx.hematJarak} km.`;
        } else { // default jarak & produktivitas
            draft = `Permohonan mutasi diajukan atas dasar pertimbangan efisiensi jarak geografis domisili dan peningkatan produktivitas kerja harian. Saat ini perjalanan dinas saya menuju ${asnContext.kantorAsal} berjarak ${asnContext.jarakLama} km. Penempatan ke ${ctx.unitNama} (${ctx.jarakBaru} km) berhasil memangkas jarak perjalanan sejauh ${ctx.hematJarak} km harian, sehingga secara nyata dapat mereduksi kelelahan fisik perjalanan, menjamin ketepatan waktu kehadiran, serta mengoptimalkan kinerja pelayanan kedinasan sebagai ${asnContext.jabatan}.`;
        }

        const textarea = document.getElementById('alasan_mutasi');
        textarea.value = draft;
        
        // Highlight feedback visual
        textarea.classList.add('ring-2', 'ring-blue-600', 'bg-blue-50/40');
        setTimeout(() => {
            textarea.classList.remove('ring-2', 'ring-blue-600', 'bg-blue-50/40');
        }, 800);

        const feedback = document.getElementById('aiFeedbackBadge');
        feedback.classList.remove('hidden');
        lucide.createIcons();
    }
</script>
@endpush
@endsection

