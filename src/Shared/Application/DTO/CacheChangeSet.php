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

    public function __construct(CacheFieldChange ...$changes)
    {
        $this->changes = new ArrayIterator($changes);
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
