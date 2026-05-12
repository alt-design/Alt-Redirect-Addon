<?php

namespace AltDesign\AltRedirect\Helpers;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Statamic\Fields\BlueprintRepository;

class DefaultQueryStrings
{
    public array $defaultQueryStrings = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'msclkid',
        'srsltid',
    ];

    public function makeDefaultQueryStrings()
    {
        $repository = app(RepositoryInterface::class);

        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../resources/blueprints')->find('query-strings');
        // Add the values to the array

        foreach ($this->defaultQueryStrings as $query) {
            $fields = $blueprint->fields();
            $arr = [
                'id' => md5($query),
                'sites' => ['default'],
                'query_string' => $query,
                'strip' => true,
            ];
            $fields = $fields->addValues($arr);
            $fields->validate();
            $repository->save('query-strings', $fields->process()->values()->toArray());
        }
    }
}
