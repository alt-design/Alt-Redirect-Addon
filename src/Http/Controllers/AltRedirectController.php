<?php

namespace AltDesign\AltRedirect\Http\Controllers;

use AltDesign\AltRedirect\Helpers\Data;
use Illuminate\Http\Request;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository;
use Statamic\Filesystem\Manager;

class AltRedirectController
{
    private string $type = 'redirects';
    private array $actions = [
        'redirects' => 'alt-redirect.create',
        'query-strings' => 'alt-redirect.query-strings.create'
    ];
    private array $titles = [
        'redirects' => 'Alt Redirect',
        'query-strings' => 'Alt Redirect - Query Strings'
    ];

    private array $instructions = [
        'redirects' => 'Manage your redirects here. For detailed instructions, please consult the Alt Redirect Readme',
        'query-strings' => 'Alt Redirect can strip query strings from your URIs before they are processed. These are listed below, add the key for query strings you want strip',
    ];

    // Work out what page we're handling
    public function __construct()
    {
        $path = request()->path();
        if (str_contains($path, 'query-strings')) {
            $this->type = 'query-strings';
        }
    }

    public function index()
    {
        // Grab the old directory just in case
        $oldDirectory = Blueprint::directory();

        //Publish form
        // Get an array of values
        $data = new Data($this->type);
        $values = $data->all();

        // Get a blueprint.So
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../../resources/blueprints')->find($this->type);
        // Get a Fields object
        $fields = $blueprint->fields();
        // Add the values to the object
        $fields = $fields->addValues($values);
        // Pre-process the values.
        $fields = $fields->preProcess();

        // Reset the directory to the old one
        if ($oldDirectory) {
            Blueprint::setDirectory($oldDirectory);
        }

        return view('alt-redirect::index', [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'data' => $values,
            'type' => $this->type,
            'action' => $this->actions[$this->type],
            'title' => $this->titles[$this->type],
            'instructions' => $this->instructions[$this->type],
        ]);
    }

    public function create(Request $request)
    {
        $data = new Data($this->type);

        // Get a blueprint.
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../../resources/blueprints')->find($this->type);

        // Get a Fields object
        $fields = $blueprint->fields();

        // Add the values to the array
        $arr = $request->all();
        $arr['id'] = uniqid();

        // Avoid looping redirects (caught by validation, but give a more helpful error)
        if (($this->type == 'redirects') && ($arr['to'] === $arr['from'])) {
            $response = [
                'message' => "'To' and 'From' addresses cannot be identical",
                'errors' => [
                    'from' => ['This field must be unique.'],
                    'to' => ['This field must be unique.'],
                ],
            ];

            return response()->json($response, 422);
        }

        $fields = $fields->addValues($arr);
        $fields->validate();

        $data->setAll($fields->process()->values()->toArray());

        $data = new Data($this->type);
        $values = $data->all();

        return [
            'data' => $values,
        ];
    }

    public function delete(Request $request)
    {
        $disk = (new Manager())->disk();
        switch($this->type) {
            case "redirects" :
                $disk->delete('content/alt-redirect/' . hash('sha512', base64_encode($request->from)) . '.yaml');
                $disk->delete('content/alt-redirect/' . base64_encode($request->from) . '.yaml');
                $disk->delete('content/alt-redirect/alt-regex/' . hash('sha512', base64_encode($request->id)) . '.yaml');
                $disk->delete('content/alt-redirect/alt-regex/'. base64_encode($request->id) . '.yaml');
                break;
            case 'query-strings':
                $disk->delete('content/alt-redirect/query-strings/' . hash('sha512', base64_encode($request->query_string)) . '.yaml');
                break;
        }

        $data = new Data($this->type);
        $values = $data->all();

        return [
            'data' => $values,
        ];
    }

    // Import and Export can stay hardcoded to redirects since I/O for Query Strings aren't supported atm
    public function export(Request $request)
    {
        $data = new Data('redirects');

        $callback = function () use ($data) {
            $df = fopen('php://output', 'w');

            fputcsv($df, ['from', 'to', 'redirect_type', 'sites', 'id']);

            // Use the data from the request instead of fetching from the database
            foreach ($data->data as $row) {
                fputcsv($df, [$row['from'], $row['to'], $row['redirect_type'], is_array($row['sites']) ? implode(',', $row['sites']) : $row['sites'], $row['id']]); // Adjust as per your data structure
            }

            fclose($df);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="redirects_'.date('Y-m-d\_H:i:s').'.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $currentData = json_decode($request->get('data'), true);
        $file = $request->file('file');
        $handle = fopen($file->path(), 'r');
        if ($handle !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $temp = [
                    'from' => $row[0],
                    'to' => $row[1],
                    'redirect_type' => $row[2],
                    'sites' => !empty($row[3] ?? false) ? explode(',', $row[3]) : ['default'],
                    'id' => ! empty($row[4] ?? false) ? $row[4] : uniqid(),
                ];
                // Skip the redirect if it'll create an infinite loop (handles empty redirects too)
                if ($temp['to'] === $temp['from']) {
                    continue;
                }
                foreach ($currentData as $rdKey => $redirect) {
                    if ($redirect['id'] === $temp['id'] || $redirect['from'] === $temp['from']) {
                        $currentData[$rdKey] = $temp;

                        continue 2;
                    }
                }
                $currentData[] = $temp;
            }

            // Close the file handle
            fclose($handle);
        }
        $data = new Data('redirects');
        $data->saveAll($currentData);

    }

    // Toggle a key in a certain item and return the data afterwards
    public function toggle(Request $request)
    {
        $toggleKey =  $request->get('toggleKey');
        $index =  $request->get('index');
        $data = new Data($this->type);

        switch ($this->type) {
            case 'query-strings':
                $item = $data->getByKey('query_string', $index);
                if ($item === null) {
                    return response('Error finding item', 500);
                }

                if (!isset($item[$toggleKey])) {
                    $item[$toggleKey] = false;
                }
                $item[$toggleKey] = !$item[$toggleKey];
                $data->setAll($item);
                break;
            default:
                return response('Method not implemented', 500);
        }
        $data = new Data($this->type);
        $values = $data->all();

        return [
            'data' => $values,
        ];
    }
}
