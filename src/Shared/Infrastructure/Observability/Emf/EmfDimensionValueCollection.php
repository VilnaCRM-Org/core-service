<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emf;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of EMF dimension values
 *
 * @implements IteratorAggregate<int, EmfDimensionValue>
 */
final readonly class EmfDimensionValueCollection implements IteratorAggregate, Countable
{
    /** @var array<int, EmfDimensionValue> */
    private array $dimensions;

    public function __construct(EmfDimensionValue ...$dimensions)
    {
        $this->dimensions = $dimensions;
    }

    /**
     * @return Traversable<int, EmfDimensionValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->dimensions);
    }

    public function count(): int
    {
        return count($this->dimensions);
    }

    /**
     * @return array<int, EmfDimensionValue>
     */
    public function all(): array
    {
        return $this->dimensions;
    }

    /**
     * @return array<string, string>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->dimensions as $dimension) {
            $result[$dimension->key()] = $dimension->value();
        }

        return $result;
    }

    public function keys(): EmfDimensionKeys
    {
        $keys = array_map(
            static fn (EmfDimensionValue $dim): string => $dim->key(),
            $this->dimensions
        );

        return new EmfDimensionKeys(...$keys);
    }
}
