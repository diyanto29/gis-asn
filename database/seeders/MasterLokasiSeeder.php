<?php

namespace Database\Seeders;

use App\Models\MasterLokasi;
use Illuminate\Database\Seeder;

class MasterLokasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi 31 Kecamatan lengkap di Kabupaten Indramayu beserta titik koordinat geospasial pusat.
     */
    public function run(): void
    {
        $kecamatanData = [
            ['kode_kecamatan' => '32.12.01', 'nama_kecamatan' => 'Indramayu', 'pusat_lat' => -6.326350, 'pusat_lng' => 108.320420, 'jumlah_desa' => 18, 'keterangan' => 'Pusat Pemerintahan Kabupaten Indramayu'],
            ['kode_kecamatan' => '32.12.02', 'nama_kecamatan' => 'Sindang', 'pusat_lat' => -6.338500, 'pusat_lng' => 108.309200, 'jumlah_desa' => 10, 'keterangan' => 'Wilayah Penyangga Kota Indramayu'],
            ['kode_kecamatan' => '32.12.03', 'nama_kecamatan' => 'Balongan', 'pusat_lat' => -6.368800, 'pusat_lng' => 108.384600, 'jumlah_desa' => 10, 'keterangan' => 'Kawasan Industri Migas Pertamina RU VI'],
            ['kode_kecamatan' => '32.12.04', 'nama_kecamatan' => 'Juntinyuat', 'pusat_lat' => -6.417200, 'pusat_lng' => 108.455100, 'jumlah_desa' => 12, 'keterangan' => 'Pesisir Timur Indramayu'],
            ['kode_kecamatan' => '32.12.05', 'nama_kecamatan' => 'Karangampel', 'pusat_lat' => -6.463200, 'pusat_lng' => 108.454200, 'jumlah_desa' => 11, 'keterangan' => 'Sentra Perdagangan Wilayah Timur'],
            ['kode_kecamatan' => '32.12.06', 'nama_kecamatan' => 'Krangkeng', 'pusat_lat' => -6.512300, 'pusat_lng' => 108.498700, 'jumlah_desa' => 11, 'keterangan' => 'Perbatasan Indramayu - Kabupaten Cirebon'],
            ['kode_kecamatan' => '32.12.07', 'nama_kecamatan' => 'Kedokan Bunder', 'pusat_lat' => -6.495000, 'pusat_lng' => 108.411000, 'jumlah_desa' => 7, 'keterangan' => 'Wilayah Pertanian & Perikanan Darat'],
            ['kode_kecamatan' => '32.12.08', 'nama_kecamatan' => 'Sliyeg', 'pusat_lat' => -6.452000, 'pusat_lng' => 108.378000, 'jumlah_desa' => 14, 'keterangan' => 'Lumbung Padi Wilayah Tengah'],
            ['kode_kecamatan' => '32.12.09', 'nama_kecamatan' => 'Jatibarang', 'pusat_lat' => -6.471500, 'pusat_lng' => 108.314200, 'jumlah_desa' => 15, 'keterangan' => 'Hub Transportasi Kereta Api & Niaga Utama'],
            ['kode_kecamatan' => '32.12.10', 'nama_kecamatan' => 'Widasari', 'pusat_lat' => -6.451000, 'pusat_lng' => 108.267000, 'jumlah_desa' => 10, 'keterangan' => 'Kawasan Pertanian Jalur Utama'],
            ['kode_kecamatan' => '32.12.11', 'nama_kecamatan' => 'Kertasmaya', 'pusat_lat' => -6.542000, 'pusat_lng' => 108.339000, 'jumlah_desa' => 13, 'keterangan' => 'Perbatasan Jalur Cirebon - Kertasmaya'],
            ['kode_kecamatan' => '32.12.12', 'nama_kecamatan' => 'Sukagumiwang', 'pusat_lat' => -6.568000, 'pusat_lng' => 108.351000, 'jumlah_desa' => 7, 'keterangan' => 'Koridor Selatan Perbatasan Majalengka/Cirebon'],
            ['kode_kecamatan' => '32.12.13', 'nama_kecamatan' => 'Bangodua', 'pusat_lat' => -6.521000, 'pusat_lng' => 108.289000, 'jumlah_desa' => 8, 'keterangan' => 'Wilayah Pertanian Irigasi Rentang'],
            ['kode_kecamatan' => '32.12.14', 'nama_kecamatan' => 'Tukdana', 'pusat_lat' => -6.551000, 'pusat_lng' => 108.254000, 'jumlah_desa' => 13, 'keterangan' => 'Wilayah Pertanian & Perkebunan'],
            ['kode_kecamatan' => '32.12.15', 'nama_kecamatan' => 'Lelea', 'pusat_lat' => -6.412000, 'pusat_lng' => 108.243000, 'jumlah_desa' => 11, 'keterangan' => 'Kawasan Budaya Tradisi Ngarot'],
            ['kode_kecamatan' => '32.12.16', 'nama_kecamatan' => 'Cikedung', 'pusat_lat' => -6.482000, 'pusat_lng' => 108.167000, 'jumlah_desa' => 7, 'keterangan' => 'Wilayah Agribisnis Mangga & Padi'],
            ['kode_kecamatan' => '32.12.17', 'nama_kecamatan' => 'Terisi', 'pusat_lat' => -6.502000, 'pusat_lng' => 108.125000, 'jumlah_desa' => 9, 'keterangan' => 'Kawasan Perhutani & Agrowisata'],
            ['kode_kecamatan' => '32.12.18', 'nama_kecamatan' => 'Losarang', 'pusat_lat' => -6.402000, 'pusat_lng' => 108.151000, 'jumlah_desa' => 12, 'keterangan' => 'Jalur Arteri Pantura Tengah'],
            ['kode_kecamatan' => '32.12.19', 'nama_kecamatan' => 'Lohbener', 'pusat_lat' => -6.389000, 'pusat_lng' => 108.278000, 'jumlah_desa' => 12, 'keterangan' => 'Simpang Jalur Arteri & Politeknik Negeri'],
            ['kode_kecamatan' => '32.12.20', 'nama_kecamatan' => 'Cantigi', 'pusat_lat' => -6.305000, 'pusat_lng' => 108.252000, 'jumlah_desa' => 7, 'keterangan' => 'Kawasan Pesisir & Tambak Garam'],
            ['kode_kecamatan' => '32.12.21', 'nama_kecamatan' => 'Pasekan', 'pusat_lat' => -6.289000, 'pusat_lng' => 108.295000, 'jumlah_desa' => 8, 'keterangan' => 'Kawasan Hutan Mangrove & Pesisir Utara'],
            ['kode_kecamatan' => '32.12.22', 'nama_kecamatan' => 'Arahan', 'pusat_lat' => -6.398000, 'pusat_lng' => 108.231000, 'jumlah_desa' => 8, 'keterangan' => 'Kawasan Pertanian Padi'],
            ['kode_kecamatan' => '32.12.23', 'nama_kecamatan' => 'Gabuswetan', 'pusat_lat' => -6.415000, 'pusat_lng' => 108.082000, 'jumlah_desa' => 10, 'keterangan' => 'Wilayah Pertanian Barat Daya'],
            ['kode_kecamatan' => '32.12.24', 'nama_kecamatan' => 'Kroya', 'pusat_lat' => -6.478000, 'pusat_lng' => 108.031000, 'jumlah_desa' => 9, 'keterangan' => 'Kawasan Agrikultur & Perkebunan'],
            ['kode_kecamatan' => '32.12.25', 'nama_kecamatan' => 'Bongas', 'pusat_lat' => -6.358000, 'pusat_lng' => 108.028000, 'jumlah_desa' => 8, 'keterangan' => 'Wilayah Pertanian Produktif'],
            ['kode_kecamatan' => '32.12.26', 'nama_kecamatan' => 'Kandanghaur', 'pusat_lat' => -6.331000, 'pusat_lng' => 108.109000, 'jumlah_desa' => 13, 'keterangan' => 'Jalur Pantura Barat & Kawasan Tambak Eretan'],
            ['kode_kecamatan' => '32.12.27', 'nama_kecamatan' => 'Anjatan', 'pusat_lat' => -6.378000, 'pusat_lng' => 107.965000, 'jumlah_desa' => 13, 'keterangan' => 'Sentra Niaga & Pertanian Bagian Barat'],
            ['kode_kecamatan' => '32.12.28', 'nama_kecamatan' => 'Haurgeulis', 'pusat_lat' => -6.459800, 'pusat_lng' => 107.954600, 'jumlah_desa' => 10, 'keterangan' => 'Kota Transit Kereta Api Wilayah Barat Daya'],
            ['kode_kecamatan' => '32.12.29', 'nama_kecamatan' => 'Gantar', 'pusat_lat' => -6.521000, 'pusat_lng' => 107.912000, 'jumlah_desa' => 7, 'keterangan' => 'Perbatasan Wilayah Subang / Hutan Jati'],
            ['kode_kecamatan' => '32.12.30', 'nama_kecamatan' => 'Patrol', 'pusat_lat' => -6.302000, 'pusat_lng' => 107.998000, 'jumlah_desa' => 8, 'keterangan' => 'Jalur Pantura Utama & PLTU Indramayu'],
            ['kode_kecamatan' => '32.12.31', 'nama_kecamatan' => 'Sukra', 'pusat_lat' => -6.279000, 'pusat_lng' => 107.945000, 'jumlah_desa' => 8, 'keterangan' => 'Gerbang Perbatasan Barat Indramayu - Subang'],
        ];

        foreach ($kecamatanData as $data) {
            MasterLokasi::updateOrCreate(
                ['kode_kecamatan' => $data['kode_kecamatan']],
                $data
            );
        }
    }
}

