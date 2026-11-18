<?php

namespace App\Services;

/**
 * Class HaversineService
 *
 * Layanan kalkulasi jarak spasial geodesik menggunakan Formula Haversine
 * untuk menghitung jarak lingkaran besar (great-circle distance) pada permukaan bola bumi.
 *
 * Digunakan sebagai:
 * 1. Metode pembanding (benchmark) terhadap rute riil jalan raya (OSRM).
 * 2. Failover fallback saat layanan OSRM tidak dapat dijangkau.
 * 3. Klasifikasi zona efisiensi jarak tempuh pegawai (Dekat, Sedang, Jauh).
 *
 * @package App\Services
 */
class HaversineService
{
    /**
     * Radius rata-rata bumi dalam kilometer (IUGG standard: 6371.0 km).
     */
    public const EARTH_RADIUS_KM = 6371.0;

    /**
     * Menghitung jarak garis lurus bola bumi antara dua koordinat geografis (km).
     *
     * FORMULASI MATEMATIS ILMIAH (BAB 3 METODOLOGI):
     * -------------------------------------------------------------
     * Delta_phi = lat2 - lat1 (dalam radian)
     * Delta_lambda = lng2 - lng1 (dalam radian)
     *
     * a = sin^2(Delta_phi / 2) + cos(phi1) * cos(phi2) * sin^2(Delta_lambda / 2)
     * c = 2 * atan2( sqrt(a), sqrt(1 - a) )
     * d = R * c
     *
     * @param float $lat1 Latitude titik asal (derajat desimal)
     * @param float $lng1 Longitude titik asal (derajat desimal)
     * @param float $lat2 Latitude titik tujuan (derajat desimal)
     * @param float $lng2 Longitude titik tujuan (derajat desimal)
     * @return float Jarak fisik dalam satuan Kilometer dibulatkan ke 2 desimal
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Langkah 1: Konversi koordinat derajat ke radian (rad = deg * pi / 180)
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo   = deg2rad($lat2);
        $lngTo   = deg2rad($lng2);

        // Langkah 2: Hitung selisih lintang dan bujur
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        // Langkah 3: Hitung sinus kuadrat dari separuh selisih sudut (haversine)
        $a = pow(sin($latDelta / 2), 2) +
             cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2);

        // Langkah 4: Hitung jarak sudut angular sentral (c dalam radian)
        $angle = 2 * asin(sqrt($a));

        // Langkah 5: Kalikan jarak sudut dengan radius bumi (R = 6371 km)
        $distance = $angle * self::EARTH_RADIUS_KM;

        return round($distance, 2);
    }

    /**
     * Mengklasifikasikan kategori zona jarak berdasarkan standar BKPSDM:
     * - 'dekat'  : d < 5.0 km (Zona Hijau - efisiensi optimal)
     * - 'sedang' : 5.0 <= d <= 20.0 km (Zona Kuning - beban wajar)
     * - 'jauh'   : d > 20.0 km (Zona Merah - prioritas rotasi/mutasi)
     *
     * @param float $distanceKm
     * @return string 'dekat'|'sedang'|'jauh'
     */
    public function classifyZone(float $distanceKm): string
    {
        if ($distanceKm < 5.0) {
            return 'dekat';
        }

        if ($distanceKm <= 20.0) {
            return 'sedang';
        }

        return 'jauh';
    }

    /**
     * Mendapatkan kode warna hexadecimal standar untuk rendering peta GIS Leaflet:
     * - 'dekat'  => #059669 (Emerald Green)
     * - 'sedang' => #d97706 (Amber Warning)
     * - 'jauh'   => #dc2626 (Rose Danger)
     *
     * @param string $zone
     * @return string Hex color code
     */
    public function getZoneColor(string $zone): string
    {
        return match ($zone) {
            'dekat' => '#059669', // Emerald Green
            'sedang' => '#d97706', // Amber Warning
            'jauh' => '#dc2626', // Rose Danger
            default => '#6b7280', // Gray fallback
        };
    }
}

