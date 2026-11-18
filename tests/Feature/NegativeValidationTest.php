<?php

namespace Tests\Feature;

use App\Models\AsnMaster;
use App\Models\TopsisKriteria;
use App\Models\UnitKerja;
use App\Models\User;
use Tests\TestCase;

class NegativeValidationTest extends TestCase
{
    /**
     * Negative Case 1: Kredensial Login Tidak Valid & Brute Force Prevention
     */
    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->post('/login', [
            'login' => '198507122010019999', // NIP tidak terdaftar
            'password' => 'passwordsalah123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Negative Case 2: Tamu (Guest) Mengakses Endpoint Terproteksi
     */
    public function test_guest_cannot_access_protected_routes(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/simulasi-mandiri',
            '/asn',
            '/unit-kerja',
            '/swap-match',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /**
     * Negative Case 3: Otorisasi & Privilege Escalation (Role ASN ke Halaman Admin)
     */
    public function test_asn_cannot_access_admin_routes_returns_403(): void
    {
        $userAsn = User::where('role', 'asn')->firstOrFail();

        $adminOnlyRoutes = [
            '/topsis',
            '/master-kriteria',
            '/master-lokasi',
            '/formasi-jabatan',
            '/asn/create',
            '/unit-kerja/create',
        ];

        foreach ($adminOnlyRoutes as $route) {
            $response = $this->actingAs($userAsn)->get($route);
            $response->assertStatus(403);
        }
    }

    /**
     * Negative Case 4: ASN Mencoba Mengedit Titik Domisili ASN Lain (ID Hijacking)
     */
    public function test_asn_cannot_edit_other_asn_domisili_returns_403(): void
    {
        $asns = AsnMaster::all();
        $this->assertGreaterThanOrEqual(2, $asns->count(), 'Dibutuhkan minimal 2 ASN untuk tes ID hijacking.');

        $asn1 = $asns[0];
        $asn2 = $asns[1];

        $user1 = User::where('nip', $asn1->nip)->firstOrFail();

        // User 1 mencoba mengedit lokasi ASN 2
        $response = $this->actingAs($user1)->get("/asn/{$asn2->id}/edit-lokasi");
        $response->assertStatus(403);

        // User 1 mencoba update lokasi ASN 2
        $postResponse = $this->actingAs($user1)->put("/asn/{$asn2->id}/update-lokasi", [
            'domisili_lat' => -6.32635,
            'domisili_lng' => 108.32042,
            'kecamatan' => 'Indramayu',
        ]);
        $postResponse->assertStatus(403);
    }

    /**
     * Negative Case 5: Penolakan Mutasi Masuk ke Unit Berkuota Nol (Zero-ABK Guard)
     */
    public function test_mutasi_rejected_when_unit_quota_is_zero(): void
    {
        $userAsn = User::where('role', 'asn')->firstOrFail();
        $asn = AsnMaster::where('nip', $userAsn->nip)->firstOrFail();

        // Cari atau set unit kerja dengan kuota formasi 0
        $unitPenuh = UnitKerja::where('kuota_formasi_kosong', '<=', 0)->first();
        if (!$unitPenuh) {
            $unitPenuh = UnitKerja::create([
                'kode_unit' => 'TEST-FULL-001',
                'nama_unit' => 'Kantor Pengujian Unit Penuh',
                'kategori' => 'Dinas',
                'kantor_lat' => -6.32635,
                'kantor_lng' => 108.32042,
                'kuota_formasi_kosong' => 0,
                'bezetting_saat_ini' => 10,
                'kebutuhan_abk' => 10,
            ]);
        }

        $response = $this->actingAs($userAsn)->post('/simulasi-mandiri/ajukan', [
            'asn_id' => $asn->id,
            'unit_tujuan_id' => $unitPenuh->id,
            'alasan_mutasi' => 'Mencoba memaksakan mutasi ke unit berkuota 0.',
        ]);

        // Harus ditolak dengan pesan error
        $response->assertSessionHas('error');
    }

    /**
     * Negative Case 6: Validasi Bobot TOPSIS Tidak Sama Dengan 100%
     */
    public function test_topsis_weight_update_fails_if_total_not_100_percent(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $activeKriteria = TopsisKriteria::where('is_active', true)->get();

        // Kirim bobot yang totalnya 120% (1.20)
        $invalidBobot = [];
        foreach ($activeKriteria as $k) {
            $invalidBobot[$k->id] = 0.40; // Jika ada 3 kriteria, total = 1.20
        }

        $response = $this->actingAs($admin)->post('/master-kriteria/bobot', [
            'bobot' => $invalidBobot,
        ]);

        // Harus gagal dan menampilkan error total bobot wajib 100%
        $response->assertSessionHas('error');
    }

    /**
     * Negative Case 7: Proteksi Penghapusan Unit Kerja yang Memiliki Pegawai Aktif
     */
    public function test_cannot_delete_unit_kerja_with_active_pegawai(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Cari unit yang memiliki staf pegawai aktif
        $unitDenganPegawai = UnitKerja::has('pegawai')->firstOrFail();

        $response = $this->actingAs($admin)->delete("/unit-kerja/{$unitDenganPegawai->id}");

        // Ditolak dan tidak terhapus
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('unit_kerja', [
            'id' => $unitDenganPegawai->id,
        ]);
    }

    /**
     * Negative Case 8: Validasi Registrasi ASN (NIP Duplikat & Koordinat Invalid)
     */
    public function test_asn_registration_fails_on_duplicate_nip_or_invalid_coordinates(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $existingAsn = AsnMaster::firstOrFail();

        // 1. Tes NIP Duplikat
        $responseDuplicateNip = $this->actingAs($admin)->post('/asn', [
            'nip' => $existingAsn->nip, // NIP sudah terdaftar
            'nama' => 'Pegawai Kloning',
            'jabatan' => 'Staff Analis',
            'pangkat_golongan' => 'III/a',
            'masa_kerja_tahun' => 5,
            'id_unit_saat_ini' => $existingAsn->id_unit_saat_ini,
            'domisili_lat' => -6.32635,
            'domisili_lng' => 108.32042,
        ]);
        $responseDuplicateNip->assertSessionHasErrors('nip');

        // 2. Tes Koordinat Latitude Melebihi Batas (-90 s/d 90)
        $responseInvalidLat = $this->actingAs($admin)->post('/asn', [
            'nip' => '199501012020011999',
            'nama' => 'Pegawai Invalid Koordinat',
            'jabatan' => 'Staff Analis',
            'pangkat_golongan' => 'III/a',
            'masa_kerja_tahun' => 2,
            'id_unit_saat_ini' => $existingAsn->id_unit_saat_ini,
            'domisili_lat' => 195.4321, // Latitude invalid (> 90)
            'domisili_lng' => 108.32042,
        ]);
        $responseInvalidLat->assertSessionHasErrors('domisili_lat');
    }
}

