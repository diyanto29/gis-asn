<?php

namespace Tests\Feature;

use App\Models\MasterJabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJabatanTest extends TestCase
{
    public function test_admin_can_access_master_jabatan_index(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('master-jabatan.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Referensi Jabatan PNS');
    }

    public function test_admin_can_create_new_master_jabatan(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $namaTest = 'Analis Sistem Informasi Geospasial ' . uniqid();
        $response = $this->actingAs($admin)->post(route('master-jabatan.store'), [
            'nama_jabatan' => $namaTest,
            'kode_jabatan' => 'TEST-' . rand(1000, 9999),
            'jenis_jabatan' => 'fungsional',
            'jenjang_jabatan' => 'Ahli Pertama',
            'rumpun_jabatan' => 'Komputer & Geospasial',
            'kualifikasi_pendidikan' => 'S-1 Geografi / Sistem Informasi',
        ]);

        $response->assertRedirect(route('master-jabatan.index'));
        $this->assertDatabaseHas('master_jabatan', [
            'nama_jabatan' => $namaTest,
            'jenis_jabatan' => 'fungsional',
        ]);
    }
}
