<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\ValueObject\Ulid;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * @psalm-suppress UndefinedClass
 */
final class UlidRangeFilter extends AbstractFilter
{
    /**
     * @return array<string, mixed>
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->getProperties() as $property => $_) {
            $description = array_merge(
                $description,
                $this->getPropertyDescription($property)
            );
        }

        return $description;
    }

    /**
     * @param mixed $value
     * @param object $queryBuilder
     * @param array<string, mixed> $context
     *
     * @psalm-suppress UndefinedClass
     */
    protected function filterProperty(
        string $property,
               $value,
               $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!$this->isPropertyEnabled($property, $resourceClass) ||
            !is_array($value)) {
            return;
        }

        $this->addRangeFilter(
            $queryBuilder,
            $queryNameGenerator,
            $property,
            $value
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getPropertyDescription(string $property): array
    {
        return [
            sprintf('%s[before]', $property) => [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'description' => sprintf(
                    'Get collection of %s before specified ULID.',
                    $property
                ),
            ],
            sprintf('%s[after]', $property) => [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'description' => sprintf(
                    'Get collection of %s after specified ULID.',
                    $property
                ),
            ],
        ];
    }

    /**
     * @param object $queryBuilder
     * @param array<string, string> $values
     *
     * @psalm-suppress UnusedMethod
     * @psalm-suppress UndefinedClass
     */
    private function addRangeFilter(
        $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $property,
        array $values
    ): void {
        $alias = $queryBuilder->getRootAliases()[0];
        $field = sprintf('%s.%s', $alias, $property);

        foreach ($values as $operator => $value) {
            $this->processOperator(
                $queryBuilder,
                $queryNameGenerator,
                $field,
                $property,
                $operator,
                $value
            );
        }
    }

    /**
     * @param object $queryBuilder
     */
    private function processOperator(
        $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $field,
        string $property,
        string $operator,
        string $value
    ): void {
        if (!is_string($value) || !SymfonyUlid::isValid($value)) {
            return;
        }

        $ulid = Ulid::fromString($value);
        $parameterName = $queryNameGenerator->generateParameterName(
            $property
        );

        match ($operator) {
            'before' => $queryBuilder
                ->andWhere(sprintf('%s < :%s', $field, $parameterName))
                ->setParameter($parameterName, $ulid),
            'after' => $queryBuilder
                ->andWhere(sprintf('%s > :%s', $field, $parameterName))
                ->setParameter($parameterName, $ulid),
            'between' => $this->addConditions(
                $queryBuilder,
                $queryNameGenerator,
                $field,
                $this->splitValues($value)
            ),
            default => null,
        };
    }

    /**
     * @param object $queryBuilder
     * @param array<string> $values
     *
     * @psalm-suppress UnusedMethod
     * @psalm-suppress UndefinedClass
     */
    private function addConditions(
        $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $field,
        array $values
    ): void {
        if (count($values) === 2) {
            $startParam = $queryNameGenerator->generateParameterName('start');
            $endParam = $queryNameGenerator->generateParameterName('end');

            $queryBuilder
                ->andWhere(sprintf(
                    '%s BETWEEN :%s AND :%s',
                    $field,
                    $startParam,
                    $endParam
                ))
                ->setParameter($startParam, Ulid::fromString($values[0]))
                ->setParameter($endParam, Ulid::fromString($values[1]));
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-suppress UnusedMethod
     */
    private function splitValues(string $value): array
    {
        $parts = explode(',', $value);

        return array_filter($parts, static function (string $part): bool {
            return SymfonyUlid::isValid(trim($part));
        });
    }
}
