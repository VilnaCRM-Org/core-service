<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Shared\Domain\ValueObject\UlidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class CustomerType implements CustomerTypeInterface
{
    public function __construct(
        private string $value,
        private UlidInterface $ulid
    ) {
    }

    #[ApiProperty(identifier: true)]
    #[Groups(['output'])]
    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    #[Groups(['output'])]
    public function getId(): string
    {
        return $this->getUlid();
    }

    #[Groups(['output'])]
    public function getValue(): string
    {
        return $this->value;
    }

    public function setUlid(UlidInterface $ulid): void
    {
        $this->ulid = $ulid;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
