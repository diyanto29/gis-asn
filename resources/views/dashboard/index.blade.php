@extends('layouts.app')

@section('title', 'Dashboard Pemetaan Spasial ASN')

@section('content')
<div class="space-y-6">

    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Pemetaan Geospasial ASN</h1>
            <p class="text-sm text-slate-500 mt-1">
                Visualisasi titik domisili pegawai, unit kerja, radius jarak tempuh, dan potensi rotasi mendekatkan rumah.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('topsis.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <i data-lucide="play" class="w-4 h-4"></i> Jalankan SPK Mutasi
            </a>
        </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Pegawai -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total ASN</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalAsn }}</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="users" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-slate-400">Terdaftar di master</div>
        </div>

        <!-- Total Unit Kerja -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unit Kerja</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalUnit }}</span>
                <span class="p-2 bg-slate-100 text-slate-600 rounded-lg"><i data-lucide="building" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-slate-400">Dinas, Camat, Puskesmas</div>
        </div>

        <!-- Formasi Lowong (ABK) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kuota Formasi</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-indigo-600">{{ $totalFormasi }}</span>
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><i data-lucide="user-plus" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-indigo-500 font-medium">Bisa diisi mutasi</div>
        </div>

        <!-- Zona Dekat -->
        <div class="bg-white p-4 rounded-xl border border-emerald-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Dekat (&lt; 5 Km)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600">{{ $zonaDekat }}</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><i data-lucide="check" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-emerald-600">Efisiensi optimal</div>
        </div>

        <!-- Zona Sedang -->
        <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Sedang (5-20 Km)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-600">{{ $zonaSedang }}</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg"><i data-lucide="navigation" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-amber-600">Beban perjalanan wajar</div>
        </div>

        <!-- Zona Jauh (Prioritas Mutasi) -->
        <div class="bg-white p-4 rounded-xl border border-rose-200 shadow-sm flex flex-col justify-between">
            <div class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Jauh (&gt; 20 Km)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-rose-600">{{ $zonaJauh }}</span>
                <span class="p-2 bg-rose-50 text-rose-600 rounded-lg"><i data-lucide="alert-octagon" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-rose-600 font-semibold">Prioritas rotasi BKPSDM</div>
        </div>
    </div>

    <!-- Interactive GIS Map Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Map Control Bar -->
        <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i data-lucide="map" class="w-4 h-4 text-blue-600"></i>
                <span class="text-sm font-semibold text-slate-800">Peta Sebaran Spasial Pegawai & Unit Kerja</span>
                <span id="routingStatusBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 animate-pulse">
                    Memuat Rute Jalan OSRM...
                </span>
            </div>

            <!-- Map Controls / Filters -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-medium">
                <!-- Mode Garis: Rute Jalan vs Garis Lurus -->
                <div class="flex items-center bg-white border border-slate-200 rounded-lg p-0.5 shadow-xs">
                    <button type="button" id="btnModeRoad" class="px-2.5 py-1 rounded text-xs font-bold bg-blue-700 text-white flex items-center gap-1 transition">
                        <i data-lucide="car" class="w-3.5 h-3.5"></i> Rute Jalan Asli (OSRM)
                    </button>
                    <button type="button" id="btnModeStraight" class="px-2.5 py-1 rounded text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1 transition">
                        <i data-lucide="ruler" class="w-3.5 h-3.5"></i> Garis Lurus (Haversine)
                    </button>
                </div>

                <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" id="toggleOffices" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Kantor ({{ $units->count() }})</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" id="toggleAsn" checked class="rounded border-slate-300 text-slate-700 focus:ring-blue-500">
                    <span>Rumah ASN ({{ $asns->count() }})</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" id="toggleLines" checked class="rounded border-slate-300 text-slate-700 focus:ring-blue-500">
                    <span>Tampilkan Rute</span>
                </label>
            </div>
        </div>

        <!-- Leaflet Map Container -->
        <div id="map" style="height: 520px;" class="w-full relative z-0"></div>

        <!-- Map Footer Legend -->
        <div class="px-5 py-3 bg-white border-t border-slate-100 flex flex-wrap items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-4 text-slate-600">
                <span class="font-semibold text-slate-800">Legenda:</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-lg bg-blue-700 text-white flex items-center justify-center text-[10px] shadow-xs"><i data-lucide="building-2" class="w-3 h-3"></i></span> Kantor / Unit</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] shadow-xs"><i data-lucide="home" class="w-3 h-3"></i></span> Rumah Dekat (&lt;5 km)</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] shadow-xs"><i data-lucide="home" class="w-3 h-3"></i></span> Rumah Sedang (5-20 km)</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-[10px] shadow-xs"><i data-lucide="home" class="w-3 h-3"></i></span> Rumah Jauh (&gt;20 km)</span>
            </div>
            <div class="text-slate-500 text-[11px] flex items-center gap-2">
                <span class="inline-block w-4 h-1 bg-rose-600 rounded"></span>
                <span>Garis rute berbelok mengikuti jaringan jalan raya nyata via <strong>Open Source Routing Machine (OSRM)</strong>.</span>
            </div>
        </div>
    </div>

    <!-- Quick Table: ASN yang Paling Membutuhkan Mutasi (Zona Jauh) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
                <h3 class="font-bold text-slate-800 text-sm">Daftar ASN dengan Jarak Domisili Terjauh (Prioritas Rekomendasi Mutasi)</h3>
            </div>
            <a href="{{ route('asn.index', ['zona' => 'jauh']) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                Lihat Semua ASN &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Nama Pegawai / NIP</th>
                        <th class="px-5 py-3.5">Jabatan & Pangkat</th>
                        <th class="px-5 py-3.5">Unit Kerja Saat Ini</th>
                        <th class="px-5 py-3.5">Lokasi Domisili (Rumah)</th>
                        <th class="px-5 py-3.5 text-right">Jarak Tempuh</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($asns->sortByDesc('jarak_ke_kantor_km')->take(5) as $asn)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $asn->nama_lengkap }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $asn->nip }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div>{{ $asn->jabatan }}</div>
                                <span class="inline-block mt-0.5 px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-semibold rounded">{{ $asn->pangkat_golongan }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-800">
                                {{ $asn->unitSaatIni->nama_unit ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                {{ $asn->kecamatan ?? '-' }}, {{ $asn->kabupaten_kota ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-base {{ $asn->zona_jarak === 'jauh' ? 'text-rose-600' : ($asn->zona_jarak === 'sedang' ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ number_format($asn->jarak_ke_kantor_km, 2) }} km
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($asn->zona_jarak === 'jauh')
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-xs rounded-full font-semibold">Prioritas</span>
                                @elseif ($asn->zona_jarak === 'sedang')
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs rounded-full font-semibold">Sedang</span>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs rounded-full font-semibold">Dekat</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <a href="{{ route('asn.show', $asn->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg inline-block transition" title="Lihat Profil">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-slate-400">Belum ada data ASN dengan titik koordinat domisili.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Data dari Controller
        const units = @json($units);
        const asns = @json($asns);

        // Center peta di Kabupaten Indramayu (-6.326350, 108.320420)
        const map = L.map('map').setView([-6.38, 108.25], 11);

        // Base Map OSM
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors | BKPSDM GIS'
        }).addTo(map);

        // Layer Groups
        const officeLayer = L.layerGroup().addTo(map);
        const asnLayer = L.layerGroup().addTo(map);
        const roadRouteLayer = L.layerGroup().addTo(map); // Default active (OSRM road curve)
        const straightLineLayer = L.layerGroup();         // Standby (Haversine straight)

        let isRoadMode = true;

        // Helper SVG Icons
        const iconBuildingSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>`;
        const iconHomeSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>`;

        // 1. Render Unit Kerja (Kantor) dengan Icon Gedung & Efek Ripple Biru
        units.forEach(u => {
            if (u.kantor_lat && u.kantor_lng) {
                const officeIcon = L.divIcon({
                    className: 'custom-office-marker',
                    html: `
                        <div class="relative flex items-center justify-center w-8 h-8 cursor-pointer group">
                            <span class="marker-ripple bg-blue-500/35"></span>
                            <div class="relative z-10 w-7 h-7 rounded-xl bg-blue-700 text-white flex items-center justify-center shadow-md border-2 border-white group-hover:scale-115 transition-transform">
                                ${iconBuildingSvg}
                            </div>
                        </div>
                    `,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });

                const marker = L.marker([u.kantor_lat, u.kantor_lng], { icon: officeIcon });

                const badgeFormasi = u.kuota_formasi_kosong > 0 
                    ? `<span style="background:#ecfdf5;color:#047857;padding:2px 6px;border-radius:4px;font-weight:bold;font-size:11px;">Tersedia Kuota: ${u.kuota_formasi_kosong}</span>`
                    : `<span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:4px;font-size:11px;">Kuota Penuh</span>`;

                const gmapsOfficeUrl = `https://www.google.com/maps/search/?api=1&query=${u.kantor_lat},${u.kantor_lng}`;
                const gmapsDirections = `https://www.google.com/maps/dir/?api=1&destination=${u.kantor_lat},${u.kantor_lng}`;

                marker.bindPopup(`
                    <div style="font-size:13px;line-height:1.45;min-width:240px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:#64748b;font-size:11px;font-weight:600;">Kode: ${u.kode_unit} &bull; ${u.kategori}</span>
                            ${badgeFormasi}
                        </div>
                        <strong style="color:#0f172a;font-size:14px;display:block;margin-bottom:2px;">${u.nama_unit}</strong>
                        <p style="margin:4px 0 8px;color:#475569;font-size:12px;">${u.alamat_kantor || '-'}</p>
                        
                        <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;display:flex;align-items:center;gap:6px;">
                            <a href="${gmapsOfficeUrl}" target="_blank" rel="noopener noreferrer" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:5px 8px;background:#f8fafc;border:1px solid #cbd5e1;color:#1e293b;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;">
                                📍 Titik Google Maps
                            </a>
                            <a href="${gmapsDirections}" target="_blank" rel="noopener noreferrer" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:5px 8px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">
                                🚗 Petunjuk Arah
                            </a>
                        </div>
                    </div>
                `);

                officeLayer.addLayer(marker);
            }
        });

        // 2. Render ASN Domisili dengan Icon Rumah & Efek Ripple Sesuai Zona
        asns.forEach(asn => {
            if (asn.domisili_lat && asn.domisili_lng) {
                let bgColor = 'bg-emerald-600';
                let rippleColor = 'bg-emerald-500/35';
                let color = '#059669';

                if (asn.zona_jarak === 'sedang') {
                    bgColor = 'bg-amber-500';
                    rippleColor = 'bg-amber-400/35';
                    color = '#d97706';
                }
                if (asn.zona_jarak === 'jauh') {
                    bgColor = 'bg-rose-600';
                    rippleColor = 'bg-rose-500/40';
                    color = '#dc2626';
                }

                const homeIcon = L.divIcon({
                    className: 'custom-home-marker',
                    html: `
                        <div class="relative flex items-center justify-center w-7 h-7 cursor-pointer group">
                            <span class="marker-ripple ${rippleColor}"></span>
                            <div class="relative z-10 w-6 h-6 rounded-full ${bgColor} text-white flex items-center justify-center shadow-md border-2 border-white group-hover:scale-115 transition-transform">
                                ${iconHomeSvg}
                            </div>
                        </div>
                    `,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                    popupAnchor: [0, -14]
                });

                const marker = L.marker([asn.domisili_lat, asn.domisili_lng], { icon: homeIcon });

                const gmapsHomeUrl = `https://www.google.com/maps/search/?api=1&query=${asn.domisili_lat},${asn.domisili_lng}`;
                const gmapsRouteUrl = (asn.unit_saat_ini && asn.unit_saat_ini.kantor_lat && asn.unit_saat_ini.kantor_lng)
                    ? `https://www.google.com/maps/dir/?api=1&origin=${asn.domisili_lat},${asn.domisili_lng}&destination=${asn.unit_saat_ini.kantor_lat},${asn.unit_saat_ini.kantor_lng}&travelmode=driving`
                    : gmapsHomeUrl;

                marker.bindPopup(`
                    <div style="font-size:12px;line-height:1.45;min-width:260px;">
                        <strong style="color:#0f172a;font-size:13px;">${asn.nama}</strong><br/>
                        <span style="color:#64748b;font-size:11px;">NIP: ${asn.nip}</span><br/>
                        <div style="margin:6px 0;padding:7px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;">
                            <div>Kantor: <strong>${asn.unit_saat_ini ? asn.unit_saat_ini.nama_unit : '-'}</strong></div>
                            <div>Jarak Lurus: <strong style="color:${color};font-size:13px;">${Number(asn.jarak_ke_kantor_km).toFixed(2)} km</strong> (${asn.zona_jarak.toUpperCase()})</div>
                            <div id="osrmInfo_${asn.id}" style="color:#2563eb;font-size:11px;margin-top:2px;">🚗 Menghitung rute jalan riil...</div>
                            <div style="color:#64748b;font-size:11px;margin-top:2px;">Domisili: ${asn.kelurahan_desa || ''}, Kec. ${asn.kecamatan || ''}</div>
                        </div>
                        <div style="margin-top:8px;padding-top:7px;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:6px;">
                            <a href="/asn/${asn.id}" style="color:#1d4ed8;font-weight:700;font-size:11px;text-decoration:none;">
                                Detail Profil &rarr;
                            </a>
                            <a href="${gmapsRouteUrl}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">
                                🚗 Rute Google Maps ↗
                            </a>
                        </div>
                    </div>
                `);

                asnLayer.addLayer(marker);

                // Buat Garis Lurus (Standby layer)
                if (asn.unit_saat_ini && asn.unit_saat_ini.kantor_lat && asn.unit_saat_ini.kantor_lng) {
                    const straightLine = L.polyline([
                        [asn.domisili_lat, asn.domisili_lng],
                        [asn.unit_saat_ini.kantor_lat, asn.unit_saat_ini.kantor_lng]
                    ], {
                        color: color,
                        weight: 2,
                        opacity: 0.6,
                        dashArray: '5, 8'
                    });
                    straightLineLayer.addLayer(straightLine);
                }
            }
        });

        // 3. Fetch Rute Jalan Asli OSRM (Meliuk mengikuti jalan aspal)
        const badgeRouting = document.getElementById('routingStatusBadge');
        badgeRouting.classList.remove('hidden');

        let fetchPromises = asns.map(asn => {
            if (asn.domisili_lat && asn.domisili_lng && asn.unit_saat_ini && asn.unit_saat_ini.kantor_lat && asn.unit_saat_ini.kantor_lng) {
                const lng1 = asn.domisili_lng;
                const lat1 = asn.domisili_lat;
                const lng2 = asn.unit_saat_ini.kantor_lng;
                const lat2 = asn.unit_saat_ini.kantor_lat;

                let color = '#059669';
                if (asn.zona_jarak === 'sedang') color = '#d97706';
                if (asn.zona_jarak === 'jauh') color = '#dc2626';

                const url = `https://router.project-osrm.org/route/v1/driving/${lng1},${lat1};${lng2},${lat2}?overview=full&geometries=geojson`;

                return fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                            const route = data.routes[0];
                            const roadDistanceKm = (route.distance / 1000).toFixed(2);
                            const durationMinutes = Math.round(route.duration / 60);

                            // GeoJSON geometry coordinates [lng, lat] -> Leaflet LatLng [lat, lng]
                            const latLngs = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

                            const roadLine = L.polyline(latLngs, {
                                color: color,
                                weight: asn.zona_jarak === 'jauh' ? 3.5 : 2.5,
                                opacity: 0.85
                            });

                            const gmapsRoadUrl = `https://www.google.com/maps/dir/?api=1&origin=${asn.domisili_lat},${asn.domisili_lng}&destination=${asn.unit_saat_ini.kantor_lat},${asn.unit_saat_ini.kantor_lng}&travelmode=driving`;

                            roadLine.bindPopup(`
                                <div style="font-size:12px;line-height:1.45;min-width:240px;">
                                    <strong style="color:#0f172a;font-size:13px;">Rute Perjalanan Harian ASN</strong><br/>
                                    <span style="color:#334155;">${asn.nama} (${asn.nip})</span>
                                    <div style="margin:6px 0;padding:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                                        <div>🚗 Jarak Rute Jalan (OSRM): <strong style="color:#0f172a;">${roadDistanceKm} km</strong> (~${durationMinutes} mnt)</div>
                                        <div>📐 Jarak Garis Lurus: <strong style="color:#64748b;">${Number(asn.jarak_ke_kantor_km).toFixed(2)} km</strong></div>
                                    </div>
                                    <a href="${gmapsRoadUrl}" target="_blank" rel="noopener noreferrer" style="display:block;text-align:center;padding:5px 8px;background:#1d4ed8;color:#ffffff;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">
                                        🚗 Buka Navigasi Rute di Google Maps ↗
                                    </a>
                                </div>
                            `);

                            roadRouteLayer.addLayer(roadLine);

                            // Update info popup jika ada
                            const infoDiv = document.getElementById(`osrmInfo_${asn.id}`);
                            if (infoDiv) {
                                infoDiv.innerHTML = `🚗 Rute Jalan Asli: <strong>${roadDistanceKm} km</strong> (~${durationMinutes} menit berkendara)`;
                            }
                        } else {
                            // Fallback jika OSRM tidak menemukan rute
                            fallbackStraightLine(asn, color);
                        }
                    })
                    .catch(err => {
                        console.warn('Gagal memuat rute OSRM untuk', asn.nama, err);
                        fallbackStraightLine(asn, color);
                    });
            }
            return Promise.resolve();
        });

        function fallbackStraightLine(asn, color) {
            const straight = L.polyline([
                [asn.domisili_lat, asn.domisili_lng],
                [asn.unit_saat_ini.kantor_lat, asn.unit_saat_ini.kantor_lng]
            ], {
                color: color,
                weight: 2,
                opacity: 0.7,
                dashArray: '4, 6'
            });
            roadRouteLayer.addLayer(straight);
        }

        Promise.allSettled(fetchPromises).then(() => {
            badgeRouting.classList.add('hidden');
        });

        // 4. Toggle Mode: Rute Jalan vs Garis Lurus
        const btnRoad = document.getElementById('btnModeRoad');
        const btnStraight = document.getElementById('btnModeStraight');

        btnRoad.addEventListener('click', function () {
            isRoadMode = true;
            btnRoad.className = 'px-2.5 py-1 rounded text-xs font-bold bg-blue-700 text-white flex items-center gap-1 transition';
            btnStraight.className = 'px-2.5 py-1 rounded text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1 transition';

            if (document.getElementById('toggleLines').checked) {
                map.removeLayer(straightLineLayer);
                map.addLayer(roadRouteLayer);
            }
        });

        btnStraight.addEventListener('click', function () {
            isRoadMode = false;
            btnStraight.className = 'px-2.5 py-1 rounded text-xs font-bold bg-blue-700 text-white flex items-center gap-1 transition';
            btnRoad.className = 'px-2.5 py-1 rounded text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1 transition';

            if (document.getElementById('toggleLines').checked) {
                map.removeLayer(roadRouteLayer);
                map.addLayer(straightLineLayer);
            }
        });

        // Event Checkbox Toggles
        document.getElementById('toggleOffices').addEventListener('change', function (e) {
            if (e.target.checked) map.addLayer(officeLayer);
            else map.removeLayer(officeLayer);
        });

        document.getElementById('toggleAsn').addEventListener('change', function (e) {
            if (e.target.checked) map.addLayer(asnLayer);
            else map.removeLayer(asnLayer);
        });

        document.getElementById('toggleLines').addEventListener('change', function (e) {
            if (e.target.checked) {
                if (isRoadMode) map.addLayer(roadRouteLayer);
                else map.addLayer(straightLineLayer);
            } else {
                map.removeLayer(roadRouteLayer);
                map.removeLayer(straightLineLayer);
            }
        });
    });
</script>
@endpush
