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

    public function __construct($type, $onlyRegex = false)
    {
        $this->type = $type;

        // New up Stat File Manager
        $this->manager = new Manager();

        // Check redirect folder exists
        if (!$this->manager->disk()->exists('content/alt-redirect')) {
            $this->manager->disk()->makeDirectory('content/alt-redirect');
        }
        if (!$this->manager->disk()->exists('content/alt-redirect/alt-regex')) {
            $this->manager->disk()->makeDirectory('content/alt-redirect/alt-regex');
        }

        if(!$onlyRegex) {
            $allRedirects = File::allFiles(base_path('/content/alt-redirect'));
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
        if (strpos($data['from'], '(.*)') === false) {
            $this->manager->disk()->put('content/alt-redirect/' . hash('sha512', (base64_encode($data['from']))) . '.yaml', Yaml::dump($this->data));
            return;
        }
        $this->manager->disk()->put('content/alt-redirect/alt-regex/' . hash('sha512', base64_encode($data['id'])) . '.yaml', Yaml::dump($this->data));
    }

    public function saveAll($data)
    {
        foreach ($data as $redirect) {
            $this->setAll($redirect);
        }
    }

}
