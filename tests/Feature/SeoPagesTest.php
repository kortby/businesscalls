<?php

use Inertia\Testing\AssertableInertia as Assert;

test('all industry vertical landing pages return 200 and render correctly', function () {
    $slugs = [
        'plumbing-answering-service',
        'hvac-ai-receptionist',
        'electrical-contractor-dispatch',
        'roofing-emergency-call-handling',
        'appliance-repair-scheduling',
        'pest-control-answering-service',
        'garage-door-emergency-dispatch',
        'locksmith-call-answering',
    ];

    foreach ($slugs as $slug) {
        $response = $this->get(route('industries.show', ['slug' => $slug]));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Industries/Show')
            ->where('slug', $slug)
            ->has('industry')
            ->has('industry.metaTitle')
            ->has('industry.metaDescription')
            ->has('industry.faqs')
        );
    }
});

test('invalid industry slug returns 404', function () {
    $response = $this->get('/industries/non-existent-trade-slug');
    $response->assertNotFound();
});

test('missed call revenue calculator returns 200', function () {
    $response = $this->get(route('tools.calculator'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Tools/Calculator')
    );
});

test('sitemap xml includes all industry urls and calculator', function () {
    $response = $this->get(route('sitemap'));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');

    $content = $response->getContent();
    expect($content)->toContain('<urlset');
    expect($content)->toContain(route('tools.calculator'));
    expect($content)->toContain(route('industries.show', ['slug' => 'plumbing-answering-service']));
    expect($content)->toContain(route('industries.show', ['slug' => 'hvac-ai-receptionist']));
    expect($content)->toContain(route('industries.show', ['slug' => 'electrical-contractor-dispatch']));
    expect($content)->toContain(route('industries.show', ['slug' => 'roofing-emergency-call-handling']));
    expect($content)->toContain(route('industries.show', ['slug' => 'appliance-repair-scheduling']));
    expect($content)->toContain(route('industries.show', ['slug' => 'pest-control-answering-service']));
    expect($content)->toContain(route('industries.show', ['slug' => 'garage-door-emergency-dispatch']));
    expect($content)->toContain(route('industries.show', ['slug' => 'locksmith-call-answering']));
});
