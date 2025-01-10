<?php namespace AltDesign\AltRedirect\Helpers;

use Statamic\Fields\BlueprintRepository;
use Statamic\Filesystem\Manager;

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
        $data = new Data('query-strings');
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../resources/blueprints')->find('query-strings');
        // Add the values to the array

        foreach($this->defaultQueryStrings as $query) {
            $fields = $blueprint->fields();
            $arr = [
                'id' => uniqid(),
                'sites' => ['default'],
                'query_string' => $query,
            ];
            $fields = $fields->addValues($arr);
            $fields->validate();
            $data->setAll($fields->process()->values()->toArray());
        }
        (new Manager())->disk()->makeDirectory('content/alt-redirect/.installed');
    }
}
