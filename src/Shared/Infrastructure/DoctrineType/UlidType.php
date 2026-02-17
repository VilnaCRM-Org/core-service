<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public const NAME = 'ulid';

    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        return $value instanceof Binary
            ? $value
            : $this->createTransformer()->toDatabaseValue($value);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value): ?Ulid
    {
        if ($value === null || $value instanceof Ulid) {
            return $value;
        }

        return $this->createTransformer()->toPhpValue(
            $this->extractBinaryData($value)
        );
    }

    #[\Override]
    public function closureToMongo(): string
    {
        return <<<'PHP'
    $return = $value instanceof \App\Shared\Domain\ValueObject\Ulid
        ? new \MongoDB\BSON\Binary(
        $value->toBinary(), \MongoDB\BSON\Binary::TYPE_GENERIC
        )
        : null;
    PHP;
    }

    #[\Override]
    public function closureToPHP(): string
    {
        return <<<'PHP'
$return = $value ? (function($value) {
    $ulidFactory = new \App\Shared\Infrastructure\Factory\UlidFactory();
    $transformer = new \App\Shared\Infrastructure\Transformer\UlidTransformer(
        $ulidFactory,
        new \App\Shared\Infrastructure\Validator\UlidValidator(),
        new \App\Shared\Infrastructure\Transformer\UlidValueTransformer($ulidFactory)
    );
    $binary = $value instanceof \MongoDB\BSON\Binary ? $value
    ->getData() : $value;
    if (!$binary instanceof \Symfony\Component\Uid\Ulid) {
        $binary = \Symfony\Component\Uid\Ulid::fromBinary($binary);
    }
    return $transformer->transformFromSymfonyUlid($binary);
})($value) : null;
PHP;
    }

    private function createTransformer(): UlidTransformer
    {
        $ulidFactory = new UlidFactory();
        return new UlidTransformer(
            $ulidFactory,
            new UlidValidator(),
            new UlidValueTransformer($ulidFactory)
        );
    }

    private function extractBinaryData(mixed $value): mixed
    {
        return $value instanceof Binary ? $value->getData() : $value;
    }
}
