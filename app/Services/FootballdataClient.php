<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FootballdataClient
{
    public function searchPlayers(string $query, int $page = 1, int $limit = 10): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) {
            throw new RuntimeException('Football data integration is not configured.');
        }

        $response = Http::baseUrl(rtrim(config('services.footballdata.base_url'), '/'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(8)
            ->retry(2, 150)
            ->get('/players', ['q' => $query, 'page' => $page, 'limit' => min($limit, 100)]);

        if ($response->failed() || $response->json('success') === false) {
            throw new RuntimeException('Football data provider request failed.');
        }

        return $response->json('data', []);
    }

    public function getPlayer(int $providerId): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) throw new RuntimeException('Football data integration is not configured.');
        $response = Http::baseUrl(rtrim(config('services.footballdata.base_url'), '/'))
            ->withToken($key)->acceptJson()->timeout(8)->retry(2, 150)->get("/players/{$providerId}");
        if ($response->failed() || $response->json('success') === false) throw new RuntimeException('Football data provider request failed.');
        return $response->json('data', []);
    }
}
