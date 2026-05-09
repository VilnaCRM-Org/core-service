<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Collection;

use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheRefreshCommandHandlerBase>
 */
final readonly class CacheRefreshCommandHandlerCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheRefreshCommandHandlerBase> */
    private ArrayIterator $handlers;

    private function __construct(CacheRefreshCommandHandlerBase ...$handlers)
    {
        $this->handlers = new ArrayIterator($handlers);
    }

    /**
     * @param iterable<CacheRefreshCommandHandlerBase> $handlers
     */
    public static function fromIterable(iterable $handlers): self
    {
        return new self(...iterator_to_array($handlers));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->handlers));
    }

    public function count(): int
    {
        return $this->handlers->count();
    }
}
