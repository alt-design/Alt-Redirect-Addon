<?php namespace AltDesign\AltRedirect;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Filesystem\Manager;
use Statamic\StaticSite\SSG;
use Illuminate\Support\Str;

use AltDesign\AltRedirect\Console\Commands\DefaultQueryStringsCommand;
use AltDesign\AltRedirect\Helpers\DefaultQueryStrings;
use AltDesign\AltRedirect\Helpers\Data;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/alt-redirect-addon.js',
            'resources/css/alt-redirect-addon.css'
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected $middlewareGroups = [
        'web' => [
            \AltDesign\AltRedirect\Http\Middleware\CheckForRedirects::class,
        ]
    ];

    /**
     * Register our addon and child menus in the nav
     *
     * @return self
     */
    public function addToNav() : self
    {
        Nav::extend(function ($nav) {
            $nav->content('Alt Redirect')
                ->section('Tools')
                ->route('alt-redirect.index')
                ->can('view alt-redirect')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 600 600"><path d="M546.82 286.83c-2.9 4.43-6.69 8.53-10.77 11.02-.9 2.31-3.01 2.98-3.74 4.33-2.81.94-4.62 4.64-7.45 5.51-2.29 2.71-4.47 3.93-7.14 6.4-3.12 2.89-6.19 5.58-9.35 8.52-1.36 1.26-3.17 2.27-4.35 3.56-1.13 1.23-1.28 3.01-3.19 2.64-.13 1.39-1.62 1.41-2.41 2.03-2.99 2.36-5.26 5.63-8.07 8.39-5.39 5.29-11.57 9.75-16.9 14.24-.25.53-.5 1.07-.75 1.6-1.77 2.12-4.2 3.34-6.19 4.89-1.88 1.47-3.11 3.41-4.9 5.25-1.09 1.12-2.68 1.66-3.97 2.76-.9.77-1.44 2.01-2.29 2.82-1.78 1.68-4.35 2.88-6.1 4.69-1.76 1.82-2.65 3.94-5.02 4.47-.25 1.64-.23 3.18-.27 4.74.81.48 1.06 1.16.74 2.05 3.01 2.07 3.95 4.44 5.9 7.15.7-.21 1.41-.36 2.26-.15 8.25-7.43 16.27-15.51 24.46-23.11-.84 2.83-3.24 4.54-4.96 6.44-2.74 3.03-5.05 6.52-7.85 9.47-3.15 3.32-6.73 6.86-10.29 10.52-2.11 2.17-4.67 4.13-6.64 6.38-2.46 2.8-4.69 5.51-7.39 7.98-1.32 1.2-2.53 2.53-3.75 3.84-4.43 4.77-9.8 9.56-14.62 14.57-1.77 1.84-3.18 4.11-4.98 5.95-1.08 1.11-2.51 1.75-3.76 3.35-2.91 3.7-6.23 8.28-9.69 10.8-1.94 3.2-4.94 5.29-7.1 7.87-2.18 2.61-4.06 5.18-6.51 7.66-4.65 4.7-9.25 9.78-14.02 14.86-2.1 2.23-4.86 4.59-7.12 6.89-5.89 5.98-11.29 13.68-17.77 18.7-4.93 5.78-9.61 11.14-14.57 16.55-1.04 1.13-2.5 1.87-3.57 2.95-2.31 2.32-4.21 5.2-6.31 7.75-2.1 2.55-5.28 4.83-7.62 6.9-2.45 2.17-4.09 5.3-6.82 7.27-.9.65-2.38 1.59-3.68 2.66-2.59 2.13-5.47 4.58-8.02 6.71-8.03 6.73-17.59 14.65-25.3 21.25-1.14.98-2.39 1.4-3.09 2.45-.7 1.04-.86 3.63-2.26 4.3.52 1.8.29 3.88 1.03 5.6 2.21.19 3.8-1.74 5.45-2.8 1.78-1.14 3.72-2.04 5.13-3.68 4.09-1.99 7.32-6.36 11.43-8.28.34-.59.73-1.03 1.15-1.41 6.73-4.93 13.16-10.68 19.6-16.38.42-.82 1.32-.32 1.85-.84 1.7-2.48 4.26-4.16 6.46-5.98 1.26-1.04 1.76-1.78 3.18-3.14 3.34-3.21 7.29-5.85 10.62-9.15 2.12-2.1 4.03-4.45 6.05-6.66 2.81-3.08 6-5.54 9.15-8.62 3.41-4.33 7.14-7.75 11.07-10.64 5.29-6.1 11.32-11.47 17.22-17.01 6.76-6.34 13.85-13.45 21.09-20.07.22-.46.44-.93.66-1.4 9.96-8.98 19.17-20.03 29.15-28.95 2.91-3.67 6.63-6.42 9.81-10.01 1.33-1.51 2.74-2.12 4.34-4.06 1.3-1.57 3.58-3.15 5.49-4.97 3.45-3.3 6.04-5.96 9.43-9.21 2.7-2.59 6.54-6.09 9.34-9.02.18.04.29-.11.49-.01-1.12.74-1.15 3.68-1.47 4.18-.31.61.32.88.92 1.16-.58 1.34.01 1.6.67 3.24.62 1.54.05 2.18 2.14 2.71 2.93.74 6.31-5.49 8.46-8.7 1.78-2.66 3.88-4.72 5.51-8.13 1.32-2.76 3.3-5.03 5.03-7.62 6-8.96 12.3-17.25 18.48-25.91.89-1.26 3.3-3.28 4.41-4.75 1.44-1.9 2.43-4.56 4.07-6.61.6-.75 1.52-1.19 2.12-1.93 1.64-1.99 2.59-4.41 3.98-6.41.98-1.4 2.56-2.35 3.36-3.54.46-.68.59-1.56 1.12-2.4 1.79-2.8 4.12-5.43 6.1-8.34 6.19-9.1 12.61-18.8 18.71-28.48.7-1.11 1.52-2.2 2.79-2.83.77-2.42 4.57-4.92 1.3-6.94.9-1.64-1.2-1.95-.18-3.25-2.35-3.1-4.58-6.97-9.54-6.66ZM490.62 92.77c-6.39 1.86-16.9 9.82-25.01 11.72-3.47-.88-6.17-3.91-9.88-4.15-.13-1.28-.37-2.6-1.08-4.09 14.79-10.93 35.91-22.74 52.33-34.55 8.72-6.28 17.18-12 24.83-18.33 7.92-6.55 15.24-13.09 22.83-19.05 2.61-2.05 7.65-6.41 10.32-6.51.53-.02 5.61 1.85 6.54 2.37.47.26.52 1.29 1.3 1.9 1.16.88 5.26 2.98 5.27 5.89-4.6 7.25-10.39 14.07-15.09 21.28-5.57 4.93-8.47 11.06-13.3 17.1-4.82 6-10.09 13.17-15.08 20.44-7.26 10.57-13.34 19.88-19.06 30.66-3.19 6.01-6.36 10.41-7.03 17.08-2.18 1.33-2.46 3.1-4.5 3.78-1.58.52-1.09-.45-2.86-.75-1.16-.2-1.95.47-2.88.1-1.63-.65-2.74-2.86-4.5-2.48-.52-11.28 3.48-20.93 8.38-30.25-17.95 16.84-30.33 35.69-46.54 53.15-.57 4.25-4.72 7.2-8.55 10.28-.49 3.07-3.9 5.02-5.95 7.8-3.88 5.27-7.76 12.57-11.81 18.49-3.22 4.7-7.64 8.7-10.83 13.44-15.98 23.67-36.45 47.58-55.61 70.35-1.34 1.6-2 3.83-3.35 5.33-2.96 3.28-6.37 5.9-9.03 9.25-7.04 8.82-14.53 17.29-22.31 25.5-4.04 6.55-9.95 11.98-15.03 18.74-2.29 3.04-4.21 6.65-6.68 9.82-2.08 2.66-4.78 4.93-6.8 7.78-7.14 10.08-14.3 20.28-21.43 30.94-4.09 6.11-7.67 10.71-13.12 16.59-4.45 4.81-10.16 11.67-18.83 15.1-6.89 2.72-15.11 1.98-20.48-.01-4.67-1.73-11.79-3.19-14.82-9.35-4.48-9.09-4.86-25.55-3.03-34.1.46-2.14 1.55-4.25 2.02-6.38.44-2 .35-4.83 2.35-5.7-.57-1.46-1.08-1.12-1.12-2.4 5.91-14.93 9.41-30.73 17.38-44.92-.2-3.8 2.02-7.03 3.66-10.91 7.16-16.9 14.07-35.17 22.98-51.72 7.37-20.19 18.49-39.02 23.7-59.99-2.93 1.1-5.53 2.54-8.06 4.2-1.85 1.21-2.85 3.21-4.52 4.62-2.82 2.4-6.77 3.75-9.64 6.19-5.04 4.28-9.57 9.45-15.73 12.8-11.88 11.16-24.07 22.2-33.2 34.36-16.54 14.97-28.24 31.7-42.5 47.5-10.37 14.36-20.66 28.75-31.22 43.05-.71 3.39-2.97 5.24-5.29 8.33-6.92 9.21-10.78 20.28-18.01 29.33-18.15 35.54-36.8 70.9-48.46 108.78-3.1 4.85-4.72 10.24-6.49 15.57-4.9.96-9.55 1.23-14.08 1.16-2.1-7.48 1.39-14.09 3.94-21.9 3.83-11.77 7.58-21.43 12.33-32.52 2.35-5.47 3.81-11.23 6.16-16.26 1.26-2.69 3.12-5.03 4.26-7.85 12.51-30.87 30.64-61.36 49.34-89.97 12.87-21.99 30.82-42.15 46.96-62.96.68-.59 1.75-1.65 2.74-1.29 6.29-10.62 17.05-20.56 27.38-31.63 15.11-16.19 30.73-31.26 49.37-44.45 3.05-2.15 6.09-4.24 9.13-6.37 8.83-6.18 22.01-16.72 32.66-13.22 5.48 3.49 11.51 5.89 9.98 13.28-.32 1.57-1.42 3.74-2.18 6.04-2.28 6.9-4.29 16.09-8.33 21.45-.36 3.98-3.56 6.31-4.99 9.86-.61 1.5-.4 3.38-.98 5.05-.9 2.6-3.17 7.19-4.65 9.7-.95 1.62-2.67 2.69-3.33 4.49-.94 2.56-.95 5.21-1.89 7.57-.71 1.79-1.25 3.38-2.16 5.2-3.49 6.91-6.92 14.77-10.04 22.25-4.1 9.82-8.3 20.22-12.45 30.49-9.37 23.14-19.47 46.74-21.99 72.56.76 4.88 1.51 7.99 1.21 12.39 1.74 3.07 2.2 9.24 5.48 10.8 11-1.8 19.3-13.23 26.13-22.69 2.76-3.82 5.47-7.57 8.07-11.31 3.66-5.25 6.38-9.56 10.19-14.8 8.41-11.56 16.35-24.1 25.95-35.56 5.27-6.29 10.21-11.79 15.54-18.56 2.66-3.39 5.07-6.01 8.53-9.43 2.9-2.87 4.64-6.52 7.19-9.64 3.5-4.27 7.5-8.53 11.16-12.75 1.84-2.13 3.6-4.19 5.4-6.3 5.24-6.1 11.39-12.98 16.08-20.07 18.26-19.19 30.89-40.41 48.4-59.87.32-.74.32-1.59.06-2.54 2.28-.98 2.35-2.75 4.5-3.78 1.19-.71.57-2.07 1.09-3.02 1.82-.39.94-1.74 2.76-2.13 5.51-8.56 13.64-19.09 20.5-27.58 6.39-7.9 12.64-16.03 20.05-23.18.57-.93.38-2.14 1.09-3.02 3.48-2.73 5.92-5.83 7.85-9.11.8-.38 1.6-.75 2.4-1.12 4.16-7.69 12.39-13.92 17.63-21.22-.59.02-.16-.43 0 0 .12-.05.23-.11.34-.16ZM75.79 246.71c3.57-2.83 7.42-5.07 10.73-8.37-.09-.62.09-1.13.56-1.55 4.98-3.74 9.79-7.08 13.82-11.1 1.31-1.3 1.96-3.4 4.27-4.06 2.99-3.56 6.33-6.32 10.64-9.1 1.57-2.19 3.61-4.19 5.98-5.89 4.59-3.28 7.38-7.03 11.81-9.91.22-.68.94-1.18 1.19-1.84 6.83-5 12.62-11.25 18.65-16.71.54-.49 1.23-.87 1.7-1.31 3.8-3.58 7.65-8.47 11.63-10.85 3.79-2.27 5.91-4.49 8.01-7.61 2.56-.88 2.89-2.57 4.05-3.96.63-.3 1.27-.59 1.9-.89.49-.76.64-1.64 1.2-2.37 8.97-7.83 16.12-17.09 26.64-23.8.56-2.59 2.12-3.56 4.52-5.72 1.13-1.02 2.57-2.06 3.61-2.72 4.33-2.77 6.81-7.31 10.45-9.52 3.47-2.11 5.11-3.67 7.85-5.73-.17-1.33.89-1.11 1.71-1.83.57-.5.89-1.8 1.3-2.15.74-.65 1.87-.8 2.64-1.49.48-.43.53-1.3.98-1.75.76-.74 1.93-1.1 2.75-1.8 2.88-2.45 4.81-6.33 8.54-8.12-5.28 10.63-11.36 20.98-16.53 31.65.45 1.1-.45 1.71.03 2.81-.71-.2-1.24.49-1.59 1-1 1.49-2.13 4.43-1.1 6.43 5.16-.84 7.68-4.41 9.57-7.57.35.4-.28.95.51.54.99-2.46 2.7-4.72 5.05-6.23.39-.68.47-1.46.78-2.17 3.48-4.75 6.13-9.82 10.05-14.22 2.25-2.53 2.19-6.84 5.33-8.93 2.97-5.98 7.44-11.43 10.03-17.55 3.17-2.29 3.25-7.14 6.19-9.84 2.51-2.32 6.12-3.49 7.05-6.9 2.05-1.19 2.53-2.93 4.48-4.16.32-1.35-.45-2.97-.94-4.19.1-.9 1.92-1.17 2.45-1.92.08-.56-.32-1.28.87-1.44-3.34-5.46-7.76-5.91-13.98-2.96-19.33 9.16-42.05 23.86-60.75 34.39-.58.2.22.89-.55 1.03-5.25 1.56-8.36 6.73-13.88 9.33-.69 1.27-1.8 2.38-1.77 3.91.83-.59.99 1.06 1.01 1.07.26.15.97-.35 1.46-.17.97.34 1.41 2.3 1.86.67.23.81.72.9 1.11 1.28 13.79-5.4 26.84-16.93 41.81-22.68-6.6 6.36-14.02 12.43-21.09 18.63-.39.63.26.63-.45 1.24-4.08 1.59-5.79 5.09-9.04 7.58-1.4 1.07-2.17 1.79-3.3 2.83-2.68 2.49-5.88 4.47-8.51 7.07-12.23 12.08-25.27 23.7-37.87 35.49-2.55 3.4-6.26 5.71-9.28 8.72-5.53 5.53-10.94 11.32-16.67 16.55-1.36 1.24-2.78 2.4-4.45 3.11.28.51-.01.81-.24 1.14-7.87 6.72-14.85 13.77-23.11 20.35-2.78 2.65-5.64 5.6-8.75 8.21-3.36 2.83-5.97 5.12-9.36 7.47-5.06 5.23-10.83 10.2-17.14 14.98-.25.32 0 .82-.55 1.03-3.33 1.3-8.73 2-11.25 4.54-1.62 1.63-2.71 3.72-4.3 5.37-1.7 1.77-3.89 2.99-5.97 4.29-7.8 4.9-14.47 11.37-21.07 17.79-.86.84-1.77 2.17-1.03 3.11.18.22.42.38.67.52 5.34 3.07 12.21 1.8 17.7-1s14.54-8.24 19.37-12.07Z"/></svg>')
                ->children([
                    'Query Strings' => cp_route('alt-redirect.query-strings.index'),
                ]);
        });

        return $this;
    }

    /**
     * Register our permissions, so we can control who can see the settings.
     *
     * @return self
     */
    public function registerPermissions() : self
    {
        Permission::register('view alt-redirect')
                  ->label('View Alt Redirect Settings');

        return $this;
    }

    /**
     * Register our artisan commands
     *
     * @return self
     */
    public function registerCommands() : self
    {
        $this->commands([
            DefaultQueryStringsCommand::class,
        ]);
        return $this;
    }

    /**
     * Install the default query strings
     *
     * @return self
     */
    public function installDefaultQueryStrings() : self
    {
        // create the standard
        if ($this->app->runningInConsole()) {
            $disk = (new Manager())->disk();
            if (!$disk->exists('content/alt-redirect/.installed')) {
                (new DefaultQueryStrings)->makeDefaultQueryStrings();
            }
        }
        return $this;
    }

    public function configureSSG() : self
    {
        if (!class_exists(SSG::class)) {
            return $this;
        }

        SSG::after(function () {
            $dest = config('statamic.ssg.destination');
            $base = rtrim(config('statamic.ssg.base_url'), '/'); // remove trailing slash
            $disk = (new Manager())->disk();

            $redirects = (new Data('redirects'))->all();
            print("Found " . count($redirects) . " redirects\n");

            $generated = $directories = 0;
            foreach( $redirects as $redirect ) {
                $fromDir = $dest . $redirect['from'];
                $from = sprintf('%s%sindex.html',
                    $fromDir,
                    (Str::endsWith($fromDir, '/') ? '' : '/')
                );
                $to = $base . $redirect['to'];

                if (!$disk->exists($from)) {
                    $contents = view('alt-redirect::ssg', ['to' => $to]);
                    if (!$disk->isDirectory($fromDir)) {
                        mkdir($fromDir, 0777, true);
                        $directories++;
                    }
                    if ($disk->put($from, $contents)) {
                        $generated++;
                    }
                }
            }

            print("Generated $generated redirect files in $directories new directories\n");
        });
        return $this;
    }

    public function bootAddon()
    {
        $this->addToNav()
            ->registerPermissions()
            ->registerCommands()
            ->configureSSG();
    }
}

