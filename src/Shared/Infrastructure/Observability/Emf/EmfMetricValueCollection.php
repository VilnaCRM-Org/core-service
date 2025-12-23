<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emf;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of EMF metric values
 *
 * @implements IteratorAggregate<int, EmfMetricValue>
 */
final readonly class EmfMetricValueCollection implements IteratorAggregate, Countable
{
    /** @var array<int, EmfMetricValue> */
    private array $values;

    public function __construct(EmfMetricValue ...$values)
    {
        $this->values = $values;
    }

    public function add(EmfMetricValue $value): self
    {
        return new self(...[...$this->values, $value]);
    }

    /**
     * @return Traversable<int, EmfMetricValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /**
     * @return array<int, EmfMetricValue>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * @return array<string, float|int>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->values as $value) {
            $result[$value->name()] = $value->value();
        }

        return $result;
    }
}
