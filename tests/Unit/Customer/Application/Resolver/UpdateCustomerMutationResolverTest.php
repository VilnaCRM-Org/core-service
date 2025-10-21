<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\MutationInput\UpdateCustomerMutationInput;
use App\Core\Customer\Application\Resolver\UpdateCustomerMutationResolver;
use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesCustomerWithProvidedData(): void
    {
        [
            'resolver' => $resolver,
            'commandBus' => $commandBus,
            'validator' => $validator,
            'transformer' => $transformer,
            'factory' => $factory,
            'iriConverter' => $iriConverter,
            'repository' => $repository,
        ] = $this->createResolverWithDependencies();

        $input = [
            'id' => $this->faker->uuid(),
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => '/api/customer_types/' . $this->faker->uuid(),
            'status' => '/api/customer_statuses/' . $this->faker->uuid(),
            'confirmed' => true,
        ];

        $mutationInput = new UpdateCustomerMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $customer = $this->createMock(Customer::class);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($input['id'])
            ->willReturn($customer);

        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([$input['type']], [$input['status']])
            ->willReturnOnConsecutiveCalls($customerType, $customerStatus);

        $caughtUpdate = null;

        $factory
            ->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($customer), $this->isInstanceOf(CustomerUpdate::class))
            ->willReturnCallback(
                function (Customer $customerArg, CustomerUpdate $update) use (&$caughtUpdate) {
                    $caughtUpdate = $update;

                    return new UpdateCustomerCommand($customerArg, $update);
                }
            );

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function ($command) use ($customer, &$caughtUpdate) {
                self::assertInstanceOf(UpdateCustomerCommand::class, $command);
                self::assertSame($customer, $command->customer);
                self::assertSame($caughtUpdate, $command->updateData);

                return true;
            }));

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
        self::assertInstanceOf(CustomerUpdate::class, $caughtUpdate);
        self::assertSame($input['initials'], $caughtUpdate->newInitials);
        self::assertSame($input['email'], $caughtUpdate->newEmail);
        self::assertSame($input['phone'], $caughtUpdate->newPhone);
        self::assertSame($input['leadSource'], $caughtUpdate->newLeadSource);
        self::assertSame($customerType, $caughtUpdate->newType);
        self::assertSame($customerStatus, $caughtUpdate->newStatus);
        self::assertTrue($caughtUpdate->newConfirmed);
    }

    public function testInvokeUsesExistingDataWhenOptionalFieldsMissing(): void
    {
        [
            'resolver' => $resolver,
            'commandBus' => $commandBus,
            'validator' => $validator,
            'transformer' => $transformer,
            'factory' => $factory,
            'iriConverter' => $iriConverter,
            'repository' => $repository,
        ] = $this->createResolverWithDependencies();

        [
            'customer' => $customer,
            'type' => $customerType,
            'status' => $customerStatus,
            'data' => $existingData,
        ] = $this->createCustomerWithExistingData();

        $input = ['id' => $this->faker->uuid()];

        $mutationInput = new UpdateCustomerMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($input['id'])
            ->willReturn($customer);

        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([
                '/api/customer_types/' . $existingData['typeUlid'],
            ], [
                '/api/customer_statuses/' . $existingData['statusUlid'],
            ])
            ->willReturnOnConsecutiveCalls($customerType, $customerStatus);

        $caughtUpdate = null;

        $factory
            ->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($customer), $this->isInstanceOf(CustomerUpdate::class))
            ->willReturnCallback(
                function (Customer $customerArg, CustomerUpdate $update) use (&$caughtUpdate) {
                    $caughtUpdate = $update;

                    return new UpdateCustomerCommand($customerArg, $update);
                }
            );

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerCommand::class));

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
        self::assertInstanceOf(CustomerUpdate::class, $caughtUpdate);
        self::assertSame($existingData['initials'], $caughtUpdate->newInitials);
        self::assertSame($existingData['email'], $caughtUpdate->newEmail);
        self::assertSame($existingData['phone'], $caughtUpdate->newPhone);
        self::assertSame($existingData['lead'], $caughtUpdate->newLeadSource);
        self::assertSame($customerType, $caughtUpdate->newType);
        self::assertSame($customerStatus, $caughtUpdate->newStatus);
        self::assertSame($existingData['confirmed'], $caughtUpdate->newConfirmed);
    }

    public function testInvokeThrowsWhenCustomerNotFound(): void
    {
        [
            'resolver' => $resolver,
            'commandBus' => $commandBus,
            'validator' => $validator,
            'transformer' => $transformer,
            'factory' => $factory,
            'iriConverter' => $iriConverter,
            'repository' => $repository,
        ] = $this->createResolverWithDependencies();

        $input = ['id' => $this->faker->uuid()];
        $mutationInput = new UpdateCustomerMutationInput();

        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($input['id'])
            ->willReturn(null);

        $commandBus
            ->expects(self::never())
            ->method('dispatch');
        $factory
            ->expects(self::never())
            ->method('create');
        $iriConverter
            ->expects(self::never())
            ->method('getResourceFromIri');

        $this->expectException(CustomerNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    /**
     * @return array{
     *     resolver: UpdateCustomerMutationResolver,
     *     commandBus: CommandBusInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     validator: MutationInputValidator&\PHPUnit\Framework\MockObject\MockObject,
     *     transformer: UpdateCustomerMutationInputTransformer&\PHPUnit\Framework\MockObject\MockObject,
     *     factory: UpdateCustomerCommandFactoryInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     iriConverter: IriConverterInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     repository: CustomerRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject,
     * }
     */
    private function createResolverWithDependencies(): array
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateCustomerMutationInputTransformer::class);
        $factory = $this->createMock(UpdateCustomerCommandFactoryInterface::class);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $repository = $this->createMock(CustomerRepositoryInterface::class);

        $resolver = new UpdateCustomerMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $iriConverter,
            $repository,
        );

        return [
            'resolver' => $resolver,
            'commandBus' => $commandBus,
            'validator' => $validator,
            'transformer' => $transformer,
            'factory' => $factory,
            'iriConverter' => $iriConverter,
            'repository' => $repository,
        ];
    }

    /**
     * @return array{
     *     customer: Customer&\PHPUnit\Framework\MockObject\MockObject,
     *     type: CustomerType&\PHPUnit\Framework\MockObject\MockObject,
     *     status: CustomerStatus&\PHPUnit\Framework\MockObject\MockObject,
     *     data: array{
     *         typeUlid: string,
     *         statusUlid: string,
     *         initials: string,
     *         email: string,
     *         phone: string,
     *         lead: string,
     *         confirmed: bool,
     *     },
     * }
     */
    private function createCustomerWithExistingData(): array
    {
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);

        $data = [
            'typeUlid' => $this->faker->uuid(),
            'statusUlid' => $this->faker->uuid(),
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'lead' => $this->faker->word(),
            'confirmed' => true,
        ];

        $customerType
            ->method('getUlid')
            ->willReturn($data['typeUlid']);
        $customerStatus
            ->method('getUlid')
            ->willReturn($data['statusUlid']);

        $customer
            ->method('getType')
            ->willReturn($customerType);
        $customer
            ->method('getStatus')
            ->willReturn($customerStatus);
        $customer
            ->method('getInitials')
            ->willReturn($data['initials']);
        $customer
            ->method('getEmail')
            ->willReturn($data['email']);
        $customer
            ->method('getPhone')
            ->willReturn($data['phone']);
        $customer
            ->method('getLeadSource')
            ->willReturn($data['lead']);
        $customer
            ->method('isConfirmed')
            ->willReturn($data['confirmed']);

        return [
            'customer' => $customer,
            'type' => $customerType,
            'status' => $customerStatus,
            'data' => $data,
        ];
    }
}
