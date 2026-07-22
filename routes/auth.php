<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;


//Login
Route::view('login', 'auth.login')->name('login');
Route::post('login', LoginController::class);

//Register
Route::view('register', 'auth.register')->name('register');
Route::post('register', RegisterController::class);

//Oauth
Route::get('redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('callback',  [OAuthController::class, 'callback']);