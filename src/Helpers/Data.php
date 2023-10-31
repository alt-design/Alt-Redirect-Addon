<?php namespace AltDesign\AltRedirect\Helpers;

use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;
use Statamic\API\Folder;

class Data
{
    public $type;
    public $manager;
    public $currentFile;
    public $data = [];

    public function __construct($type)
    {
        $this->type = $type;

        // New up Stat File Manager
        $this->manager = new Manager();

        // Get all files in the redirects folder
        $allRedirects = \Statamic\Facades\File::getFiles('content/alt-redirect');
        // Loop through and get the Data
        foreach ($allRedirects as $redirect) {;
            $file = $this->manager->disk()->get($redirect);
            $data = Yaml::parse($file);
            $this->data[] = $data;
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
        function trimLeadingSlash($string) {
            return ltrim($string, '/');
        }

        $this->data = $data;
        $from = str_replace("/", "-", trimLeadingSlash($data['from']));
        $to = str_replace("/", "-",trimLeadingSlash($data['to']));

        $this->manager->disk()->put('content/alt-redirect/' . $from . '_' . $to . '_' . $data['id'] . '.yaml', Yaml::dump($this->data));
    }

}
