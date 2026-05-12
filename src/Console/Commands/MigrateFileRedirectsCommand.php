<?php

namespace AltDesign\AltRedirect\Console\Commands;

use AltDesign\AltRedirect\Repositories\DatabaseRepository;
use AltDesign\AltRedirect\Repositories\FileRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class MigrateFileRedirectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alt-redirect:migrate-file-redirects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates existing file-based redirects to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! Schema::hasTable('alt_redirects') || ! Schema::hasTable('alt_query_strings')) {
            $this->error('The required database tables do not exist.');
            $this->warn('Please run the following commands to set up the database:');
            $this->line('1. php artisan vendor:publish --tag=alt-redirect-migrations');
            $this->line('2. php artisan migrate');

            return 1;
        }

        if (! $this->confirm('This will migrate all file-based redirects and query strings to the database. Do you wish to continue?')) {
            $this->error('User aborted command.');

            return 1;
        }

        $fileRepository = new FileRepository;
        $databaseRepository = new DatabaseRepository;

        $this->info('Migrating redirects...');
        $redirects = $fileRepository->all('redirects');
        if (count($redirects) > 0) {
            foreach ($redirects as $redirect) {
                $databaseRepository->save('redirects', $redirect);
            }
            $this->info(count($redirects).' redirects migrated.');
        } else {
            $this->info('No redirects found to migrate.');
        }

        $this->info('Migrating query strings...');
        $queryStrings = $fileRepository->all('query-strings');
        if (count($queryStrings) > 0) {
            foreach ($queryStrings as $queryString) {
                $databaseRepository->save('query-strings', $queryString);
            }
            $this->info(count($queryStrings).' query strings migrated.');
        } else {
            $this->info('No query strings found to migrate.');
        }

        $this->info('Migration completed successfully!');

        return 0;
    }
}
