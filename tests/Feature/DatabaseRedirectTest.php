<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Statamic\Facades\Site;

beforeEach(function () {
    config(['alt-redirect.driver' => 'database']);
});

it('can redirect a simple path', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'simple-test-database',
        'from' => '/old-path-database',
        'to' => '/new-path-database',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-path-database')
        ->assertRedirect('/new-path-database')
        ->assertStatus(301);
});

it('can redirect with regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'regex-test-database',
        'from' => '/old-database/(.*)',
        'to' => '/new-database/$1',
        'redirect_type' => 302,
        'sites' => ['default'],
    ]);

    $this->get('/old-database/something')
        ->assertRedirect('/new-database/something')
        ->assertStatus(302);
});

it('can redirect with query strings in regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'query-regex-test-database',
        'from' => '/test-database\?source=(.*)',
        'to' => '/beans-database/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    // It should match and redirect, but since 'source' is not stripped, it's added back to the target
    $this->get('/test-database?source=mandem')
        ->assertRedirect('/beans-database/mandem?source=mandem')
        ->assertStatus(301);
});

it('can strip query strings', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('query-strings', [
        'id' => 'strip-gclid-database',
        'query_string' => 'gclid',
        'strip' => true,
        'sites' => ['default'],
    ]);

    $repository->save('redirects', [
        'id' => 'simple-strip-test-database',
        'from' => '/old-strip-database',
        'to' => '/new-strip-database',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-strip-database?gclid=123&other=456')
        ->assertRedirect('/new-strip-database?other=456')
        ->assertStatus(301);
});

it('can handle site specific redirects', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'site-test-database',
        'from' => '/only-default-database',
        'to' => '/matched-database',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    Site::setCurrent('default');
    $this->get('/only-default-database')->assertRedirect('/matched-database');

    Site::setCurrent('other');
    // If not default site, it should not match and return 404
    $this->get('/only-default-database')->assertNotFound();
});

it('forgives unescaped question marks in regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'unescaped-test-database',
        'from' => '/test-unescaped-database?source=(.*)', // Unescaped ?
        'to' => '/new-test-database/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/test-unescaped-database?source=mandem')
        ->assertRedirect('/new-test-database/mandem?source=mandem');
});

it('can redirect with non-standard regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'non-standard-regex-database',
        'from' => '^/products/(.+)$',
        'to' => '/shop/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/products/shoes')
        ->assertRedirect('/shop/shoes')
        ->assertStatus(301);
});

it('can delete a redirect', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'delete-test-database',
        'from' => '/old-delete',
        'to' => '/new-delete',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $repository->delete('redirects', ['id' => 'delete-test-database', 'from' => '/old-delete']);

    $this->get('/old-delete')->assertNotFound();
});

it('can delete a query string', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('query-strings', [
        'id' => 'delete-qs-database',
        'query_string' => 'gclid-delete',
        'strip' => true,
        'sites' => ['default'],
    ]);

    // This is what the frontend does: sends only query_string
    $repository->delete('query-strings', ['query_string' => 'gclid-delete']);

    // Check it's gone
    expect($repository->find('query-strings', 'query_string', 'gclid-delete'))->toBeNull();
});
