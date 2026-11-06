<?php

use AltDesign\AltRedirect\ServiceProvider;
use Illuminate\Support\Collection;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\CP\Navigation\NavItem;
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

/**
 * Find a top-level item in the Alt Tools section.
 */
function altToolsGroup(string $name): ?NavItem
{
    $section = navSections()->firstWhere('display', 'Alt Tools');

    return collect($section['items'] ?? [])->first(fn (NavItem $item) => $item->display() === $name);
}

it('lists Alt Redirect in the SEO group under Alt Tools', function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());

    $group = altToolsGroup('SEO');

    expect($group)->not->toBeNull()
        ->and($group->children()->map->display()->all())->toContain('Alt Redirect');
});

it('links the SEO group to one of its pages', function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());

    $group = altToolsGroup('SEO');

    expect($group->url())->toBe($group->children()->first()->url());
});

it('uses icons that exist in the running Statamic version', function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());

    $group = altToolsGroup('SEO');

    collect([$group, ...$group->children()])->each(
        fn (NavItem $item) => expect($item->svg())->toContain('<svg')
    );
});

it('leaves Alt Redirect out of the core Tools section', function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());

    $tools = navSections()->firstWhere('display', 'Tools');

    expect($tools)->not->toBeNull()
        ->and(collect($tools['items'])->map->display()->all())->not->toContain('Alt Redirect');
});

it('shows no Alt Tools section to a user without the permission', function () {
    $this->actingAs(User::make()->id('nav-editor'));

    expect(navSections()->firstWhere('display', 'Alt Tools'))->toBeNull();
});

it('lists the query strings page alongside the redirects', function () {
    $this->actingAs(User::make()->id('nav-test')->makeSuper());

    expect(altToolsGroup('SEO')->children()->map->display()->all())
        ->toBe(['Alt Redirect', 'Redirect query strings']);
});
