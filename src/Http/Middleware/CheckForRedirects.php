<?php

namespace AltDesign\AltRedirect\Http\Middleware;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\URISupport;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
use Closure;
use Illuminate\Http\Request;
use Statamic\Facades\Site;

class CheckForRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $repository = app(RepositoryInterface::class);

        // Grab path, make alternate / permutation
        $pathOnly = URISupport::path();
        if (str_ends_with($pathOnly, '/')) {
            $permuPathOnly = substr($pathOnly, 0, strlen($pathOnly) - 1);
        } else {
            $permuPathOnly = $pathOnly.'/';
        }

        $path = URISupport::uriWithFilteredQueryStrings($pathOnly);
        $permuPath = URISupport::uriWithFilteredQueryStrings($permuPathOnly);

        // Check simple redirects
        $redirect = $repository->find('redirects', 'from', $path) ??
                    $repository->find('redirects', 'from', $permuPath) ??
                    $repository->find('redirects', 'from', $pathOnly) ??
                    $repository->find('redirects', 'from', $permuPathOnly);

        if ($redirect) {
            $to = $redirect['to'] ?? '/';
            // There's no need to redirect.
            if ($to === $path || $to === $permuPath || $to === $pathOnly || $to === $permuPathOnly) {
                return $next($request);
            }
            if (! ($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                return $this->redirectWithPreservedParams($to, $redirect['redirect_type'] ?? 301);
            }
        }

        // Regex checks
        $uri = URISupport::uriWithFilteredQueryStrings();
        foreach ($repository->getRegex('redirects') as $redirect) {
            $from = $redirect['from'];

            // Determine if the pattern is already delimited
            $isDelimited = @preg_match($from, '') !== false;
            $pattern = $isDelimited ? $from : '#' . $from . '#';

            // Handle the ? hack for non-delimited patterns
            if (!$isDelimited && ! preg_match($pattern, $uri) && strpos($from, '?') !== false && strpos($from, '\?') === false) {
                $pattern = '#' . str_replace('?', '\?', $from) . '#';
            }

            if (preg_match($pattern, $uri)) {
                $redirectTo = preg_replace($pattern, $redirect['to'], $uri);
                if (! ($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                    return $this->redirectWithPreservedParams($redirectTo ?? '/', $redirect['redirect_type'] ?? 301);
                }
            }
        }

        // No redirect
        return $next($request);
    }

    private function redirectWithPreservedParams($to, $status)
    {
        $stripKeys = [];
        $repository = app(RepositoryInterface::class);
        foreach ($repository->all('query-strings') as $item) {
            if ($item['strip'] ?? false) {
                $stripKeys[] = strtolower($item['query_string']);
            }
        }

        // Parse raw query string to handle double-encoding and duplicates
        $rawQueryString = request()->getQueryString();
        $filteredStrings = [];
        $seenKeys = [];

        if ($rawQueryString) {
            // Decode the query string recursively to handle multiple levels of encoding
            $decodedQueryString = $rawQueryString;
            $previousQueryString = '';

            // Keep decoding until no more changes occur (handles double/triple encoding)
            while ($decodedQueryString !== $previousQueryString) {
                $previousQueryString = $decodedQueryString;
                $decodedQueryString = urldecode($decodedQueryString);
            }

            parse_str($decodedQueryString, $parsedParams);

            foreach ($parsedParams as $key => $value) {
                $normalizedKey = strtolower($key);
                // Strip only parameters marked with strip:true, preserve all others
                if (! in_array($normalizedKey, $stripKeys) && ! isset($seenKeys[$normalizedKey])) {
                    $seenKeys[$normalizedKey] = true;
                    $filteredStrings[$key] = $value;
                }
            }
        }

        $destinationUri = (new Uri($to))
            ->withQuery($filteredStrings);

        return redirect((string) $destinationUri , $status, config('alt-redirect.headers', []));
    }
}
