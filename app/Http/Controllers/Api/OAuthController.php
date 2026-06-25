<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantOAuthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Handle client credentials grant and return access tokens.
     */
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');

        if ($grantType !== 'client_credentials') {
            return response()->json([
                'error' => 'unsupported_grant_type',
                'message' => 'The grant type is unsupported.',
            ], 400);
        }

        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        // Allow Basic Authentication as standard server-to-server OAuth configuration
        if (! $clientId || ! $clientSecret) {
            $clientId = $request->getUser();
            $clientSecret = $request->getPassword();
        }

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'error' => 'invalid_client',
                'message' => 'Client credentials are required.',
            ], 401);
        }

        // Query the master database using the Master connection
        $tenant = Tenant::where('client_id', $clientId)->first();

        if (! $tenant || ! hash_equals($tenant->client_secret, $clientSecret)) {
            return response()->json([
                'error' => 'invalid_client',
                'message' => 'Invalid client credentials.',
            ], 401);
        }

        $token = Str::random(40);
        $expiresIn = 3600; // 1 hour

        TenantOAuthToken::create([
            'tenant_id' => $tenant->id,
            'access_token' => $token,
            'expires_at' => now()->addSeconds($expiresIn),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
        ]);
    }
}
