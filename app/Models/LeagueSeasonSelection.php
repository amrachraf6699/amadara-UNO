<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueSeasonSelection extends Model
{
    protected $fillable = ['season_id', 'user_id', 'player_id', 'player_data', 'slot_key', 'role'];
    protected $casts = ['player_data' => 'array'];
    public function season(): BelongsTo { return $this->belongsTo(LeagueSeason::class, 'season_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getPlayerAttribute(): object { return (object) ($this->player_data ?? []); }
}
