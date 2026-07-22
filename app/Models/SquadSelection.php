<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SquadSelection extends Model
{
    protected $fillable = ['squad_id', 'league_id', 'football_player_id', 'slot_key', 'role'];

    public function squad(): BelongsTo { return $this->belongsTo(Squad::class); }
    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function footballPlayer(): BelongsTo { return $this->belongsTo(FootballPlayer::class); }
}
