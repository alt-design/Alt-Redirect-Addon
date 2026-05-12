<?php

namespace AltDesign\AltRedirect\Repositories;

use AltDesign\AltRedirect\Contracts\RepositoryInterface;
use Illuminate\Support\Manager;

class RepositoryManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('alt-redirect.driver', 'file');
    }

    public function createFileDriver(): RepositoryInterface
    {
        return new FileRepository;
    }

    public function createDatabaseDriver(): RepositoryInterface
    {
        return new DatabaseRepository;
    }
}
