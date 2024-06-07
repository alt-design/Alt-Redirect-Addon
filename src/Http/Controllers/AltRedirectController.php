<?php

namespace AltDesign\AltRedirect\Http\Controllers;

use AltDesign\AltRedirect\Helpers\Data;
use Illuminate\Http\Request;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository;
use Statamic\Filesystem\Manager;

class AltRedirectController
{
    public function index()
    {
        // Grab the old directory just in case
        $oldDirectory = Blueprint::directory();

        //Publish form
        // Get an array of values
        $data = new Data('redirects');
        $values = $data->all();

        // Get a blueprint.So
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../../resources/blueprints')->find('redirects');
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
        ]);
    }

    public function create(Request $request)
    {
        $data = new Data('redirects');

        // Get a blueprint.
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__.'/../../../resources/blueprints')->find('redirects');

        // Get a Fields object
        $fields = $blueprint->fields();

        // Add the values to the array
        $arr = $request->all();
        $arr['id'] = uniqid();

        // Avoid looping redirects (caught by validation, but give a more helpful error)
        if ($arr['to'] === $arr['from']) {
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

        $data = new Data('redirects');
        $values = $data->all();

        return [
            'data' => $values,
        ];
    }

    public function delete(Request $request)
    {
        $manager = new Manager();
        $manager->disk()->delete('content/alt-redirect/'.hash('sha512', base64_encode($request->from)).'.yaml');
        $manager->disk()->delete('content/alt-redirect/'.base64_encode($request->from).'.yaml');

        $data = new Data('redirects');
        $values = $data->all();

        return [
            'data' => $values,
        ];
    }

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
        $data = new Data('redirect');
        $data->saveAll($currentData);

    }
}
