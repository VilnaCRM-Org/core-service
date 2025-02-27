<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use DateTime;
use DateTimeInterface;
use Symfony\Component\Uid\Ulid;

class Customer implements CustomerInterface
{
    private ?DateTimeInterface $createdAt;
    private ?DateTimeInterface $updatedAt;

    private Ulid $ulid;

    public function __construct(
        private string $initials,
        private string $email,
        private string $phone,
        private string $leadSource,
        private CustomerType $type,
        private CustomerStatus $status,
        private ?bool $confirmed = false,
    ) {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->ulid = new Ulid();
    }

    public function getUlid(): Ulid
    {
        return $this->ulid;
    }

    public function setUlid(?string $ulid): void
    {
        $this->ulid = new Ulid($ulid);
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
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
