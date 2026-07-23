<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueEffectiveSelection extends Model
{
    protected $fillable = ['league_id', 'user_id', 'player_id', 'player_data', 'slot_key', 'role'];
    protected $casts = ['player_data' => 'array'];

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getPlayerAttribute(): object { return (object) ($this->player_data ?? []); }
}
