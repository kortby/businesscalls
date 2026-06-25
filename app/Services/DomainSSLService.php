<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainSSLService
{
    /**
     * Programmatically request, verify, and apply Let's Encrypt SSL certificates for a custom domain.
     */
    public function provisionSSL(string $domain): bool
    {
        Log::info("Initiating Let's Encrypt SSL provisioning for custom domain: {$domain}");

        // Endpoint for external ACME client gateway / proxy manager (e.g. Caddy Admin API)
        $caddyUrl = env('CADDY_API_URL', 'http://localhost:2019/config/apps/http/servers/srv0/routes');

        try {
            $response = Http::timeout(10)
                ->post($caddyUrl, [
                    'match' => [
                        [
                            'host' => [$domain],
                        ],
                    ],
                    'handle' => [
                        [
                            'handler' => 'subroute',
                            'routes' => [
                                [
                                    'handle' => [
                                        [
                                            'handler' => 'reverse_proxy',
                                            'upstreams' => [
                                                ['dial' => env('APP_UPSTREAM_DIAL', '127.0.0.1:8000')],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info("Successfully registered and applied SSL certificate routing for custom domain: {$domain}");

                return true;
            }

            Log::error("SSL provisioning API rejected domain {$domain}: ".$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error("ACME SSL provisioning request failed for domain {$domain}: ".$e->getMessage());

            return false;
        }
    }
}
