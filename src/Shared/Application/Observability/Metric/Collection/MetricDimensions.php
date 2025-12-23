<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\Collection;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Typed collection of metric dimensions.
 *
 * @implements IteratorAggregate<int, MetricDimension>
 */
final readonly class MetricDimensions implements IteratorAggregate, Countable
{
    /** @var array<int, MetricDimension> */
    private array $dimensions;

    public function __construct(MetricDimension ...$dimensions)
    {
        $this->dimensions = $dimensions;
    }

    /**
     * @return Traversable<int, MetricDimension>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->dimensions);
    }

    public function count(): int
    {
        return count($this->dimensions);
    }

    public function get(string $key): ?string
    {
        foreach ($this->dimensions as $dimension) {
            if ($dimension->key() === $key) {
                return $dimension->value();
            }
        }

        return null;
    }

    public function contains(MetricDimension $expected): bool
    {
        return $this->get($expected->key()) === $expected->value();
    }

    /**
     * Infrastructure boundary helper.
     *
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
}
