<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status === false) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been inactived. Please contact the administrator.']);
        }

        return $next($request);
    }
}