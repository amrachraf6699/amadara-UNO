<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FootballPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id', 'name', 'known_name', 'first_name', 'last_name', 'normalized_name', 'position', 'nationality', 'age', 'height_cm',
        'team_provider_id', 'team_name', 'image_url', 'profile_url', 'raw_data',
    ];

    protected $casts = ['raw_data' => 'array'];

    public function selections(): HasMany
    {
        return $this->hasMany(SquadSelection::class);
    }
}
