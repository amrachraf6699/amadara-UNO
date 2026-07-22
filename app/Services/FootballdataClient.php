<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FootballdataClient
{
    public function searchPlayers(string $query, int $page = 1, int $limit = 10): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) {
            throw new RuntimeException('Football data integration is not configured.');
        }

        $response = Http::baseUrl(rtrim(config('services.footballdata.base_url') ?: 'https://footballdata.io/api/v1', '/'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(8)
            ->retry(2, 150)
            ->get('/players', ['q' => $query, 'page' => $page, 'limit' => min($limit, 100)]);

        $payload = $response->json();
        Log::info('Footballdata players search response', [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'status' => $response->status(),
            'response' => $payload ?? $response->body(),
        ]);

        if ($response->failed() || $response->json('success') === false) {
            throw new RuntimeException('Football data provider request failed.');
        }

        return is_array($payload) ? $payload : [];
    }

    public function getPlayer(int $providerId): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) throw new RuntimeException('Football data integration is not configured.');
        $response = Http::baseUrl(rtrim(config('services.footballdata.base_url') ?: 'https://footballdata.io/api/v1', '/'))
            ->withToken($key)->acceptJson()->timeout(8)->retry(2, 150)->get("/players/{$providerId}");
        $payload = $response->json();
        Log::info('Footballdata player response', [
            'provider_id' => $providerId,
            'status' => $response->status(),
            'response' => $payload ?? $response->body(),
        ]);
        if ($response->failed() || $response->json('success') === false) throw new RuntimeException('Football data provider request failed.');
        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }
}
