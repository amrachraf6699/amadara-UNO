<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class League extends Model
{
    use HasFactory;

    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_YET_TO_START = 'yet_to_start';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FINISHED = 'finished';

    public const STATUSES = [
        self::STATUS_ARCHIVED,
        self::STATUS_YET_TO_START,
        self::STATUS_RUNNING,
        self::STATUS_FINISHED,
    ];

    public const ICONS = [
        'bx bx-football',
        'bx bx-trophy',
        'bx bx-shield',
        'bx bx-flag',
        'bx bx-medal',
        'bx bx-crown',
        'bx bx-bolt-circle',
        'bx bx-star',
        'bx bx-target-lock',
    ];

    protected $fillable = [
        'name',
        'max_users',
        'icon',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (League $league): void {
            $league->code ??= static::generateUniqueCode();
            $league->status ??= self::STATUS_YET_TO_START;
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(5));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
