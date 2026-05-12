<?php

namespace AltDesign\AltRedirect\Repositories;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\URISupport;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;

class FileRepository implements RepositoryInterface
{
    protected Manager $manager;

    protected array $paths = [
        'redirects' => [
            'content/alt-redirect',
            'content/alt-redirect/alt-regex',
        ],
        'query-strings' => [
            'content/alt-redirect/query-strings',
        ],
    ];

    public function __construct()
    {
        $this->manager = new Manager;
        $this->checkOrMakeDirectories();
    }

    public function all(string $type): array
    {
        if (! isset($this->paths[$type])) {
            return [];
        }

        $allData = [];
        $disk = $this->manager->disk();
        foreach ($this->paths[$type] as $path) {
            if (! $disk->exists($path)) {
                continue;
            }
            $allData = array_merge($allData, $disk->getFiles($path)->filter(fn ($f) => str_ends_with($f, '.yaml'))->all());
        }

        $allData = collect($allData)->sortByDesc(function ($file) use ($disk) {
            return $disk->lastModified($file);
        });

        $results = [];
        foreach ($allData as $file) {
            $results[] = YAML::parse($disk->get($file));
        }

        return $results;
    }

    public function getRegex(string $type): array
    {
        if ($type !== 'redirects' && $type !== 'redirect') {
            return [];
        }

        $disk = $this->manager->disk();
        if (! $disk->exists('content/alt-redirect/alt-regex')) {
            return [];
        }

        $allRegexRedirects = $disk->getFilesRecursively('content/alt-redirect/alt-regex')->all();
        $allRegexRedirects = collect($allRegexRedirects)->sortBy(function ($file) use ($disk) {
            return $disk->lastModified($file);
        });

        $results = [];
        foreach ($allRegexRedirects as $file) {
            $results[] = YAML::parse($disk->get($file));
        }

        return $results;
    }

    public function find(string $type, string $key, $value): ?array
    {
        if ($type === 'redirects' && $key === 'from') {
            $b64 = base64_encode($value);
            $possibleFiles = [
                'content/alt-redirect/'.$b64.'.yaml',
                'content/alt-redirect/'.hash('sha512', $b64).'.yaml',
            ];

            foreach ($possibleFiles as $file) {
                if ($this->manager->disk()->exists($file)) {
                    return YAML::parse($this->manager->disk()->get($file));
                }
            }
        }

        $data = collect($this->all($type));

        return $data->firstWhere($key, $value);
    }

    public function save(string $type, array $data): void
    {
        switch ($type) {
            case 'redirects':
                if (! isset($data['from'])) {
                    return;
                }
                if (! URISupport::isRegex($data['from'])) {
                    $this->manager->disk()->put('content/alt-redirect/'.hash('sha512', (base64_encode($data['from']))).'.yaml', YAML::dump($data));

                    return;
                }
                $this->manager->disk()->put('content/alt-redirect/alt-regex/'.hash('sha512', base64_encode($data['id'])).'.yaml', YAML::dump($data));
                break;
            case 'query-strings':
                $this->manager->disk()->put('content/alt-redirect/query-strings/'.hash('sha512', (base64_encode($data['query_string']))).'.yaml', YAML::dump($data));
                break;
        }
    }

    protected function isRegex(string $str): bool
    {
        return URISupport::isRegex($str);
    }

    public function saveAll(string $type, array $data): void
    {
        foreach ($data as $item) {
            $this->save($type, $item);
        }
    }

    public function delete(string $type, array $data): void
    {
        $disk = $this->manager->disk();
        switch ($type) {
            case 'redirects':
                if (isset($data['from'])) {
                    $disk->delete('content/alt-redirect/'.hash('sha512', base64_encode($data['from'])).'.yaml');
                    $disk->delete('content/alt-redirect/'.base64_encode($data['from']).'.yaml');
                }
                if (isset($data['id'])) {
                    $disk->delete('content/alt-redirect/alt-regex/'.hash('sha512', base64_encode($data['id'])).'.yaml');
                    $disk->delete('content/alt-redirect/alt-regex/'.base64_encode($data['id']).'.yaml');
                }
                break;
            case 'query-strings':
                if (isset($data['query_string'])) {
                    $disk->delete('content/alt-redirect/query-strings/'.hash('sha512', base64_encode($data['query_string'])).'.yaml');
                } elseif (isset($data['id'])) {
                    $item = $this->find($type, 'id', $data['id']);
                    if ($item && isset($item['query_string'])) {
                        $disk->delete('content/alt-redirect/query-strings/'.hash('sha512', base64_encode($item['query_string'])).'.yaml');
                    }
                }
                break;
        }
    }

    protected function checkOrMakeDirectories(): void
    {
        foreach ($this->paths as $type) {
            foreach ($type as $directory) {
                if (! $this->manager->disk()->exists($directory)) {
                    $this->manager->disk()->makeDirectory($directory);
                }
            }
        }
    }
}
