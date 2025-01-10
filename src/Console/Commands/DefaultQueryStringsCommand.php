<?php

namespace AltDesign\AltRedirect\Console\Commands;

use AltDesign\AltRedirect\Helpers\DefaultQueryStrings;
use Illuminate\Console\Command;

class DefaultQueryStringsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alt-redirect:default-query-strings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the default query string flags to be stripped from URIs in the Alt Redirect Addon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!$this->confirm('Do you wish to (re)create the list of default query strings?')) {
            $this->error('User aborted Command');
        }

        (new DefaultQueryStrings)->makeDefaultQueryStrings();
        $this->info('Default query strings list created');
    }
}
