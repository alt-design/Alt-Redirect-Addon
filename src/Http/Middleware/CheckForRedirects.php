<?php namespace AltDesign\AltRedirect\Http\Middleware;

use AltDesign\AltRedirect\Helpers\URISupport;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

use Statamic\Facades\Site;
use Statamic\Facades\YAML;

use AltDesign\AltRedirect\Helpers\Data;

class CheckForRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // Grab uri, make alternate / permutation
        $uri = URISupport::uriWithFilteredQueryStrings();
        if (str_ends_with($uri, '/')) {
            $permuURI = substr($uri, 0, strlen($uri) - 1);
        } else {
            $permuURI = $uri . '/';
        }

        //Build all potential versions
        $possibleSimple = [];
        $possibleSimple[] = $b64 = base64_encode($uri);
        $possibleSimple[] = hash( 'sha512', $b64);
        $possibleSimple[] = $permuB64 = base64_encode($permuURI);
        $possibleSimple[] = hash( 'sha512', $permuB64);

        //Check potentials
        foreach($possibleSimple as $simple) {
            if (\Statamic\Facades\File::exists('content/alt-redirect/' . $simple . '.yaml')) {
                $redirect = Yaml::parse(\Statamic\Facades\File::get('content/alt-redirect/' . $simple . '.yaml'));
                $to = $redirect['to'] ?? '/';
                //There's no need to redirect.
                if ($to === $uri || $to === $permuURI ) {
                    return $next($request);
                }
                if (!($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                    return $this->redirectWithPreservedParams($to , $redirect['redirect_type'] ?? 301);
                }
            }
        }

        //Regex checks
        $data = new Data('redirect', true);
        foreach ($data->regexData as $redirect) {
            if (preg_match('#' . $redirect['from'] . '#', $uri)) {
                $redirectTo = preg_replace('#' . $redirect['from'] . '#', $redirect['to'], $uri);
                if (!($redirect['sites'] ?? false) || (in_array(Site::current(), $redirect['sites']))) {
                    return $this->redirectWithPreservedParams($redirectTo ?? '/', $redirect['redirect_type'] ?? 301);
                }
            }
        }
        //No redirect
        return $next($request);
    }

    private function redirectWithPreservedParams($to, $status)
    {
        $preserveKeys = [];
        foreach ((new Data('query-strings'))->all() as $item) {
            if (!($item['strip'] ?? false)) {
                $preserveKeys[] = $item['query_string'];
            }
        }

        $filteredStrings = [];
        foreach(request()->all() as $key => $value) {
            if (!in_array($key, $preserveKeys)) {
                continue;
            }
            $filteredStrings[] = sprintf( "%s=%s", $key, $value);
        }

        if ($filteredStrings) {
            $to .= str_contains($to, '?') ? '&' : '?';
            $to .= implode('&', $filteredStrings);
        }
        return redirect($to , $status, config('alt-redirect.headers', []));
    }
}

