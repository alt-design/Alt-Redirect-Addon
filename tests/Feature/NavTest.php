<?php

use AltDesign\AltRedirect\ServiceProvider;
use Illuminate\Support\Collection;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\User;

/**
 * Build the control panel nav with this addon's item registered.
 *
 * AddonTestCase swaps the nav for a mock after the addon has booted, so a real
 * nav is put back and the addon's own nav registration is run against it.
 */
function navSections(): Collection
{
    Nav::swap(new Navigation);

    (fn () => $this->addToNav())->call(app()->getProvider(ServiceProvider::class));

    return Nav::build(false);
}

beforeEach(function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());
});

it('lists Alt Redirect under Alt Tools in the control panel nav', function () {
    $section = navSections()->firstWhere('display', 'Alt Tools');

    expect($section)->not->toBeNull()
        ->and(collect($section['items'])->map->display()->all())->toContain('Alt Redirect');
});

it('leaves Alt Redirect out of the core Tools section', function () {
    $tools = navSections()->firstWhere('display', 'Tools');

    expect($tools)->not->toBeNull()
        ->and(collect($tools['items'])->map->display()->all())->not->toContain('Alt Redirect');
});
