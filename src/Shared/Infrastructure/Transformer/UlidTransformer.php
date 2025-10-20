<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class UlidTransformer
{
    public function __construct(private UlidFactory $ulidFactory)
    {
    }

    public function toDatabaseValue(mixed $value): ?Binary
    {
        if ($this->isInvalidValue($value)) {
            return null;
        }

        $ulid = $this->ensureUlidInstance($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(mixed $binary): ?Ulid
    {
        $symfonyUlid = $this->ensureSymfonyUlidInstance($binary);
        return $this->transformFromSymfonyUlid($symfonyUlid);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function isInvalidValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return !SymfonyUlid::isValid($value);
        }

        return false;
    }

    private function ensureUlidInstance(mixed $value): Ulid
    {
        return $value instanceof Ulid
            ? $value
            : $this->ulidFactory->create($value);
    }

    private function ensureSymfonyUlidInstance(
        mixed $binary
    ): SymfonyUlid {
        return $binary instanceof SymfonyUlid
            ? $binary
            : SymfonyUlid::fromBinary($binary);
    }
}
