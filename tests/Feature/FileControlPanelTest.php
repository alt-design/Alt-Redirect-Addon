<?php

use Statamic\Facades\User;

beforeEach(function () {
    config(['alt-redirect.driver' => 'file']);
    $this->user = User::make()->makeSuper()->save();
});

it('can access the redirects index page', function () {
    $this->actingAs($this->user)
        ->get(cp_route('alt-redirect.index'))
        ->assertStatus(200)
        ->assertSee('Alt Redirect');
});

it('can access the query strings index page', function () {
    $this->actingAs($this->user)
        ->get(cp_route('alt-redirect.query-strings.index'))
        ->assertStatus(200)
        ->assertSee('Alt Redirect - Query Strings');
});
