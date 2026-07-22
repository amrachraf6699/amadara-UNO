<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    private const STATE_TTL = 600;

    public function redirect()
    {
        $nonce = Str::random(40);
        $issuedAt = now()->timestamp;
        $statePayload = $nonce.'|'.$issuedAt;
        $state = $nonce.'.'.$issuedAt.'.'.hash_hmac(
            'sha256',
            $statePayload,
            (string) config('app.key')
        );

        // Keep state self-contained so OAuth does not depend on shared session
        // storage between the redirect and callback on production hosts.
        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $state = (string) $request->query('state', '');
        $stateParts = explode('.', $state, 3);
        $nonce = $stateParts[0] ?? '';
        $issuedAt = $stateParts[1] ?? '';
        $signature = $stateParts[2] ?? '';
        $statePayload = $nonce.'|'.$issuedAt;
        $isFresh = ctype_digit($issuedAt)
            && abs(now()->timestamp - (int) $issuedAt) <= self::STATE_TTL;

        if (! $nonce || ! $signature || ! $isFresh || ! hash_equals(
            $signature,
            hash_hmac('sha256', $statePayload, (string) config('app.key'))
        )) {
            return redirect()->route('login')
                ->with('error', 'Your Google sign-in session expired. Please try again.');
        }

        // State has been validated above, so Socialite does not need its
        // session-backed validator that fails on some production hosts.
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $user = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        // Do not link an OAuth login to an account registered with a password.
        if (! $user && $socialiteUser->getEmail()) {
            $existingUser = User::where('email', $socialiteUser->getEmail())->first();

            if ($existingUser) {
                return redirect()->route('login')
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
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME)
            ->with('status', 'Welcome back to Amadara UNO.');
    }
}
