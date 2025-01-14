<?php
namespace AltDesign\AltRedirect\Helpers;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;

class Data
{
    public $type;
    public $manager;
    public $currentFile;
    public $data = [];
    public $regexData = [];

    // This array is used to create the paths and to load files
    protected $types = [
        'redirects' => [
            'content/alt-redirect',
            'content/alt-redirect/alt-regex'
        ],
        'query-strings' => [
            'content/alt-redirect/query-strings'
        ],
    ];

    public function __construct($type, $onlyRegex = false)
    {
        $this->type = $type;

        // New up Stat File Manager
        $this->manager = new Manager();

        // Check redirect folder exists
        $this->checkOrMakeDirectories();

        if(!$onlyRegex) {
            $allRedirects = [];
            foreach($this->types[$this->type] as $path) {
                $filePath = base_path($path);
                $allRedirects = array_merge($allRedirects, File::files($filePath));
            }

            $allRedirects = collect($allRedirects)->sortByDesc(function ($file) {
                return $file->getCTime();
            });
            foreach ($allRedirects as $redirect) {
                $data = Yaml::parse(File::get($redirect));
                $this->data[] = $data;
            }
        }

        $allRegexRedirects = File::allFiles(base_path('/content/alt-redirect/alt-regex'));
        $allRegexRedirects = collect($allRegexRedirects)->sortBy(function ($file) {
            return $file->getCTime();
        });
        foreach ($allRegexRedirects as $redirect) {
            $data = Yaml::parse(File::get($redirect));
            $this->regexData[] = $data;
        }
    }

    public function checkOrMakeDirectories()
    {
        foreach($this->types as $type) {
            foreach($type as $directory) {
                if (!$this->manager->disk()->exists($directory)) {
                    $this->manager->disk()->makeDirectory($directory);
                }
            }
        }
    }

    public function getByKey($key, $value) : array | null
    {
        $data = collect($this->data);
        $result = $data->firstWhere($key, $value);
        if ($result) {
            return $result;
        }
        return null;
    }

    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;

        Yaml::dump($this->data, $this->currentFile);
    }

    public function all()
    {
        return $this->data;
    }

    public function setAll($data)
    {
        $this->data = $data;

        switch ($this->type) {
            case 'redirects':
                if (strpos($data['from'], '(.*)') === false) {
                    $this->manager->disk()->put('content/alt-redirect/' . hash('sha512', (base64_encode($data['from']))) . '.yaml', Yaml::dump($this->data));
                    return;
                }
                $this->manager->disk()->put('content/alt-redirect/alt-regex/' . hash('sha512', base64_encode($data['id'])) . '.yaml', Yaml::dump($this->data));
                break;
            case 'query-strings':
                $this->manager->disk()->put('content/alt-redirect/query-strings/' . hash('sha512', (base64_encode($data['query_string']))) . '.yaml', Yaml::dump($this->data));
                break;
        }
    }

    public function saveAll($data)
    {
        foreach ($data as $redirect) {
            $this->setAll($redirect);
        }
    }

}
