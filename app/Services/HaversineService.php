<?php

namespace App\Services;

class HaversineService
{
    /**
     * Radius rata-rata bumi dalam kilometer.
     */
    public const EARTH_RADIUS_KM = 6371.0;

    /**
     * Menghitung jarak garis lurus (great-circle distance) antara 2 titik koordinat (dalam Kilometer).
     *
     * @param float $lat1 Latitude titik pertama
     * @param float $lng1 Longitude titik pertama
     * @param float $lat2 Latitude titik kedua
     * @param float $lng2 Longitude titik kedua
     * @return float Jarak dalam Kilometer dibulatkan ke 2 desimal
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Konversi derajat ke radian
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo   = deg2rad($lat2);
        $lngTo   = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        $distance = $angle * self::EARTH_RADIUS_KM;

        return round($distance, 2);
    }

    /**
     * Mengklasifikasikan kategori zona jarak berdasarkan nilai kilometer:
     * - 'dekat'  : < 5 km
     * - 'sedang' : 5 s/d 20 km
     * - 'jauh'   : > 20 km
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
     * Mendapatkan warna hex standar untuk status zona peta:
     */
    public function getZoneColor(string $zone): string
    {
        return match ($zone) {
            'dekat' => '#059669', // Emerald Green
            'sedang' => '#d97706', // Amber Yellow
            'jauh' => '#dc2626',   // Rose Red (Prioritas mutasi)
            default => '#6b7280',
        };
    }
}

