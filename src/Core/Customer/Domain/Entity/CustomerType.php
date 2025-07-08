<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'customer_types')]
class CustomerType implements CustomerTypeInterface
{
    #[ODM\Id(strategy: 'NONE')]
    #[Groups(['customer_type:read', 'customer:read'])]
    private string $ulid;

    public function __construct(
        #[ODM\Field(type: 'string')]
        #[Groups([
            'customer_type:read',
            'customer_type:write',
            'customer:read',
        ])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private string $value,
        UlidInterface $ulid
    ) {
        $this->ulid = (string) $ulid;
    }

    /**
     * API Platform identifier method
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getId(): string
    {
        return $this->ulid;
    }

    #[Groups(['customer_type:read', 'customer:read'])]
    public function getUlid(): string
    {
        return $this->ulid;
    }

    #[Groups(['customer_type:read', 'customer:read'])]
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUlid(UlidInterface $ulid): void
    {
        $this->ulid = (string) $ulid;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
