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
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="crosshair" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-xs font-bold text-slate-800">Geser Pin Merah tepat di atas Atap Rumah Anda</span>
                    </div>
                    <div class="text-[11px] text-slate-500">
                        💡 <em>Bisa geser pin merah atau cari alamat di bawah</em>
                    </div>
                </div>

                <!-- Search Toolbar Lokasi & GPS (Model Satpol PP) -->
                <div class="p-3 bg-white border-b border-slate-200 flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" id="mapSearchInput" placeholder="Ketik nama jalan, desa, patokan di Indramayu (mis: Jl. Veteran, Haurgeulis)..." class="w-full text-xs pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5"></i>
                    </div>
                    <button type="button" id="btnSearchMap" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i> Cari Lokasi
                    </button>
                    <button type="button" id="btnGps" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        <i data-lucide="locate-fixed" class="w-3.5 h-3.5"></i> Gunakan GPS Saya
                    </button>
                </div>

                <!-- Leaflet Container -->
                <div id="pickerMap" style="height: 480px;" class="w-full relative z-0"></div>

                <!-- Live Distance Indicator Bar -->
                <div class="px-5 py-3 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 text-xs">
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
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i> Koordinat & Wilayah (Wilayah.id)
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
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi</label>
                            <input type="text" name="provinsi" value="{{ old('provinsi', $asn->provinsi ?? 'Jawa Barat') }}" readonly class="w-full text-xs bg-slate-100 border border-slate-300 rounded-lg p-2 text-slate-600 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota</label>
                            <input type="text" name="kabupaten_kota" value="{{ old('kabupaten_kota', $asn->kabupaten_kota ?? 'Kabupaten Indramayu') }}" readonly class="w-full text-xs bg-slate-100 border border-slate-300 rounded-lg p-2 text-slate-600 font-medium">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan (Indramayu) <span class="text-rose-500">*</span></label>
                        <select id="kecamatanSelect" name="kecamatan" required class="searchable-select w-full" data-placeholder="-- Pilih Kecamatan --">
                            <option value="">-- Pilih Kecamatan Indramayu --</option>
                            @foreach ($kecamatans as $kec)
                                <option value="{{ $kec->nama_kecamatan }}" data-lat="{{ $kec->pusat_lat }}" data-lng="{{ $kec->pusat_lng }}" {{ old('kecamatan', $asn->kecamatan) == $kec->nama_kecamatan ? 'selected' : '' }}>
                                    Kec. {{ $kec->nama_kecamatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan <span class="text-rose-500">*</span></label>
                        <select id="desaSelect" name="kelurahan_desa" required class="searchable-select w-full" data-placeholder="-- Pilih Desa / Kelurahan --">
                            <option value="{{ old('kelurahan_desa', $asn->kelurahan_desa) }}">{{ old('kelurahan_desa', $asn->kelurahan_desa) ?: '-- Pilih Desa / Kelurahan --' }}</option>
                        </select>
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
        let wilayahIndramayu = {};

        if (typeof $.fn.select2 !== 'undefined') {
            $('.searchable-select').select2({
                width: '100%',
                placeholder: function(){ return $(this).data('placeholder'); },
                allowClear: true
            });
        }

        fetch('/data/indramayu_wilayah.json')
            .then(res => res.json())
            .then(data => {
                wilayahIndramayu = data;
                const initialKec = $('#kecamatanSelect').val();
                if (initialKec) {
                    populateDesa(initialKec, "{{ old('kelurahan_desa', $asn->kelurahan_desa) }}");
                }
            })
            .catch(err => console.warn('Gagal memuat katalog desa:', err));

        function populateDesa(kecamatanName, selectedDesa) {
            const desaSelect = $('#desaSelect');
            desaSelect.empty().append('<option value="">-- Pilih Desa / Kelurahan --</option>');

            const key = kecamatanName.toUpperCase().trim();
            const district = wilayahIndramayu[key];
            if (district && district.villages) {
                district.villages.forEach(v => {
                    const isSel = (selectedDesa && selectedDesa.toLowerCase() === v.name.toLowerCase()) ? 'selected' : '';
                    desaSelect.append(`<option value="${v.name}" ${isSel}>${v.name}</option>`);
                });
            }
            desaSelect.trigger('change.select2');
        }

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
        }).addTo(map).bindPopup("<strong>Titik Domisili Anda</strong><br/><span class='text-xs text-slate-500'>Geser pin ini tepat di atas atap rumah Anda</span>").openPopup();

        // Polyline connecting home & office
        const line = L.polyline([[curLat, curLng], [officeLat, officeLng]], {
            color: '#dc2626',
            weight: 2,
            dashArray: '5, 8'
        }).addTo(map);

        function calculateHaversine(lat1, lon1, lat2, lon2) {
            const R = 6371;
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

        // Fast fly to kecamatan when dropdown changed
        $('#kecamatanSelect').on('change', function () {
            const kecName = $(this).val();
            const opt = $(this).find('option:selected');
            const lat = parseFloat(opt.data('lat'));
            const lng = parseFloat(opt.data('lng'));
            if (lat && lng) {
                map.flyTo([lat, lng], 14, { duration: 1.2 });
                updateMarkerPosition(lat, lng);
            }
            if (kecName) {
                populateDesa(kecName, null);
            }
        });

        $('#desaSelect').on('change', function () {
            const desaName = $(this).val();
            const kecName = $('#kecamatanSelect').val();
            if (desaName && kecName) {
                const query = `Desa ${desaName}, ${kecName}, Kabupaten Indramayu`;
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=1`)
                    .then(res => res.json())
                    .then(res => {
                        if (res && res.length > 0) {
                            const lat = parseFloat(res[0].lat);
                            const lng = parseFloat(res[0].lon);
                            map.flyTo([lat, lng], 16, { duration: 1.2 });
                            updateMarkerPosition(lat, lng);
                        }
                    })
                    .catch(err => console.log('Geosearch desa fallback:', err));
            }
        });

        // Pencarian Alamat / Patokan (Model Satpol PP)
        function searchLocation() {
            const query = document.getElementById('mapSearchInput').value.trim();
            if (!query) return;

            const btn = document.getElementById('btnSearchMap');
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Mencari...';
            if (window.lucide) lucide.createIcons();

            const fullQuery = query.toLowerCase().includes('indramayu') ? query : `${query}, Kabupaten Indramayu`;
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullQuery)}&countrycodes=id&limit=5`)
                .then(res => res.json())
                .then(data => {
                    btn.innerHTML = origHtml;
                    if (window.lucide) lucide.createIcons();

                    if (data && data.length > 0) {
                        const top = data[0];
                        const lat = parseFloat(top.lat);
                        const lng = parseFloat(top.lon);

                        map.flyTo([lat, lng], 16, { duration: 1.2 });
                        updateMarkerPosition(lat, lng);
                        homeMarker.bindPopup(`<strong>Hasil Pencarian:</strong><br/>${top.display_name}`).openPopup();
                    } else {
                        alert(`Lokasi "${query}" tidak ditemukan di area Indramayu. Silakan geser pin merah secara manual.`);
                    }
                })
                .catch(err => {
                    btn.innerHTML = origHtml;
                    if (window.lucide) lucide.createIcons();
                    alert('Gagal melakukan pencarian alamat: ' + err.message);
                });
        }

        document.getElementById('btnSearchMap').addEventListener('click', searchLocation);
        document.getElementById('mapSearchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchLocation();
            }
        });

        // GPS Button
        document.getElementById('btnGps').addEventListener('click', function () {
            if (navigator.geolocation) {
                const btn = this;
                const orig = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Melacak...';
                if (window.lucide) lucide.createIcons();

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.flyTo([lat, lng], 16, { duration: 1.2 });
                        updateMarkerPosition(lat, lng);
                        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Lokasi Terdeteksi';
                        if (window.lucide) lucide.createIcons();
                    },
                    (err) => {
                        alert('Tidak dapat mendeteksi lokasi GPS: ' + err.message);
                        btn.innerHTML = orig;
                        if (window.lucide) lucide.createIcons();
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert('Browser Anda tidak mendukung deteksi GPS geolokasi.');
            }
        });
    });
</script>
@endpush

