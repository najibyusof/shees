<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardAuthController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('view_dashboard');

        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return $this->error('No active dashboard session.', null, 400);
        }

        if (! method_exists($token, 'delete')) {
            return $this->error('Current dashboard token cannot be revoked.', null, 400);
        }

        $token->delete();

        return $this->success(null, 'Dashboard session revoked.');
    }
}
