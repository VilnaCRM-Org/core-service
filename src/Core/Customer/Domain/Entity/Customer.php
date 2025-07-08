<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class Customer implements CustomerInterface
{
    #[Groups(['customer:read'])]
    private string $ulid;

    #[Groups(['customer:read'])]
    private DateTimeInterface $createdAt;

    #[Groups(['customer:read'])]
    private DateTimeInterface $updatedAt;

    #[Groups(['customer:read', 'customer:write'])]
    private CustomerType $type;

    #[Groups(['customer:read', 'customer:write'])]
    private CustomerStatus $status;

    public function __construct(
        #[Groups(['customer:read', 'customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private string $initials,
        #[Groups(['customer:read', 'customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        private string $email,
        #[Groups(['customer:read', 'customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private string $phone,
        #[Groups(['customer:read', 'customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        #[Groups(['customer:read', 'customer:write'])]
        private ?bool $confirmed,
        UlidInterface $ulid,
        DateTimeInterface $createdAt = new DateTimeImmutable(),
        DateTimeInterface $updatedAt = new DateTimeImmutable(),
    ) {
        $this->ulid = (string) $ulid;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->type = $type;
        $this->status = $status;
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

    /**
     * Get ULID identifier
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    #[Groups(['customer:read'])]
    public function getUlid(): string
    {
        return $this->ulid;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getLeadSource(): string
    {
        return $this->leadSource;
    }

    public function setLeadSource(string $leadSource): void
    {
        $this->leadSource = $leadSource;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getType(): ?CustomerType
    {
        return $this->type;
    }

    public function setType(CustomerType $type): void
    {
        $this->type = $type;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function getStatus(): ?CustomerStatus
    {
        return $this->status;
    }

    public function setStatus(CustomerStatus $status): void
    {
        $this->status = $status;
    }

    #[Groups(['customer:read', 'customer:write'])]
    public function isConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    #[Groups(['customer:read'])]
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    #[Groups(['customer:read'])]
    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUlid(UlidInterface $ulid): void
    {
        $this->ulid = (string) $ulid;
    }
}
