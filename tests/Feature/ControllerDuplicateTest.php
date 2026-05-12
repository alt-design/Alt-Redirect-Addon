<?php

use AltDesign\AltRedirect\Models\Redirect;
use AltDesign\AltRedirect\Models\QueryString;
use Statamic\Facades\User;

beforeEach(function () {
    config(['alt-redirect.driver' => 'database']);
    // Authenticate as a user to bypass middleware if necessary
    $user = User::make()->makeSuper()->save();
    $this->actingAs($user);
});

it('does not create duplicate redirects when updating', function () {
    // 1. Create an initial redirect in the database
    $redirect = Redirect::create([
        'id' => 'existing-id',
        'from' => '/old-path',
        'to' => '/initial-new-path',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    // 2. Post to the create endpoint with the SAME ID but different 'to' path
    $response = $this->post(cp_route('alt-redirect.create'), [
        'id' => 'existing-id',
        'from' => '/old-path',
        'to' => '/updated-new-path',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $response->assertStatus(302); // Should redirect back

    // 3. Verify that we still only have ONE record and it was UPDATED
    $redirects = Redirect::where('from', '/old-path')->get();
    
    expect($redirects->count())->toBe(1);
    expect($redirects->first()->id)->toBe('existing-id');
    expect($redirects->first()->to)->toBe('/updated-new-path');
});

it('does not create duplicate query strings when updating', function () {
    // 1. Create an initial query string
    $qs = QueryString::create([
        'id' => 'existing-qs-id',
        'query_string' => 'gclid',
        'strip' => true,
        'sites' => ['default'],
    ]);

    // 2. Post to the create endpoint with the SAME ID but different 'strip' value
    $response = $this->post(cp_route('alt-redirect.query-strings.create'), [
        'type' => 'query-strings',
        'id' => 'existing-qs-id',
        'query_string' => 'gclid',
        'strip' => false,
        'sites' => ['default'],
    ]);

    $response->assertStatus(302);

    // 3. Verify that we still only have ONE record and it was UPDATED
    $queryStrings = QueryString::where('query_string', 'gclid')->get();
    
    expect($queryStrings->count())->toBe(1);
    expect($queryStrings->first()->id)->toBe('existing-qs-id');
    expect($queryStrings->first()->strip)->toBe(false);
});
