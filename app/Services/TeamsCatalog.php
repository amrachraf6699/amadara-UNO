<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class TeamsCatalog
{
    private ?array $players = null;

    public function search(string $query, int $page = 1, bool $more = false, int $limit = 10): array
    {
        $needle = $this->normalize($query);
        $matches = array_values(array_filter($this->players(), fn (array $player): bool => str_contains($player['_normalized_name'], $needle)));
        $offset = $more ? 3 + max(0, $page - 2) * $limit : 0;

        return [
            'players' => array_slice($matches, $offset, $more ? $limit : 3),
            'has_more' => count($matches) > $offset + ($more ? $limit : 3),
        ];
    }

    public function find(int $id): ?array
    {
        return $this->players()[$id - 1] ?? null;
    }

    private function players(): array
    {
        if ($this->players !== null) return $this->players;

        $path = public_path('teams.json');
        if (! is_file($path)) throw new RuntimeException('The local player catalogue is missing.');
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) throw new RuntimeException('The local player catalogue is invalid.');

        return $this->players = array_map(function (array $player, int $index): array {
            $clubs = array_values(array_filter($player['clubes'] ?? [], 'is_string'));
            $name = trim((string) ($player['nombre'] ?? ''));
            $birthDate = $player['fecha_nacimiento'] ?? null;
            $age = null;
            if ($birthDate) {
                try { $age = Carbon::parse($birthDate)->age; } catch (\Throwable) { }
            }

            return [
                'id' => $index + 1,
                'name' => $name,
                'known_name' => $name,
                'first_name' => Str::before($name, ' '),
                'last_name' => Str::afterLast($name, ' '),
                'nationality' => $clubs[1] ?? null,
                'age' => $age,
                'height_cm' => null,
                'position' => null,
                'team_name' => $clubs[0] ?? null,
                'image_url' => null,
                '_normalized_name' => $this->normalize($name),
            ];
        }, $data, array_keys($data));
    }

    private function normalize(string $value): string
    {
        return trim(Str::ascii(Str::lower($value)));
    }
}
