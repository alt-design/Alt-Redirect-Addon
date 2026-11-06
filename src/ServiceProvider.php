<?php

namespace AltDesign\AltRedirect;

use AltDesign\AltRedirect\Console\Commands\DefaultQueryStringsCommand;
use AltDesign\AltRedirect\Console\Commands\MigrateFileRedirectsCommand;
use AltDesign\AltRedirect\Console\Commands\ReScanRegexCommand;
use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\DefaultQueryStrings;
use AltDesign\AltRedirect\Http\Middleware\CheckForRedirects;
use AltDesign\AltRedirect\Repositories\RepositoryManager;
use Illuminate\Support\Str;
use AltDesign\AltRedirect\Support\AltToolsNav;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Filesystem\Manager;
use Statamic\Providers\AddonServiceProvider;
use Statamic\StaticSite\SSG;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/alt-redirect-addon.js',
            'resources/css/alt-redirect-addon.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected $middlewareGroups = [
        'web' => [
            CheckForRedirects::class,
        ],
    ];

    /**
     * Register our addon and child menus in the nav
     */
    public function addToNav(): self
    {
        Nav::extend(function ($nav) {
            AltToolsNav::add($nav, 'seo', 'Alt Redirect', 'alt-redirect.index', 'view alt-redirect', ['moved', 'hyperlink']);
            AltToolsNav::add($nav, 'seo', 'Redirect query strings', 'alt-redirect.query-strings.index', 'view alt-redirect', ['link', 'link']);
        });

        return $this;
    }

    /**
     * Register our permissions, so we can control who can see the settings.
     */
    public function registerPermissions(): self
    {
        Permission::register('view alt-redirect')
            ->label('View Alt Redirect Settings');

        return $this;
    }

    /**
     * Register our artisan commands
     */
    public function registerCommands(): self
    {
        $this->commands([
            DefaultQueryStringsCommand::class,
            MigrateFileRedirectsCommand::class,
            ReScanRegexCommand::class,
        ]);

        return $this;
    }

    public function installDefaultQueryStrings(): self
    {
        if (config('alt-redirect.driver') !== 'file') {
            return $this;
        }

        // create the standard
        $disk = (new Manager)->disk();
        if (! $disk->exists('content/alt-redirect/.installed')) {
            (new DefaultQueryStrings)->makeDefaultQueryStrings();
            $disk->put('content/alt-redirect/.installed', '');
        }

        return $this;
    }

    public function configureSSG(): self
    {
        if (! class_exists(SSG::class)) {
            return $this;
        }

        SSG::after(function () {
            $dest = config('statamic.ssg.destination');
            $base = rtrim(config('statamic.ssg.base_url'), '/'); // remove trailing slash
            $disk = (new Manager)->disk();

            $redirects = app(RepositoryInterface::class)->all('redirects');
            echo 'Found '.count($redirects)." redirects\n";

            $generated = $directories = 0;
            foreach ($redirects as $redirect) {
                $fromDir = $dest.$redirect['from'];
                $from = sprintf('%s%sindex.html',
                    $fromDir,
                    (Str::endsWith($fromDir, '/') ? '' : '/')
                );
                $to = $base.$redirect['to'];

                if (! $disk->exists($from)) {
                    $contents = view('alt-redirect::ssg', ['to' => $to]);
                    if (! $disk->isDirectory($fromDir)) {
                        mkdir($fromDir, 0777, true);
                        $directories++;
                    }
                    if ($disk->put($from, $contents)) {
                        $generated++;
                    }
                }
            }

            echo "Generated $generated redirect files in $directories new directories\n";
        });

        return $this;
    }

    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/alt-redirect.php', 'alt-redirect');

        $this->app->singleton(RepositoryManager::class, function ($app) {
            return new RepositoryManager($app);
        });

        $this->app->bind(RepositoryInterface::class, function ($app) {
            return $app->make(RepositoryManager::class)->driver();
        });
    }

    public function bootAddon()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

            $this->publishes([
                __DIR__.'/../config/alt-redirect.php' => config_path('alt-redirect.php'),
            ], 'alt-redirect-config');

            $this->publishes([
                __DIR__.'/Database/Migrations' => database_path('migrations'),
            ], 'alt-redirect-migrations');
        }

        $this->addToNav()
            ->registerPermissions()
            ->registerCommands()
            ->configureSSG()
            ->installDefaultQueryStrings();
    }
}
