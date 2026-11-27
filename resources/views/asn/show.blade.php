@extends('layouts.app')

@section('title', 'Profil Pegawai: ' . $asn->nama_lengkap)

@section('content')
<div class="space-y-6">

    <!-- Back Button & Title -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('asn.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $asn->nama_lengkap }}</h1>
                <p class="text-xs text-slate-500 font-mono mt-0.5">NIP: {{ $asn->nip }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('asn.edit', $asn->id) }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Profil
                </a>
            @endif
            <a href="{{ route('asn.edit-lokasi', $asn->id) }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Perbarui Titik Domisili Peta
            </a>
        </div>
    </div>

    <!-- 2-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1: Info Kepegawaian & Spasial Card -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Profil Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($asn->nama, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-base leading-tight">{{ $asn->nama_lengkap }}</div>
                        <div class="text-xs text-slate-600 mt-0.5">{{ $asn->jabatan }}</div>
                        <div class="flex flex-wrap items-center gap-1 mt-1.5">
                            @if ($asn->jenis_jabatan === 'fungsional')
                                <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-semibold rounded">Fungsional</span>
                            @elseif ($asn->jenis_jabatan === 'pelaksana')
                                <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold rounded">Pelaksana</span>
                            @else
                                <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-semibold rounded">Struktural</span>
                            @endif
                            @if ($asn->masterJabatan?->jenjang_jabatan)
                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-medium rounded">{{ $asn->masterJabatan->jenjang_jabatan }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs text-slate-600">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Pangkat / Golongan:</span>
                        <span class="font-bold text-slate-800">{{ $asn->pangkat_golongan }}{{ $asn->tmt_golongan ? ' (TMT ' . $asn->tmt_golongan->format('d/m/Y') . ')' : '' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">TMT Jabatan / CPNS:</span>
                        <span class="font-mono text-slate-800">{{ $asn->tgl_jabatan ? $asn->tgl_jabatan->format('d/m/Y') : '-' }} &bull; {{ $asn->tgl_cpns ? $asn->tgl_cpns->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Masa Kerja:</span>
                        <span class="font-bold text-slate-800">{{ $asn->masa_kerja_tahun }} Tahun</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Pendidikan Terakhir:</span>
                        <span class="font-medium text-slate-800 text-right">{{ $asn->pendidikan_terakhir ?? $asn->tingkat_pendidikan ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Unit Kerja Saat Ini:</span>
                        <span class="font-bold text-slate-900 text-right">{{ $asn->unitSaatIni->nama_unit ?? '-' }}</span>
                    </div>
                    @if ($asn->formasiJabatan)
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-400">Formasi ABK ({{ $asn->formasiJabatan->tahun_anggaran }}):</span>
                            <span class="font-mono font-semibold text-teal-700 text-right">
                                ABK {{ $asn->formasiJabatan->kebutuhan_abk }} / Bez {{ $asn->formasiJabatan->bezetting_riil }} (Lowong {{ $asn->formasiJabatan->kuota_lowong }})
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card Analisis Regulasi BKN & Kepatuhan Spasial -->
            @php
                $komitmen = $asn->status_komitmen_10_tahun;
                $spasial = $asn->status_spasial_regional;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-indigo-600"></i>
                        <h3 class="font-bold text-slate-900 text-sm">Status Regulasi Kepegawaian (BKN)</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $komitmen['badge_color'] }}">
                        {{ $komitmen['pill_short'] }}
                    </span>
                </div>

                <!-- 1. Aturan Komitmen 10 Tahun (PermenPAN-RB 27/2021) -->
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Tahun Pengangkatan CPNS:</span>
                        <span class="font-bold text-slate-800 font-mono">{{ $komitmen['tahun_cpns'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Masa Pengabdian Riil:</span>
                        <span class="font-bold text-slate-800">{{ $komitmen['masa_pengabdian'] }} Tahun</span>
                    </div>

                    <!-- Hak Mutasi Indicators -->
                    <div class="pt-2 grid grid-cols-2 gap-2 text-[11px]">
                        <div class="p-2.5 rounded-lg border {{ $komitmen['hak_rotasi_internal'] ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                            <div class="font-bold flex items-center gap-1">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i> Rotasi Internal
                            </div>
                            <span class="text-[10px] text-slate-600 mt-0.5 block">Antar-SKPD Kabupaten XYZ Sah</span>
                        </div>
                        <div class="p-2.5 rounded-lg border {{ $komitmen['hak_mutasi_keluar'] ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800' : 'bg-amber-50/80 border-amber-200 text-amber-900' }}">
                            <div class="font-bold flex items-center gap-1">
                                @if ($komitmen['hak_mutasi_keluar'])
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i> Mutasi Keluar
                                @else
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-amber-700"></i> Mutasi Keluar
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-600 mt-0.5 block">
                                {{ $komitmen['hak_mutasi_keluar'] ? 'Memenuhi Syarat 5 Th' : 'Terkunci (Sisa ' . $komitmen['sisa_tahun'] . ' Th)' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px] text-slate-600 leading-relaxed">
                        <strong class="text-slate-800 block mb-0.5">Dasar Hukum: {{ $komitmen['dasar_hukum'] }}</strong>
                        {{ $komitmen['notice'] }}
                    </div>
                </div>

                <!-- 2. Analisis Spasial Regional (Cirebon / Riau / Indramayu) -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-900 flex items-center gap-1">
                            <i data-lucide="map" class="w-3.5 h-3.5 text-blue-600"></i> Status Wilayah Domisili:
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $spasial['badge_color'] }}">
                            {{ $spasial['pill_short'] }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl border text-[11px] leading-relaxed {{ $spasial['is_alert'] ? 'bg-rose-50/70 border-rose-200 text-rose-900' : ($spasial['type'] === 'perbatasan_komuter' ? 'bg-purple-50/70 border-purple-200 text-purple-900' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900') }}">
                        <strong class="block font-bold mb-1">{{ $spasial['notice_title'] }}</strong>
                        {{ $spasial['notice'] }}
                    </div>
                </div>
            </div>

            <!-- Jarak Spasial Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-blue-600"></i>
                    Beban Perjalanan Harian
                </h3>

                <div class="p-4 rounded-lg {{ $asn->zona_jarak === 'jauh' ? 'bg-rose-50 border border-rose-200' : ($asn->zona_jarak === 'sedang' ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200') }} text-center">
                    <div id="distanceLabel" class="text-xs uppercase tracking-wider font-semibold {{ $asn->zona_jarak === 'jauh' ? 'text-rose-700' : ($asn->zona_jarak === 'sedang' ? 'text-amber-700' : 'text-emerald-700') }}">
                        Jarak Tempuh Rute Jalan (OSRM)
                    </div>
                    <div id="distanceValue" class="text-3xl font-extrabold my-1 {{ $asn->zona_jarak === 'jauh' ? 'text-rose-600' : ($asn->zona_jarak === 'sedang' ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ number_format($asn->jarak_ke_kantor_km, 2) }} <span class="text-base font-normal">km</span>
                    </div>
                    <div id="distanceZoneMeta" class="text-xs font-medium {{ $asn->zona_jarak === 'jauh' ? 'text-rose-700' : ($asn->zona_jarak === 'sedang' ? 'text-amber-700' : 'text-emerald-700') }}">
                        Zona: {{ strtoupper($asn->zona_jarak ?? '-') }}
                    </div>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <div>Estimasi Perjalanan Pulang-Pergi: <strong id="distanceRoundTrip" class="text-slate-700">{{ number_format($asn->jarak_ke_kantor_km * 2, 2) }} km/hari</strong></div>
                    <div>Koordinat Rumah: <span class="font-mono text-slate-700">{{ $asn->domisili_lat }}, {{ $asn->domisili_lng }}</span></div>
                    <div>Alamat: <span class="text-slate-700">{{ $asn->alamat_detail ?? '-' }}{{ $asn->kelurahan_desa ? ', ' . $asn->kelurahan_desa : '' }}{{ $asn->kecamatan ? ', Kec. ' . $asn->kecamatan : '' }}</span></div>
                </div>

                @if ($asn->domisili_lat && $asn->domisili_lng && $asn->unitSaatIni)
                    <div class="pt-2">
                        <a href="https://www.google.com/maps/dir/?api=1&origin={{ $asn->domisili_lat }},{{ $asn->domisili_lng }}&destination={{ $asn->unitSaatIni->kantor_lat }},{{ $asn->unitSaatIni->kantor_lng }}&travelmode=driving" target="_blank" rel="noopener noreferrer" class="w-full py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition">
                            <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                            Buka Navigasi Rute di Google Maps ↗
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Column 2: Peta Detail Rute -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Peta Leaflet Personal -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                        <i data-lucide="map" class="w-4 h-4 text-blue-600"></i> Peta Posisi Domisili vs Unit Kerja
                    </span>
                    <span class="text-[11px] text-slate-400">Rute Jalan Raya OSRM</span>
                </div>
                <div id="personalMap" style="height: 560px;" class="w-full"></div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const homeLat = {{ $asn->domisili_lat ?? -6.326 }};
        const homeLng = {{ $asn->domisili_lng ?? 108.320 }};
        const officeLat = {{ $asn->unitSaatIni->kantor_lat ?? -6.328 }};
        const officeLng = {{ $asn->unitSaatIni->kantor_lng ?? 108.323 }};

        const map = L.map('personalMap');

        window.attachGmapBasemap(map);

        const homePinColor = '{{ $asn->zona_jarak === "jauh" ? "#e11d48" : ($asn->zona_jarak === "sedang" ? "#d97706" : "#059669") }}';

        // Marker Rumah (Google Maps Teardrop Pin)
        const homeIcon = window.createGmapPinIcon({ color: homePinColor, type: 'home', size: 'lg' });

        const homeMarker = L.marker([homeLat, homeLng], { icon: homeIcon })
            .addTo(map)
            .bindPopup("<strong>Rumah: {{ $asn->nama }}</strong><br/>{{ $asn->kelurahan_desa ? $asn->kelurahan_desa . ', ' : '' }}Kec. {{ $asn->kecamatan }}");

        // Marker Kantor (Google Maps Teardrop Pin Biru)
        const officeIcon = window.createGmapPinIcon({ color: '#1d4ed8', type: 'office', size: 'lg' });

        const officeMarker = L.marker([officeLat, officeLng], { icon: officeIcon })
            .addTo(map)
            .bindPopup("<strong>Kantor: {{ $asn->unitSaatIni->nama_unit ?? '' }}</strong>");

        // Rute Jalan Asli OSRM
        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${homeLng},${homeLat};${officeLng},${officeLat}?overview=full&geometries=geojson`;

        fetch(osrmUrl)
            .then(res => res.json())
            .then(data => {
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const roadDistanceKm = (route.distance / 1000).toFixed(2);
                    const durationMinutes = Math.max(1, Math.round(route.duration / 60));
                    const latLngs = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

                    // Sinkronkan kartu Beban Perjalanan Harian dengan jarak & durasi rute jalan OSRM
                    const distValEl = document.getElementById('distanceValue');
                    const distMetaEl = document.getElementById('distanceZoneMeta');
                    const distRtEl = document.getElementById('distanceRoundTrip');
                    if (distValEl) distValEl.innerHTML = `${roadDistanceKm} <span class="text-base font-normal">km</span>`;
                    if (distMetaEl) distMetaEl.textContent = `Zona: {{ strtoupper($asn->zona_jarak ?? '-') }} • ~${durationMinutes} menit`;
                    if (distRtEl) distRtEl.textContent = `${(parseFloat(roadDistanceKm) * 2).toFixed(2)} km/hari`;

                    const roadLine = L.polyline(latLngs, {
                        color: '{{ $asn->zona_jarak === "jauh" ? "#dc2626" : ($asn->zona_jarak === "sedang" ? "#d97706" : "#059669") }}',
                        weight: 4,
                        opacity: 0.85
                    }).addTo(map);

                    const gmapsDirectUrl = `https://www.google.com/maps/dir/?api=1&origin=${homeLat},${homeLng}&destination=${officeLat},${officeLng}&travelmode=driving`;

                    roadLine.bindPopup(`
                        <div style="font-size:12px;line-height:1.45;min-width:240px;">
                            <strong style="color:#0f172a;font-size:13px;">Rute Perjalanan Darat (OSRM)</strong><br/>
                            <div style="margin:6px 0;padding:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                                <div>🚗 Jarak Rute Jalan: <strong>${roadDistanceKm} km</strong></div>
                                <div>⏱ Estimasi Waktu: <strong>~${durationMinutes} menit</strong></div>
                                <div>📐 Jarak Garis Lurus: <strong>{{ number_format($asn->jarak_ke_kantor_km, 2) }} km</strong></div>
                            </div>
                            <a href="${gmapsDirectUrl}" target="_blank" rel="noopener noreferrer" style="display:block;text-align:center;padding:5px 8px;background:#15803d;color:#ffffff;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">
                                🚗 Buka Navigasi Google Maps ↗
                            </a>
                        </div>
                    `).openPopup();

                    map.fitBounds(roadLine.getBounds(), { padding: [40, 40] });
                } else {
                    fallbackLine();
                }
            })
            .catch(() => fallbackLine());

        function fallbackLine() {
            const line = L.polyline([[homeLat, homeLng], [officeLat, officeLng]], {
                color: '{{ $asn->zona_jarak === "jauh" ? "#dc2626" : "#0284c7" }}',
                weight: 2.5,
                dashArray: '6, 8'
            }).addTo(map);
            map.fitBounds(line.getBounds(), { padding: [40, 40] });
        }
    });
</script>
@endpush

