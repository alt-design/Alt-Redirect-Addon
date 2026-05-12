<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Models\Redirect;
use Illuminate\Support\Facades\Config;
use Statamic\Facades\File;

beforeEach(function () {
    Config::set('alt-redirect.driver', 'database');
    $this->artisan('migrate');
});

it('does not flag simple URLs with query strings as regex', function ($url) {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'test-id-'.md5($url),
        'from' => $url,
        'to' => '/target',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $redirect = $repository->find('redirects', 'from', $url);

    expect($redirect['is_regex'] ?? false)->toBeFalse();
})->with([
    '/fire-equipment-and-first-aid-storage-cabinets/dangerous-and-flammable-substance-coshh-storage-cabinet/?combination=286_1295',
    '/fire-equipment-and-first-aid-storage-cabinets/dangerous-and-flammable-substance-coshh-storage-cabinet/?combination=286_1297',
    '/sti-theft-stoppers/emergency-lighting-covers/?sort_by=price&sort_order=asc',
    '/industrial-spill-control/chemical-spill-control/?items_per_page=32',
    '/disabled-equipment/c-tec-quantec-addressable-nurse-call/page-3/?sort_by=position&sort_order=asc',
    '/search?q=foo',
    '/test.html?a=b',
    '/search/results+for+query',
    '/product-categories/spill-care?selectedTaxonomies[product_categories][spill-care]=spill-care',
    '/products/test.php',
    '/search?q=(foo)',
    '/credit-application/',
    '/profiles-add/',
    '/about/',
    '/delivery/',
    '/engineers-stock-and-spares/',
    '/special-offers/',
    '/services/',
    '/fire-risk-assessment-en/',
    '/online-training-courses/',
    '/personal-safety/',
    '/industrial-spill-control/',
    '/disabled-equipment/',
    '/fire-safety-stick/',
    '/sti-theft-stoppers/',
    '/fire-brigade-equipment/',
    '/fire-hose-reels/',
    '/intumescent-products/',
    '/fire-door-furniture/',
    '/fire-safety-signs/',
    '/site-fire-alarms/',
    '/emergency-lighting/',
    '/fire-alarms/',
    '/fire-equipment-and-storage-cabinets/',
    '/stands-and-trolleys/',
    '/fire-safety-packs/',
    '/spares/',
    '/shop/........../',
    '/welding-drapes/',
    '/fire-blankets/',
    '/fire-extinguishers/',
]);

it('still flags actual regex as regex', function ($url) {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'regex-id-'.md5($url),
        'from' => $url,
        'to' => '/target/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $redirect = $repository->find('redirects', 'from', $url);

    expect($redirect['is_regex'] ?? false)->toBeTrue();
})->with([
    '/products/(.*)',
    '^/old-path/(.+)$',
    '/blog/[0-9]{4}/[0-9]{2}/.*',
    '/test/(foo|bar)',
    '/test/\d+',
    '#/products/(.*)#',
    '/^foo$/i',
]);

it('handles file driver regex detection correctly', function () {
    Config::set('alt-redirect.driver', 'file');
    $repository = app(RepositoryInterface::class);

    // For file repository, we can't check 'is_regex' column but we can check if it's placed in the regex folder
    // but the file repository implementation also uses isRegex to decide on filename
    // Actually, FileRepository doesn't store is_regex in the YAML, but uses it for saving.

    $url = '/test?a=b';
    $repository->save('redirects', [
        'id' => 'file-test',
        'from' => $url,
        'to' => '/target',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    // If it's NOT regex, it should be saved with a hashed filename
    $expectedPath = 'content/alt-redirect/'.hash('sha512', base64_encode($url)).'.yaml';
    expect(File::disk()->exists($expectedPath))->toBeTrue();
});

it('can re-scan redirects and fix regex flags', function () {
    Config::set('alt-redirect.driver', 'database');
    $repository = app(RepositoryInterface::class);

    // Use model directly to bypass repository auto-calculation for setup
    Redirect::create([
        'id' => 'wrong-flag-1',
        'from' => '/simple-path?a=b',
        'to' => '/target',
        'redirect_type' => 301,
        'sites' => ['default'],
        'is_regex' => true, // Wrong!
    ]);

    Redirect::create([
        'id' => 'wrong-flag-2',
        'from' => '/products/(.*)',
        'to' => '/shop/$1',
        'redirect_type' => 301,
        'sites' => ['default'],
        'is_regex' => false, // Wrong!
    ]);

    $this->artisan('alt-redirect:re-scan-regex')
        ->expectsOutput('Successfully updated 2 redirects.')
        ->assertExitCode(0);

    $redirect1 = $repository->find('redirects', 'id', 'wrong-flag-1');
    $redirect2 = $repository->find('redirects', 'id', 'wrong-flag-2');

    expect($redirect1['is_regex'] ?? true)->toBeFalse();
    expect($redirect2['is_regex'] ?? false)->toBeTrue();
});
