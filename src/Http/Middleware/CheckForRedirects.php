<?php

namespace AltDesign\AltRedirect\Http\Middleware;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\URISupport;
use Closure;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\Response;

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
        $pathOnlyDecoded = rawurldecode($pathOnly);

        if (str_ends_with($pathOnly, '/')) {
            $permuPathOnly = substr($pathOnly, 0, strlen($pathOnly) - 1);
        } else {
            $permuPathOnly = $pathOnly.'/';
        }

        if (str_ends_with($pathOnlyDecoded, '/')) {
            $permuPathOnlyDecoded = substr($pathOnlyDecoded, 0, strlen($pathOnlyDecoded) - 1);
        } else {
            $permuPathOnlyDecoded = $pathOnlyDecoded.'/';
        }

        $path = URISupport::uriWithFilteredQueryStrings($pathOnly);
        $permuPath = URISupport::uriWithFilteredQueryStrings($permuPathOnly);

        $pathDecoded = URISupport::uriWithFilteredQueryStrings($pathOnlyDecoded);
        $permuPathDecoded = URISupport::uriWithFilteredQueryStrings($permuPathOnlyDecoded);

        // Check simple redirects
        $redirect = $repository->find('redirects', 'from', $pathDecoded) ??
                    $repository->find('redirects', 'from', $permuPathDecoded) ??
                    $repository->find('redirects', 'from', $pathOnlyDecoded) ??
                    $repository->find('redirects', 'from', $permuPathOnlyDecoded) ??
                    $repository->find('redirects', 'from', $path) ??
                    $repository->find('redirects', 'from', $permuPath) ??
                    $repository->find('redirects', 'from', $pathOnly) ??
                    $repository->find('redirects', 'from', $permuPathOnly);

        if ($redirect) {
            $to = $redirect['to'] ?? '/';
            // There's no need to redirect.
            if ($to === $path || $to === $permuPath || $to === $pathOnly || $to === $permuPathOnly ||
                $to === $pathDecoded || $to === $permuPathDecoded || $to === $pathOnlyDecoded || $to === $permuPathOnlyDecoded) {
                return $next($request);
            }
            if (! ($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                return $this->redirectWithPreservedParams($to, $redirect['redirect_type'] ?? 301);
            }
        }

        // Regex checks
        $uri = URISupport::uriWithFilteredQueryStrings();
        $uriDecoded = rawurldecode($uri);

        foreach ($repository->getRegex('redirects') as $redirect) {
            $from = $redirect['from'];

            // Determine if the pattern is already delimited
            $isDelimited = @preg_match($from, '') !== false;
            $pattern = $isDelimited ? $from : '#' . $from . '#u';

            // Handle the ? hack for non-delimited patterns
            if (!$isDelimited && ! preg_match($pattern, $uriDecoded) && ! preg_match($pattern, $uri) && strpos($from, '?') !== false && strpos($from, '\?') === false) {
                $pattern = '#' . str_replace('?', '\?', $from) . '#u';
            }

            $matched = false;
            $subject = $uriDecoded;

            if (preg_match($pattern, $uriDecoded)) {
                $matched = true;
                $subject = $uriDecoded;
            } elseif (preg_match($pattern, $uri)) {
                $matched = true;
                $subject = $uri;
            }

            if ($matched) {
                $redirectTo = preg_replace($pattern, $redirect['to'], $subject);
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
                    $filteredStrings[] = sprintf('%s=%s', urlencode($key), urlencode($value));
                }
            }
        }

        if ($filteredStrings) {
            $to .= str_contains($to, '?') ? '&' : '?';
            $to .= implode('&', $filteredStrings);
        }

        return redirect($to, $status, config('alt-redirect.headers', []));
    }
}
