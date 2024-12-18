<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UuidInterface;
use DateTime;

class Customer implements CustomerInterface
{
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        private UuidInterface $id,
        private string $initials,
        private string $email,
        private string $phone,
        private string $leadSource,
        private string $type,
        private string $status,
        private ?bool $confirmed = false
    ) {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
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
