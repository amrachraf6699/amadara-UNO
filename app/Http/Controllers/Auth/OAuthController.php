<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $user = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        // Do not link an OAuth login to an account registered with a password.
        if (! $user && $socialiteUser->getEmail()) {
            $existingUser = User::where('email', $socialiteUser->getEmail())->first();

            if ($existingUser) {
                return redirect(RouteServiceProvider::HOME)
                    ->with('error', 'You are not registered that way.');
            }
        }

        $user ??= new User;
        $user->fill([
            'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'Google User',
            'email' => $socialiteUser->getEmail(),
            'provider' => 'google',
            'provider_id' => $socialiteUser->getId(),
            'avatar' => $socialiteUser->getAvatar(),
        ]);

        if ($user->email && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended(RouteServiceProvider::HOME)->with('status', 'Welcome back to Amadara UNO.');
    }
}
