<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SquadSelection extends Model
{
    protected $fillable = ['squad_id', 'league_id', 'player_id', 'player_data', 'slot_key', 'role'];

    protected $casts = ['player_data' => 'array'];

    public function squad(): BelongsTo { return $this->belongsTo(Squad::class); }
    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function getPlayerAttribute(): object { return (object) ($this->player_data ?? []); }
}
