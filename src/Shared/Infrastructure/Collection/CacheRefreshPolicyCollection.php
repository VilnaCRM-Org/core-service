<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Collection;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheRefreshPolicy>
 */
final readonly class CacheRefreshPolicyCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheRefreshPolicy> */
    private ArrayIterator $policies;

    private function __construct(CacheRefreshPolicy ...$policies)
    {
        $this->policies = new ArrayIterator($policies);
    }

    public static function create(CacheRefreshPolicy ...$policies): self
    {
        return new self(...$policies);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->policies));
    }

    public function count(): int
    {
        return $this->policies->count();
    }
}
