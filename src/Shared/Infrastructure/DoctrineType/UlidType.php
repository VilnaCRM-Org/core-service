<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidConverter;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValidator;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public const NAME = 'ulid';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        if ($value instanceof Binary) {
            return $value;
        }

        return $this->createTransformer()->toDatabaseValue($value);
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->createTransformer()->toPhpValue(
            $this->extractBinaryData($value)
        );
    }

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

    public function closureToPHP(): string
    {
        return <<<'PHP'
$return = $value ? (function($value) {
    $ulidFactory = new \App\Shared\Infrastructure\Factory\UlidFactory();
    $transformer = new \App\Shared\Infrastructure\Transformer\UlidTransformer(
        $ulidFactory,
        new \App\Shared\Infrastructure\Transformer\UlidValidator(),
        new \App\Shared\Infrastructure\Transformer\UlidConverter($ulidFactory)
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
            new UlidConverter($ulidFactory)
        );
    }

    private function extractBinaryData(mixed $value): mixed
    {
        return $value instanceof Binary ? $value->getData() : $value;
    }
}
