<?php

namespace AltDesign\AltRedirect\Tests;

use AltDesign\AltRedirect\ServiceProvider;
use Statamic\Facades\Site;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.edits.file', true);
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.system.multisite', true);
        $app['config']->set('statamic.editions.pro', true);

        // Configure sites
        $app['config']->set('statamic.sites', [
            'default' => [
                'name' => 'English',
                'locale' => 'en_US',
                'url' => '/',
            ],
            'other' => [
                'name' => 'French',
                'locale' => 'fr_FR',
                'url' => '/fr/',
            ],
        ]);

        // Use a temporary database for testing
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Mock the disk for file driver
        $app['config']->set('filesystems.disks.local.root', __DIR__.'/__fixtures__/storage');
        $app->bind('filesystems.paths.standard', fn () => __DIR__.'/__fixtures__/storage');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/Database/Migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Site::setSites(config('statamic.sites'));
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__.'/__fixtures__/storage/content/alt-redirect')) {
            $this->deleteDirectory(__DIR__.'/__fixtures__/storage/content/alt-redirect');
        }

        parent::tearDown();
    }

    private function deleteDirectory($dir)
    {
        if (! file_exists($dir)) {
            return true;
        }

        if (! is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (! $this->deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
