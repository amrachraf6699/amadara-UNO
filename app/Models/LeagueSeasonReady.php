<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeagueSeasonReady extends Model { protected $fillable = ['season_id', 'user_id', 'ready_at']; protected $casts = ['ready_at' => 'datetime']; }
