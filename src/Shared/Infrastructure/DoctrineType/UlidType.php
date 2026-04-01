<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidTransformerFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public const NAME = 'ulid';

    private ?UlidTransformer $transformer = null;

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        return $value instanceof Binary
            ? $value
            : $this->getTransformer()->toDatabaseValue($value);
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        if ($value === null || $value instanceof Ulid) {
            return $value;
        }

        return $this->getTransformer()->toPhpValue(
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

    private function getTransformer(): UlidTransformer
    {
        return $this->transformer ??= UlidTransformerFactory::create();
    }

    private function extractBinaryData(mixed $value): mixed
    {
        return $value instanceof Binary ? $value->getData() : $value;
    }
}
