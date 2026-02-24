<?php

declare(strict_types=1);

namespace AltDesign\AltRedirect\Helpers;

use Illuminate\Support\Str;

class URISupport
{
    /**
     * Returns the current URI without any query strings for redirect matching.
     *
     * @return string $uri
     */
    public static function uriWithFilteredQueryStrings() : string
    {
        $request = request();

        // Return just the path without any query parameters
        // Query parameters will be handled separately in redirectWithPreservedParams
        return Str::replace(
            $request->root(),
            '',
            $request->url()
        );
    }

}
