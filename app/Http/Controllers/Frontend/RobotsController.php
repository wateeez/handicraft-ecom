<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class RobotsController extends Controller
{
    public function index()
    {
        $privatePaths = [
            '/cart',
            '/checkout',
            '/quote/success',
            '/login',
            '/register',
            '/password',
            '/admin/',
        ];
        $crawlerGroups = [
            'GPTBot',
            'OAI-SearchBot',
            'ClaudeBot',
            'PerplexityBot',
            'Google-Extended',
            'CCBot',
            '*',
        ];

        $lines = [];
        foreach ($crawlerGroups as $crawler) {
            $lines[] = 'User-agent: ' . $crawler;
            $lines[] = 'Allow: /';
            foreach ($privatePaths as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
            $lines[] = '';
        }
        $lines[] = 'Sitemap: ' . route('sitemap');

        return response(implode(PHP_EOL, $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
