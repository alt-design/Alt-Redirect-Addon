<?php
namespace AltDesign\AltRedirect\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Statamic\Facades\Blueprint;
use Statamic\Facades\File;
use AltDesign\AltRedirect\Helpers\Data;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;

class AltRedirectController
{

    public function index()
    {
        //Publish form
        // Get an array of values
        $data = new Data('redirects');
        $values = $data->all();

        // Get a blueprint.
        $blueprint = Blueprint::setDirectory(__DIR__ . '/../../../resources/blueprints')->find('redirects');
        // Get a Fields object
        $fields = $blueprint->fields();
        // Add the values to the object
        $fields = $fields->addValues($values);
        // Pre-process the values.
        $fields = $fields->preProcess();

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
        $blueprint = Blueprint::setDirectory(__DIR__ . '/../../../resources/blueprints')->find('redirects');

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

        return [
            'data' => $values
        ];
    }

    public function delete(Request $request)
    {
        $manager = new Manager();
        try {
            $manager->disk()->delete('content/alt-redirect/' . hash( 'sha512', base64_encode($request->from)) . '.yaml');
        } catch (err) {
            $manager->disk()->delete('content/alt-redirect/' . hash( 'sha512', base64_encode($request->from)) . '.yaml');
        }

        $data = new Data('redirects');
        $values = $data->all();

        return [
            'data' => $values
        ];
    }

    public function export(Request $request)
    {
        $data = $request->get('data');
        $filePath = storage_path('routes.csv'); // Adjust the path as needed

        $handle = fopen($filePath, 'w');
        fputcsv($handle, ['from', 'to', 'redirect_type', 'id']);

        // Use the data from the request instead of fetching from the database
        foreach ($data as $row) {
            fputcsv($handle, [$row['from'], $row['to'], $row['redirect_type'], $row['id']]); // Adjust as per your data structure
        }
        fclose($handle);

        // Read the file's content
        $file = File::get($filePath);

        // Create the response with the file content
        return response()->download($filePath, 'routes.csv', [
            'Content-Type' => 'text/csv',
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
