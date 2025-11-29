<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Converter\UlidConverter;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Validator\UlidValidator;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class UlidTransformer
{
    public function __construct(
        private UlidFactory $ulidFactory,
        private UlidValidator $validator,
        private UlidConverter $converter
    ) {
    }

    public function toDatabaseValue(array|string|int|float|bool|object|null $value): ?Binary
    {
        if (!$this->validator->isValid($value)) {
            return null;
        }

        $ulid = $this->converter->toUlid($value);

        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(array|string|int|float|bool|object|null $binary): Ulid
    {
        $symfonyUlid = $this->converter->fromBinary($binary);

        return $this->transformFromSymfonyUlid($symfonyUlid);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->create((string) $symfonyUlid);
    }
}
