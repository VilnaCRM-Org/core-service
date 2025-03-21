<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Entity;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;

final class CustomerTest extends UnitTestCase
{
    private CustomerInterface $customer;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ulidTransformer = new UlidTransformer(new UlidFactory());
        $ulid = $this->ulidTransformer->transformFromSymfonyUlid($this->faker->ulid());

        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);

        $this->customer = new Customer(
            $this->faker->name(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->word(),
            $type,
            $status,
            false,
            $ulid
        );
    }

    public function testGetAndSetUlid(): void
    {
        $ulidString = $this->faker->ulid();
        $ulidObject = $this->ulidTransformer->transformFromSymfonyUlid($ulidString);
        $this->customer->setUlid($ulidObject);
        $this->assertEquals($ulidString, $this->customer->getUlid());
    }

    public function testGetAndSetEmail(): void
    {
        $email = $this->faker->email();
        $this->customer->setEmail($email);
        $this->assertEquals($email, $this->customer->getEmail());
    }

    public function testGetAndSetInitials(): void
    {
        $initials = $this->faker->name();
        $this->customer->setInitials($initials);
        $this->assertEquals($initials, $this->customer->getInitials());
    }

    public function testGetAndSetPhone(): void
    {
        $phone = $this->faker->phoneNumber();
        $this->customer->setPhone($phone);
        $this->assertEquals($phone, $this->customer->getPhone());
    }

    public function testGetAndSetLeadSource(): void
    {
        $leadSource = $this->faker->word();
        $this->customer->setLeadSource($leadSource);
        $this->assertEquals($leadSource, $this->customer->getLeadSource());
    }

    public function testGetAndSetType(): void
    {
        $newType = $this->createMock(CustomerType::class);
        $this->customer->setType($newType);
        $this->assertEquals($newType, $this->customer->getType());
    }

    public function testGetAndSetStatus(): void
    {
        $newStatus = $this->createMock(CustomerStatus::class);
        $this->customer->setStatus($newStatus);
        $this->assertEquals($newStatus, $this->customer->getStatus());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $createdAt = new DateTimeImmutable($this->faker->date('Y-m-d H:i:s'));
        $this->customer->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->customer->getCreatedAt());
    }

    public function testGetAndSetUpdatedAt(): void
    {
        $updatedAt = new DateTimeImmutable($this->faker->date('Y-m-d H:i:s'));
        $this->customer->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $this->customer->getUpdatedAt());
    }

    public function testGetAndSetConfirmed(): void
    {
        $this->customer->setConfirmed(true);
        $this->assertTrue($this->customer->isConfirmed());

        $this->customer->setConfirmed(false);
        $this->assertFalse($this->customer->isConfirmed());
    }
}
