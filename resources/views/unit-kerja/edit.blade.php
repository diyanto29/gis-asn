@extends('layouts.app')

@section('title', 'Edit Unit Kerja: ' . $unit->nama_unit)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('unit-kerja.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Unit Kerja</h1>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $unit->nama_unit }} ({{ $unit->kode_unit }})</p>
        </div>
    </div>

    <form method="POST" action="{{ route('unit-kerja.update', $unit->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Peta Pinpoint Lokasi Kantor (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="building-2" class="w-4 h-4 text-blue-600"></i>
                        <span class="text-xs font-bold text-slate-800">Klik di peta atau geser pin untuk memperbarui lokasi kantor absensi</span>
                    </div>
                </div>
                <div id="pickerMap" style="height: 480px;" class="w-full relative z-0"></div>
                <div class="px-5 py-3 bg-slate-900 text-white flex items-center justify-between text-xs">
                    <span class="text-slate-400">Koordinat Kantor:</span>
                    <span id="coordBadge" class="font-mono text-emerald-400 font-bold">{{ number_format((float)$unit->kantor_lat, 6) }}, {{ number_format((float)$unit->kantor_lng, 6) }}</span>
                </div>
            </div>

            <!-- Form Card (1 Column) -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">Identitas &amp; Wilayah Kantor</h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Unit Kerja <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_unit" value="{{ old('nama_unit', $unit->nama_unit) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="kategori" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="Dinas" {{ $unit->kategori === 'Dinas' ? 'selected' : '' }}>Dinas</option>
                        <option value="Badan" {{ $unit->kategori === 'Badan' ? 'selected' : '' }}>Badan</option>
                        <option value="Kecamatan" {{ $unit->kategori === 'Kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                        <option value="UPTD" {{ $unit->kategori === 'UPTD' ? 'selected' : '' }}>UPTD / Rumah Sakit</option>
                        <option value="Sekolah" {{ $unit->kategori === 'Sekolah' ? 'selected' : '' }}>Sekolah</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude</label>
                        <input type="text" id="kantor_lat" name="kantor_lat" value="{{ old('kantor_lat', $unit->kantor_lat) }}" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                        <input type="text" id="kantor_lng" name="kantor_lng" value="{{ old('kantor_lng', $unit->kantor_lng) }}" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5">
                    </div>
                </div>

                <!-- Wilayah Kantor (Wilayah.id: Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa) -->
                <div class="pt-2 border-t border-slate-100 space-y-3">
                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i> Wilayah Kantor (Wilayah.id)
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Provinsi</label>
                        <select id="ukProvinsi" class="searchable-select w-full" data-placeholder="-- Pilih Provinsi --">
                            <option value="">-- Memuat Provinsi... --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kabupaten / Kota</label>
                        <select id="ukKabupaten" class="searchable-select w-full" data-placeholder="-- Pilih Kabupaten / Kota --">
                            <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kecamatan</label>
                        <select id="ukKecamatan" class="searchable-select w-full" data-placeholder="-- Pilih Kecamatan --">
                            <option value="">-- Pilih Kabupaten / Kota Terlebih Dahulu --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Desa / Kelurahan</label>
                        <select id="ukDesa" class="searchable-select w-full" data-placeholder="-- Pilih Desa / Kelurahan --">
                            <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Kantor</label>
                    <textarea id="alamatKantorInput" name="alamat_kantor" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2.5">{{ old('alamat_kantor', $unit->alamat_kantor) }}</textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <a href="{{ route('unit-kerja.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const defaultLat = parseFloat(document.getElementById('kantor_lat').value) || -6.326350;
        const defaultLng = parseFloat(document.getElementById('kantor_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 14);
        window.attachGmapBasemap(map);

        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true,
            icon: window.createGmapPinIcon({ color: '#1d4ed8', type: 'office', size: 'lg' })
        }).addTo(map);

        function updateCoords(lat, lng) {
            document.getElementById('kantor_lat').value = lat.toFixed(6);
            document.getElementById('kantor_lng').value = lng.toFixed(6);
            document.getElementById('coordBadge').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        marker.on('dragend', () => {
            const p = marker.getLatLng();
            updateCoords(p.lat, p.lng);
        });
        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });

        if (typeof $.fn.select2 !== 'undefined') {
            $('.searchable-select').select2({ width: '100%', allowClear: true });
        }

        function syncAlamatFromWilayah() {
            const parts = [
                $('#ukDesa').val(),
                $('#ukKecamatan').val() ? 'Kec. ' + $('#ukKecamatan').val() : '',
                $('#ukKabupaten').val(),
                $('#ukProvinsi').val()
            ].filter(Boolean);
            if (parts.length > 0) {
                document.getElementById('alamatKantorInput').value = parts.join(', ');
            }
        }

        function flyToWilayah(q, zoom) {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&countrycodes=id&limit=1`)
                .then(r => r.json())
                .then(res => {
                    if (res && res.length > 0) {
                        const lat = parseFloat(res[0].lat), lng = parseFloat(res[0].lon);
                        map.flyTo([lat, lng], zoom, { duration: 1.0 });
                        marker.setLatLng([lat, lng]);
                        updateCoords(lat, lng);
                    }
                }).catch(() => {});
        }

        window.initWilayahCascade({
            provSelector: '#ukProvinsi',
            kabSelector: '#ukKabupaten',
            kecSelector: '#ukKecamatan',
            desaSelector: '#ukDesa',
            prefixKec: false,
            onRegencyChange: function (kabName, provName) {
                if (!kabName) return;
                syncAlamatFromWilayah();
                flyToWilayah(`${kabName}, ${provName}, Indonesia`, 11);
            },
            onDistrictChange: function (kecName, kabName, provName) {
                if (!kecName) return;
                syncAlamatFromWilayah();
                flyToWilayah(`Kecamatan ${kecName}, ${kabName}, ${provName}, Indonesia`, 13);
            },
            onVillageChange: function (desaName, kecName, kabName, provName) {
                if (!desaName) return;
                syncAlamatFromWilayah();
                flyToWilayah(`${desaName}, Kecamatan ${kecName}, ${kabName}, Indonesia`, 15);
            }
        });
    });
</script>
@endpush
