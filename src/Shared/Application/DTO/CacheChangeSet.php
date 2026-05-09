<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheFieldChange>
 */
final readonly class CacheChangeSet implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheFieldChange> */
    private ArrayIterator $changes;

    private function __construct(CacheFieldChange ...$changes)
    {
        $this->changes = new ArrayIterator($changes);
    }

    public static function create(CacheFieldChange ...$changes): self
    {
        return new self(...$changes);
    }

    /**
     * @param iterable<string, array{
     *     0: bool|float|int|string|object|array<array-key, bool|float|int|string|object|null>|null,
     *     1: bool|float|int|string|object|array<array-key, bool|float|int|string|object|null>|null
     * }> $changeSet
     */
    public static function fromDoctrineChangeSet(iterable $changeSet): self
    {
        $changes = [];

        foreach ($changeSet as $field => $change) {
            $changes[] = CacheFieldChange::create(
                (string) $field,
                $change[0] ?? null,
                $change[1] ?? null
            );
        }

        return new self(...$changes);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function get(string $field): ?CacheFieldChange
    {
        foreach ($this->changes as $change) {
            if ($change->field() === $field) {
                return $change;
            }
        }

        return null;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->changes));
    }

    public function count(): int
    {
        return $this->changes->count();
    }
}
