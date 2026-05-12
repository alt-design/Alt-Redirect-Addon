<?php

namespace AltDesign\AltRedirect\Console\Commands;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use AltDesign\AltRedirect\Helpers\URISupport;
use Illuminate\Console\Command;

class ReScanRegexCommand extends Command
{
    protected $signature = 'alt-redirect:re-scan-regex';

    protected $description = 'Re-scans all redirects and updates the is_regex flag based on the latest detection logic.';

    public function handle()
    {
        $repository = app(RepositoryInterface::class);
        $redirects = $repository->all('redirects');

        $count = 0;
        foreach ($redirects as $redirect) {
            if (! isset($redirect['from'])) {
                $this->error('Redirect missing "from" key: ' . ($redirect['id'] ?? 'unknown ID'));
                continue;
            }

            $isRegexBefore = (bool) ($redirect['is_regex'] ?? false);
            $isRegexAfter = URISupport::isRegex($redirect['from']);

            if ($isRegexBefore !== $isRegexAfter) {
                $redirect['is_regex'] = $isRegexAfter;
                $repository->save('redirects', $redirect);
                $count++;
            }
        }

        $this->info("Successfully updated $count redirects.");
    }

    protected function isRegex(string $str): bool
    {
        return URISupport::isRegex($str);
    }
}
