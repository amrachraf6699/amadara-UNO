<?php

use App\Http\Controllers\Dashboard\LeagueController;
use App\Http\Controllers\Dashboard\LogoutController;
use Illuminate\Support\Facades\Route;


Route::get('', [LeagueController::class, 'index'])->name('dashboard.index');
Route::post('leagues', [LeagueController::class, 'store'])->name('leagues.store');
Route::post('leagues/join', [LeagueController::class, 'join'])->name('leagues.join');

Route::post('logout', LogoutController::class)->name('logout');
