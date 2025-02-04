<?php

declare(strict_types=1);

namespace AltDesign\AltRedirect\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class URISupport
{
    /**
     * Returns the current URI with named Query Strings filtered out .
     *
     * @return string $uri
     */
    public static function uriWithFilteredQueryStrings() : string
    {
        $request = request();
        $data = new Data('query-strings');
        $withoutQueryStrings = Arr::pluck($data->all(), 'query_string');
        // Filter out unwanted params, then strip the base url to get a filtered uri
        $encoding = str_contains($request->getRequestURI(), '+')
            ? PHP_QUERY_RFC1738
            : PHP_QUERY_RFC3986;

        return Str::replace(
            $request->root(),
            '',
            self::fullUrlWithoutQuery($withoutQueryStrings, $encoding)
        );
    }

    /**
     * Replacement for fullUrlWithoutQuery() in Request to use custom Arr::query() implementation
     *
     * @param array $keys
     * @param int $encoding_type (optional) Encoding Type
     * @return string
     */
    private static function fullUrlWithoutQuery(array $keys, $encoding_type = PHP_QUERY_RFC1738) : string
    {
        $request = request();
        $query = Arr::except($request->query(), $keys);

        $question = $request->getBaseUrl().$request->getPathInfo() === '/' ? '/?' : '?';

        return count($query) > 0
            ? $request->url().$question.self::myArrQuery($query, $encoding_type)
            : $request->url();
    }

    /**
     * Replacement for Arr::query() to use default encoding method
     *
     * @param array $array
     * @param int $encoding_type (optional) Encoding Type
     * @return string
     */
    private static function myArrQuery(array $array, $encoding_type = PHP_QUERY_RFC1738) : string
    {
        return http_build_query($array, '', '&', $encoding_type);
    }
}
