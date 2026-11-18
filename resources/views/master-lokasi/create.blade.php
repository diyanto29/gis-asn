@extends('layouts.app')

@section('title', 'Tambah Master Wilayah Baru')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('master-lokasi.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Master Wilayah / Kecamatan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Menambahkan data batas dan koordinat pusat kecamatan di Kabupaten Indramayu.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('master-lokasi.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Peta Pinpoint Leaflet (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-xs font-bold text-slate-800">Klik di peta atau geser pin untuk menentukan titik pusat kecamatan</span>
                    </div>
                </div>

                <!-- Leaflet Container -->
                <div id="pickerMap" style="height: 480px;" class="w-full relative z-0"></div>

                <div class="px-5 py-3 bg-slate-900 text-white flex items-center justify-between text-xs">
                    <span class="text-slate-400">Koordinat Terpilih:</span>
                    <span id="coordBadge" class="font-mono text-emerald-400 font-bold">-6.326350, 108.320420</span>
                </div>
            </div>

            <!-- Form Inputs (1 Column) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">
                        Data Identitas Wilayah
                    </h3>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kemendagri / Wilayah <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_kecamatan" value="{{ old('kode_kecamatan') }}" required placeholder="Contoh: 32.12.32" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 font-mono focus:border-blue-500 focus:ring-blue-500">
                        @error('kode_kecamatan')
                            <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kecamatan <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_kecamatan" value="{{ old('nama_kecamatan') }}" required placeholder="Nama Kecamatan..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        @error('nama_kecamatan')
                            <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude Pusat <span class="text-rose-500">*</span></label>
                            <input type="text" id="pusat_lat" name="pusat_lat" value="{{ old('pusat_lat', -6.326350) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude Pusat <span class="text-rose-500">*</span></label>
                            <input type="text" id="pusat_lng" name="pusat_lng" value="{{ old('pusat_lng', 108.320420) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Desa / Kelurahan <span class="text-rose-500">*</span></label>
                        <input type="number" name="jumlah_desa" value="{{ old('jumlah_desa', 10) }}" min="0" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Karakteristik / Keterangan Wilayah</label>
                        <textarea name="keterangan" rows="3" placeholder="Contoh: Kawasan sentra pertanian lumbung padi..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <a href="{{ route('master-lokasi.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            Simpan Wilayah
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
    document.addEventListener('DOMContentLoaded', function () {
        const defaultLat = parseFloat(document.getElementById('pusat_lat').value) || -6.326350;
        const defaultLng = parseFloat(document.getElementById('pusat_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map);

        function updateCoords(lat, lng) {
            document.getElementById('pusat_lat').value = lat.toFixed(6);
            document.getElementById('pusat_lng').value = lng.toFixed(6);
            document.getElementById('coordBadge').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        marker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
    });
</script>
@endpush

