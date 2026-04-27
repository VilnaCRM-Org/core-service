<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, string>
 */
final readonly class CacheInvalidationTagSet implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, string> */
    private ArrayIterator $tags;

    private function __construct(string ...$tags)
    {
        $this->tags = new ArrayIterator(array_values(array_unique($tags)));
    }

    public static function create(string ...$tags): self
    {
        return new self(...$tags);
    }

    public function with(string ...$tags): self
    {
        return new self(...iterator_to_array($this->tags), ...$tags);
    }

    public function isEmpty(): bool
    {
        return $this->tags->count() === 0;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->tags));
    }

    public function count(): int
    {
        return $this->tags->count();
    }
}
