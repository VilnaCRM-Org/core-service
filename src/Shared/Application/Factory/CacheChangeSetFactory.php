<?php

declare(strict_types=1);

namespace App\Shared\Application\Factory;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheFieldChange;

final readonly class CacheChangeSetFactory
{
    public function empty(): CacheChangeSet
    {
        return new CacheChangeSet();
    }

    /**
     * @param iterable<string, array{
     *     0: bool|float|int|string|object|array<array-key, bool|float|int|string|object|null>|null,
     *     1: bool|float|int|string|object|array<array-key, bool|float|int|string|object|null>|null
     * }> $changeSet
     */
    public function fromDoctrineChangeSet(iterable $changeSet): CacheChangeSet
    {
        $changes = [];

        foreach ($changeSet as $field => $change) {
            $changes[] = new CacheFieldChange(
                (string) $field,
                $change[0] ?? null,
                $change[1] ?? null
            );
        }

        return new CacheChangeSet(...$changes);
    }
}
