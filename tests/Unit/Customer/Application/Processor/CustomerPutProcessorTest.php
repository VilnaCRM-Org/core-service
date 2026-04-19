<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPut;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerPutProcessor;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\SymfonyUlidBinaryTransformer;
use App\Shared\Infrastructure\Transformer\UlidRepresentationTransformer;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class CustomerPutProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private UpdateCustomerCommandFactoryInterface|MockObject $factory;
    private CustomerRelationTransformerInterface|MockObject $relationTransformer;
    private CustomerRepositoryInterface|MockObject $repository;
    private CustomerPutProcessor $processor;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this
            ->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateCustomerCommandFactoryInterface::class);
        $this->relationTransformer = $this
            ->createMock(CustomerRelationTransformerInterface::class);
        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $ulidFactory = new UlidFactory();
        $this->ulidTransformer = new UlidTransformer(
            $ulidFactory,
            new UlidValidator(),
            new UlidValueTransformer(
                $ulidFactory,
                new UlidRepresentationTransformer(),
                new SymfonyUlidBinaryTransformer()
            )
        );
        $this->processor = new CustomerPutProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->relationTransformer,
        );
    }

    public function testProcessUpdatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);
        $uriVariables = ['ulid' => $ulidString];
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);
        $command = $this->createMock(UpdateCustomerCommand::class);

        $this->setupRepository($ulid, $customer);
        $this->setupReferenceResolver($dto, $type, $status, $customer);
        $this->setupFactoryAndCommandBus(
            $dto,
            $type,
            $status,
            $customer,
            $command
        );

        $result = $this->processor->process($dto, $operation, $uriVariables);

        $this->assertSame($customer, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);
        $uriVariables = ['ulid' => $ulidString];

        $this->repository
            ->expects($this->once())
            ->method('findFresh')
            ->with($this->ulidTransformer->transformFromSymfonyUlid($ulid))
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVariables);
    }

    public function testProcessThrowsExceptionWhenRepositoryReturnsUnexpectedObject(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);
        $uriVariables = ['ulid' => $ulidString];

        $this->repository
            ->expects($this->once())
            ->method('findFresh')
            ->with($this->ulidTransformer->transformFromSymfonyUlid($ulid))
            ->willReturn(new ArrayObject());

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVariables);
    }

    private function setupRepository(Ulid $ulid, Customer $customer): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findFresh')
            ->with($this->ulidTransformer->transformFromSymfonyUlid($ulid))
            ->willReturn($customer);
    }

    private function setupReferenceResolver(
        CustomerPut $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): void {
        $this->relationTransformer
            ->expects($this->once())
            ->method('resolveType')
            ->with($dto->type, $customer)
            ->willReturn($type);
        $this->relationTransformer
            ->expects($this->once())
            ->method('resolveStatus')
            ->with($dto->status, $customer)
            ->willReturn($status);
    }

    private function setupFactoryAndCommandBus(
        CustomerPut $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer,
        UpdateCustomerCommand $command
    ): void {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with(
                $customer,
                $this->callback(
                    fn ($update) => $this->isUpdateValid(
                        $update,
                        $dto,
                        $type,
                        $status
                    )
                )
            )
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function isUpdateValid(
        object $update,
        CustomerPut $dto,
        CustomerType $type,
        CustomerStatus $status
    ): bool {
        $expected = [
            'newInitials' => $dto->initials,
            'newEmail' => $dto->email,
            'newPhone' => $dto->phone,
            'newLeadSource' => $dto->leadSource,
            'newType' => $type,
            'newStatus' => $status,
            'newConfirmed' => $dto->confirmed,
        ];

        return get_object_vars($update) === $expected;
    }

    private function createDto(): CustomerPut
    {
        return new CustomerPut(
            initials: $this->faker->name(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: '/api/customer_types/' . $this->faker->ulid(),
            status: '/api/customer_statuses/' . $this->faker->ulid(),
            confirmed: $this->faker->boolean(),
        );
    }
}
