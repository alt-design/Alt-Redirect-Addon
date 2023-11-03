<?php

namespace AltDesign\AltRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Statamic\Facades\YAML;

class CheckForRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $toCheck = base64_encode($request->getRequestUri());

        if(!\Statamic\Facades\File::exists('content/alt-redirect/' . $toCheck . '.yaml' )) {
            return $next($request);
        }

        $redirect = Yaml::parse(\Statamic\Facades\File::get('content/alt-redirect/' . $toCheck . '.yaml'));

        return redirect(($redirect['to'] ?? '/'), $redirect['redirect_type'] ?? 301);
    }
}
