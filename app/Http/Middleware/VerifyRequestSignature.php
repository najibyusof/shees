<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates HMAC-SHA256 signatures on API requests for non-repudiation.
 *
 * Expected header: X-Signature with format: sha256=base64(hmac_sha256(request_body, shared_secret))
 * For GET requests with no body, signature is computed against the URL query string.
 *
 * Usage in routes:
 *   Route::post('/api/critical-action', action)->middleware('verify-signature:api_signature_secret')
 */
class VerifyRequestSignature
{
    public function handle(Request $request, Closure $next, ?string $secretKey = null): Response
    {
        if (! $secretKey) {
            $secretKey = config('app.api_signature_secret');
        }

        if (! $secretKey) {
            return $next($request);
        }

        $signature = $request->header('X-Signature');

        if (! $signature) {
            if ($request->isMethod('POST', 'PUT', 'PATCH', 'DELETE')) {
                return response()->json([
                    'error' => 'Missing X-Signature header',
                    'message' => 'Request signature is required for this action',
                ], 400);
            }

            return $next($request);
        }

        $payload = $request->getContent() ?: $request->getQueryString() ?: '';
        $expectedSignature = 'sha256=' . base64_encode(hash_hmac('sha256', $payload, $secretKey, true));

        if (! hash_equals($signature, $expectedSignature)) {
            return response()->json([
                'error' => 'Invalid signature',
                'message' => 'Request signature verification failed',
            ], 403);
        }

        return $next($request);
    }
}
