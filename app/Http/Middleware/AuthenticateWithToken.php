<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $accessToken = PersonalAccessToken::where('token', $hashedToken)->first();

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.',
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired.',
            ], 401);
        }

        // Update last used timestamp
        $accessToken->forceFill(['last_used_at' => now()])->save();

        // Get the user and set the current access token
        $user = $accessToken->tokenable;
        $user->withAccessToken($accessToken);

        // Set the authenticated user
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

