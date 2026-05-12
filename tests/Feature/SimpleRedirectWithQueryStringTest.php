<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Statamic\Facades\Site;

beforeEach(function () {
    config(['alt-redirect.driver' => 'database']);
});

it('can redirect a simple path with a query string', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'simple-qs-test',
        'from' => '/old-qs?foo=bar',
        'to' => '/new-qs',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-qs?foo=bar')
        ->assertRedirect('/new-qs?foo=bar')
        ->assertStatus(301);
});

it('can redirect a simple path with a query string when other query strings are present and stripped', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('query-strings', [
        'id' => 'strip-gclid',
        'query_string' => 'gclid',
        'strip' => true,
        'sites' => ['default'],
    ]);

    $repository->save('redirects', [
        'id' => 'simple-qs-strip-test',
        'from' => '/old-qs-strip?foo=bar',
        'to' => '/new-qs-strip',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-qs-strip?foo=bar&gclid=123')
        ->assertRedirect('/new-qs-strip?foo=bar')
        ->assertStatus(301);
});

it('can redirect a simple path without a query string when the request has a query string', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'simple-no-qs-test',
        'from' => '/old-no-qs',
        'to' => '/new-no-qs',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-no-qs?foo=bar')
        ->assertRedirect('/new-no-qs?foo=bar')
        ->assertStatus(301);
});

it('can redirect a simple path with a query string even with trailing slash differences', function () {
    $repository = app(RepositoryInterface::class);
    // 1. Redirect has trailing slash, request doesn't
    $repository->save('redirects', [
        'id' => 'simple-qs-trailing-test',
        'from' => '/old-qs-trailing/?foo=bar',
        'to' => '/new-qs-trailing',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-qs-trailing?foo=bar')
        ->assertRedirect('/new-qs-trailing?foo=bar')
        ->assertStatus(301);

    // 2. Redirect doesn't have trailing slash, request does
    $repository->save('redirects', [
        'id' => 'simple-qs-no-trailing-test',
        'from' => '/old-qs-no-trailing?foo=bar',
        'to' => '/new-qs-no-trailing',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-qs-no-trailing/?foo=bar')
        ->assertRedirect('/new-qs-no-trailing?foo=bar')
        ->assertStatus(301);
});
