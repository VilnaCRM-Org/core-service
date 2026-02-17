<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\CustomerTransformer;
use App\Core\Customer\Application\Transformer\CustomerTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class CreateCustomerTransformerTest extends UnitTestCase
{
    private UlidTransformer $ulidTransformer;
    private UlidFactory $ulidFactory;
    private CustomerFactoryInterface $customerFactory;
    private CustomerTransformerInterface $transformer;
    private SymfonyUlid $symfonyUlid;
    private Ulid $voUlid;

    private CustomerType $type;

    private CustomerStatus $status;

    private string $initials;

    private string $email;

    private string $phone;

    private string $lead;

    private bool $confirmed;

    private Customer $customer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ulidTransformer = $this->createMock(UlidTransformer::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->customerFactory = $this->createMock(
            CustomerFactoryInterface::class
        );

        $this->symfonyUlid = $this->createMock(SymfonyUlid::class);
        $this->voUlid = $this->createMock(Ulid::class);

        $this->ulidFactory
            ->method('create')
            ->willReturn($this->symfonyUlid);

        $this->ulidTransformer
            ->method('transformFromSymfonyUlid')
            ->with($this->symfonyUlid)
            ->willReturn($this->voUlid);

        $this->transformer = new CustomerTransformer(
            $this->customerFactory,
            $this->ulidTransformer,
            $this->ulidFactory
        );
    }

    public function testTransformCreatesCustomerWithGeneratedUlid(): void
    {
        $this->prepareData();
        $this->customerFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->initials,
                $this->email,
                $this->phone,
                $this->lead,
                self::identicalTo($this->type),
                self::identicalTo($this->status),
                $this->confirmed,
                self::identicalTo($this->voUlid)
            )
            ->willReturn($this->customer);

        $result = $this->receiveResult();

        $this->assertSame($this->customer, $result);
    }

    private function prepareData(): void
    {
        $this->type = $this->createMock(CustomerType::class);
        $this->status = $this->createMock(CustomerStatus::class);
        $this->initials = $this->faker->name();
        $this->email = $this->faker->email();
        $this->phone = $this->faker->phoneNumber();
        $this->lead = $this->faker->word();
        $this->confirmed = $this->faker->boolean();

        $this->customer = $this->createMock(Customer::class);
    }

    private function receiveResult(): Customer
    {
        return $this->transformer->transform(
            $this->initials,
            $this->email,
            $this->phone,
            $this->lead,
            $this->type,
            $this->status,
            $this->confirmed,
        );
    }
}
