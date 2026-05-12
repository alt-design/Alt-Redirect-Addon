<?php

namespace AltDesign\AltRedirect\Contracts;

interface RepositoryInterface
{
    public function all(string $type): array;

    public function getRegex(string $type): array;

    public function find(string $type, string $key, $value): ?array;

    public function save(string $type, array $data): void;

    public function saveAll(string $type, array $data): void;

    public function delete(string $type, array $data): void;
}
