@extends('layouts.app')

@section('title', 'Daftar Usulan Rekomendasi Mutasi Baperjakat')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Daftar Usulan Rekomendasi Mutasi Baperjakat</h1>
            <p class="text-sm text-slate-500 mt-1">
                Data usulan mutasi resmi berbasis zonasi spasial yang siap disidangkan oleh Tim Penilai Kinerja / Baperjakat.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 bg-white rounded-lg text-xs font-semibold hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Dokumen Sidang
            </button>
            <a href="{{ route('topsis.index') }}" class="px-3.5 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Buat Rekomendasi Baru
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-center">Rank</th>
                        <th class="px-5 py-3.5">Pegawai</th>
                        <th class="px-5 py-3.5">Unit Asal</th>
                        <th class="px-5 py-3.5">Unit Tujuan Mutasi</th>
                        <th class="px-5 py-3.5 text-right">Jarak Lama</th>
                        <th class="px-5 py-3.5 text-right">Jarak Baru</th>
                        <th class="px-5 py-3.5 text-right">Efisiensi Jarak</th>
                        <th class="px-5 py-3.5 text-right">Nilai Vi</th>
                        <th class="px-5 py-3.5 text-center">Status Sidang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $rek)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3.5 text-center font-bold text-slate-800">
                                #{{ $rek->ranking }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $rek->asn->nama_lengkap }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $rek->asn->nip }} &bull; {{ $rek->asn->jabatan }}</div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-700">
                                {{ $rek->unitAsal->nama_unit ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-blue-900">
                                {{ $rek->unitTujuan->nama_unit ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-slate-500">
                                {{ number_format($rek->jarak_asal_km, 2) }} km
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-emerald-600">
                                {{ number_format($rek->jarak_baru_km, 2) }} km
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-indigo-600">
                                +{{ number_format($rek->selisih_hemat_jarak_km, 2) }} km
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-800">
                                {{ number_format($rek->nilai_preferensi_v, 4) }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs rounded-full font-semibold uppercase">
                                    {{ $rek->status_usulan }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-400">Belum ada usulan rekomendasi mutasi yang disimpan ke database Baperjakat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayat->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $riwayat->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

