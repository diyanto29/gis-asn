@extends('layouts.app')

@section('title', 'Perbarui Titik Domisili: ' . $asn->nama_lengkap)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('asn.show', $asn->id) }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Perbarui Titik Domisili Tempat Tinggal</h1>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $asn->nama_lengkap }} &bull; NIP: {{ $asn->nip }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('asn.update-lokasi', $asn->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Peta Pinpoint Leaflet (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="crosshair" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-xs font-bold text-slate-800">Geser Pin Merah tepat di atas Atap Rumah Anda</span>
                    </div>
                    <button type="button" id="btnGps" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        <i data-lucide="locate-fixed" class="w-3.5 h-3.5"></i> Gunakan GPS Saya
                    </button>
                </div>

                <!-- Leaflet Container -->
                <div id="pickerMap" style="height: 480px;" class="w-full relative z-0"></div>

                <!-- Live Distance Indicator Bar -->
                <div class="px-5 py-3 bg-slate-900 text-white flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">Kantor Anda Saat Ini:</span>
                        <strong class="text-white">{{ $asn->unitSaatIni->nama_unit ?? '-' }}</strong>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">Jarak Spasial Hasil Pin:</span>
                        <span id="liveDistanceBadge" class="px-2.5 py-0.5 rounded font-extrabold text-sm bg-emerald-500 text-white">
                            {{ number_format($asn->jarak_ke_kantor_km ?? 0, 2) }} km
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Data Alamat & Koordinat (1 Column) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">
                        Koordinat & Data Wilayah
                    </h3>

                    <!-- Lat / Lng (Auto-filled by map) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude</label>
                            <input type="text" id="domisili_lat" name="domisili_lat" value="{{ old('domisili_lat', $asn->domisili_lat ?? -6.32635) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700 focus:ring-0">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                            <input type="text" id="domisili_lng" name="domisili_lng" value="{{ old('domisili_lng', $asn->domisili_lng ?? 108.32042) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700 focus:ring-0">
                        </div>
                    </div>

                    <!-- Cascading Alamat Naratif -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi</label>
                        <input type="text" name="provinsi" value="{{ old('provinsi', $asn->provinsi ?? 'Jawa Barat') }}" class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota</label>
                        <input type="text" name="kabupaten_kota" value="{{ old('kabupaten_kota', $asn->kabupaten_kota ?? 'Kabupaten Indramayu') }}" class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                            @if(isset($kecamatans) && $kecamatans->count() > 0)
                                <select id="kecamatanSelect" name="kecamatan" required class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach ($kecamatans as $kec)
                                        <option value="{{ $kec->nama_kecamatan }}" data-lat="{{ $kec->pusat_lat }}" data-lng="{{ $kec->pusat_lng }}" {{ old('kecamatan', $asn->kecamatan) == $kec->nama_kecamatan ? 'selected' : '' }}>
                                            Kec. {{ $kec->nama_kecamatan }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="kecamatan" value="{{ old('kecamatan', $asn->kecamatan) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500" placeholder="Kecamatan">
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan</label>
                            <input type="text" name="kelurahan_desa" value="{{ old('kelurahan_desa', $asn->kelurahan_desa) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500" placeholder="Desa/Kelurahan">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap / RT RW</label>
                        <textarea name="alamat_detail" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500" placeholder="Jl. Veteran No. 12 RT 02/RW 04...">{{ old('alamat_detail', $asn->alamat_detail) }}</textarea>
                    </div>

                    <!-- Checklist Ajukan Permohonan Mutasi -->
                    <div class="pt-3 border-t border-slate-100">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="status_permohonan_mutasi" value="1" {{ old('status_permohonan_mutasi', $asn->status_permohonan_mutasi) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-800">Ajukan Rekomendasi Mutasi ke Baperjakat</span>
                        </label>

                        <div class="mt-2">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Alasan Pengajuan (Opsional)</label>
                            <textarea name="alasan_mutasi" rows="2" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Mengurangi beban jarak 40km setiap hari...">{{ old('alasan_mutasi', $asn->alasan_mutasi) }}</textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Titik Domisili
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const officeLat = {{ $asn->unitSaatIni->kantor_lat ?? -6.32635 }};
        const officeLng = {{ $asn->unitSaatIni->kantor_lng ?? 108.32042 }};
        
        let curLat = parseFloat(document.getElementById('domisili_lat').value) || officeLat;
        let curLng = parseFloat(document.getElementById('domisili_lng').value) || officeLng;

        const map = L.map('pickerMap').setView([curLat, curLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Marker Kantor Saat Ini (Fixed Blue)
        const officeMarker = L.circleMarker([officeLat, officeLng], {
            radius: 8,
            fillColor: '#1d4ed8',
            color: '#ffffff',
            weight: 2,
            opacity: 1,
            fillOpacity: 1
        }).addTo(map).bindPopup("<strong>Kantor Anda: {{ $asn->unitSaatIni->nama_unit }}</strong>");

        // Draggable Home Marker (Red Pin)
        const homeMarker = L.marker([curLat, curLng], {
            draggable: true
        }).addTo(map).bindPopup("Titik Domisili Anda (Geser marker ini)").openPopup();

        // Polyline connecting home & office
        const line = L.polyline([[curLat, curLng], [officeLat, officeLng]], {
            color: '#dc2626',
            weight: 2,
            dashArray: '5, 8'
        }).addTo(map);

        // Fungsi Hitung Haversine di Javascript untuk Live Preview
        function calculateHaversine(lat1, lon1, lat2, lon2) {
            const R = 6371; // km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return (R * c).toFixed(2);
        }

        function updateMarkerPosition(lat, lng) {
            document.getElementById('domisili_lat').value = lat.toFixed(7);
            document.getElementById('domisili_lng').value = lng.toFixed(7);

            homeMarker.setLatLng([lat, lng]);
            line.setLatLngs([[lat, lng], [officeLat, officeLng]]);

            // Update live distance
            const dKm = calculateHaversine(lat, lng, officeLat, officeLng);
            const badge = document.getElementById('liveDistanceBadge');
            badge.innerText = dKm + ' km';

            if (dKm < 5) {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-emerald-500 text-white';
            } else if (dKm <= 20) {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-amber-500 text-white';
            } else {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-rose-600 text-white';
            }
        }

        homeMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateMarkerPosition(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            updateMarkerPosition(e.latlng.lat, e.latlng.lng);
        });

        // GPS Button
        document.getElementById('btnGps').addEventListener('click', function () {
            if (navigator.geolocation) {
                this.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Mendeteksi GPS...';
                lucide.createIcons();

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.setView([lat, lng], 16);
                        updateMarkerPosition(lat, lng);
                        this.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Lokasi Terdeteksi';
                        lucide.createIcons();
                    },
                    (err) => {
                        alert('Tidak dapat mendeteksi lokasi GPS: ' + err.message);
                        this.innerHTML = '<i data-lucide="locate-fixed" class="w-3.5 h-3.5"></i> Gunakan GPS Saya';
                        lucide.createIcons();
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert('Browser Anda tidak mendukung deteksi GPS geolokasi.');
            }
        });
        // Fast fly to kecamatan when dropdown changed
        const kecSelect = document.getElementById('kecamatanSelect');
        if (kecSelect) {
            kecSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const lat = parseFloat(opt.getAttribute('data-lat'));
                const lng = parseFloat(opt.getAttribute('data-lng'));
                if (lat && lng) {
                    map.flyTo([lat, lng], 14);
                    updateMarkerPosition(lat, lng);
                }
            });
        }
    });
</script>
@endpush

