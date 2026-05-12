<?php

declare(strict_types=1);

namespace AltDesign\AltRedirect\Helpers;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Illuminate\Support\Str;

class URISupport
{
    /**
     * Returns the current path for simple redirect matching.
     *
     * @return string $path
     */
    public static function path(): string
    {
        $request = request();

        return Str::replace(
            $request->root(),
            '',
            $request->url()
        );
    }

    /**
     * Returns the current URI with filtered query strings for regex redirect matching.
     *
     * @param string|null $path Optional path to use instead of current path.
     * @return string $uri
     */
    public static function uriWithFilteredQueryStrings(?string $path = null): string
    {
        $request = request();
        $path = $path ?? self::path();
        $queryString = $request->getQueryString();

        if (! $queryString) {
            return $path;
        }

        $stripKeys = [];
        try {
            $repository = app(RepositoryInterface::class);
            foreach ($repository->all('query-strings') as $item) {
                if ($item['strip'] ?? false) {
                    $stripKeys[] = strtolower($item['query_string']);
                }
            }
        } catch (\Exception $e) {
            // Fallback if repository is not available
        }

        parse_str($queryString, $params);
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if (! in_array(strtolower((string) $key), $stripKeys)) {
                $filteredParams[$key] = $value;
            }
        }

        if (empty($filteredParams)) {
            return $path;
        }

        return $path.'?'.http_build_query($filteredParams);
    }

    /**
     * Returns true if the given string is a regex redirect.
     *
     * @param string $str
     * @return bool
     */
    public static function isRegex(string $str): bool
    {
        if (empty($str)) {
            return false;
        }

        // 1. Check if it's a valid delimited PHP regex.
        if (@preg_match($str, '') !== false) {
            // BUT, if the delimiter is '/', it might just be a simple path.
            // Many URLs start and end with '/'.
            if (str_starts_with($str, '/') && str_ends_with($str, '/')) {
                // High confidence indicators
                if (preg_match('/[\*\^\$\|{}\\\\[\]]/', $str)) {
                    return true;
                }
                // Grouping with wildcards/quantifiers (e.g. (.+))
                if (preg_match('/\([^\)]*[\.\+\?\*][^\)]*\)/', $str)) {
                    return true;
                }

                return false;
            }

            // If it uses other delimiters (like # or ~), assume the user knows what they're doing.
            return true;
        }

        // 2. Check for naked regex indicators.
        // High confidence indicators
        if (preg_match('/[\*\^\$\|{}\\\\[\]]/', $str)) {
            return @preg_match('#'.$str.'#', '') !== false;
        }
        // Grouping with wildcards/quantifiers (e.g. (.+))
        if (preg_match('/\([^\)]*[\.\+\?\*][^\)]*\)/', $str)) {
            return @preg_match('#'.$str.'#', '') !== false;
        }

        return false;
    }
}
