<?php namespace AltDesign\AltRedirect\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Blueprint;
use AltDesign\AltRedirect\Helpers\Data;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;

class AltRedirectController{

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
            'values'    => $fields->values(),
            'meta'      => $fields->meta(),
            'data'      => $values,
        ]);
    }

    public function create(Request $request) {

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
        $manager->disk()->delete('content/alt-redirect/' . base64_encode($request->from) . '.yaml');

        $data = new Data('redirects');
        $values = $data->all();

        return [
            'data' => $values
        ];
    }

}
