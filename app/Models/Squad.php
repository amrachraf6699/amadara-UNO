<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Squad extends Model
{
    protected $fillable = ['league_id', 'user_id', 'formation', 'locked_at'];

    protected $casts = ['locked_at' => 'datetime'];

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function selections(): HasMany { return $this->hasMany(SquadSelection::class); }
}
