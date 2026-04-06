<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, string>
 */
final class CustomerCacheTagCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, string> */
    private ArrayIterator $tags;

    public function __construct(string ...$tags)
    {
        $this->tags = new ArrayIterator(array_values(array_unique($tags)));
    }

    public static function forCustomerCache(): self
    {
        return new self('customer', 'customer.collection');
    }

    public function with(string ...$tags): self
    {
        return new self(...iterator_to_array($this->tags), ...$tags);
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->tags));
    }

    public function count(): int
    {
        return $this->tags->count();
    }
}
