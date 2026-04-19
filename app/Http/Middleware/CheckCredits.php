<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCredits
{
    public function handle(Request $request, Closure $next, int $required = 100): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasEnoughCredits($required)) {
            return response()->json(['message' => 'Недостаточно кредитов.'], 402);
        }

        return $next($request);
    }
}
