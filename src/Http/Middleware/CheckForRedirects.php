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
        if (str_ends_with($pathOnly, '/')) {
            $permuPathOnly = substr($pathOnly, 0, strlen($pathOnly) - 1);
        } else {
            $permuPathOnly = $pathOnly.'/';
        }

        $path = URISupport::uriWithFilteredQueryStrings($pathOnly);
        $permuPath = URISupport::uriWithFilteredQueryStrings($permuPathOnly);
        $candidates = array_values(array_unique([
            $path,
            $permuPath,
            $pathOnly,
            $permuPathOnly,
            $this->decodeUnicodeEscapes($path),
            $this->decodeUnicodeEscapes($permuPath),
            $this->decodeUnicodeEscapes($pathOnly),
            $this->decodeUnicodeEscapes($permuPathOnly),
        ]));

        // Check simple redirects
        $redirect = null;
        foreach ($candidates as $candidate) {
            if ($redirect = $repository->find('redirects', 'from', $candidate)) {
                break;
            }
        }

        if ($redirect) {
            $to = $redirect['to'] ?? '/';
            // There's no need to redirect.
            if (in_array($to, $candidates, true)) {
                return $next($request);
            }
            if (! ($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                return $this->redirectWithPreservedParams($to, $redirect['redirect_type'] ?? 301);
            }
        }

        // Regex checks
        $uri = URISupport::uriWithFilteredQueryStrings();
        $subjects = array_values(array_unique([$uri, $this->decodeUnicodeEscapes($uri)]));

        foreach ($repository->getRegex('redirects') as $redirect) {
            $from = $redirect['from'];

            // Determine if the pattern is already delimited
            $isDelimited = @preg_match($from, '') !== false;
            $pattern = $isDelimited ? $from : '#'.$from.'#u';

            // Handle the ? hack for non-delimited patterns
            if (! $isDelimited && ! $this->matchesAny($pattern, $subjects) && strpos($from, '?') !== false && strpos($from, '\?') === false) {
                $pattern = '#'.str_replace('?', '\?', $from).'#u';
            }

            foreach ($subjects as $subject) {
                if (preg_match($pattern, $subject)) {
                    $redirectTo = preg_replace($pattern, $redirect['to'], $subject);
                    if (! ($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                        return $this->redirectWithPreservedParams($redirectTo ?? '/', $redirect['redirect_type'] ?? 301);
                    }
                }
            }
        }

        // No redirect
        return $next($request);
    }

    private function decodeUnicodeEscapes(string $uri): string
    {
        return preg_replace_callback(
            '/(?:%[89a-f][0-9a-f])+/i',
            fn (array $matches) => rawurldecode($matches[0]),
            $uri
        ) ?? $uri;
    }

    private function matchesAny(string $pattern, array $subjects): bool
    {
        foreach ($subjects as $subject) {
            if (preg_match($pattern, $subject)) {
                return true;
            }
        }

        return false;
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
