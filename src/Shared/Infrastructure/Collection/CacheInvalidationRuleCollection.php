<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Collection;

use App\Shared\Application\DTO\CacheInvalidationRule;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CacheInvalidationRule>
 */
final readonly class CacheInvalidationRuleCollection implements IteratorAggregate, Countable
{
    /** @var ArrayIterator<int, CacheInvalidationRule> */
    private ArrayIterator $rules;

    private function __construct(CacheInvalidationRule ...$rules)
    {
        $this->rules = new ArrayIterator($rules);
    }

    public static function create(CacheInvalidationRule ...$rules): self
    {
        return new self(...$rules);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(iterator_to_array($this->rules));
    }

    public function count(): int
    {
        return $this->rules->count();
    }
}
