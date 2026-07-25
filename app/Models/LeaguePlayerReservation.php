<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePlayerReservation extends Model
{
    protected $fillable = ['league_id', 'user_id', 'player_id', 'player_data', 'slot_key', 'role', 'locked_at'];
    protected $casts = ['player_data' => 'array', 'locked_at' => 'datetime'];

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
