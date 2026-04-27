<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Collection;

use App\Shared\Application\Resolver\CacheRefreshTargetResolverInterface;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheRefreshTargetResolverInterface>
 */
final readonly class CacheRefreshTargetResolverCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheRefreshTargetResolverInterface> */
    private ArrayIterator $resolvers;

    private function __construct(CacheRefreshTargetResolverInterface ...$resolvers)
    {
        $this->resolvers = new ArrayIterator($resolvers);
    }

    /**
     * @param iterable<CacheRefreshTargetResolverInterface> $resolvers
     */
    public static function fromIterable(iterable $resolvers): self
    {
        return new self(...iterator_to_array($resolvers));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->resolvers));
    }

    public function count(): int
    {
        return $this->resolvers->count();
    }
}
