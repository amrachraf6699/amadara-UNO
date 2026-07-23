<?php

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    protected $model = League::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' League',
            'max_users' => 10,
            'icon' => fake()->randomElement(League::ICONS),
            'status' => League::STATUS_YET_TO_START,
        ];
    }
}
