<?php

test('sitemap.xml route returns successful xml response', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml');

    $content = $response->getContent();
    expect($content)->toContain('<urlset');
    expect($content)->toContain('<loc>');
    expect($content)->toContain(route('home'));
    expect($content)->toContain(route('about'));
    expect($content)->toContain(route('pricing'));
    expect($content)->toContain(route('contact'));
    expect($content)->toContain(route('privacy'));
    expect($content)->toContain(route('terms'));
});

test('robots.txt file exists and has correct directives', function () {
    $path = public_path('robots.txt');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)->toContain('User-agent: *');
    expect($content)->toContain('Disallow: /admin');
    expect($content)->toContain('Sitemap: https://justmascot.io/sitemap.xml');
});

test('public marketing pages return successful response', function () {
    $this->get(route('home'))->assertStatus(200);
    $this->get(route('about'))->assertStatus(200);
    $this->get(route('pricing'))->assertStatus(200);
    $this->get(route('contact'))->assertStatus(200);
    $this->get(route('privacy'))->assertStatus(200);
    $this->get(route('terms'))->assertStatus(200);
});
