<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;
use DateTimeImmutable;
use DateTimeInterface;

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
        private DateTimeInterface $createdAt = new DateTimeImmutable(),
        private DateTimeInterface $updatedAt = new DateTimeImmutable(),
    ) {
    }

    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

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

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getLeadSource(): string
    {
        return $this->leadSource;
    }

    public function setLeadSource(string $leadSource): void
    {
        $this->leadSource = $leadSource;
    }

    public function getType(): CustomerType
    {
        return $this->type;
    }

    public function setType(CustomerType $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): CustomerStatus
    {
        return $this->status;
    }

    public function setStatus(CustomerStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }
}
