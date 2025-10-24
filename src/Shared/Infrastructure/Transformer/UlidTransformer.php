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
        if ($binary instanceof SymfonyUlid) {
            return $this->transformFromSymfonyUlid($binary);
        }

        $symfonyUlid = SymfonyUlid::fromBinary($binary);
        return $this->transformFromSymfonyUlid($symfonyUlid);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function shouldReturnNull(mixed $value): bool
    {
        return $value === null || $this->isInvalidString($value);
    }

    private function ensureUlidInstance(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->ulidFactory->create($value);
    }

    private function isInvalidString(mixed $value): bool
    {
        return is_string($value) && !SymfonyUlid::isValid($value);
    }
}
