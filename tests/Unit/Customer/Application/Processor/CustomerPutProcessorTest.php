<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPut;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerPutProcessor;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class CustomerPutProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private UpdateCustomerCommandFactoryInterface|MockObject $factory;
    private IriConverterInterface|MockObject $iriConverter;
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
        $this->iriConverter = $this
            ->createMock(IriConverterInterface::class);
        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $ulidFactory = new UlidFactory();
        $this->ulidTransformer = new UlidTransformer($ulidFactory, new UlidValidator(), new UlidValueTransformer($ulidFactory));
        $this->processor = new CustomerPutProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->iriConverter,
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
        $this->setupIriConverter($dto, $type, $status);
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
            ->method('find')
            ->with($this->ulidTransformer->transformFromSymfonyUlid($ulid))
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVariables);
    }

    private function setupRepository(Ulid $ulid, Customer $customer): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->ulidTransformer->transformFromSymfonyUlid($ulid))
            ->willReturn($customer);
    }

    private function setupIriConverter(
        CustomerPut $dto,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $this->iriConverter
            ->expects($this->exactly(2))
            ->method('getResourceFromIri')
            ->willReturnCallback(fn (
                string $iri
            ) => $this->resolveIri($iri, $dto, $type, $status));
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

    /**
     * Resolves the IRI to the corresponding resource using a mapping array.
     *
     * @throws \InvalidArgumentException if the IRI is unexpected.
     */
    private function resolveIri(
        string $iri,
        CustomerPut $dto,
        CustomerType $type,
        CustomerStatus $status
    ): CustomerType|CustomerStatus {
        $mapping = [
            $dto->type => $type,
            $dto->status => $status,
        ];

        if (isset($mapping[$iri])) {
            return $mapping[$iri];
        }
        throw new \InvalidArgumentException('Unexpected IRI');
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
