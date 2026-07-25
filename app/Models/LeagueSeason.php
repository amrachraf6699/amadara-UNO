<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueSeason extends Model
{
    public const SETUP = 'setup';
    public const TRANSFER_WINDOW = 'transfer_window';
    public const RUNNING = 'running';
    public const FINISHED = 'finished';

    protected $fillable = ['league_id', 'number', 'status', 'started_at', 'completed_at'];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function selections(): HasMany { return $this->hasMany(LeagueSeasonSelection::class, 'season_id'); }
    public function readyEntries(): HasMany { return $this->hasMany(LeagueSeasonReady::class, 'season_id'); }
    public function transfers(): HasMany { return $this->hasMany(LeagueSeasonTransfer::class, 'season_id'); }
    public function simulations(): HasMany { return $this->hasMany(LeagueSimulation::class, 'season_id'); }
}
