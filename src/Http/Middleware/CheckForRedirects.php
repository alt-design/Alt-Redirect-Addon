<?php

namespace AltDesign\AltRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
        //Handle simple redirects
        $uri = $request->getRequestUri();
        $b64 = base64_encode($uri);
        $toCheck = hash( 'sha512', $b64);

        if (\Statamic\Facades\File::exists('content/alt-redirect/' . $toCheck . '.yaml')) {
            $redirect = Yaml::parse(\Statamic\Facades\File::get('content/alt-redirect/' . $toCheck . '.yaml'));
            return redirect(($redirect['to'] ?? '/'), $redirect['redirect_type'] ?? 301);
        }

        if (\Statamic\Facades\File::exists('content/alt-redirect/' . $b64 . '.yaml')) {
            $redirect = Yaml::parse(\Statamic\Facades\File::get('content/alt-redirect/' . $b64 . '.yaml'));
            return redirect(($redirect['to'] ?? '/'), $redirect['redirect_type'] ?? 301);
        }

        $data = new Data('redirect');
        foreach ($data->regexData as $redirect) {
            if (preg_match('#' . $redirect['from'] . '#', $uri)) {
                $redirectTo = preg_replace('#' . $redirect['from'] . '#', $redirect['to'], $uri);
                return redirect($redirectTo ?? '/', $redirect['redirect_type'] ?? 301);
            }
        }

        return $next($request);
    }
}
