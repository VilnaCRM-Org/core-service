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

    public function toDatabaseValue(string|Ulid|null $value): ?Binary
    {
        if ($value === null || $this->isInvalidUlidString($value)) {
            return null;
        }

        $ulid = $value instanceof Ulid
            ? $value
            : $this->ulidFactory->create($value);

        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(string|SymfonyUlid $binaryData): Ulid
    {
        if (!$binaryData instanceof SymfonyUlid) {
            $binaryData = SymfonyUlid::fromBinary($binaryData);
        }
        return $this->transformFromSymfonyUlid($binaryData);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->createUlid((string) $symfonyUlid);
    }

    private function createUlid(string $ulid): Ulid
    {
        return $this->ulidFactory->create($ulid);
    }

    private function isInvalidUlidString(mixed $value): bool
    {
        return is_string($value) && !SymfonyUlid::isValid($value);
    }
}
