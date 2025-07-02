<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class UlidFilterProcessor
{
    /**
     * @var array<string, object>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            'lt' => new Lt(),
            'lte' => new Lte(),
            'gt' => new Gt(),
            'gte' => new Gte(),
            'between' => new Between(),
        ];
    }

    public function process(
        string $field,
        string $operator,
        string $rawValue,
        Builder $builder
    ): void {
        $strategy = $this->strategies[$operator] ?? null;
        if (!$strategy || !$this->isValidUlid($rawValue)) {
            return;
        }

        $filterValue = $this->prepareFilterValue($operator, $rawValue);
        $strategy->apply($builder, $field, $filterValue);
    }

    /**
     * @return Ulid|array<Ulid>
     */
    private function prepareFilterValue(
        string $operator,
        string $rawValue
    ): Ulid|array {
        if ($operator === 'between') {
            $values = explode(',', $rawValue);
            return array_map(
                static fn ($val) => new Ulid(trim($val)),
                $values
            );
        }

        return new Ulid($rawValue);
    }

    private function isValidUlid(string $value): bool
    {
        try {
            new Ulid($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
