<?php

namespace AltDesign\AltRedirect\Repositories;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\URISupport;
use AltDesign\AltRedirect\Models\QueryString;
use AltDesign\AltRedirect\Models\Redirect;

class DatabaseRepository implements RepositoryInterface
{
    public function all(string $type): array
    {
        if ($type === 'redirects') {
            return Redirect::all()->toArray();
        } elseif ($type === 'query-strings') {
            return QueryString::all()->toArray();
        }

        return [];
    }

    public function getRegex(string $type): array
    {
        if ($type !== 'redirects') {
            return [];
        }

        return Redirect::where('is_regex', true)->get()->toArray();
    }

    public function find(string $type, string $key, $value): ?array
    {
        if ($type === 'redirects') {
            $model = Redirect::where($key, $value)->first();

            return $model ? $model->toArray() : null;
        } elseif ($type === 'query-strings') {
            $model = QueryString::where($key, $value)->first();

            return $model ? $model->toArray() : null;
        }

        return null;
    }

    public function save(string $type, array $data): void
    {
        if ($type === 'redirects') {
            if (! isset($data['from'])) {
                return;
            }
            $data['is_regex'] = URISupport::isRegex($data['from']);
            if (empty($data['id'])) {
                $existing = Redirect::where('from', $data['from'])->first();
                $data['id'] = $existing ? $existing->id : (string) uniqid();
            }
            Redirect::updateOrCreate(['id' => $data['id']], $data);
        } elseif ($type === 'query-strings') {
            if (empty($data['id'])) {
                $existing = QueryString::where('query_string', $data['query_string'])->first();
                $data['id'] = $existing ? $existing->id : (string) uniqid();
            }
            QueryString::updateOrCreate(['id' => $data['id']], $data);
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
        if ($type === 'redirects') {
            if (isset($data['id'])) {
                Redirect::where('id', $data['id'])->delete();
            } elseif (isset($data['from'])) {
                Redirect::where('from', $data['from'])->delete();
            }
        } elseif ($type === 'query-strings') {
            if (isset($data['id'])) {
                QueryString::where('id', $data['id'])->delete();
            } elseif (isset($data['query_string'])) {
                QueryString::where('query_string', $data['query_string'])->delete();
            }
        }
    }
}
