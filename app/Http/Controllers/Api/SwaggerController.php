<?php

namespace App\Http\Controllers\Api;

use Illuminate\View\View;

class SwaggerController
{
    /**
     * Display Swagger UI documentation
     */
    public function index(): View
    {
        return view('swagger');
    }

    /**
     * Get JSON documentation
     */
    public function json()
    {
        $docsPath = storage_path('api-docs/api-docs.json');

        if (!file_exists($docsPath)) {
            return response()->json(['error' => 'Documentation not found'], 404);
        }

        return response()->file($docsPath, [
            'Content-Type' => 'application/json',
        ]);
    }
}
