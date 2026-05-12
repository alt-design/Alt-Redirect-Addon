<?php

namespace AltDesign\AltRedirect\Http\Controllers;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository;

class AltRedirectController
{
    private string $type = 'redirects';

    private array $actions = [
        'redirects' => 'alt-redirect.create',
        'query-strings' => 'alt-redirect.query-strings.create',
    ];

    private array $titles = [
        'redirects' => 'Alt Redirect',
        'query-strings' => 'Alt Redirect - Query Strings',
    ];

    private array $instructions = [
        'redirects' => 'Manage your redirects here. For detailed instructions, please consult the Alt Redirect Readme',
        'query-strings' => 'Alt Redirect can strip query strings from your URIs before they are processed. These are listed below, add the key for query strings you want strip',
    ];

    // Work out what page we're handling
    public function __construct()
    {
        if (collect([request()->path(), request()->input('type')])->filter(fn ($value) => ! empty($value) && str_contains($value, 'query-strings'))->isNotEmpty()) {
            $this->type = 'query-strings';
        }
    }

    public function index()
    {
        // Grab the old directory just in case
        $oldDirectory = Blueprint::directory();

        // Publish form
        // Get an array of values
        $values = app(RepositoryInterface::class)->all($this->type);

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

        return Inertia::render('alt-redirect::Index', [
            'blueprint' => $blueprint->toPublishArray(),
            'initialValues' => $fields->values()->all(),
            'initialMeta' => $fields->meta()->all(),
            'items' => $values,
            'type' => $this->type,
            'action' => $this->actions[$this->type],
            'title' => $this->titles[$this->type],
            'instructions' => $this->instructions[$this->type],
        ]);
    }

    public function create(Request $request)
    {
        $repository = app(RepositoryInterface::class);

        // Get a blueprint.
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../../resources/blueprints')->find($this->type);

        // Get a Fields object
        $fields = $blueprint->fields();

        // Add the values to the array
        $arr = $request->all();
        if (empty($arr['id'])) {
            $arr['id'] = uniqid();
        }

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

        $repository->save($this->type, $fields->process()->values()->toArray());

        return redirect()->back()->with([
            'items' => $repository->all($this->type),
        ]);
    }

    public function delete(Request $request)
    {
        $repository = app(RepositoryInterface::class);
        $repository->delete($this->type, $request->all());

        return redirect()->back()->with([
            'items' => $repository->all($this->type),
        ]);
    }

    // Import and Export can stay hardcoded to redirects since I/O for Query Strings aren't supported atm
    public function export(Request $request)
    {
        $redirects = app(RepositoryInterface::class)->all('redirects');

        $callback = function () use ($redirects) {
            $df = fopen('php://output', 'w');

            fputcsv($df, ['from', 'to', 'redirect_type', 'sites', 'id']);

            // Use the data from the request instead of fetching from the database
            foreach ($redirects as $row) {
                fputcsv($df, [$row['from'], $row['to'], $row['redirect_type'], is_array($row['sites'] ?? null) ? implode(',', $row['sites']) : ($row['sites'] ?? ''), $row['id']]); // Adjust as per your data structure
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
        $repository = app(RepositoryInterface::class);
        $currentData = $repository->all('redirects');

        $file = $request->file('file');
        $handle = fopen($file->path(), 'r');
        if ($handle !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $temp = [
                    'from' => $row[0],
                    'to' => $row[1],
                    'redirect_type' => $row[2],
                    'sites' => ! empty($row[3] ?? false) ? explode(',', $row[3]) : ['default'],
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
        $repository->saveAll('redirects', $currentData);

        return redirect()->back()->with([
            'items' => $repository->all('redirects'),
        ]);
    }

    // Toggle a key in a certain item and return the data afterwards
    public function toggle(Request $request)
    {
        $toggleKey = $request->get('toggleKey');
        $index = $request->get('index');
        $repository = app(RepositoryInterface::class);

        switch ($this->type) {
            case 'query-strings':
                $item = $repository->find($this->type, 'query_string', $index);
                if ($item === null) {
                    return response('Error finding item', 500);
                }

                if (! isset($item[$toggleKey])) {
                    $item[$toggleKey] = false;
                }
                $item[$toggleKey] = ! $item[$toggleKey];
                $repository->save($this->type, $item);
                break;
            default:
                return response('Method not implemented', 500);
        }

        return redirect()->back()->with([
            'items' => $repository->all($this->type),
        ]);
    }
}
