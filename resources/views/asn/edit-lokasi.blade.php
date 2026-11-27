@extends('layouts.app')

@section('title', 'Perbarui Titik Domisili: ' . $asn->nama_lengkap)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('asn.show', $asn->id) }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
            @endif
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Profil Pegawai &amp; Perbarui Titik Domisili</h1>
                <p class="text-xs text-slate-500 mt-0.5">Data kepegawaian bersifat hanya-baca (dikelola Admin BKPSDM). Anda dapat memperbarui titik koordinat &amp; wilayah domisili di peta.</p>
            </div>
        </div>
        @if (auth()->user()->isAsn())
            <a href="{{ route('asn.simulasi-mandiri') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                <i data-lucide="compass" class="w-4 h-4"></i> Lihat Rekomendasi Unit Kerja (SPK)
            </a>
        @endif
    </div>

    <!-- Identitas Kepegawaian (Read-Only / Disabled) -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="lock" class="w-4 h-4 text-slate-400"></i> Identitas Kepegawaian (Sementara Tidak Dapat Diubah)
            </h3>
            <span class="px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200 rounded">Read-Only</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">NIP (18 Digit)</label>
                <input type="text" value="{{ $asn->nip }}" disabled class="w-full text-xs font-mono bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Nama Lengkap</label>
                <input type="text" value="{{ $asn->nama_lengkap }}" disabled class="w-full text-xs bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Unit Kerja Saat Ini</label>
                <input type="text" value="{{ $asn->unitSaatIni->nama_unit ?? '-' }}" disabled class="w-full text-xs bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Jabatan</label>
                <input type="text" value="{{ $asn->jabatan }}" disabled class="w-full text-xs bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Pangkat / Golongan</label>
                <input type="text" value="{{ $asn->pangkat_golongan }} (Masa Kerja {{ $asn->masa_kerja_tahun }} Thn)" disabled class="w-full text-xs bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Pendidikan Terakhir</label>
                <input type="text" value="{{ $asn->pendidikan_terakhir ?? $asn->tingkat_pendidikan ?? '-' }}" disabled class="w-full text-xs bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-slate-600 cursor-not-allowed">
            </div>
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
                        <input type="text" id="mapSearchInput" placeholder="Ketik nama jalan, desa, patokan di Kabupaten XYZ (mis: Jl. Veteran, Haurgeulis)..." class="w-full text-xs pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
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

                    <!-- Cascading Wilayah Administratif (Wilayah.id se-Indonesia) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi <span class="text-rose-500">*</span></label>
                        <select id="provinsiSelect" name="provinsi" required class="searchable-select w-full" data-placeholder="-- Pilih Provinsi --">
                            <option value="">-- Memuat Provinsi... --</option>
                            @if (old('provinsi', $asn->provinsi))
                                <option value="{{ old('provinsi', $asn->provinsi) }}" selected>{{ old('provinsi', $asn->provinsi) }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
                        <select id="kabupatenSelect" name="kabupaten_kota" required class="searchable-select w-full" data-placeholder="-- Pilih Kabupaten / Kota --">
                            <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                            @if (old('kabupaten_kota', $asn->kabupaten_kota))
                                <option value="{{ old('kabupaten_kota', $asn->kabupaten_kota) }}" selected>{{ old('kabupaten_kota', $asn->kabupaten_kota) }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan <span class="text-rose-500">*</span></label>
                        <select id="kecamatanSelect" name="kecamatan" required class="searchable-select w-full" data-placeholder="-- Pilih Kecamatan --">
                            <option value="">-- Pilih Kabupaten / Kota Terlebih Dahulu --</option>
                            @if (old('kecamatan', $asn->kecamatan))
                                <option value="{{ old('kecamatan', $asn->kecamatan) }}" selected>Kec. {{ old('kecamatan', $asn->kecamatan) }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan</label>
                        <select id="desaSelect" name="kelurahan_desa" class="searchable-select w-full" data-placeholder="-- Pilih Desa / Kelurahan --">
                            <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                            @if (old('kelurahan_desa', $asn->kelurahan_desa))
                                <option value="{{ old('kelurahan_desa', $asn->kelurahan_desa) }}" selected>{{ old('kelurahan_desa', $asn->kelurahan_desa) }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap / RT RW</label>
                        <textarea name="alamat_detail" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500" placeholder="Jl. Veteran No. 12 RT 02/RW 04...">{{ old('alamat_detail', $asn->alamat_detail) }}</textarea>
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

        const initProv = @json(old('provinsi', $asn->provinsi ?? ''));
        const initKab  = @json(old('kabupaten_kota', $asn->kabupaten_kota ?? ''));
        const initKec  = @json(old('kecamatan', $asn->kecamatan ?? ''));
        const initDesa = @json(old('kelurahan_desa', $asn->kelurahan_desa ?? ''));

        function normalizeWilayah(s) {
            return (s || '').toLowerCase().replace(/^kab(\.|upaten)?\s+/i, '').replace(/^kota\s+/i, '').trim();
        }

        function geocodeAndFly(query, zoom) {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=1`)
                .then(res => res.json())
                .then(res => {
                    if (res && res.length > 0) {
                        const lat = parseFloat(res[0].lat);
                        const lng = parseFloat(res[0].lon);
                        map.flyTo([lat, lng], zoom, { duration: 1.1 });
                        updateMarkerPosition(lat, lng);
                    }
                })
                .catch(() => {});
        }

        function loadVillages(distCode, preselectName) {
            const $desa = $('#desaSelect');
            $desa.empty().append('<option value="">-- Memuat Desa / Kelurahan... --</option>').trigger('change.select2');
            if (!distCode) {
                $desa.empty().append('<option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>').trigger('change.select2');
                return;
            }
            fetch(`{{ url('/api/wilayah/villages') }}/${encodeURIComponent(distCode)}`)
                .then(r => r.json())
                .then(res => {
                    $desa.empty().append('<option value="">-- Pilih Desa / Kelurahan --</option>');
                    (res.data || []).forEach(v => {
                        const sel = preselectName && v.name.toLowerCase() === preselectName.toLowerCase();
                        $desa.append(new Option(v.name, v.name, sel, sel));
                    });
                    $desa.trigger('change.select2');
                });
        }

        function loadDistricts(regCode, preselectKec, preselectDesa) {
            const $kec = $('#kecamatanSelect');
            $kec.empty().append('<option value="">-- Memuat Kecamatan... --</option>').trigger('change.select2');
            if (!regCode) {
                $kec.empty().append('<option value="">-- Pilih Kabupaten / Kota Terlebih Dahulu --</option>').trigger('change.select2');
                return;
            }
            fetch(`{{ url('/api/wilayah/districts') }}/${encodeURIComponent(regCode)}`)
                .then(r => r.json())
                .then(res => {
                    $kec.empty().append('<option value="">-- Pilih Kecamatan --</option>');
                    let matchedCode = null;
                    (res.data || []).forEach(d => {
                        const sel = preselectKec && d.name.toLowerCase() === preselectKec.toLowerCase();
                        const opt = $(`<option value="${d.name}" data-code="${d.code}" ${sel ? 'selected' : ''}>Kec. ${d.name}</option>`);
                        $kec.append(opt);
                        if (sel) matchedCode = d.code;
                    });
                    if (preselectKec && !matchedCode) {
                        $kec.append(new Option(`Kec. ${preselectKec}`, preselectKec, true, true));
                    }
                    $kec.trigger('change.select2');
                    if (matchedCode) loadVillages(matchedCode, preselectDesa);
                });
        }

        function loadRegencies(provCode, preselectKab, preselectKec, preselectDesa) {
            const $kab = $('#kabupatenSelect');
            $kab.empty().append('<option value="">-- Memuat Kabupaten / Kota... --</option>').trigger('change.select2');
            if (!provCode) {
                $kab.empty().append('<option value="">-- Pilih Provinsi Terlebih Dahulu --</option>').trigger('change.select2');
                return;
            }
            fetch(`{{ url('/api/wilayah/regencies') }}/${encodeURIComponent(provCode)}`)
                .then(r => r.json())
                .then(res => {
                    $kab.empty().append('<option value="">-- Pilih Kabupaten / Kota --</option>');
                    let matchedCode = null;
                    (res.data || []).forEach(reg => {
                        const sel = preselectKab && (
                            reg.name.toLowerCase() === preselectKab.toLowerCase() ||
                            normalizeWilayah(reg.name) === normalizeWilayah(preselectKab)
                        );
                        const opt = $(`<option value="${reg.name}" data-code="${reg.code}" ${sel ? 'selected' : ''}>${reg.name}</option>`);
                        $kab.append(opt);
                        if (sel) matchedCode = reg.code;
                    });
                    if (preselectKab && !matchedCode) {
                        $kab.append(new Option(preselectKab, preselectKab, true, true));
                    }
                    $kab.trigger('change.select2');
                    if (matchedCode) loadDistricts(matchedCode, preselectKec, preselectDesa);
                });
        }

        fetch(`{{ route('api.wilayah.provinces') }}`)
            .then(r => r.json())
            .then(res => {
                const $prov = $('#provinsiSelect');
                $prov.empty().append('<option value="">-- Pilih Provinsi --</option>');
                let matchedCode = null;
                (res.data || []).forEach(p => {
                    const sel = initProv && p.name.toLowerCase() === initProv.toLowerCase();
                    const opt = $(`<option value="${p.name}" data-code="${p.code}" ${sel ? 'selected' : ''}>${p.name}</option>`);
                    $prov.append(opt);
                    if (sel) matchedCode = p.code;
                });
                if (initProv && !matchedCode) {
                    $prov.append(new Option(initProv, initProv, true, true));
                }
                $prov.trigger('change.select2');
                if (matchedCode) loadRegencies(matchedCode, initKab, initKec, initDesa);
            })
            .catch(err => console.warn('Gagal memuat daftar provinsi:', err));

        $('#provinsiSelect').on('change', function () {
            const code = $(this).find('option:selected').data('code');
            loadRegencies(code, null, null, null);
        });

        $('#kabupatenSelect').on('change', function () {
            const code = $(this).find('option:selected').data('code');
            const kabName = $(this).val();
            const provName = $('#provinsiSelect').val();
            loadDistricts(code, null, null);
            if (kabName) geocodeAndFly(`${kabName}, ${provName || 'Indonesia'}`, 11);
        });

        const officeLat = {{ $asn->unitSaatIni->kantor_lat ?? -6.32635 }};
        const officeLng = {{ $asn->unitSaatIni->kantor_lng ?? 108.32042 }};
        
        let curLat = parseFloat(document.getElementById('domisili_lat').value) || officeLat;
        let curLng = parseFloat(document.getElementById('domisili_lng').value) || officeLng;

        const map = L.map('pickerMap').setView([curLat, curLng], 13);

        window.attachGmapBasemap(map);

        // Marker Kantor Saat Ini (Google Maps Blue Office Pin)
        const officeMarker = L.marker([officeLat, officeLng], {
            icon: window.createGmapPinIcon({ color: '#1d4ed8', type: 'office', size: 'md' })
        }).addTo(map).bindPopup("<strong>Kantor Anda: {{ $asn->unitSaatIni->nama_unit }}</strong>");

        // Draggable Home Marker (Google Maps Red Home Pin)
        const homeMarker = L.marker([curLat, curLng], {
            draggable: true,
            icon: window.createGmapPinIcon({ color: '#ea4335', type: 'home', size: 'lg' })
        }).addTo(map).bindPopup("<strong>Titik Domisili Anda</strong><br/><span class='text-xs text-slate-500'>Geser pin ini tepat di atas atap rumah Anda</span>").openPopup();

        // Polyline rute jalan raya OSRM (fallback ke Haversine)
        let line = null;
        let routeReqSeq = 0;

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

        function setDistanceBadge(dKm, suffix) {
            const badge = document.getElementById('liveDistanceBadge');
            badge.innerText = dKm + ' km' + (suffix ? ' ' + suffix : '');
            if (parseFloat(dKm) < 5) {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-emerald-500 text-white';
            } else if (parseFloat(dKm) <= 20) {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-amber-500 text-white';
            } else {
                badge.className = 'px-2.5 py-0.5 rounded font-extrabold text-sm bg-rose-600 text-white';
            }
        }

        function drawRouteLine(coords, isOsrm, dKm) {
            const km = parseFloat(dKm);
            const routeColor = km < 5 ? '#059669' : (km <= 20 ? '#d97706' : '#e11d48');
            if (line) map.removeLayer(line);
            line = L.polyline(coords, {
                color: routeColor,
                weight: isOsrm ? 4 : 2,
                opacity: isOsrm ? 0.88 : 0.75,
                dashArray: isOsrm ? null : '5, 8'
            }).addTo(map);
        }

        function updateMarkerPosition(lat, lng) {
            document.getElementById('domisili_lat').value = lat.toFixed(7);
            document.getElementById('domisili_lng').value = lng.toFixed(7);
            homeMarker.setLatLng([lat, lng]);

            const fallbackKm = calculateHaversine(lat, lng, officeLat, officeLng);
            const reqId = ++routeReqSeq;

            fetch(`https://router.project-osrm.org/route/v1/driving/${lng},${lat};${officeLng},${officeLat}?overview=full&geometries=geojson`)
                .then(res => res.json())
                .then(data => {
                    if (reqId !== routeReqSeq) return;
                    if (data && data.code === 'Ok' && data.routes && data.routes.length > 0) {
                        const route = data.routes[0];
                        const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);
                        const osrmKm = (route.distance / 1000).toFixed(2);
                        const mins = Math.max(1, Math.round(route.duration / 60));
                        drawRouteLine(coords, true, osrmKm);
                        setDistanceBadge(osrmKm, `(~${mins} mnt • OSRM)`);
                    } else {
                        drawRouteLine([[lat, lng], [officeLat, officeLng]], false, fallbackKm);
                        setDistanceBadge(fallbackKm, '(Haversine)');
                    }
                })
                .catch(() => {
                    if (reqId !== routeReqSeq) return;
                    drawRouteLine([[lat, lng], [officeLat, officeLng]], false, fallbackKm);
                    setDistanceBadge(fallbackKm, '(Haversine)');
                });
        }

        // Gambar rute OSRM awal saat peta dimuat
        updateMarkerPosition(curLat, curLng);

        homeMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateMarkerPosition(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            updateMarkerPosition(e.latlng.lat, e.latlng.lng);
        });

        $('#kecamatanSelect').on('change', function () {
            const kecName = $(this).val();
            const distCode = $(this).find('option:selected').data('code');
            const kabName = $('#kabupatenSelect').val();
            const provName = $('#provinsiSelect').val();
            loadVillages(distCode, null);
            if (kecName) {
                geocodeAndFly(`Kecamatan ${kecName}, ${kabName || ''}, ${provName || 'Indonesia'}`, 13);
            }
        });

        $('#desaSelect').on('change', function () {
            const desaName = $(this).val();
            const kecName = $('#kecamatanSelect').val();
            const kabName = $('#kabupatenSelect').val();
            if (desaName && kecName) {
                geocodeAndFly(`${desaName}, ${kecName}, ${kabName || 'Indonesia'}`, 15);
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
                        alert(`Lokasi "${query}" tidak ditemukan di area Kabupaten XYZ. Silakan geser pin merah secara manual.`);
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

