<?php

use AltDesign\AltRedirect\Repositories\DatabaseRepository;
use AltDesign\AltRedirect\Repositories\FileRepository;
use AltDesign\AltRedirect\Models\Redirect;
use AltDesign\AltRedirect\Models\QueryString;

it('saves redirect without id by generating one', function () {
    $repository = new DatabaseRepository();
    $data = [
        'from' => '/old-url',
        'to' => '/new-url',
        'redirect_type' => '301',
        'sites' => ['default'],
    ];

    $repository->save('redirects', $data);
    
    $redirect = Redirect::where('from', '/old-url')->first();
    expect($redirect)->not->toBeNull();
    expect($redirect->id)->not->toBeEmpty();
    expect($redirect->to)->toBe('/new-url');
});

it('saves query string without id by generating one', function () {
    $repository = new DatabaseRepository();
    $data = [
        'query_string' => 'foo=bar',
        'strip' => true,
        'sites' => ['default'],
    ];

    $repository->save('query-strings', $data);

    $qs = QueryString::where('query_string', 'foo=bar')->first();
    expect($qs)->not->toBeNull();
    expect($qs->id)->not->toBeEmpty();
});

it('is idempotent when saving without id', function () {
    $repository = new DatabaseRepository();
    $data = [
        'from' => '/stable-url',
        'to' => '/target-1',
        'redirect_type' => '301',
        'sites' => ['default'],
    ];

    $repository->save('redirects', $data);
    $redirect1 = Redirect::where('from', '/stable-url')->first();
    $id1 = $redirect1->id;

    // Save again with same from but different to
    $data['to'] = '/target-2';
    $repository->save('redirects', $data);
    $redirect2 = Redirect::where('from', '/stable-url')->first();
    
    expect($redirect2->id)->toBe($id1);
    expect($redirect2->to)->toBe('/target-2');
    expect(Redirect::where('from', '/stable-url')->count())->toBe(1);
});

it('is idempotent for query strings when saving without id', function () {
    $repository = new DatabaseRepository();
    $data = [
        'query_string' => 'stable-qs',
        'strip' => true,
        'sites' => ['default'],
    ];

    $repository->save('query-strings', $data);
    $qs1 = QueryString::where('query_string', 'stable-qs')->first();
    $id1 = $qs1->id;

    // Save again with same query_string but different strip value
    $data['strip'] = false;
    $repository->save('query-strings', $data);
    $qs2 = QueryString::where('query_string', 'stable-qs')->first();
    
    expect($qs2->id)->toBe($id1);
    expect($qs2->strip)->toBe(false);
    expect(QueryString::where('query_string', 'stable-qs')->count())->toBe(1);
});

it('can migrate redirects without id from files to database', function () {
    // 1. Setup file-based redirects without ID
    $fileRepo = new FileRepository();
    $from = '/legacy-url';
    $fileRepo->save('redirects', [
        'from' => $from,
        'to' => '/new-url',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);
    
    // 2. Verify it's in the FileRepository and has no ID
    $legacy = $fileRepo->find('redirects', 'from', $from);
    expect($legacy)->not->toBeNull();
    expect($legacy)->not->toHaveKey('id');
    
    // 3. Run migration command
    $this->artisan('alt-redirect:migrate-file-redirects')
        ->expectsConfirmation('This will migrate all file-based redirects and query strings to the database. Do you wish to continue?', 'yes')
        ->assertExitCode(0);
        
    // 4. Verify it's in the database with a generated ID
    $redirect = Redirect::where('from', $from)->first();
    expect($redirect)->not->toBeNull();
    expect($redirect->id)->not->toBeEmpty();
    expect($redirect->to)->toBe('/new-url');
});
