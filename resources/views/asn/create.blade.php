@extends('layouts.app')

@section('title', 'Tambah Pegawai ASN Baru')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('asn.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Pegawai ASN Baru</h1>
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
                        <i data-lucide="user-check" class="w-4 h-4 text-blue-600"></i> Informasi Identitas & Jabatan ASN
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

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jabatan (Get Data / Ketik) <span class="text-rose-500">*</span></label>
                            <input list="jabatanListOptions" type="text" name="jabatan" value="{{ old('jabatan') }}" required placeholder="Pilih atau ketik nama jabatan..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            <datalist id="jabatanListOptions">
                                @foreach ($jabatanList as $jbt)
                                    <option value="{{ $jbt->nama_jabatan }}">{{ $jbt->jenis_jabatan ? ucfirst($jbt->jenis_jabatan) : '' }}</option>
                                @endforeach
                            </datalist>
                            @error('jabatan')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Jabatan <span class="text-rose-500">*</span></label>
                            <select name="jenis_jabatan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                <option value="fungsional" {{ old('jenis_jabatan') == 'fungsional' ? 'selected' : '' }}>Fungsional</option>
                                <option value="pelaksana" {{ old('jenis_jabatan', 'pelaksana') == 'pelaksana' ? 'selected' : '' }}>Pelaksana</option>
                                <option value="struktural" {{ old('jenis_jabatan') == 'struktural' ? 'selected' : '' }}>Struktural</option>
                            </select>
                            @error('jenis_jabatan')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pangkat / Golongan <span class="text-rose-500">*</span></label>
                            <select name="pangkat_golongan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                <option value="Juru Muda (I/a)" {{ old('pangkat_golongan') == 'Juru Muda (I/a)' ? 'selected' : '' }}>Juru Muda (I/a)</option>
                                <option value="Juru Muda Tk. I (I/b)" {{ old('pangkat_golongan') == 'Juru Muda Tk. I (I/b)' ? 'selected' : '' }}>Juru Muda Tk. I (I/b)</option>
                                <option value="Juru (I/c)" {{ old('pangkat_golongan') == 'Juru (I/c)' ? 'selected' : '' }}>Juru (I/c)</option>
                                <option value="Juru Tk. I (I/d)" {{ old('pangkat_golongan') == 'Juru Tk. I (I/d)' ? 'selected' : '' }}>Juru Tk. I (I/d)</option>
                                <option value="Pengatur Muda (II/a)" {{ old('pangkat_golongan') == 'Pengatur Muda (II/a)' ? 'selected' : '' }}>Pengatur Muda (II/a)</option>
                                <option value="Pengatur Muda Tk. I (II/b)" {{ old('pangkat_golongan') == 'Pengatur Muda Tk. I (II/b)' ? 'selected' : '' }}>Pengatur Muda Tk. I (II/b)</option>
                                <option value="Pengatur (II/c)" {{ old('pangkat_golongan') == 'Pengatur (II/c)' ? 'selected' : '' }}>Pengatur (II/c)</option>
                                <option value="Pengatur Tk. I (II/d)" {{ old('pangkat_golongan') == 'Pengatur Tk. I (II/d)' ? 'selected' : '' }}>Pengatur Tk. I (II/d)</option>
                                <option value="Penata Muda (III/a)" {{ old('pangkat_golongan') == 'Penata Muda (III/a)' ? 'selected' : '' }}>Penata Muda (III/a)</option>
                                <option value="Penata Muda Tk. I (III/b)" {{ old('pangkat_golongan') == 'Penata Muda Tk. I (III/b)' ? 'selected' : '' }}>Penata Muda Tk. I (III/b)</option>
                                <option value="Penata (III/c)" {{ old('pangkat_golongan', 'Penata (III/c)') == 'Penata (III/c)' ? 'selected' : '' }}>Penata (III/c)</option>
                                <option value="Penata Tk. I (III/d)" {{ old('pangkat_golongan') == 'Penata Tk. I (III/d)' ? 'selected' : '' }}>Penata Tk. I (III/d)</option>
                                <option value="Pembina (IV/a)" {{ old('pangkat_golongan') == 'Pembina (IV/a)' ? 'selected' : '' }}>Pembina (IV/a)</option>
                                <option value="Pembina Tk. I (IV/b)" {{ old('pangkat_golongan') == 'Pembina Tk. I (IV/b)' ? 'selected' : '' }}>Pembina Tk. I (IV/b)</option>
                                <option value="Pembina Utama Muda (IV/c)" {{ old('pangkat_golongan') == 'Pembina Utama Muda (IV/c)' ? 'selected' : '' }}>Pembina Utama Muda (IV/c)</option>
                                <option value="Pembina Utama Madya (IV/d)" {{ old('pangkat_golongan') == 'Pembina Utama Madya (IV/d)' ? 'selected' : '' }}>Pembina Utama Madya (IV/d)</option>
                                <option value="Pembina Utama (IV/e)" {{ old('pangkat_golongan') == 'Pembina Utama (IV/e)' ? 'selected' : '' }}>Pembina Utama (IV/e)</option>
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
                                <option value="SLTA" {{ old('tingkat_pendidikan') == 'SLTA' ? 'selected' : '' }}>SLTA / Sederajat</option>
                                <option value="D-III" {{ old('tingkat_pendidikan') == 'D-III' ? 'selected' : '' }}>Diploma III (D-III)</option>
                                <option value="D-IV" {{ old('tingkat_pendidikan') == 'D-IV' ? 'selected' : '' }}>Diploma IV (D-IV)</option>
                                <option value="S-1" {{ old('tingkat_pendidikan', 'S-1') == 'S-1' ? 'selected' : '' }}>Strata 1 (S-1)</option>
                                <option value="S-2" {{ old('tingkat_pendidikan') == 'S-2' ? 'selected' : '' }}>Strata 2 (S-2)</option>
                                <option value="S-3" {{ old('tingkat_pendidikan') == 'S-3' ? 'selected' : '' }}>Strata 3 (S-3)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pendidikan Terakhir (Jurusan)</label>
                            <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir') }}" placeholder="Contoh: S-1 ILMU PEMERINTAHAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Saat Ini <span class="text-rose-500">*</span></label>
                            <select id="unitSaatIniSelect" name="id_unit_saat_ini" required class="searchable-select w-full" data-placeholder="Cari nama dinas / kantor unit kerja...">
                                <option value="">-- Cari & Pilih Unit Kerja (1.080 Unit) --</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}" data-lat="{{ $u->kantor_lat }}" data-lng="{{ $u->kantor_lng }}" {{ old('id_unit_saat_ini') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nama_unit }} ({{ $u->kategori }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Induk (OPD Pengampu)</label>
                            <input type="text" name="unit_kerja_induk" value="{{ old('unit_kerja_induk') }}" placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
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
                            <input type="text" id="mapSearchInput" placeholder="Ketik nama jalan, desa, patokan di Indramayu (mis: Jl. Veteran, Haurgeulis)..." class="w-full text-xs pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
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

                    <!-- Cascading Wilayah Administratif -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi</label>
                            <input type="text" name="provinsi" value="Jawa Barat" readonly class="w-full text-xs bg-slate-100 border border-slate-300 rounded-lg p-2 text-slate-600 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota</label>
                            <input type="text" name="kabupaten_kota" value="Kabupaten Indramayu" readonly class="w-full text-xs bg-slate-100 border border-slate-300 rounded-lg p-2 text-slate-600 font-medium">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan (Indramayu) <span class="text-rose-500">*</span></label>
                        <select id="kecamatanSelect" name="kecamatan" required class="searchable-select w-full" data-placeholder="-- Pilih Kecamatan --">
                            <option value="">-- Pilih Kecamatan Indramayu --</option>
                            @foreach ($kecamatans as $kec)
                                <option value="{{ $kec->nama_kecamatan }}" data-lat="{{ $kec->pusat_lat }}" data-lng="{{ $kec->pusat_lng }}" {{ old('kecamatan') == $kec->nama_kecamatan ? 'selected' : '' }}>
                                    Kec. {{ $kec->nama_kecamatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan <span class="text-rose-500">*</span></label>
                        <select id="desaSelect" name="kelurahan_desa" required class="searchable-select w-full" data-placeholder="-- Pilih Desa / Kelurahan --">
                            <option value="{{ old('kelurahan_desa') }}">{{ old('kelurahan_desa') ?: '-- Pilih Kecamatan Terlebih Dahulu --' }}</option>
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
                            Setelah disimpan, sistem akan secara otomatis membuat akun login ASN mandiri menggunakan 18-digit NIP dengan kata sandi bawaan: <code class="font-mono bg-blue-100 px-1 py-0.5 rounded">password123</code>.
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
        }

        // Ambil Data Wilayah (Wilayah.id offline fallback lokal)
        fetch('/data/indramayu_wilayah.json')
            .then(res => res.json())
            .then(data => {
                wilayahIndramayu = data;
                const initialKec = $('#kecamatanSelect').val();
                if (initialKec) {
                    populateDesa(initialKec, "{{ old('kelurahan_desa') }}");
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

        // Peta Leaflet & Draggable Marker
        let defaultLat = parseFloat(document.getElementById('domisili_lat').value) || -6.326350;
        let defaultLng = parseFloat(document.getElementById('domisili_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Marker Domisili (Red Draggable Pin)
        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map).bindPopup("<strong>Titik Domisili Pegawai</strong><br/><span class='text-xs text-slate-500'>Geser pin ini tepat di atas atap rumah Anda</span>").openPopup();

        // Marker Kantor Saat Ini (Fixed Blue)
        let officeMarker = null;
        let connectLine = null;

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

        function updateDistanceAndLine(lat, lng) {
            const unitOpt = $('#unitSaatIniSelect option:selected');
            const offLat = parseFloat(unitOpt.data('lat'));
            const offLng = parseFloat(unitOpt.data('lng'));

            if (offLat && offLng) {
                if (!officeMarker) {
                    officeMarker = L.circleMarker([offLat, offLng], {
                        radius: 8,
                        fillColor: '#1d4ed8',
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 1
                    }).addTo(map);
                } else {
                    officeMarker.setLatLng([offLat, offLng]);
                }
                officeMarker.bindPopup(`<strong>Kantor: ${unitOpt.text().trim()}</strong>`);

                if (!connectLine) {
                    connectLine = L.polyline([[lat, lng], [offLat, offLng]], {
                        color: '#dc2626',
                        weight: 2,
                        dashArray: '5, 8'
                    }).addTo(map);
                } else {
                    connectLine.setLatLngs([[lat, lng], [offLat, offLng]]);
                }

                const dKm = calculateHaversine(lat, lng, offLat, offLng);
                const badge = document.getElementById('liveDistanceBadge');
                badge.innerText = dKm + ' km';
                if (dKm < 5) badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-emerald-500 text-white';
                else if (dKm <= 20) badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-amber-500 text-white';
                else badge.className = 'px-2 py-0.5 rounded font-extrabold text-xs bg-rose-600 text-white';
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

        // Event saat Unit Kerja berubah
        $('#unitSaatIniSelect').on('change', function () {
            const pos = marker.getLatLng();
            updateDistanceAndLine(pos.lat, pos.lng);
        });

        // Event saat Kecamatan berubah: Auto populate Desa & Geser Peta ke Pusat Kecamatan
        $('#kecamatanSelect').on('change', function () {
            const kecName = $(this).val();
            const opt = $(this).find('option:selected');
            const lat = parseFloat(opt.data('lat'));
            const lng = parseFloat(opt.data('lng'));

            if (lat && lng) {
                map.flyTo([lat, lng], 14, { duration: 1.2 });
                marker.setLatLng([lat, lng]);
                updateCoords(lat, lng);
            }

            if (kecName) {
                populateDesa(kecName, null);
            }
        });

        // Event saat Desa berubah: Geser Peta ke Desa
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
                            marker.setLatLng([lat, lng]);
                            updateCoords(lat, lng);
                        }
                    })
                    .catch(err => console.log('Geosearch desa fallback:', err));
            }
        });

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
                        alert(`Lokasi "${query}" tidak ditemukan di area Indramayu. Silakan geser pin merah secara manual pada peta.`);
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

