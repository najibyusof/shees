<?php

namespace App\Http\Middleware;

use App\Services\MobileTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function __construct(private readonly MobileTokenService $tokenService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json([
                'message' => 'Missing mobile access token.',
            ], 401);
        }

        $token = $this->tokenService->findValidToken($bearer);

        if (! $token || ! $token->user) {
            return response()->json([
                'message' => 'Invalid or expired mobile access token.',
            ], 401);
        }

        $this->tokenService->touch($token);
        $request->attributes->set('mobile_token_id', $token->id);
        $request->setUserResolver(fn () => $token->user);
        // Also wire the auth guard so Gate/policies resolve the mobile user
        // correctly – without this, $this->authorize() and authorizeResource()
        // in API controllers see no user and deny every request.
        Auth::setUser($token->user);

        return $next($request);
    }
}
