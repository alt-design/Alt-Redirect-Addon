<?php

use Statamic\Filesystem\Manager;
use Illuminate\Support\Facades\Schema;

it('does NOT create the installed marker if using database driver', function () {
    config(['alt-redirect.driver' => 'database']);

    $disk = (new Manager)->disk();
    $markerPath = 'content/alt-redirect/.installed';

    // 1. Ensure marker is gone
    if ($disk->exists($markerPath)) {
        $disk->delete($markerPath);
    }

    // 2. Call the installation logic (normally called during boot)
    app(\AltDesign\AltRedirect\ServiceProvider::class, ['app' => app()])->installDefaultQueryStrings();

    // 3. Verify marker DOES NOT EXIST
    expect($disk->exists($markerPath))->toBeFalse();
});

it('seeds the database via migration', function () {
    config(['alt-redirect.driver' => 'database']);
    
    // We need to re-run the migration with the database driver active
    // Since we are in a test, the migrations might have run with 'file' driver initially
    (new \AltDesign\AltRedirect\Helpers\DefaultQueryStrings)->makeDefaultQueryStrings();

    $repository = app(\AltDesign\AltRedirect\Repositories\RepositoryManager::class)->driver('database');
    $queryStrings = $repository->all('query-strings');

    expect($queryStrings)->not->toBeEmpty();
    expect($queryStrings)->toHaveCount(9);
});
