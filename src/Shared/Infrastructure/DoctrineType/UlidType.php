<?php

namespace App\Shared\Infrastructure\DoctrineType;


use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Component\Uid\Ulid;

class UlidType extends Type
{
    public const NAME = 'ulid';

    public function convertToDatabaseValue($value)
    {
        return $value instanceof Ulid ? $value->toString() : null;
    }

    public function convertToPHPValue($value)
    {
        return $value ? new Ulid($value) : null;
    }

    public function closureToMongo(): string
    {
        return '$return = $value instanceof \Symfony\Component\Uid\Ulid ? $value->toRfc4122() : null;';
    }

    public function closureToPHP(): string
    {
        return '$return = $value ? new \Symfony\Component\Uid\Ulid($value) : null;';
    }
    public function getName(): string
    {
        return self::NAME;
    }
}