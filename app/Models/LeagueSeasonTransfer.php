<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeagueSeasonTransfer extends Model { protected $fillable = ['season_id', 'user_id', 'outgoing_player_id', 'incoming_player_id', 'slot_key']; }
