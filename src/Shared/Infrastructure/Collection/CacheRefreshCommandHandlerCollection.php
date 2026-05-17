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

    public function __construct(CacheRefreshCommandHandlerBase ...$handlers)
    {
        $this->handlers = new ArrayIterator($handlers);
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
