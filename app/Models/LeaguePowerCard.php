<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePowerCard extends Model
{
    public const STEAL = 'steal';
    public const GUARD = 'guard';
    public const BOOST = 'boost';
    public const TYPES = [self::STEAL, self::GUARD, self::BOOST];

    protected $fillable = [
        'league_id', 'user_id', 'card_type', 'target_user_id', 'target_player_id', 'replacement_player_id',
        'resolution_status', 'resolution_reason', 'resolution_data',
    ];

    protected $casts = ['resolution_data' => 'array'];

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function targetUser(): BelongsTo { return $this->belongsTo(User::class, 'target_user_id'); }
}
