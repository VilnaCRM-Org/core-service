<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\ValueObject\UlidInterface;
use DateTime;
use DateTimeImmutable;

/**
 * @psalm-suppress PossiblyUnusedMethod
 */
class Customer implements CustomerInterface
{
    public function __construct(
        private string $initials,
        private string $email,
        private string $phone,
        private string $leadSource,
        private CustomerType $type,
        private CustomerStatus $status,
        private ?bool $confirmed,
        private UlidInterface $ulid,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTime $updatedAt = null,
    ) {
        $this->createdAt ??= new DateTimeImmutable();
        $this->updatedAt ??= new DateTime();
    }

    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUlid(UlidInterface $ulid): void
    {
        $this->ulid = $ulid;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getLeadSource(): string
    {
        return $this->leadSource;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setLeadSource(string $leadSource): void
    {
        $this->leadSource = $leadSource;
    }

    public function getType(): CustomerType
    {
        return $this->type;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setType(CustomerType $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): CustomerStatus
    {
        return $this->status;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setStatus(CustomerStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isConfirmed(): bool
    {
        return (bool) $this->confirmed;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    public function update(CustomerUpdate $updateData): void
    {
        $this->initials = $updateData->newInitials;
        $this->email = $updateData->newEmail;
        $this->phone = $updateData->newPhone;
        $this->leadSource = $updateData->newLeadSource;
        $this->type = $updateData->newType;
        $this->status = $updateData->newStatus;
        $this->confirmed = $updateData->newConfirmed;
        $this->updatedAt = new DateTime();
    }
}
