<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext\Builder;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Faker\Generator;

final class CustomerTestDataBuilder
{
    private string $initials;
    private string $email;
    private string $phone;
    private string $leadSource;
    private bool $confirmed = true;
    private ?CustomerType $type = null;
    private ?CustomerStatus $status = null;
    private ?Ulid $ulid = null;

    public function __construct(
        private readonly CustomerFactoryInterface $customerFactory,
        private readonly Generator $faker
    ) {
        $this->initials = $this->faker->lexify('??');
        $this->email = $this->faker->email();
        $this->phone = $this->faker->e164PhoneNumber();
        $this->leadSource = $this->faker->word();
    }

    public function withInitials(string $initials): self
    {
        $this->initials = $initials;
        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function withPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function withLeadSource(string $leadSource): self
    {
        $this->leadSource = $leadSource;
        return $this;
    }

    public function withConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function withType(CustomerType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function withStatus(CustomerStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function withUlid(Ulid $ulid): self
    {
        $this->ulid = $ulid;
        return $this;
    }

    public function build(): Customer
    {
        if ($this->type === null) {
            throw new \RuntimeException('CustomerType must be set before building');
        }

        if ($this->status === null) {
            throw new \RuntimeException('CustomerStatus must be set before building');
        }

        if ($this->ulid === null) {
            throw new \RuntimeException('Ulid must be set before building');
        }

        return $this->customerFactory->create(
            $this->initials,
            $this->email,
            $this->phone,
            $this->leadSource,
            $this->type,
            $this->status,
            $this->confirmed,
            $this->ulid
        );
    }

    public function reset(): self
    {
        $this->initials = $this->faker->lexify('??');
        $this->email = $this->faker->email();
        $this->phone = $this->faker->e164PhoneNumber();
        $this->leadSource = $this->faker->word();
        $this->confirmed = true;
        $this->type = null;
        $this->status = null;
        $this->ulid = null;
        return $this;
    }
}
