<?php

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Illuminate\Http\UploadedFile;
use Statamic\Facades\User;

beforeEach(function () {
    config(['alt-redirect.driver' => 'file']);
    $this->user = User::make()->makeSuper()->save();
});

it('can export redirects as csv', function () {
    $repository = app(RepositoryInterface::class);
    $repository->save('redirects', [
        'id' => 'export-test-1',
        'from' => '/export-old-1',
        'to' => '/export-new-1',
        'redirect_type' => 301,
        'sites' => ['default'],
    ]);

    $response = $this->actingAs($this->user)
        ->get(cp_route('alt-redirect.export'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    $rows = explode("\n", trim($content));

    expect($rows)->toHaveCount(2);
    expect($rows[0])->toBe('from,to,redirect_type,sites,id');
    expect($rows[1])->toContain('/export-old-1,/export-new-1,301,default,export-test-1');
});

it('can import redirects from csv', function () {
    $csvContent = "from,to,redirect_type,sites,id\n";
    $csvContent .= "/import-old-1,/import-new-1,301,default,import-test-1\n";
    $csvContent .= '/import-old-2,/import-new-2,302,"default,other",import-test-2';

    $file = UploadedFile::fake()->createWithContent('redirects.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->post(cp_route('alt-redirect.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();

    $repository = app(RepositoryInterface::class);
    $redirects = $repository->all('redirects');

    expect($redirects)->toHaveCount(2);
    expect($redirects[0]['from'])->toBe('/import-old-1');
    expect($redirects[1]['sites'])->toBe(['default', 'other']);
});
