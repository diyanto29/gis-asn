@extends('layouts.app')

@section('title', 'Tambah Unit Kerja Baru')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('unit-kerja.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Unit Kerja Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Mendaftarkan instansi dan titik koordinat lokasi kantor.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('unit-kerja.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Peta Pinpoint Lokasi Kantor (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="building-2" class="w-4 h-4 text-blue-600"></i>
                        <span class="text-xs font-bold text-slate-800">Klik di peta atau geser pin untuk menentukan lokasi kantor absensi</span>
                    </div>
                </div>

                <!-- Leaflet Container -->
                <div id="pickerMap" style="height: 480px;" class="w-full relative z-0"></div>

                <div class="px-5 py-3 bg-slate-900 text-white flex items-center justify-between text-xs">
                    <span class="text-slate-400">Koordinat Kantor Terpilih:</span>
                    <span id="coordBadge" class="font-mono text-emerald-400 font-bold">-6.326350, 108.320420</span>
                </div>
            </div>

            <!-- Form Inputs (1 Column) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">
                        Identitas Unit Kerja
                    </h3>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Unit Kerja <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_unit" value="{{ old('kode_unit') }}" required placeholder="Contoh: SKPD-010" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 font-mono focus:border-blue-500 focus:ring-blue-500">
                        @error('kode_unit')
                            <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Unit Kerja <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_unit" value="{{ old('nama_unit') }}" required placeholder="Contoh: Dinas Kesehatan..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        @error('nama_unit')
                            <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Instansi <span class="text-rose-500">*</span></label>
                        <select name="kategori" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            <option value="Dinas" {{ old('kategori') == 'Dinas' ? 'selected' : '' }}>Dinas Daerah</option>
                            <option value="Badan" {{ old('kategori') == 'Badan' ? 'selected' : '' }}>Badan Daerah</option>
                            <option value="Kecamatan" {{ old('kategori') == 'Kecamatan' ? 'selected' : '' }}>Kantor Kecamatan</option>
                            <option value="UPTD" {{ old('kategori') == 'UPTD' ? 'selected' : '' }}>UPTD / Rumah Sakit / Puskesmas</option>
                            <option value="Sekolah" {{ old('kategori') == 'Sekolah' ? 'selected' : '' }}>Sekolah / Satuan Pendidikan</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude Kantor <span class="text-rose-500">*</span></label>
                            <input type="text" id="kantor_lat" name="kantor_lat" value="{{ old('kantor_lat', -6.326350) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude Kantor <span class="text-rose-500">*</span></label>
                            <input type="text" id="kantor_lng" name="kantor_lng" value="{{ old('kantor_lng', 108.320420) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Kantor Lengkap</label>
                        <textarea name="alamat_kantor" rows="2" placeholder="Jl. Mayor Oking No. 1, Kabupaten XYZ..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('alamat_kantor') }}</textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <a href="{{ route('unit-kerja.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            Simpan Unit Kerja
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
        const defaultLat = parseFloat(document.getElementById('kantor_lat').value) || -6.326350;
        const defaultLng = parseFloat(document.getElementById('kantor_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 12);
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

