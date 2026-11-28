@extends('layouts.app')

@section('title', 'Tambah Pegawai PNS Baru')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('asn.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Pegawai PNS Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pendaftaran pegawai, penempatan unit kerja, dan pemetaan koordinat domisili tempat tinggal.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('asn.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Data Kepegawaian (Kolom Kiri 2 Col) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4 text-blue-600"></i> Informasi Identitas & Jabatan PNS
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">NIP (18 Digit) <span class="text-rose-500">*</span></label>
                            <input type="text" name="nip" value="{{ old('nip') }}" maxlength="18" required placeholder="Contoh: 198905142015031002" class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            @error('nip')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap (Tanpa Gelar) <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Budi Santoso" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            @error('nama')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar Depan</label>
                            <input type="text" name="gelar_depan" value="{{ old('gelar_depan') }}" placeholder="Contoh: Dr., Ir., Drs." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar Belakang</label>
                            <input type="text" name="gelar_belakang" value="{{ old('gelar_belakang') }}" placeholder="Contoh: S.STP, M.Si, S.Kom" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- 1. Pilih Unit Kerja Terlebih Dahulu -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Saat Ini <span class="text-rose-500">*</span></label>
                            <select id="unitSaatIniSelect" name="id_unit_saat_ini" required class="searchable-select w-full" data-placeholder="Cari & pilih unit kerja terlebih dahulu...">
                                <option value="">-- Pilih Unit Kerja Terlebih Dahulu --</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}" data-lat="{{ $u->kantor_lat }}" data-lng="{{ $u->kantor_lng }}" {{ old('id_unit_saat_ini') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nama_unit }} ({{ $u->kategori }})
                                    </option>
                                @endforeach
                            </select>
                            @error('id_unit_saat_ini')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Induk (OPD Pengampu)</label>
                            <input type="text" name="unit_kerja_induk" value="{{ old('unit_kerja_induk') }}" placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- 2. Pilih Jabatan (Otomatis dari Formasi Tersedia pada Unit Kerja Terpilih) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan (Formasi Tersedia di Unit Kerja) <span class="text-rose-500">*</span></label>
                        <select id="formasiJabatanSelect" name="id_formasi_jabatan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Unit Kerja Terlebih Dahulu --</option>
                        </select>
                        <div id="formasiEmptyAlert" class="hidden mt-1.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-1.5">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                            <span>Tidak ada formasi jabatan yang tersedia (belum dibuka atau sudah penuh) pada unit kerja ini.</span>
                        </div>
                        @error('id_formasi_jabatan')
                            <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pangkat / Golongan <span class="text-rose-500">*</span></label>
                            <select name="pangkat_golongan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                @php
                                    $golOptions = [
                                        'I/a' => 'Juru Muda (I/a)',
                                        'I/b' => 'Juru Muda Tk. I (I/b)',
                                        'I/c' => 'Juru (I/c)',
                                        'I/d' => 'Juru Tk. I (I/d)',
                                        'II/a' => 'Pengatur Muda (II/a)',
                                        'II/b' => 'Pengatur Muda Tk. I (II/b)',
                                        'II/c' => 'Pengatur (II/c)',
                                        'II/d' => 'Pengatur Tk. I (II/d)',
                                        'III/a' => 'Penata Muda (III/a)',
                                        'III/b' => 'Penata Muda Tk. I (III/b)',
                                        'III/c' => 'Penata (III/c)',
                                        'III/d' => 'Penata Tk. I (III/d)',
                                        'IV/a' => 'Pembina (IV/a)',
                                        'IV/b' => 'Pembina Tk. I (IV/b)',
                                        'IV/c' => 'Pembina Utama Muda (IV/c)',
                                        'IV/d' => 'Pembina Utama Madya (IV/d)',
                                        'IV/e' => 'Pembina Utama (IV/e)',
                                    ];
                                @endphp
                                @foreach ($golOptions as $kodeGol => $labelGol)
                                    <option value="{{ $kodeGol }}" {{ old('pangkat_golongan', 'III/a') == $kodeGol ? 'selected' : '' }}>{{ $labelGol }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">TMT Golongan Terakhir</label>
                            <input type="date" name="tmt_golongan" value="{{ old('tmt_golongan') }}" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Tanggal Riwayat Kepegawaian Sesuai Nota Klien -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal Jabatan (TMT)</label>
                            <input type="date" name="tgl_jabatan" value="{{ old('tgl_jabatan') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Dasar masa jabatan</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal CPNS</label>
                            <input type="date" name="tgl_cpns" value="{{ old('tgl_cpns') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Dasar masa kerja</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal PNS</label>
                            <input type="date" name="tgl_pns" value="{{ old('tgl_pns') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Pengangkatan penuh</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Masa Kerja (Thn) <span class="text-rose-500">*</span></label>
                            <input type="number" name="masa_kerja_tahun" value="{{ old('masa_kerja_tahun', 5) }}" min="0" required class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Otomatis/manual</span>
                        </div>
                    </div>

                    <!-- Riwayat Pendidikan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Pendidikan</label>
                            <select name="tingkat_pendidikan" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Pilih Jenjang --</option>
                                <option value="SLTA/SMA Sederajat" {{ old('tingkat_pendidikan') == 'SLTA/SMA Sederajat' ? 'selected' : '' }}>SLTA / SMA Sederajat</option>
                                <option value="Diploma III/Sarjana Muda" {{ old('tingkat_pendidikan') == 'Diploma III/Sarjana Muda' ? 'selected' : '' }}>Diploma III (D-III)</option>
                                <option value="Diploma IV" {{ old('tingkat_pendidikan') == 'Diploma IV' ? 'selected' : '' }}>Diploma IV (D-IV)</option>
                                <option value="S-1/Sarjana" {{ old('tingkat_pendidikan', 'S-1/Sarjana') == 'S-1/Sarjana' ? 'selected' : '' }}>Strata 1 (S-1 / Sarjana)</option>
                                <option value="Profesi" {{ old('tingkat_pendidikan') == 'Profesi' ? 'selected' : '' }}>Profesi</option>
                                <option value="S-2" {{ old('tingkat_pendidikan') == 'S-2' ? 'selected' : '' }}>Strata 2 (S-2)</option>
                                <option value="S-3" {{ old('tingkat_pendidikan') == 'S-3' ? 'selected' : '' }}>Strata 3 (S-3)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pendidikan Terakhir (Jurusan)</label>
                            <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir') }}" placeholder="Contoh: S-1 ILMU PEMERINTAHAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Leaflet Map Picker dengan Pencarian & GPS -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <i data-lucide="crosshair" class="w-4 h-4 text-emerald-600"></i>
                            <span class="text-xs font-bold text-slate-800">Tentukan Titik Koordinat Domisili (Pinpoint di Peta)</span>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            💡 <em>Geser pin merah tepat di atas rumah atau klik langsung pada peta</em>
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
                        <button type="button" id="btnGps" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5" title="Gunakan GPS perangkat Anda">
                            <i data-lucide="locate-fixed" class="w-3.5 h-3.5"></i> GPS Saya
                        </button>
                    </div>

                    <div id="pickerMap" style="height: 380px;" class="w-full relative z-0"></div>

                    <div class="px-5 py-3 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Titik Koordinat:</span>
                            <span id="coordBadge" class="font-mono text-emerald-400 font-bold">-6.326350, 108.320420</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Jarak ke Unit Kerja:</span>
                            <span id="liveDistanceBadge" class="px-2 py-0.5 rounded font-extrabold text-xs bg-emerald-500 text-white">0.00 km</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Wilayah & Submit (Kolom Kanan 1 Col) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i> Wilayah Domisili (Wilayah.id)
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude</label>
                            <input type="text" id="domisili_lat" name="domisili_lat" value="{{ old('domisili_lat', -6.326350) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                            <input type="text" id="domisili_lng" name="domisili_lng" value="{{ old('domisili_lng', 108.320420) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-700">
                        </div>
                    </div>

                    <!-- Cascading Wilayah Administratif (Wilayah.id se-Indonesia) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi <span class="text-rose-500">*</span></label>
                        <select id="provinsiSelect" name="provinsi" required class="searchable-select w-full" data-placeholder="-- Pilih Provinsi --">
                            <option value="">-- Memuat Provinsi... --</option>
                            @if (old('provinsi'))
                                <option value="{{ old('provinsi') }}" selected>{{ old('provinsi') }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
                        <select id="kabupatenSelect" name="kabupaten_kota" required class="searchable-select w-full" data-placeholder="-- Pilih Kabupaten / Kota --">
                            <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                            @if (old('kabupaten_kota'))
                                <option value="{{ old('kabupaten_kota') }}" selected>{{ old('kabupaten_kota') }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan <span class="text-rose-500">*</span></label>
                        <select id="kecamatanSelect" name="kecamatan" required class="searchable-select w-full" data-placeholder="-- Pilih Kecamatan --">
                            <option value="">-- Pilih Kabupaten / Kota Terlebih Dahulu --</option>
                            @if (old('kecamatan'))
                                <option value="{{ old('kecamatan') }}" selected>{{ old('kecamatan') }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan</label>
                        <select id="desaSelect" name="kelurahan_desa" class="searchable-select w-full" data-placeholder="-- Pilih Desa / Kelurahan --">
                            <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                            @if (old('kelurahan_desa'))
                                <option value="{{ old('kelurahan_desa') }}" selected>{{ old('kelurahan_desa') }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap / RT RW</label>
                        <textarea name="alamat_detail" rows="2" placeholder="Jl. Raya No. 12 RT 01/RW 02..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('alamat_detail') }}</textarea>
                    </div>

                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-800 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-600"></i> Akun Login Otomatis
                        </div>
                        <p class="text-[11px] text-blue-700 leading-relaxed">
                            Setelah disimpan, sistem akan secara otomatis membuat akun login PNS mandiri menggunakan 18-digit NIP dengan kata sandi bawaan: <code class="font-mono bg-blue-100 px-1 py-0.5 rounded">NIP Pegawai (18 digit)</code>.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <a href="{{ route('asn.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            Simpan & Daftarkan
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
        let wilayahIndramayu = {};

        // Inisialisasi Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $('.searchable-select').select2({
                width: '100%',
                placeholder: function(){ return $(this).data('placeholder'); },
                allowClear: true
            });

            $('.searchable-select-jabatan').select2({
                width: '100%',
                placeholder: '-- Pilih atau Ketik Nama Jabatan --',
                allowClear: true,
                tags: true
            }).on('change', function () {
                const selectedOption = $(this).find('option:selected');
                const jenis = selectedOption.data('jenis');
                if (jenis) {
                    $('#jenisJabatanSelect').val(jenis);
                }
            });
        }

        // Cascading Wilayah.id se-Indonesia (Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa/Kelurahan)
        const initProv = @json(old('provinsi', ''));
        const initKab  = @json(old('kabupaten_kota', ''));
        const initKec  = @json(old('kecamatan', ''));
        const initDesa = @json(old('kelurahan_desa', ''));

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
                        marker.setLatLng([lat, lng]);
                        updateCoords(lat, lng);
                    }
                })
                .catch(() => {});
        }

        window.initWilayahCascade({
            provSelector: '#provinsiSelect',
            kabSelector: '#kabupatenSelect',
            kecSelector: '#kecamatanSelect',
            desaSelector: '#desaSelect',
            initProv: initProv,
            initKab: initKab,
            initKec: initKec,
            initDesa: initDesa,
            onRegencyChange: function (kabName, provName) {
                if (kabName) geocodeAndFly(`${kabName}, ${provName || 'Indonesia'}`, 11);
            },
            onDistrictChange: function (kecName, kabName, provName) {
                if (kecName) geocodeAndFly(`Kecamatan ${kecName}, ${kabName || ''}, ${provName || 'Indonesia'}`, 13);
            },
            onVillageChange: function (desaName, kecName, kabName, provName) {
                if (desaName) geocodeAndFly(`${desaName}, Kecamatan ${kecName || ''}, ${kabName || ''}, ${provName || 'Indonesia'}`, 15);
            }
        });

        // Peta Leaflet & Draggable Marker
        let defaultLat = parseFloat(document.getElementById('domisili_lat').value) || -6.326350;
        let defaultLng = parseFloat(document.getElementById('domisili_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 13);
        window.attachGmapBasemap(map);

        // Marker Domisili (Google Maps Red Draggable Pin)
        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true,
            icon: window.createGmapPinIcon({ color: '#ea4335', type: 'home', size: 'lg' })
        }).addTo(map).bindPopup("<strong>Titik Domisili Pegawai</strong><br/><span class='text-xs text-slate-500'>Geser pin ini tepat di atas atap rumah Anda</span>").openPopup();

        // Marker Kantor Saat Ini (Fixed Blue)
        let officeMarker = null;
        let connectLine = null;
        let routeReqSeq = 0;

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

        function setDistanceBadge(dKm, suffix) {
            const badge = document.getElementById('liveDistanceBadge');
            badge.innerText = dKm + ' km' + (suffix ? ' ' + suffix : '');
            if (parseFloat(dKm) < 5) badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-emerald-500 text-white';
            else if (parseFloat(dKm) <= 20) badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-amber-500 text-white';
            else badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-rose-600 text-white';
        }

        function drawRouteLine(coords, isOsrm, dKm) {
            const km = parseFloat(dKm);
            const routeColor = km < 5 ? '#059669' : (km <= 20 ? '#d97706' : '#e11d48');
            if (connectLine) {
                map.removeLayer(connectLine);
            }
            connectLine = L.polyline(coords, {
                color: routeColor,
                weight: isOsrm ? 4 : 2,
                opacity: isOsrm ? 0.88 : 0.75,
                dashArray: isOsrm ? null : '5, 8'
            }).addTo(map);
        }

        function updateDistanceAndLine(lat, lng) {
            const unitOpt = $('#unitSaatIniSelect option:selected');
            const offLat = parseFloat(unitOpt.data('lat'));
            const offLng = parseFloat(unitOpt.data('lng'));

            if (offLat && offLng) {
                if (!officeMarker) {
                    officeMarker = L.marker([offLat, offLng], {
                        icon: window.createGmapPinIcon({ color: '#1d4ed8', type: 'office', size: 'md' })
                    }).addTo(map);
                } else {
                    officeMarker.setLatLng([offLat, offLng]);
                }
                officeMarker.bindPopup(`<strong>Kantor: ${unitOpt.text().trim()}</strong>`);

                const fallbackKm = calculateHaversine(lat, lng, offLat, offLng);
                const reqId = ++routeReqSeq;

                fetch(`https://router.project-osrm.org/route/v1/driving/${lng},${lat};${offLng},${offLat}?overview=full&geometries=geojson`)
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
                            drawRouteLine([[lat, lng], [offLat, offLng]], false, fallbackKm);
                            setDistanceBadge(fallbackKm, '(Haversine)');
                        }
                    })
                    .catch(() => {
                        if (reqId !== routeReqSeq) return;
                        drawRouteLine([[lat, lng], [offLat, offLng]], false, fallbackKm);
                        setDistanceBadge(fallbackKm, '(Haversine)');
                    });
            }
        }

        function updateCoords(lat, lng) {
            document.getElementById('domisili_lat').value = lat.toFixed(6);
            document.getElementById('domisili_lng').value = lng.toFixed(6);
            document.getElementById('coordBadge').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            updateDistanceAndLine(lat, lng);
        }

        marker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });

        // Filter dinamis Formasi Jabatan berdasarkan Unit Kerja yang dipilih
        const allFormasiList = @json($formasiList);
        const oldFormasiId = "{{ old('id_formasi_jabatan') }}";

        function populateFormasiByUnit(unitId, selectedFormasiId) {
            const sel = document.getElementById('formasiJabatanSelect');
            const alertBox = document.getElementById('formasiEmptyAlert');
            if (!sel) return;

            sel.innerHTML = '';
            if (!unitId) {
                sel.innerHTML = '<option value="">-- Pilih Unit Kerja Terlebih Dahulu --</option>';
                alertBox?.classList.add('hidden');
                return;
            }

            const available = allFormasiList.filter(f => String(f.id_unit_kerja) === String(unitId) && f.kuota_lowong > 0);
            if (available.length === 0) {
                sel.innerHTML = '<option value="">-- Tidak ada formasi jabatan tersedia / penuh --</option>';
                alertBox?.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
                return;
            }

            alertBox?.classList.add('hidden');
            sel.innerHTML = '<option value="">-- Pilih Jabatan Tersedia (' + available.length + ' Formasi Lowong) --</option>';
            available.forEach(f => {
                const jenisLabel = f.jenis_jabatan === 'fungsional' ? 'Fungsional' : 'Pelaksana';
                const jenjangTxt = (f.jenis_jabatan === 'fungsional' && f.jenjang_jabatan) ? ` - ${f.jenjang_jabatan}` : '';
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = `${f.nama_jabatan} [${jenisLabel}${jenjangTxt}] — Lowong ${f.kuota_lowong} dari ${f.kebutuhan_abk}`;
                if (selectedFormasiId && String(selectedFormasiId) === String(f.id)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
        }

        // Event saat Unit Kerja berubah
        $('#unitSaatIniSelect').on('change', function () {
            populateFormasiByUnit($(this).val(), null);
            const pos = marker.getLatLng();
            updateDistanceAndLine(pos.lat, pos.lng);
        });

        // Inisialisasi formasi jika old('id_unit_saat_ini') sudah terisi
        if ($('#unitSaatIniSelect').val()) {
            populateFormasiByUnit($('#unitSaatIniSelect').val(), oldFormasiId);
        }

        // Fitur Pencarian Alamat / Patokan (Search Bar seperti Satpol PP)
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
                        marker.setLatLng([lat, lng]);
                        updateCoords(lat, lng);
                        marker.bindPopup(`<strong>Hasil Pencarian:</strong><br/>${top.display_name}`).openPopup();
                    } else {
                        alert(`Lokasi "${query}" tidak ditemukan di area Kabupaten XYZ. Silakan geser pin merah secara manual pada peta.`);
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

        // Fitur GPS Perangkat
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
                        map.flyTo([lat, lng], 17, { duration: 1.2 });
                        marker.setLatLng([lat, lng]);
                        updateCoords(lat, lng);
                        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Terdeteksi';
                        if (window.lucide) lucide.createIcons();
                    },
                    (err) => {
                        alert('Tidak dapat mendeteksi GPS: ' + err.message);
                        btn.innerHTML = orig;
                        if (window.lucide) lucide.createIcons();
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert('Browser Anda tidak mendukung deteksi geolokasi GPS.');
            }
        });

        // Trigger perhitungan awal
        updateDistanceAndLine(defaultLat, defaultLng);
    });
</script>
@endpush

