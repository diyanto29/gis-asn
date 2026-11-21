<?php

namespace Tests\Feature;

use App\Models\AsnMaster;
use App\Models\RekomendasiMutasi;
use App\Models\UnitKerja;
use App\Models\User;
use Tests\TestCase;

class EndToEndUserJourneyTest extends TestCase
{
    /**
     * Test POV 1: Pegawai ASN Journey (Login, Dashboard, Simulasi, AI Ajukan, Swap Match, RBAC Guard)
     */
    public function test_pegawai_asn_user_journey(): void
    {
        $userAsn = User::where('nip', '198507122010011005')->firstOrFail();
        $asnModel = AsnMaster::where('nip', $userAsn->nip)->firstOrFail();

        // 1. Login flow via NIP (Role ASN otomatis diarahkan ke Simulasi Mandiri)
        $response = $this->post('/login', [
            'login' => '198507122010011005',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/simulasi-mandiri');
        $this->assertAuthenticatedAs($userAsn);

        // 2. Akses Dashboard GIS
        $dashResponse = $this->actingAs($userAsn)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee($asnModel->nama_lengkap);

        // 3. Akses & Pembaruan Domisili Mandiri
        $editLokasiResponse = $this->actingAs($userAsn)->get("/asn/{$asnModel->id}/edit-lokasi");
        $editLokasiResponse->assertStatus(200);

        $updateLokasi = $this->actingAs($userAsn)->put("/asn/{$asnModel->id}/update-lokasi", [
            'domisili_lat' => -6.45980,
            'domisili_lng' => 107.95460,
            'kelurahan_desa' => 'Haurgeulis',
            'kecamatan' => 'Haurgeulis',
            'alamat_detail' => 'Jl. Jenderal Sudirman No. 12',
        ]);
        $updateLokasi->assertRedirect("/asn/{$asnModel->id}");

        // 4. Akses Simulasi Mandiri (TOPSIS Tahap 2)
        $simulasiResponse = $this->actingAs($userAsn)->get('/simulasi-mandiri');
        $simulasiResponse->assertStatus(200);
        $simulasiResponse->assertSee('Simulasi Rekomendasi Penempatan Mandiri');

        // 5. Pengajuan Mutasi Mandiri dengan Alasan AI
        $unitTujuan = UnitKerja::where('kuota_formasi_kosong', '>', 0)->firstOrFail();
        $ajukanResponse = $this->actingAs($userAsn)->post('/simulasi-mandiri/ajukan', [
            'asn_id' => $asnModel->id,
            'unit_tujuan_id' => $unitTujuan->id,
            'alasan_mutasi' => 'Efisiensi mobilitas dinas harian untuk meningkatkan produktivitas kerja.',
            'nilai_preferensi_v' => 0.8950,
            'ranking' => 1,
        ]);
        $ajukanResponse->assertSessionHas('success');

        // Verifikasi record masuk ke database
        $this->assertDatabaseHas('rekomendasi_mutasi', [
            'id_asn' => $asnModel->id,
            'id_unit_tujuan' => $unitTujuan->id,
            'status_usulan' => 'diajukan_baperjakat',
        ]);

        // 6. Akses Modul Swap Match
        $swapResponse = $this->actingAs($userAsn)->get('/swap-match');
        $swapResponse->assertStatus(200);

        // 7. RBAC Guard: ASN DILARANG mengakses modul Admin Baperjakat
        $forbiddenRoutes = [
            '/topsis',
            '/master-kriteria',
            '/master-lokasi',
            '/formasi-jabatan',
            '/asn/create',
            '/unit-kerja/create',
        ];

        foreach ($forbiddenRoutes as $route) {
            $blockedResponse = $this->actingAs($userAsn)->get($route);
            $this->assertEquals(403, $blockedResponse->status(), "Route {$route} seharusnya di-blokir untuk role ASN.");
        }
    }

    /**
     * Test POV 2: Admin BKPSDM & Baperjakat Journey (Master Data, SPK TOPSIS, Swap Match, Approval & Eksekusi SK)
     */
    public function test_admin_bkpsdm_and_baperjakat_user_journey(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. Login Admin
        $loginAdmin = $this->post('/login', [
            'login' => 'admin@bkpsdm.go.id',
            'password' => 'admin123',
        ]);
        $loginAdmin->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        // 2. Akses Master Pegawai ASN (Index & Create Form)
        $asnsResponse = $this->actingAs($admin)->get('/asn');
        $asnsResponse->assertStatus(200);

        $createAsnForm = $this->actingAs($admin)->get('/asn/create');
        $createAsnForm->assertStatus(200);

        // 3. Akses Master Unit Kerja & Formasi
        $unitResponse = $this->actingAs($admin)->get('/unit-kerja');
        $unitResponse->assertStatus(200);

        // 4. Akses Master Formasi Jabatan Multi-Tahun (Filter 2026 Aktif & 2025 Historis)
        $formasi2026 = $this->actingAs($admin)->get('/formasi-jabatan?tahun=2026');
        $formasi2026->assertStatus(200);

        $formasi2025 = $this->actingAs($admin)->get('/formasi-jabatan?tahun=2025');
        $formasi2025->assertStatus(200);

        // 5. Akses Master Wilayah (31 Kecamatan)
        $wilayahResponse = $this->actingAs($admin)->get('/master-lokasi');
        $wilayahResponse->assertStatus(200);

        // 6. Akses Master Kriteria TOPSIS (Dynamic Weighting)
        $kriteriaResponse = $this->actingAs($admin)->get('/master-kriteria');
        $kriteriaResponse->assertStatus(200);

        // 7. Eksekusi SPK TOPSIS (Mode 1: PNS to Dinas & Mode 2: Dinas to PNS)
        $sampleAsn = AsnMaster::firstOrFail();
        $topsisMode1 = $this->actingAs($admin)->get("/topsis/pns-to-dinas?asn_id={$sampleAsn->id}");
        $topsisMode1->assertStatus(200);
        $topsisMode1->assertSee('Rekomendasi Mutasi: PNS ke Dinas');

        $unitTujuan = UnitKerja::where('kuota_formasi_kosong', '>', 0)->firstOrFail();
        $topsisMode2 = $this->actingAs($admin)->get("/topsis/dinas-to-pns?unit_id={$unitTujuan->id}");
        $topsisMode2->assertStatus(200);
        $topsisMode2->assertSee('Rekomendasi Mutasi: Dinas ke PNS');

        // 8. Pengajuan Swap Match ke Baperjakat
        $asns = AsnMaster::all();
        if ($asns->count() >= 2) {
            $asn1 = $asns[0];
            $asn2 = $asns[1];
            $swapSubmit = $this->actingAs($admin)->post('/swap-match/ajukan', [
                'asn_a_id' => $asn1->id,
                'asn_b_id' => $asn2->id,
                'unit_a_id' => $asn1->id_unit_saat_ini,
                'unit_b_id' => $asn2->id_unit_saat_ini,
                'dist_a_swap' => 5.2,
                'dist_b_swap' => 6.1,
                'save_a' => 12.0,
                'save_b' => 10.5,
                'combined_save_daily' => 22.5,
            ]);
            $swapSubmit->assertRedirect('/topsis/riwayat');
        }

        // 9. Akses Riwayat Sidang Baperjakat
        $riwayatResponse = $this->actingAs($admin)->get('/topsis/riwayat');
        $riwayatResponse->assertStatus(200);
        $riwayatResponse->assertSee('Eksekusi Mutasi Baperjakat');

        // 10. EKSEKUSI MUTASI (Approval & Penerbitan SK Riil)
        $usulan = RekomendasiMutasi::where('status_usulan', '!=', 'disetujui')->first();
        if ($usulan) {
            $targetAsnId = $usulan->id_asn;
            $targetUnitTujuanId = $usulan->id_unit_tujuan;

            $approveResponse = $this->actingAs($admin)->post("/topsis/riwayat/{$usulan->id}/approve", [
                'catatan' => 'Disetujui dalam Sidang Baperjakat Periode November 2026.',
            ]);
            $approveResponse->assertSessionHas('success');

            // Verifikasi data ASN di database benar-benar berpindah unit kerja
            $this->assertDatabaseHas('asn_master', [
                'id' => $targetAsnId,
                'id_unit_saat_ini' => $targetUnitTujuanId,
            ]);

            // Verifikasi status usulan berubah menjadi disetujui
            $this->assertDatabaseHas('rekomendasi_mutasi', [
                'id' => $usulan->id,
                'status_usulan' => 'disetujui',
            ]);
        }
    }
}
