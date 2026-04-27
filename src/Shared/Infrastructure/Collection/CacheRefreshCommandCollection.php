<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Collection;

use App\Shared\Application\Command\CacheRefreshCommand;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheRefreshCommand>
 */
final readonly class CacheRefreshCommandCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheRefreshCommand> */
    private ArrayIterator $commands;

    private function __construct(CacheRefreshCommand ...$commands)
    {
        $this->commands = new ArrayIterator($commands);
    }

    public static function create(CacheRefreshCommand ...$commands): self
    {
        return new self(...$commands);
    }

    public function with(CacheRefreshCommand ...$commands): self
    {
        return new self(...iterator_to_array($this->commands), ...$commands);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->commands));
    }

    public function count(): int
    {
        return $this->commands->count();
    }
}
