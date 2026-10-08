<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsEndpointTest extends TestCase
{
    public function test_robots_file_references_the_absolute_sitemap_url(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: GPTBot', false)
            ->assertSee('User-agent: OAI-SearchBot', false)
            ->assertSee('User-agent: ClaudeBot', false)
            ->assertSee('User-agent: PerplexityBot', false)
            ->assertSee('User-agent: Google-Extended', false)
            ->assertSee('User-agent: CCBot', false)
            ->assertSee('Sitemap: ' . route('sitemap'), false);
    }
}
