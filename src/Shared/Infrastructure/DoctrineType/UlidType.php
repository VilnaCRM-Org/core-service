<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Doctrine\ODM\MongoDB\Types\Type;

final class UlidType extends Type
{
    public const NAME = 'ulid';

    public function convertToDatabaseValue($value)
    {
        return (new UlidTransformer())->toDatabase($value);
    }

    public function convertToPHPValue($value)
    {
        return (new UlidTransformer())->toPHP($value);
    }

    public function closureToMongo(): string
    {
        return <<<'PHP'
        $return = $value instanceof \Symfony\Component\Uid\Ulid
            ? new \MongoDB\BSON\Binary(
            $value->toBinary(), \MongoDB\BSON\Binary::TYPE_GENERIC
            )
            : null;
        PHP;
    }

    public function closureToPHP(): string
    {
        return <<<'PHP'
        $return = $value 
        ? ($value instanceof \MongoDB\BSON\Binary 
            ? \Symfony\Component\Uid\Ulid::fromString($value->getData()) 
            : \Symfony\Component\Uid\Ulid::fromString($value)) 
        : null;
        PHP;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
