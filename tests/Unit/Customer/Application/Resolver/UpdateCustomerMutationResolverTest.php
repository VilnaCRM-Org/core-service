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
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesCustomerWithProvidedData(): void
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

        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $existingTypeUlid = $this->faker->uuid();
        $existingStatusUlid = $this->faker->uuid();
        $existingInitials = $this->faker->lexify('??');
        $existingEmail = $this->faker->email();
        $existingPhone = $this->faker->phoneNumber();
        $existingLead = $this->faker->word();

        $customer = $this->createMock(Customer::class);

        $customer
            ->method('getType')
            ->willReturn($customerType);
        $customerType
            ->method('getUlid')
            ->willReturn($existingTypeUlid);

        $customer
            ->method('getStatus')
            ->willReturn($customerStatus);
        $customerStatus
            ->method('getUlid')
            ->willReturn($existingStatusUlid);

        $customer
            ->method('getInitials')
            ->willReturn($existingInitials);
        $customer
            ->method('getEmail')
            ->willReturn($existingEmail);
        $customer
            ->method('getPhone')
            ->willReturn($existingPhone);
        $customer
            ->method('getLeadSource')
            ->willReturn($existingLead);
        $customer
            ->method('isConfirmed')
            ->willReturn(true);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($input['id'])
            ->willReturn($customer);

        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([
                '/api/customer_types/' . $existingTypeUlid,
            ], [
                '/api/customer_statuses/' . $existingStatusUlid,
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
        self::assertSame($existingInitials, $caughtUpdate->newInitials);
        self::assertSame($existingEmail, $caughtUpdate->newEmail);
        self::assertSame($existingPhone, $caughtUpdate->newPhone);
        self::assertSame($existingLead, $caughtUpdate->newLeadSource);
        self::assertSame($customerType, $caughtUpdate->newType);
        self::assertSame($customerStatus, $caughtUpdate->newStatus);
        self::assertTrue($caughtUpdate->newConfirmed);
    }

    public function testInvokeThrowsWhenCustomerNotFound(): void
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
}
