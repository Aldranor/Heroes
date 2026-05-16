<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAvatarIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->avatar()->exists()) {
            return redirect()->route('onboarding.avatar.create');
        }

        return $next($request);
    }
}
