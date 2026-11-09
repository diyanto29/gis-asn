<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalAsn = AsnMaster::count();
        $totalUnit = UnitKerja::count();
        $totalFormasi = UnitKerja::sum('kuota_formasi_kosong');
        
        $zonaDekat = AsnMaster::where('zona_jarak', 'dekat')->count();
        $zonaSedang = AsnMaster::where('zona_jarak', 'sedang')->count();
        $zonaJauh = AsnMaster::where('zona_jarak', 'jauh')->count();

        // Data Unit Kerja untuk marker Peta
        $units = UnitKerja::select(
            'id', 'kode_unit', 'nama_unit', 'kategori', 
            'kantor_lat', 'kantor_lng', 'kuota_formasi_kosong', 'alamat_kantor'
        )->get();

        // Data ASN dengan koordinat domisili valid
        $asns = AsnMaster::with('unitSaatIni')
            ->whereNotNull('domisili_lat')
            ->whereNotNull('domisili_lng')
            ->get();

        return view('dashboard.index', compact(
            'totalAsn', 'totalUnit', 'totalFormasi',
            'zonaDekat', 'zonaSedang', 'zonaJauh',
            'units', 'asns'
        ));
    }
}
