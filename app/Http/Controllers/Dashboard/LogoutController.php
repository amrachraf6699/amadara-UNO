<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'You have been logged out.', 'redirect_url' => url('/')]);
        }

        return redirect('/')
            ->with('status', 'You have been logged out.');
    }
}
