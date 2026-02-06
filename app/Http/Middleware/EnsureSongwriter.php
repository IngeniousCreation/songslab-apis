<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSongwriter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'songwriter' && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'This action is only available to songwriters. Sounding Board Members can provide feedback but cannot upload songs.',
            ], 403);
        }

        return $next($request);
    }
}

