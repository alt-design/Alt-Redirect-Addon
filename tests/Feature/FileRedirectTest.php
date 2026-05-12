<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Statamic\Facades\Site;

beforeEach(function () {
    config(['alt-redirect.driver' => 'file']);
});

it('can redirect a simple path', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'simple-test-file',
        'from' => '/old-path-file',
        'to' => '/new-path-file',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-path-file')
        ->assertRedirect('/new-path-file')
        ->assertStatus(301);
});

it('can redirect with regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'regex-test-file',
        'from' => '/old-file/(.*)',
        'to' => '/new-file/$1',
        'redirect_type' => 302,
        'sites' => ['default'],
    ]);

    $this->get('/old-file/something')
        ->assertRedirect('/new-file/something')
        ->assertStatus(302);
});

it('can redirect with query strings in regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'query-regex-test-file',
        'from' => '/test-file\?source=(.*)',
        'to' => '/beans-file/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    // It should match and redirect, but since 'source' is not stripped, it's added back to the target
    $this->get('/test-file?source=mandem')
        ->assertRedirect('/beans-file/mandem?source=mandem')
        ->assertStatus(301);
});

it('can strip query strings', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('query-strings', [
        'id' => 'strip-gclid-file',
        'query_string' => 'gclid',
        'strip' => true,
        'sites' => ['default'],
    ]);

    $repository->save('redirects', [
        'id' => 'simple-strip-test-file',
        'from' => '/old-strip-file',
        'to' => '/new-strip-file',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/old-strip-file?gclid=123&other=456')
        ->assertRedirect('/new-strip-file?other=456')
        ->assertStatus(301);
});

it('can handle site specific redirects', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'site-test-file',
        'from' => '/only-default-file',
        'to' => '/matched-file',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    Site::setCurrent('default');
    $this->get('/only-default-file')->assertRedirect('/matched-file');

    Site::setCurrent('other');
    // If not default site, it should not match and return 404
    $this->get('/only-default-file')->assertNotFound();
});

it('forgives unescaped question marks in regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'unescaped-test-file',
        'from' => '/test-unescaped-file?source=(.*)', // Unescaped ?
        'to' => '/new-test-file/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $this->get('/test-unescaped-file?source=mandem')
        ->assertRedirect('/new-test-file/mandem?source=mandem');
});

it('can redirect with non-standard regex', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'non-standard-regex-file',
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
        'id' => 'delete-test-file',
        'from' => '/old-delete',
        'to' => '/new-delete',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $repository->delete('redirects', ['id' => 'delete-test-file', 'from' => '/old-delete']);

    $this->get('/old-delete')->assertNotFound();
});

it('can delete a query string', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('query-strings', [
        'id' => 'delete-qs-file',
        'query_string' => 'gclid-delete',
        'strip' => true,
        'sites' => ['default'],
    ]);

    // This is what the frontend does: sends only query_string
    $repository->delete('query-strings', ['query_string' => 'gclid-delete']);

    // Check it's gone
    expect($repository->find('query-strings', 'query_string', 'gclid-delete'))->toBeNull();
});
