<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueSimulation extends Model
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    protected $fillable = [
        'league_id', 'status', 'prompt_version', 'prompt_hash', 'model', 'generation_options',
        'request_payload_hash', 'raw_response', 'normalized_response', 'validation_errors',
        'attempt_count', 'started_at', 'completed_at', 'failed_at',
    ];

    protected $casts = [
        'generation_options' => 'array', 'normalized_response' => 'array', 'validation_errors' => 'array',
        'started_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime',
    ];

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function matches(): HasMany { return $this->hasMany(LeagueMatch::class, 'simulation_id'); }
    public function standings(): HasMany { return $this->hasMany(LeagueStanding::class, 'simulation_id'); }
}
