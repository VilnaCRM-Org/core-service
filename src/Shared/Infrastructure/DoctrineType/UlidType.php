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

    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        return $value instanceof Binary
            ? $value
            : $this->createTransformer()->toDatabaseValue($value);
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        if ($value === null || $value instanceof Ulid) {
            return $value;
        }

        return $this->createTransformer()->toPhpValue(
            $this->extractBinaryData($value)
        );
    }

    public function closureToMongo(): string
    {
        return <<<'PHP'
$return = \Doctrine\ODM\MongoDB\Types\Type::getType('ulid')->convertToDatabaseValue($value);
PHP;
    }

    public function closureToPHP(): string
    {
        return <<<'PHP'
$return = \Doctrine\ODM\MongoDB\Types\Type::getType('ulid')->convertToPHPValue($value);
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
