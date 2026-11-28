<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy & Cache Controller untuk API Wilayah Administratif Indonesia (wilayah.id).
 * Mendukung pemilihan bebas 4 tingkat: Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa/Kelurahan.
 *
 * Dilengkapi fallback SSL (untuk Laragon Windows tanpa cacert.pem), fallback API Emsifa,
 * serta proteksi agar hasil kosong tidak pernah di-cache.
 */
class WilayahController extends Controller
{
    private const BASE_URL = 'https://wilayah.id/api';
    private const EMSIFA_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    /**
     * Mengambil data wilayah dengan cache aman (tidak pernah meng-cache array kosong),
     * fallback withoutVerifying() untuk Windows Laragon, dan fallback API Emsifa.
     */
    private function fetchWilayah(string $cacheKey, string $wilayahPath, ?string $emsifaPath = null): array
    {
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $data = $this->requestJson(self::BASE_URL . $wilayahPath, 'data');

        // Fallback ke API Emsifa jika wilayah.id sedang tidak dapat dijangkau
        if (empty($data) && $emsifaPath) {
            $raw = $this->requestJson(self::EMSIFA_URL . $emsifaPath, null);
            if (is_array($raw) && !empty($raw)) {
                $data = array_values(array_filter(array_map(function ($row) {
                    if (!is_array($row) || empty($row['id']) || empty($row['name'])) {
                        return null;
                    }
                    $code = $this->formatEmsifaCode((string)$row['id']);
                    return [
                        'code' => $code,
                        'name' => ucwords(strtolower((string)$row['name'])),
                    ];
                }, $raw)));
            }
        }

        if (!empty($data)) {
            Cache::put($cacheKey, $data, now()->addHours(24));
        }

        return $data;
    }

    /**
     * HTTP GET dengan fallback withoutVerifying() bila CA bundle SSL tidak tersedia (Laragon Windows).
     */
    private function requestJson(string $url, ?string $dataKey): array
    {
        foreach ([true, false] as $verifySsl) {
            try {
                $client = $verifySsl ? Http::timeout(8) : Http::withoutVerifying()->timeout(8);
                $res = $client->acceptJson()->get($url);
                if ($res->successful()) {
                    $json = $dataKey ? $res->json($dataKey) : $res->json();
                    if (is_array($json) && !empty($json)) {
                        return $json;
                    }
                }
            } catch (\Throwable $e) {
                // Coba ulang tanpa verifikasi SSL pada iterasi berikutnya
                continue;
            }
        }

        return [];
    }

    /**
     * Mengubah format ID Emsifa (321215) ke format bertitik wilayah.id (32.12.15).
     */
    private function formatEmsifaCode(string $id): string
    {
        $digits = preg_replace('/\D/', '', $id);
        $len = strlen($digits);
        if ($len === 2) {
            return $digits;
        }
        if ($len === 4) {
            return substr($digits, 0, 2) . '.' . substr($digits, 2, 2);
        }
        if ($len >= 6) {
            return substr($digits, 0, 2) . '.' . substr($digits, 2, 2) . '.' . substr($digits, 4, 2);
        }
        return $id;
    }

    public function provinces(): JsonResponse
    {
        $data = $this->fetchWilayah('wilayah_id:v2:provinces', '/provinces.json', '/provinces.json');
        return response()->json(['data' => $data]);
    }

    public function regencies(string $provinceCode): JsonResponse
    {
        if (!preg_match('/^\d{2}$/', $provinceCode)) {
            return response()->json(['data' => []], 422);
        }

        $data = $this->fetchWilayah(
            "wilayah_id:v2:regencies:{$provinceCode}",
            "/regencies/{$provinceCode}.json",
            "/regencies/{$provinceCode}.json"
        );

        return response()->json(['data' => $data]);
    }

    public function districts(string $regencyCode): JsonResponse
    {
        if (!preg_match('/^\d{2}\.\d{2}$/', $regencyCode)) {
            return response()->json(['data' => []], 422);
        }

        $emsifaCode = str_replace('.', '', $regencyCode);
        $data = $this->fetchWilayah(
            "wilayah_id:v2:districts:{$regencyCode}",
            "/districts/{$regencyCode}.json",
            "/districts/{$emsifaCode}.json"
        );

        return response()->json(['data' => $data]);
    }

    public function villages(string $districtCode): JsonResponse
    {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $districtCode)) {
            return response()->json(['data' => []], 422);
        }

        $emsifaCode = str_replace('.', '', $districtCode);
        $data = $this->fetchWilayah(
            "wilayah_id:v2:villages:{$districtCode}",
            "/villages/{$districtCode}.json",
            "/villages/{$emsifaCode}.json"
        );

        return response()->json(['data' => $data]);
    }
}
