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
        if ($this->shouldReturnNull($value)) {
            return null;
        }

        $ulid = $this->ensureUlidInstance($value);

        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(mixed $binary): ?Ulid
    {
        $symfonyUlid = $this->ensureSymfonyUlid($binary);
        return $this->transformFromSymfonyUlid($symfonyUlid);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function shouldReturnNull(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return $this->isInvalidStringValue($value);
    }

    private function isInvalidStringValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return !SymfonyUlid::isValid($value);
    }

    private function ensureUlidInstance(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->ulidFactory->create($value);
    }

    private function ensureSymfonyUlid(mixed $binary): SymfonyUlid
    {
        if ($binary instanceof SymfonyUlid) {
            return $binary;
        }

        return SymfonyUlid::fromBinary($binary);
    }
}
