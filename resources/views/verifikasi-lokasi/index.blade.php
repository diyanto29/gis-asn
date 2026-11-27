@extends('layouts.app')

@section('title', 'Verifikasi & Riwayat Perubahan Pin Titik Lokasi')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Verifikasi &amp; Riwayat Perubahan Titik Lokasi</h1>
            <p class="text-sm text-slate-500 mt-1">
                Persetujuan pengajuan perubahan pin domisili mandiri PNS berbasis bukti KTP beserta audit riwayat pergantian pin lokasi.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <div class="font-medium">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <div class="font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Menunggu Verifikasi</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-amber-600 mt-2">{{ $stats['pending'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Perlu pemeriksaan KTP &amp; peta</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Terverifikasi (Disetujui)</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700 mt-2">{{ $stats['disetujui'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Pin lokasi resmi diperbarui</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Ditolak</span>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-rose-600 mt-2">{{ $stats['ditolak'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Tidak sesuai bukti KTP</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Riwayat Pin</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                    <i data-lucide="history" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">{{ $stats['total'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Seluruh log pergantian pin</div>
        </div>
    </div>

    <!-- Filter Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
        @php
            $tabs = [
                'all' => ['label' => 'Semua Riwayat', 'count' => $stats['total']],
                'pending' => ['label' => 'Menunggu Verifikasi', 'count' => $stats['pending']],
                'disetujui' => ['label' => 'Terverifikasi', 'count' => $stats['disetujui']],
                'ditolak' => ['label' => 'Ditolak', 'count' => $stats['ditolak']],
            ];
        @endphp
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('verifikasi-lokasi.index', ['status' => $key]) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $statusFilter === $key ? 'bg-blue-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono {{ $statusFilter === $key ? 'bg-blue-800 text-white' : 'bg-slate-100 text-slate-700' }}">
                    {{ $tab['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- Tabel Pengajuan & Riwayat -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200 text-[11px]">
                    <tr>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Pegawai PNS</th>
                        <th class="px-4 py-3.5">Pin Lama &rarr; Pin Baru</th>
                        <th class="px-4 py-3.5">Wilayah Baru</th>
                        <th class="px-4 py-3.5 text-center">Jarak</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center no-sort">Periksa &amp; Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengajuanList as $item)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3.5 whitespace-nowrap font-mono text-[11px] text-slate-700">
                                <div>{{ $item->created_at->format('d/m/Y') }}</div>
                                <div class="text-slate-400">{{ $item->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900">{{ $item->asn->nama ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $item->asn->nip ?? '-' }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $item->asn->unitSaatIni->nama_unit ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px]">
                                <div class="text-slate-400">Lama: {{ $item->lat_lama ? number_format($item->lat_lama, 5) . ', ' . number_format($item->lng_lama, 5) : '-' }}</div>
                                <div class="font-bold text-blue-900">Baru: {{ number_format($item->lat_baru, 5) }}, {{ number_format($item->lng_baru, 5) }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-[11px]">
                                <div class="font-semibold text-slate-800">{{ $item->kelurahan_desa_baru ? $item->kelurahan_desa_baru . ', ' : '' }}{{ $item->kecamatan_baru ? 'Kec. ' . $item->kecamatan_baru : '-' }}</div>
                                <div class="text-slate-400">{{ $item->kabupaten_kota_baru ?? '' }}{{ $item->provinsi_baru ? ', ' . $item->provinsi_baru : '' }}</div>
                                @if ($item->alamat_detail_baru)
                                    <div class="text-[10px] text-slate-500 mt-0.5 truncate max-w-[200px]" title="{{ $item->alamat_detail_baru }}">{{ $item->alamat_detail_baru }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="text-slate-400">{{ $item->jarak_lama_km !== null ? number_format($item->jarak_lama_km, 1) . ' km' : '-' }}</div>
                                <div class="font-bold text-emerald-700">&rarr; {{ $item->jarak_baru_km !== null ? number_format($item->jarak_baru_km, 1) . ' km' : '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($item->status === 'disetujui')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px]">
                                        <i data-lucide="check-circle-2" class="w-3 h-3"></i> Terverifikasi
                                    </span>
                                @elseif ($item->status === 'ditolak')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[10px]">
                                        <i data-lucide="x-circle" class="w-3 h-3"></i> Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[10px]">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Menunggu
                                    </span>
                                @endif
                                @if ($item->verified_at)
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $item->verified_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if ($item->catatan_admin)
                                    <div class="text-[10px] text-slate-500 mt-0.5 max-w-[160px] mx-auto truncate" title="{{ $item->catatan_admin }}">{{ $item->catatan_admin }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @php
                                    $modalPayload = [
                                        'id' => $item->id,
                                        'nama' => $item->asn->nama ?? '-',
                                        'nip' => $item->asn->nip ?? '-',
                                        'unit' => $item->asn->unitSaatIni->nama_unit ?? '-',
                                        'status' => $item->status,
                                        'lat_lama' => $item->lat_lama,
                                        'lng_lama' => $item->lng_lama,
                                        'lat_baru' => $item->lat_baru,
                                        'lng_baru' => $item->lng_baru,
                                        'alamat_lama' => $item->alamat_lama ?? '-',
                                        'alamat_baru' => trim(implode(', ', array_filter([
                                            $item->alamat_detail_baru,
                                            $item->kelurahan_desa_baru,
                                            $item->kecamatan_baru ? 'Kec. ' . $item->kecamatan_baru : null,
                                            $item->kabupaten_kota_baru,
                                            $item->provinsi_baru,
                                        ]))),
                                        'jarak_lama' => $item->jarak_lama_km,
                                        'jarak_baru' => $item->jarak_baru_km,
                                        'ktp_url' => $item->ktp_path ? route('verifikasi-lokasi.ktp', $item->id) : null,
                                        'ktp_is_pdf' => $item->ktp_path ? str_ends_with(strtolower($item->ktp_path), '.pdf') : false,
                                        'approve_url' => route('verifikasi-lokasi.approve', $item->id),
                                        'reject_url' => route('verifikasi-lokasi.reject', $item->id),
                                        'catatan_admin' => $item->catatan_admin ?? '',
                                    ];
                                @endphp
                                <button type="button" onclick='openInspectModal(@json($modalPayload))'
                                    class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-700 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-700 text-xs font-bold transition inline-flex items-center gap-1.5">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    {{ $item->status === 'pending' ? 'Verifikasi & KTP' : 'Lihat Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                                Belum ada pengajuan atau riwayat perubahan pin titik lokasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Pemeriksaan Peta (Pin Lama vs Pin Baru) + Bukti KTP + Approval -->
<div id="inspectModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-5xl w-full border border-slate-200 shadow-2xl overflow-hidden my-auto">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-700"></i>
                    Verifikasi Perubahan Pin Titik Lokasi — <span id="mNama" class="text-blue-700">-</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5"><span id="mNip" class="font-mono">-</span> &bull; <span id="mUnit">-</span></p>
            </div>
            <button type="button" onclick="closeInspectModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/70">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Kiri: Peta Perbandingan Pin Lama vs Pin Baru (7 Kolom) -->
            <div class="lg:col-span-7 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-800">Perbandingan Peta Pin Lama vs Pin Baru</span>
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-500 inline-block"></span> Pin Lama</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span> Pin Baru</span>
                    </div>
                </div>
                <div id="compareMap" class="w-full h-[320px] rounded-xl border border-slate-200 bg-slate-100 z-0"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Lokasi Lama</div>
                        <div id="mCoordLama" class="font-mono font-bold text-slate-700 mt-0.5">-</div>
                        <div id="mAlamatLama" class="text-[11px] text-slate-500 mt-1">-</div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200">
                        <div class="text-[10px] font-bold uppercase text-emerald-700">Lokasi Usulan Baru</div>
                        <div id="mCoordBaru" class="font-mono font-bold text-emerald-900 mt-0.5">-</div>
                        <div id="mAlamatBaru" class="text-[11px] text-emerald-800 mt-1">-</div>
                    </div>
                </div>
            </div>

            <!-- Kanan: Bukti KTP & Form Approval (5 Kolom) -->
            <div class="lg:col-span-5 flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="id-card" class="w-4 h-4 text-blue-700"></i> Dokumen Bukti KTP
                        </span>
                        <a id="mKtpLink" href="#" target="_blank" class="hidden text-[11px] font-semibold text-blue-700 hover:underline">Buka Tab Baru &nearr;</a>
                    </div>
                    <div id="mKtpContainer" class="w-full h-[220px] rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden p-2">
                        <span class="text-xs text-slate-400">Tidak ada dokumen KTP</span>
                    </div>
                </div>

                <!-- Form Keputusan Admin (hanya muncul jika status == pending) -->
                <div id="mActionWrap" class="hidden space-y-3 pt-3 border-t border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Verifikasi / Alasan Penolakan</label>
                        <textarea id="mCatatanInput" rows="2" placeholder="Opsional jika disetujui; tuliskan alasan jika ditolak (misal: alamat KTP tidak cocok dengan pin)..."
                            class="w-full text-xs border border-slate-300 rounded-xl p-2.5 focus:border-blue-600 focus:ring-blue-600"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <form id="mRejectForm" method="POST" action="">
                            @csrf
                            <input type="hidden" name="catatan_admin" id="mRejectCatatan">
                            <button type="submit" onclick="document.getElementById('mRejectCatatan').value = document.getElementById('mCatatanInput').value;"
                                class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center justify-center gap-1.5 shadow-xs">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> Tolak
                            </button>
                        </form>
                        <form id="mApproveForm" method="POST" action="">
                            @csrf
                            <input type="hidden" name="catatan_admin" id="mApproveCatatan">
                            <button type="submit" onclick="document.getElementById('mApproveCatatan').value = document.getElementById('mCatatanInput').value;"
                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center justify-center gap-1.5 shadow-xs">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i> Verifikasi &amp; Setujui
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info Status Sudah Diproses -->
                <div id="mDoneWrap" class="hidden p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    <div class="font-bold text-slate-800">Status: <span id="mDoneStatus">-</span></div>
                    <div id="mDoneCatatan" class="mt-1 text-[11px]">-</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let compareMap = null;
    let compareLayerGroup = null;

    window.openInspectModal = function(data) {
        document.getElementById('mNama').textContent = data.nama;
        document.getElementById('mNip').textContent = 'NIP. ' + data.nip;
        document.getElementById('mUnit').textContent = data.unit;

        document.getElementById('mCoordLama').textContent = data.lat_lama ? `${Number(data.lat_lama).toFixed(5)}, ${Number(data.lng_lama).toFixed(5)} (${data.jarak_lama ?? '-'} km)` : '-';
        document.getElementById('mAlamatLama').textContent = data.alamat_lama || '-';
        document.getElementById('mCoordBaru').textContent = `${Number(data.lat_baru).toFixed(5)}, ${Number(data.lng_baru).toFixed(5)} (${data.jarak_baru ?? '-'} km)`;
        document.getElementById('mAlamatBaru').textContent = data.alamat_baru || '-';

        // Preview KTP
        const ktpWrap = document.getElementById('mKtpContainer');
        const ktpLink = document.getElementById('mKtpLink');
        if (data.ktp_url) {
            ktpLink.href = data.ktp_url;
            ktpLink.classList.remove('hidden');
            if (data.ktp_is_pdf) {
                ktpWrap.innerHTML = `<iframe src="${data.ktp_url}" class="w-full h-full rounded-lg border-0"></iframe>`;
            } else {
                ktpWrap.innerHTML = `<img src="${data.ktp_url}" alt="Bukti KTP" class="max-w-full max-h-full object-contain rounded-lg">`;
            }
        } else {
            ktpLink.classList.add('hidden');
            ktpWrap.innerHTML = `<span class="text-xs text-slate-400">Tidak ada unggahan KTP (diperbarui langsung oleh Admin)</span>`;
        }

        // Toggle form approval vs info selesai
        const actionWrap = document.getElementById('mActionWrap');
        const doneWrap = document.getElementById('mDoneWrap');
        if (data.status === 'pending') {
            actionWrap.classList.remove('hidden');
            doneWrap.classList.add('hidden');
            document.getElementById('mApproveForm').action = data.approve_url;
            document.getElementById('mRejectForm').action = data.reject_url;
            document.getElementById('mCatatanInput').value = '';
        } else {
            actionWrap.classList.add('hidden');
            doneWrap.classList.remove('hidden');
            document.getElementById('mDoneStatus').textContent = data.status === 'disetujui' ? 'Terverifikasi (Disetujui)' : 'Ditolak';
            document.getElementById('mDoneCatatan').textContent = data.catatan_admin || '-';
        }

        document.getElementById('inspectModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();

        // Render peta perbandingan pin lama vs baru
        setTimeout(() => {
            if (!compareMap) {
                compareMap = L.map('compareMap').setView([data.lat_baru, data.lng_baru], 13);
                if (window.attachGmapBasemap) window.attachGmapBasemap(compareMap);
                compareLayerGroup = L.layerGroup().addTo(compareMap);
            }
            compareMap.invalidateSize();
            compareLayerGroup.clearLayers();

            const pts = [];
            if (data.lat_lama && data.lng_lama) {
                const oldIcon = window.createGmapPinIcon ? window.createGmapPinIcon('#64748b', 'L') : undefined;
                L.marker([data.lat_lama, data.lng_lama], oldIcon ? { icon: oldIcon } : {})
                    .bindPopup('<strong>Pin Lokasi Lama</strong>')
                    .addTo(compareLayerGroup);
                pts.push([data.lat_lama, data.lng_lama]);
            }

            const newIcon = window.createGmapPinIcon ? window.createGmapPinIcon('#059669', 'B') : undefined;
            L.marker([data.lat_baru, data.lng_baru], newIcon ? { icon: newIcon } : {})
                .bindPopup('<strong>Pin Usulan Baru</strong>')
                .addTo(compareLayerGroup)
                .openPopup();
            pts.push([data.lat_baru, data.lng_baru]);

            if (pts.length === 2) {
                L.polyline(pts, { color: '#2563eb', dashArray: '6, 6', weight: 3 }).addTo(compareLayerGroup);
                compareMap.fitBounds(pts, { padding: [45, 45], maxZoom: 15 });
            } else {
                compareMap.setView([data.lat_baru, data.lng_baru], 15);
            }
        }, 120);
    };

    window.closeInspectModal = function() {
        document.getElementById('inspectModal').classList.add('hidden');
    };
</script>
@endpush
