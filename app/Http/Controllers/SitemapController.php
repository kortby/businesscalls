<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate the sitemap XML response for JustMascot.
     */
    public function __invoke(): Response
    {
        $today = now()->toDateString();

        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => $today,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('tools.calculator'),
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('about'),
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('pricing'),
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('contact'),
                'lastmod' => $today,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('privacy'),
                'lastmod' => $today,
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('terms'),
                'lastmod' => $today,
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
        ];

        // Add all programmatic industry landing pages
        $industrySlugs = [
            'plumbing-answering-service',
            'hvac-ai-receptionist',
            'electrical-contractor-dispatch',
            'roofing-emergency-call-handling',
            'appliance-repair-scheduling',
            'pest-control-answering-service',
            'garage-door-emergency-dispatch',
            'locksmith-call-answering',
        ];

        foreach ($industrySlugs as $slug) {
            $urls[] = [
                'loc' => route('industries.show', ['slug' => $slug]),
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($url['loc']).'</loc>';
            $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            $xml .= '<changefreq>'.$url['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$url['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
