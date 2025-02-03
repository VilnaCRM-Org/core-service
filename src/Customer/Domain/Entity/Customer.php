<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Shared\Domain\ValueObject\UuidInterface;
use DateTime;
use Symfony\Component\Uid\Ulid;

#[ApiResource(paginationPartial: true,
    paginationItemsPerPage: 1,
    paginationViaCursor: [['field' => 'ulid', 'direction' => 'DESC']])]
#[ApiFilter(RangeFilter::class, properties: ["ulid"])]
#[ApiFilter(OrderFilter::class, properties: ["ulid" => "DESC"])]
class Customer implements CustomerInterface
{
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    private Ulid $ulid;

    public function __construct(
        private string         $initials,
        private string         $email,
        private string         $phone,
        private string         $leadSource,
        private CustomerType   $type,
        private CustomerStatus $status,
        private ?bool          $confirmed = false,
        private ?string               $id = null,
    ) {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->ulid = new Ulid();
    }

    public function getUlid(): Ulid
    {
        return $this->ulid;
    }

    public function setUlid(Ulid $ulid): void
    {
        $this->ulid = $ulid;
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

    public function getCreatedAt(): ?string
    {
        return (string) $this->createdAt?->getTimestamp();
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
