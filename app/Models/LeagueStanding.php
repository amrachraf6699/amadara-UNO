<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueStanding extends Model
{
    protected $fillable = [
        'league_id', 'simulation_id', 'user_id', 'played', 'wins', 'draws', 'losses',
        'goals_for', 'goals_against', 'goal_difference', 'points',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function simulation(): BelongsTo { return $this->belongsTo(LeagueSimulation::class, 'simulation_id'); }
}
