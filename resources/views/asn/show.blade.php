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
        <a href="{{ route('asn.edit-lokasi', $asn->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
            <i data-lucide="map-pin" class="w-4 h-4"></i> Perbarui Titik Domisili Peta
        </a>
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
                        <div class="text-xs text-slate-500">{{ $asn->jabatan }}</div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs text-slate-600">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Pangkat / Golongan:</span>
                        <span class="font-bold text-slate-800">{{ $asn->pangkat_golongan }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Masa Kerja di Unit:</span>
                        <span class="font-bold text-slate-800">{{ $asn->masa_kerja_tahun }} Tahun</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">TMT Kerja:</span>
                        <span class="font-medium text-slate-800">{{ $asn->tmt_kerja_unit ? $asn->tmt_kerja_unit->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Unit Kerja Saat Ini:</span>
                        <span class="font-bold text-slate-900 text-right">{{ $asn->unitSaatIni->nama_unit ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Status Permohonan:</span>
                        @if ($asn->status_permohonan_mutasi)
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-semibold rounded">Mengajukan Mutasi</span>
                        @else
                            <span class="text-slate-400">Tidak Mengajukan</span>
                        @endif
                    </div>
                </div>

                @if ($asn->alasan_mutasi)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs">
                        <span class="font-semibold text-slate-700 block mb-1">Catatan / Alasan Permohonan:</span>
                        <p class="text-slate-600 italic">"{{ $asn->alasan_mutasi }}"</p>
                    </div>
                @endif
            </div>

            <!-- Jarak Spasial Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-blue-600"></i>
                    Beban Perjalanan Harian
                </h3>

                <div class="p-4 rounded-lg {{ $asn->zona_jarak === 'jauh' ? 'bg-rose-50 border border-rose-200' : ($asn->zona_jarak === 'sedang' ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200') }} text-center">
                    <div class="text-xs uppercase tracking-wider font-semibold {{ $asn->zona_jarak === 'jauh' ? 'text-rose-700' : ($asn->zona_jarak === 'sedang' ? 'text-amber-700' : 'text-emerald-700') }}">
                        Jarak Titik Garis Lurus
                    </div>
                    <div class="text-3xl font-extrabold my-1 {{ $asn->zona_jarak === 'jauh' ? 'text-rose-600' : ($asn->zona_jarak === 'sedang' ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ number_format($asn->jarak_ke_kantor_km, 2) }} <span class="text-base font-normal">km</span>
                    </div>
                    <div class="text-xs font-medium {{ $asn->zona_jarak === 'jauh' ? 'text-rose-700' : ($asn->zona_jarak === 'sedang' ? 'text-amber-700' : 'text-emerald-700') }}">
                        Zona: {{ strtoupper($asn->zona_jarak ?? '-') }}
                    </div>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <div>Estimasi Perjalanan Pulang-Pergi: <strong class="text-slate-700">{{ number_format($asn->jarak_ke_kantor_km * 2, 2) }} km/hari</strong></div>
                    <div>Koordinat Rumah: <span class="font-mono text-slate-700">{{ $asn->domisili_lat }}, {{ $asn->domisili_lng }}</span></div>
                    <div>Alamat: <span class="text-slate-700">{{ $asn->alamat_detail ?? '-' }}, {{ $asn->kelurahan_desa }}, Kec. {{ $asn->kecamatan }}</span></div>
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

        <!-- Column 2: Peta Detail Rute & Riwayat Rekomendasi -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Peta Leaflet Personal -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                        <i data-lucide="map" class="w-4 h-4 text-blue-600"></i> Peta Posisi Domisili vs Unit Kerja
                    </span>
                    <span class="text-[11px] text-slate-400">Formula Haversine Spherical</span>
                </div>
                <div id="personalMap" style="height: 380px;" class="w-full"></div>
            </div>

            <!-- Rekomendasi Mutasi yang Pernah Terkalkulasi -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Hasil Rekomendasi Mutasi ke Unit Terdekat</h3>
                    <a href="{{ route('topsis.index') }}" class="text-xs text-blue-600 font-semibold hover:underline">Jalankan SPK Baru &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-semibold uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Unit Tujuan</th>
                                <th class="px-4 py-3 text-right">Jarak Baru</th>
                                <th class="px-4 py-3 text-right">Hemat Jarak</th>
                                <th class="px-4 py-3 text-right">Nilai Vi</th>
                                <th class="px-4 py-3 text-center">Peringkat</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($asn->rekomendasi as $rek)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-bold text-slate-900">{{ $rek->unitTujuan->nama_unit }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ number_format($rek->jarak_baru_km, 2) }} km</td>
                                    <td class="px-4 py-3 text-right font-bold text-indigo-600">+{{ number_format($rek->selisih_hemat_jarak_km, 2) }} km</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold">{{ number_format($rek->nilai_preferensi_v, 4) }}</td>
                                    <td class="px-4 py-3 text-center font-bold">#{{ $rek->ranking }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-semibold uppercase">{{ $rek->status_usulan }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-400">Pegawai ini belum dimasukkan dalam simulasi kalkulasi Baperjakat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const iconBuildingSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>`;
        const iconHomeSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>`;

        let homeBg = 'bg-emerald-600';
        let homeRipple = 'bg-emerald-500/35';
        if ('{{ $asn->zona_jarak }}' === 'sedang') { homeBg = 'bg-amber-500'; homeRipple = 'bg-amber-400/35'; }
        if ('{{ $asn->zona_jarak }}' === 'jauh') { homeBg = 'bg-rose-600'; homeRipple = 'bg-rose-500/40'; }

        // Marker Rumah Custom Icon
        const homeIcon = L.divIcon({
            className: 'custom-home-marker',
            html: `
                <div class="relative flex items-center justify-center w-8 h-8">
                    <span class="marker-ripple ${homeRipple}"></span>
                    <div class="relative z-10 w-7 h-7 rounded-full ${homeBg} text-white flex items-center justify-center shadow-md border-2 border-white">
                        ${iconHomeSvg}
                    </div>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

        const homeMarker = L.marker([homeLat, homeLng], { icon: homeIcon })
            .addTo(map)
            .bindPopup("<strong>Rumah: {{ $asn->nama }}</strong><br/>{{ $asn->kelurahan_desa }}, Kec. {{ $asn->kecamatan }}");

        // Marker Kantor Custom Icon
        const officeIcon = L.divIcon({
            className: 'custom-office-marker',
            html: `
                <div class="relative flex items-center justify-center w-8 h-8">
                    <span class="marker-ripple bg-blue-500/35"></span>
                    <div class="relative z-10 w-7 h-7 rounded-xl bg-blue-700 text-white flex items-center justify-center shadow-md border-2 border-white">
                        ${iconBuildingSvg}
                    </div>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

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
                    const durationMinutes = Math.round(route.duration / 60);
                    const latLngs = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

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

