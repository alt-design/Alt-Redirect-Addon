<?php
namespace AltDesign\AltRedirect\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Filesystem\Manager;

use Statamic\Fields\BlueprintRepository as Blueprint;

use AltDesign\AltRedirect\Helpers\Data;

class AltRedirectController
{

    public function index()
    {
        // Grab the old directory just in case
        $oldDirectory = with(new Blueprint)->directory();

        //Publish form
        // Get an array of values
        $data = new Data('redirects');
        $values = $data->all();

        // Get a blueprint.
        $blueprint = with(new Blueprint)->setDirectory(__DIR__ . '/../../../resources/blueprints')->find('redirects');
        // Get a Fields object
        $fields = $blueprint->fields();
        // Add the values to the object
        $fields = $fields->addValues($values);
        // Pre-process the values.
        $fields = $fields->preProcess();

        // Reset the directory to the old one
        with(new Blueprint)->setDirectory($oldDirectory);

        return view('alt-redirect::index', [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'data' => $values,
        ]);
    }

    public function create(Request $request)
    {

        // Grab the old directory just in case
        $oldDirectory = with(new Blueprint)->directory();

        $data = new Data('redirects');

        // Get a blueprint.
        $blueprint = with(new Blueprint)->setDirectory(__DIR__ . '/../../../resources/blueprints')->find('redirects');

        // Get a Fields object
        $fields = $blueprint->fields();

        // Add the values to the object
        $arr = $request->all();
        $arr['id'] = uniqid();
        $fields = $fields->addValues($arr);
        $fields->validate();

        $data->setAll($fields->process()->values()->toArray());

        $data = new Data('redirects');
        $values = $data->all();

        // Reset the directory to the old one
        with(new Blueprint)->setDirectory($oldDirectory);

        return [
            'data' => $values
        ];
    }

    public function delete(Request $request)
    {
        $manager = new Manager();
        $manager->disk()->delete('content/alt-redirect/' . hash( 'sha512', base64_encode($request->from)) . '.yaml');
        $manager->disk()->delete('content/alt-redirect/' . base64_encode($request->from) . '.yaml');

        $data = new Data('redirects');
        $values = $data->all();

        return [
            'data' => $values
        ];
    }

    public function export(Request $request)
    {
        $data = new Data('redirects');

        $callback = function() use ($data) {
            $df = fopen("php://output", 'w');

            fputcsv($df, ['from', 'to', 'redirect_type', 'id']);

            // Use the data from the request instead of fetching from the database
            foreach ($data->data as $row) {
                fputcsv($df, [$row['from'], $row['to'], $row['redirect_type'], $row['id']]); // Adjust as per your data structure
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
        if ($handle !== FALSE) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                $temp = [
                    'from' => $row[0],
                    'to' => $row[1],
                    'redirect_type' => $row[2],
                    'id' => $row[3] ?? uniqid(),
                ];
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
        return;
    }
}
