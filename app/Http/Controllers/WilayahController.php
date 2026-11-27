<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy & Cache Controller untuk API Wilayah Administratif Indonesia (wilayah.id).
 * Mendukung pemilihan bebas 4 tingkat: Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa/Kelurahan.
 */
class WilayahController extends Controller
{
    private const BASE_URL = 'https://wilayah.id/api';

    public function provinces(): JsonResponse
    {
        $data = Cache::remember('wilayah_id:provinces', now()->addHours(24), function () {
            $res = Http::timeout(8)->get(self::BASE_URL . '/provinces.json');
            return $res->successful() ? ($res->json('data') ?? []) : [];
        });

        return response()->json(['data' => $data]);
    }

    public function regencies(string $provinceCode): JsonResponse
    {
        if (!preg_match('/^\d{2}$/', $provinceCode)) {
            return response()->json(['data' => []], 422);
        }

        $data = Cache::remember("wilayah_id:regencies:{$provinceCode}", now()->addHours(24), function () use ($provinceCode) {
            $res = Http::timeout(8)->get(self::BASE_URL . "/regencies/{$provinceCode}.json");
            return $res->successful() ? ($res->json('data') ?? []) : [];
        });

        return response()->json(['data' => $data]);
    }

    public function districts(string $regencyCode): JsonResponse
    {
        if (!preg_match('/^\d{2}\.\d{2}$/', $regencyCode)) {
            return response()->json(['data' => []], 422);
        }

        $data = Cache::remember("wilayah_id:districts:{$regencyCode}", now()->addHours(24), function () use ($regencyCode) {
            $res = Http::timeout(8)->get(self::BASE_URL . "/districts/{$regencyCode}.json");
            return $res->successful() ? ($res->json('data') ?? []) : [];
        });

        return response()->json(['data' => $data]);
    }

    public function villages(string $districtCode): JsonResponse
    {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $districtCode)) {
            return response()->json(['data' => []], 422);
        }

        $data = Cache::remember("wilayah_id:villages:{$districtCode}", now()->addHours(24), function () use ($districtCode) {
            $res = Http::timeout(8)->get(self::BASE_URL . "/villages/{$districtCode}.json");
            return $res->successful() ? ($res->json('data') ?? []) : [];
        });

        return response()->json(['data' => $data]);
    }
}
