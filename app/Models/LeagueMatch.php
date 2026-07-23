<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueMatch extends Model
{
    protected $fillable = [
        'league_id', 'simulation_id', 'fixture_id', 'home_user_id', 'away_user_id', 'leg', 'status',
        'home_score', 'away_score', 'goal_scorers', 'result', 'home_points', 'away_points', 'boost_user_id',
        'decisive_factors', 'player_impacts', 'narrative', 'raw_data',
    ];

    protected $casts = ['goal_scorers' => 'array', 'decisive_factors' => 'array', 'player_impacts' => 'array', 'raw_data' => 'array'];

    public function simulation(): BelongsTo { return $this->belongsTo(LeagueSimulation::class, 'simulation_id'); }
    public function homeUser(): BelongsTo { return $this->belongsTo(User::class, 'home_user_id'); }
    public function awayUser(): BelongsTo { return $this->belongsTo(User::class, 'away_user_id'); }
}
