<?php

use App\Http\Controllers\Dashboard\LeagueController;
use App\Http\Controllers\Dashboard\LogoutController;
use App\Http\Controllers\Dashboard\PowerCardController;
use App\Http\Controllers\Dashboard\SquadController;
use Illuminate\Support\Facades\Route;


Route::get('', [LeagueController::class, 'index'])->name('dashboard.index');
Route::post('leagues', [LeagueController::class, 'store'])->name('leagues.store');
Route::post('leagues/join', [LeagueController::class, 'join'])->name('leagues.join');
Route::get('leagues/{league}', [LeagueController::class, 'show'])->name('leagues.show');
Route::get('leagues/{league}/simulation-status', [LeagueController::class, 'simulationStatus'])->name('leagues.simulation.status');
Route::post('leagues/{league}/ready', [LeagueController::class, 'ready'])->name('leagues.ready');
Route::post('leagues/{league}/start', [LeagueController::class, 'start'])->name('leagues.start');
Route::get('leagues/{league}/squad', [SquadController::class, 'show'])->name('squads.show');
Route::get('leagues/{league}/members/{user}/squad', [SquadController::class, 'member'])->name('leagues.members.squad');
Route::get('leagues/{league}/players/search', [SquadController::class, 'search'])->middleware('throttle:30,1')->name('squads.players.search');
Route::post('leagues/{league}/squad', [SquadController::class, 'store'])->name('squads.store');
Route::get('leagues/{league}/cards', [PowerCardController::class, 'index'])->name('cards.index');
Route::post('leagues/{league}/cards', [PowerCardController::class, 'store'])->name('cards.store');

Route::post('logout', LogoutController::class)->name('logout');
