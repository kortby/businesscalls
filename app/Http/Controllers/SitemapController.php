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
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('about'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('pricing'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('contact'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('privacy'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('terms'),
                'lastmod' => '2026-07-26',
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
        ];

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
