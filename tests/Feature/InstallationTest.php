<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;

it('installs default query strings on boot when not present', function () {
    $repository = app(RepositoryInterface::class);
    $queryStrings = $repository->all('query-strings');

    // It should now install them automatically
    expect($queryStrings)->not->toBeEmpty();
    expect($queryStrings)->toHaveCount(9);

    $handles = collect($queryStrings)->pluck('query_string')->toArray();
    expect($handles)->toContain('utm_source');
    expect($handles)->toContain('utm_medium');
    expect($handles)->toContain('gclid');
});
