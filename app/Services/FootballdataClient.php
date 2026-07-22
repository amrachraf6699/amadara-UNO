<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FootballdataClient
{
    public function searchPlayers(string $query, int $page = 1, int $limit = 10): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) {
            throw new RuntimeException('Football data integration is not configured.');
        }

        $http = Http::baseUrl(rtrim(config('services.footballdata.base_url') ?: 'https://footballdata.io/api/v1', '/'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(8)
            ->retry(2, 150, null, false);

        $response = $http
            ->get('/players', [
                'q' => $query,
                'page' => $page,
                'limit' => min($limit, 100),
            ]);

        $payload = $response->json();
        Log::info('Footballdata players search response', [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'status' => $response->status(),
            'response' => $payload ?? $response->body(),
        ]);

        if (! $response->failed() && $response->json('success') !== false && $this->containsQuery($this->responsePlayers($payload), $query)) {
            return is_array($payload) ? $payload : [];
        }

        $fallback = $http->get('/search', ['q' => $query, 'type' => 'players', 'limit' => min($limit, 25)]);
        $fallbackPayload = $fallback->json();
        Log::info('Footballdata players fallback response', [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'status' => $fallback->status(),
            'response' => $fallbackPayload ?? $fallback->body(),
        ]);

        if ($fallback->failed() || $fallback->json('success') === false) {
            throw new RuntimeException('Football data provider request failed.');
        }

        return is_array($fallbackPayload) ? $fallbackPayload : [];
    }

    public function getPlayer(int $providerId): array
    {
        $key = config('services.footballdata.api_key');
        if (! $key) throw new RuntimeException('Football data integration is not configured.');
        $response = Http::baseUrl(rtrim(config('services.footballdata.base_url') ?: 'https://footballdata.io/api/v1', '/'))
            ->withToken($key)->acceptJson()->timeout(8)->retry(2, 150, null, false)->get("/players/{$providerId}");
        $payload = $response->json();
        Log::info('Footballdata player response', [
            'provider_id' => $providerId,
            'status' => $response->status(),
            'response' => $payload ?? $response->body(),
        ]);
        if ($response->failed() || $response->json('success') === false) throw new RuntimeException('Football data provider request failed.');
        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    private function responsePlayers(?array $payload): array
    {
        if (! is_array($payload)) return [];
        $data = $payload['data'] ?? null;
        if (is_array($data) && array_is_list($data)) return $data;
        $players = (is_array($data) ? ($data['players'] ?? $data['results'] ?? null) : null)
            ?? $payload['players']
            ?? $payload['results']
            ?? $data
            ?? $payload;
        return is_array($players) && array_is_list($players) ? $players : [];
    }

    private function containsQuery(array $players, string $query): bool
    {
        $query = Str::lower($query);
        return collect($players)->contains(function (array $player) use ($query): bool {
            $name = $player['known_name'] ?? $player['player_name'] ?? $player['name'] ?? '';
            return Str::contains(Str::lower($name), $query);
        });
    }
}
