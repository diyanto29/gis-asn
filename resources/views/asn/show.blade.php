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

        // Marker Rumah
        const homeMarker = L.circleMarker([homeLat, homeLng], {
            radius: 8,
            fillColor: '{{ $asn->zona_jarak === "jauh" ? "#dc2626" : ($asn->zona_jarak === "sedang" ? "#d97706" : "#059669") }}',
            color: '#ffffff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.95
        }).addTo(map).bindPopup("<strong>Rumah: {{ $asn->nama }}</strong><br/>{{ $asn->kelurahan_desa }}, Kec. {{ $asn->kecamatan }}");

        // Marker Kantor
        const officeMarker = L.circleMarker([officeLat, officeLng], {
            radius: 9,
            fillColor: '#1d4ed8',
            color: '#ffffff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.95
        }).addTo(map).bindPopup("<strong>Kantor: {{ $asn->unitSaatIni->nama_unit ?? '' }}</strong>");

        // Garis Polyline
        const line = L.polyline([[homeLat, homeLng], [officeLat, officeLng]], {
            color: '{{ $asn->zona_jarak === "jauh" ? "#dc2626" : "#0284c7" }}',
            weight: 2.5,
            dashArray: '6, 8'
        }).addTo(map);

        // Fit bounds agar kedua marker terlihat pas
        map.fitBounds(line.getBounds(), { padding: [40, 40] });
    });
</script>
@endpush
