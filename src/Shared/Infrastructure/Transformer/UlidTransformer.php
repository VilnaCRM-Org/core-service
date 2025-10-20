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

        return $this->isInvalidStringValue($value);
    }

    private function isInvalidStringValue(mixed $value): bool
    {
        return $this->isStringValue($value) && $this->isInvalidUlid($value);
    }

    private function isStringValue(mixed $value): bool
    {
        return is_string($value);
    }

    private function isInvalidUlid(string $value): bool
    {
        return !SymfonyUlid::isValid($value);
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
