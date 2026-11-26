@extends('layouts.app')

@section('title', 'Edit Data Pegawai: ' . $asn->nama_lengkap)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('asn.show', $asn->id) }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Data Pegawai PNS</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $asn->nama_lengkap }} &bull; NIP: {{ $asn->nip }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('asn.update', $asn->id) }}">
        @csrf
        @method('PUT')

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
                            <input type="text" name="nip" value="{{ old('nip', $asn->nip) }}" maxlength="18" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            @error('nip')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap (Tanpa Gelar) <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama', $asn->nama) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                            @error('nama')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar Depan</label>
                            <input type="text" name="gelar_depan" value="{{ old('gelar_depan', $asn->gelar_depan) }}" placeholder="Contoh: Dr., Ir." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar Belakang</label>
                            <input type="text" name="gelar_belakang" value="{{ old('gelar_belakang', $asn->gelar_belakang) }}" placeholder="Contoh: S.IP, M.Si" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jabatan (Get Data / Ketik) <span class="text-rose-500">*</span></label>
                            <select id="jabatanSelect" name="jabatan" class="searchable-select-jabatan w-full text-sm" required data-placeholder="-- Pilih atau Ketik Nama Jabatan --">
                                <option value="">-- Pilih atau Ketik Nama Jabatan --</option>
                                @foreach ($jabatanList as $jbt)
                                    <option value="{{ $jbt->nama_jabatan }}" data-jenis="{{ $jbt->jenis_jabatan }}" data-kelas="{{ $jbt->kelas_jabatan }}" data-bup="{{ $jbt->bup_usia }}" {{ strcasecmp(old('jabatan', $asn->jabatan), $jbt->nama_jabatan) === 0 ? 'selected' : '' }}>
                                        {{ $jbt->nama_jabatan }} [{{ ucfirst($jbt->jenis_jabatan) }}{{ $jbt->kelas_jabatan ? ' • Kls ' . $jbt->kelas_jabatan : '' }}]
                                    </option>
                                @endforeach
                                @if(old('jabatan', $asn->jabatan) && !$jabatanList->contains(fn($j) => strcasecmp($j->nama_jabatan, old('jabatan', $asn->jabatan)) === 0))
                                    <option value="{{ old('jabatan', $asn->jabatan) }}" selected>{{ old('jabatan', $asn->jabatan) }}</option>
                                @endif
                            </select>
                            @if ($asn->masterJabatan)
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px] font-mono text-slate-500">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700">{{ $asn->masterJabatan->kode_jabatan }}</span>
                                    @if ($asn->masterJabatan->kelas_jabatan)
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 border border-blue-100 text-blue-700">Kelas {{ $asn->masterJabatan->kelas_jabatan }}</span>
                                    @endif
                                    @if ($asn->masterJabatan->bup_usia)
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-100 text-emerald-700">BUP {{ $asn->masterJabatan->bup_usia }} th</span>
                                    @endif
                                </div>
                            @endif
                            @error('jabatan')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Jabatan <span class="text-rose-500">*</span></label>
                            <select id="jenisJabatanSelect" name="jenis_jabatan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                <option value="fungsional" {{ old('jenis_jabatan', $asn->jenis_jabatan) == 'fungsional' ? 'selected' : '' }}>Fungsional</option>
                                <option value="pelaksana" {{ old('jenis_jabatan', $asn->jenis_jabatan ?? 'pelaksana') == 'pelaksana' ? 'selected' : '' }}>Pelaksana</option>
                                <option value="struktural" {{ old('jenis_jabatan', $asn->jenis_jabatan) == 'struktural' ? 'selected' : '' }}>Struktural</option>
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
                                    $currentGol = old('pangkat_golongan', $asn->pangkat_golongan);
                                @endphp
                                @foreach ($golOptions as $kodeGol => $labelGol)
                                    <option value="{{ $kodeGol }}" {{ ($currentGol === $kodeGol || $currentGol === $labelGol) ? 'selected' : '' }}>{{ $labelGol }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">TMT Golongan Terakhir</label>
                            <input type="date" name="tmt_golongan" value="{{ old('tmt_golongan', $asn->tmt_golongan ? date('Y-m-d', strtotime($asn->tmt_golongan)) : '') }}" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Tanggal Riwayat Kepegawaian Sesuai Nota Klien -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal Jabatan (TMT)</label>
                            <input type="date" name="tgl_jabatan" value="{{ old('tgl_jabatan', $asn->tgl_jabatan ? date('Y-m-d', strtotime($asn->tgl_jabatan)) : '') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Dasar masa jabatan</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal CPNS</label>
                            <input type="date" name="tgl_cpns" value="{{ old('tgl_cpns', $asn->tgl_cpns ? date('Y-m-d', strtotime($asn->tgl_cpns)) : '') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Dasar masa kerja</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal PNS</label>
                            <input type="date" name="tgl_pns" value="{{ old('tgl_pns', $asn->tgl_pns ? date('Y-m-d', strtotime($asn->tgl_pns)) : '') }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Pengangkatan penuh</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Masa Kerja (Thn) <span class="text-rose-500">*</span></label>
                            <input type="number" name="masa_kerja_tahun" value="{{ old('masa_kerja_tahun', $asn->masa_kerja_tahun) }}" min="0" required class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-[10px] text-slate-400">Otomatis/manual</span>
                        </div>
                    </div>

                    <!-- Riwayat Pendidikan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Pendidikan</label>
                            <select name="tingkat_pendidikan" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                                @php
                                    $pendOptions = [
                                        'SLTA/SMA Sederajat' => 'SLTA / SMA Sederajat',
                                        'Diploma III/Sarjana Muda' => 'Diploma III (D-III)',
                                        'Diploma IV' => 'Diploma IV (D-IV)',
                                        'S-1/Sarjana' => 'Strata 1 (S-1 / Sarjana)',
                                        'Profesi' => 'Profesi',
                                        'S-2' => 'Strata 2 (S-2)',
                                        'S-3' => 'Strata 3 (S-3)',
                                    ];
                                    $currentPend = old('tingkat_pendidikan', $asn->tingkat_pendidikan);
                                @endphp
                                <option value="">-- Pilih Jenjang --</option>
                                @foreach ($pendOptions as $valPend => $lblPend)
                                    <option value="{{ $valPend }}" {{ $currentPend == $valPend ? 'selected' : '' }}>{{ $lblPend }}</option>
                                @endforeach
                                @if ($currentPend && !array_key_exists($currentPend, $pendOptions))
                                    <option value="{{ $currentPend }}" selected>{{ $currentPend }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pendidikan Terakhir (Jurusan)</label>
                            <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir', $asn->pendidikan_terakhir) }}" placeholder="Contoh: S-1 ILMU PEMERINTAHAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Saat Ini <span class="text-rose-500">*</span></label>
                            <select id="unitSaatIniSelect" name="id_unit_saat_ini" required class="searchable-select w-full" data-placeholder="Cari nama dinas / unit kerja...">
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}" data-lat="{{ $u->kantor_lat }}" data-lng="{{ $u->kantor_lng }}" {{ old('id_unit_saat_ini', $asn->id_unit_saat_ini) == $u->id ? 'selected' : '' }}>
                                        {{ $u->nama_unit }} ({{ $u->kategori }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Induk (OPD Pengampu)</label>
                            <input type="text" name="unit_kerja_induk" value="{{ old('unit_kerja_induk', $asn->unit_kerja_induk) }}" placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Leaflet Map Picker dengan Pencarian & GPS -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <i data-lucide="crosshair" class="w-4 h-4 text-emerald-600"></i>
                            <span class="text-xs font-bold text-slate-800">Perbarui Titik Koordinat Domisili Tempat Tinggal</span>
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
                            <span id="coordBadge" class="font-mono text-emerald-400 font-bold">{{ number_format($asn->domisili_lat, 6) }}, {{ number_format($asn->domisili_lng, 6) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Jarak ke Unit Kerja:</span>
                            <span id="liveDistanceBadge" class="px-2 py-0.5 rounded font-extrabold text-xs bg-emerald-500 text-white">{{ number_format($asn->jarak_ke_kantor_km ?? 0, 2) }} km</span>
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
                            <input type="text" id="domisili_lat" name="domisili_lat" value="{{ old('domisili_lat', $asn->domisili_lat) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                            <input type="text" id="domisili_lng" name="domisili_lng" value="{{ old('domisili_lng', $asn->domisili_lng) }}" required readonly class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-700">
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
                        <textarea name="alamat_detail" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('alamat_detail', $asn->alamat_detail) }}</textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <a href="{{ route('asn.show', $asn->id) }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            Simpan Perubahan
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

        // Ambil Data Wilayah
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

        const defaultLat = parseFloat(document.getElementById('domisili_lat').value) || -6.326350;
        const defaultLng = parseFloat(document.getElementById('domisili_lng').value) || 108.320420;

        const map = L.map('pickerMap').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map).bindPopup("<strong>Titik Domisili Pegawai</strong><br/><span class='text-xs text-slate-500'>Geser pin ini tepat di atas atap rumah Anda</span>").openPopup();

        let officeMarker = null;
        let connectLine = null;

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

        $('#unitSaatIniSelect').on('change', function () {
            const pos = marker.getLatLng();
            updateDistanceAndLine(pos.lat, pos.lng);
        });

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

        updateDistanceAndLine(defaultLat, defaultLng);
    });
</script>
@endpush

