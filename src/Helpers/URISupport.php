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
        $data = new Data('query-strings');
        $withoutQueryStrings = Arr::pluck($data->all(), 'query_string');
        // Filter out unwanted params, then strip the base url to get a filtered uri
        return Str::replace(request()->root(), '', self::fullUrlWithoutQuery($withoutQueryStrings));
    }

    /**
     * Replacement for fullUrlWithoutQuery() in Request to use custom Arr::query() implementation
     *
     * @param array $keys
     * @return string
     */
    private static function fullUrlWithoutQuery(array $keys) : string
    {
        $request = request();
        $query = Arr::except($request->query(), $keys);

        $question = $request->getBaseUrl().$request->getPathInfo() === '/' ? '/?' : '?';

        return count($query) > 0
            ? $request->url().$question.self::myArrQuery($query)
            : $request->url();
    }

    /**
     * Replacement for Arr::query() to use default encoding method
     *
     * @param array $array
     * @return string
     */
    private static function myArrQuery(array $array) : string
    {
        return http_build_query($array, '', '&');
    }
}
