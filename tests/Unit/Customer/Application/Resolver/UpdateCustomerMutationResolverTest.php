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
        $dependencies = $this->createResolverWithDependencies();
        $input = $this->generateFullInput();

        $this->setupTransformerAndValidatorMocks($dependencies, $input);
        $customer = $this->setupRepositoryMock($dependencies['repository'], $input['id']);
        $entities = $this->setupIriConverterMock($dependencies['iriConverter'], $input);

        $caughtUpdate = null;
        $this->setupFactoryAndCommandBusMocks(
            $dependencies,
            $customer,
            $caughtUpdate
        );

        $result = $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);

        $this->assertUpdateResult($result, $customer, $caughtUpdate, $input, $entities);
    }

    public function testInvokeUsesExistingDataWhenOptionalFieldsMissing(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $customerData = $this->createCustomerWithExistingData();
        $input = ['id' => $this->faker->uuid()];

        $this->setupMocksForExistingDataTest($dependencies, $input, $customerData);

        $caughtUpdate = null;
        $this->setupFactoryForExistingData(
            $dependencies['factory'],
            $customerData['customer'],
            $caughtUpdate
        );
        $dependencies['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerCommand::class));

        $result = $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);

        $this->assertExistingDataResult($result, $customerData, $caughtUpdate);
    }

    public function testInvokeThrowsWhenCustomerNotFound(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $input = ['id' => $this->faker->uuid()];

        $this->setupTransformerAndValidatorForNotFound(
            $dependencies['transformer'],
            $dependencies['validator'],
            $input
        );
        $this->setupRepositoryToReturnNull($dependencies['repository'], $input['id']);
        $this->expectNoInteractionsWithFactoryAndConverters($dependencies);

        $this->expectException(CustomerNotFoundException::class);
        $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);
    }

    /** @return array<string, string|bool> */
    private function generateFullInput(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => '/api/customer_types/' . $this->faker->uuid(),
            'status' => '/api/customer_statuses/' . $this->faker->uuid(),
            'confirmed' => true,
        ];
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |UpdateCustomerMutationResolver> $deps
     * @param array<string, string|bool> $input
     */
    private function setupTransformerAndValidatorMocks(array $deps, array $input): void
    {
        $mutationInput = new UpdateCustomerMutationInput();
        $deps['transformer']->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);
        $deps['validator']->expects(self::once())
            ->method('validate')
            ->with($mutationInput);
    }

    private function setupRepositoryMock(
        \PHPUnit\Framework\MockObject\MockObject $repository,
        string $customerId
    ): \PHPUnit\Framework\MockObject\MockObject {
        $customer = $this->createMock(Customer::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($customerId)
            ->willReturn($customer);
        return $customer;
    }

    /**
     * @param array<string, string|bool> $input
     *
     * @return array<string, \PHPUnit\Framework\MockObject\MockObject>
     */
    private function setupIriConverterMock(
        \PHPUnit\Framework\MockObject\MockObject $iriConverter,
        array $input
    ): array {
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([$input['type']], [$input['status']])
            ->willReturnOnConsecutiveCalls($customerType, $customerStatus);

        return ['type' => $customerType, 'status' => $customerStatus];
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |UpdateCustomerMutationResolver> $deps
     */
    private function setupFactoryAndCommandBusMocks(
        array $deps,
        \PHPUnit\Framework\MockObject\MockObject $customer,
        ?CustomerUpdate &$caughtUpdate
    ): void {
        $this->setupFactoryMock($deps['factory'], $customer, $caughtUpdate);
        $this->setupCommandBusMock($deps['commandBus'], $customer, $caughtUpdate);
    }

    private function setupFactoryMock(
        \PHPUnit\Framework\MockObject\MockObject $factory,
        \PHPUnit\Framework\MockObject\MockObject $customer,
        ?CustomerUpdate &$caughtUpdate
    ): void {
        $factory->expects(self::once())
            ->method('create')
            ->with(
                self::identicalTo($customer),
                $this->isInstanceOf(CustomerUpdate::class)
            )
            ->willReturnCallback(
                static function (Customer $c, CustomerUpdate $u) use (&$caughtUpdate) {
                    $caughtUpdate = $u;
                    return new UpdateCustomerCommand($c, $u);
                }
            );
    }

    private function setupCommandBusMock(
        \PHPUnit\Framework\MockObject\MockObject $commandBus,
        \PHPUnit\Framework\MockObject\MockObject $customer,
        ?CustomerUpdate &$caughtUpdate
    ): void {
        $commandBus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function ($cmd) use ($customer, &$caughtUpdate) {
                self::assertInstanceOf(UpdateCustomerCommand::class, $cmd);
                self::assertSame($customer, $cmd->customer);
                self::assertSame($caughtUpdate, $cmd->updateData);
                return true;
            }));
    }

    /**
     * @param array<string, string|bool> $input
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $entities
     */
    private function assertUpdateResult(
        \PHPUnit\Framework\MockObject\MockObject $result,
        \PHPUnit\Framework\MockObject\MockObject $customer,
        ?CustomerUpdate $caughtUpdate,
        array $input,
        array $entities
    ): void {
        self::assertSame($customer, $result);
        self::assertInstanceOf(CustomerUpdate::class, $caughtUpdate);
        self::assertSame($input['initials'], $caughtUpdate->newInitials);
        self::assertSame($input['email'], $caughtUpdate->newEmail);
        self::assertSame($input['phone'], $caughtUpdate->newPhone);
        self::assertSame($input['leadSource'], $caughtUpdate->newLeadSource);
        self::assertSame($entities['type'], $caughtUpdate->newType);
        self::assertSame($entities['status'], $caughtUpdate->newStatus);
        self::assertTrue($caughtUpdate->newConfirmed);
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |UpdateCustomerMutationResolver> $deps
     * @param array<string, string> $input
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |array<string, string|bool>> $customerData
     */
    private function setupMocksForExistingDataTest(
        array $deps,
        array $input,
        array $customerData
    ): void {
        $mutationInput = new UpdateCustomerMutationInput();
        $deps['transformer']->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);
        $deps['validator']->expects(self::once())
            ->method('validate')
            ->with($mutationInput);
        $deps['repository']->expects(self::once())
            ->method('find')
            ->with($input['id'])
            ->willReturn($customerData['customer']);

        $deps['iriConverter']
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive(
                ['/api/customer_types/' . $customerData['data']['typeUlid']],
                ['/api/customer_statuses/' . $customerData['data']['statusUlid']]
            )
            ->willReturnOnConsecutiveCalls($customerData['type'], $customerData['status']);
    }

    private function setupFactoryForExistingData(
        \PHPUnit\Framework\MockObject\MockObject $factory,
        \PHPUnit\Framework\MockObject\MockObject $customer,
        ?CustomerUpdate &$caughtUpdate
    ): void {
        $factory
            ->expects(self::once())
            ->method('create')
            ->with(
                self::identicalTo($customer),
                $this->isInstanceOf(CustomerUpdate::class)
            )
            ->willReturnCallback(
                static function (
                    Customer $customerArg,
                    CustomerUpdate $update
                ) use (&$caughtUpdate) {
                    $caughtUpdate = $update;
                    return new UpdateCustomerCommand($customerArg, $update);
                }
            );
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |array<string, string|bool>> $customerData
     */
    private function assertExistingDataResult(
        \PHPUnit\Framework\MockObject\MockObject $result,
        array $customerData,
        ?CustomerUpdate $caughtUpdate
    ): void {
        $existingData = $customerData['data'];
        self::assertSame($customerData['customer'], $result);
        self::assertInstanceOf(CustomerUpdate::class, $caughtUpdate);
        self::assertSame($existingData['initials'], $caughtUpdate->newInitials);
        self::assertSame($existingData['email'], $caughtUpdate->newEmail);
        self::assertSame($existingData['phone'], $caughtUpdate->newPhone);
        self::assertSame($existingData['lead'], $caughtUpdate->newLeadSource);
        self::assertSame($customerData['type'], $caughtUpdate->newType);
        self::assertSame($customerData['status'], $caughtUpdate->newStatus);
        self::assertSame($existingData['confirmed'], $caughtUpdate->newConfirmed);
    }

    /** @param array<string, string> $input */
    private function setupTransformerAndValidatorForNotFound(
        \PHPUnit\Framework\MockObject\MockObject $transformer,
        \PHPUnit\Framework\MockObject\MockObject $validator,
        array $input
    ): void {
        $mutationInput = new UpdateCustomerMutationInput();
        $transformer->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);
        $validator->expects(self::once())
            ->method('validate')
            ->with($mutationInput);
    }

    private function setupRepositoryToReturnNull(
        \PHPUnit\Framework\MockObject\MockObject $repository,
        string $customerId
    ): void {
        $repository->expects(self::once())->method('find')->with($customerId)->willReturn(null);
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject
     *     |UpdateCustomerMutationResolver> $deps
     */
    private function expectNoInteractionsWithFactoryAndConverters(array $deps): void
    {
        $deps['commandBus']->expects(self::never())->method('dispatch');
        $deps['factory']->expects(self::never())->method('create');
        $deps['iriConverter']->expects(self::never())->method('getResourceFromIri');
    }

    /**
     * @return array{
     *     resolver: UpdateCustomerMutationResolver,
     *     commandBus: CommandBusInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     validator: MutationInputValidator&\PHPUnit\Framework\MockObject\MockObject,
     *     transformer: UpdateCustomerMutationInputTransformer
     *         &\PHPUnit\Framework\MockObject\MockObject,
     *     factory: UpdateCustomerCommandFactoryInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     iriConverter: IriConverterInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     repository: CustomerRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject,
     * }
     */
    private function createResolverWithDependencies(): array
    {
        $mocks = $this->createAllMocks();
        $resolver = $this->createResolverFromMocks($mocks);

        return array_merge(['resolver' => $resolver], $mocks);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function createAllMocks(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(UpdateCustomerMutationInputTransformer::class),
            'factory' => $this->createMock(UpdateCustomerCommandFactoryInterface::class),
            'iriConverter' => $this->createMock(IriConverterInterface::class),
            'repository' => $this->createMock(CustomerRepositoryInterface::class),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $mocks */
    private function createResolverFromMocks(array $mocks): UpdateCustomerMutationResolver
    {
        return new UpdateCustomerMutationResolver(
            $mocks['commandBus'],
            $mocks['validator'],
            $mocks['transformer'],
            $mocks['factory'],
            $mocks['iriConverter'],
            $mocks['repository'],
        );
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

        $data = $this->generateCustomerData();

        $this->configureTypeMock($customerType, $data['typeUlid']);
        $this->configureStatusMock($customerStatus, $data['statusUlid']);
        $this->configureCustomerMock($customer, $customerType, $customerStatus, $data);

        return [
            'customer' => $customer,
            'type' => $customerType,
            'status' => $customerStatus,
            'data' => $data,
        ];
    }

    /**
     * @return array{
     *     typeUlid: string,
     *     statusUlid: string,
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     lead: string,
     *     confirmed: bool
     * }
     */
    private function generateCustomerData(): array
    {
        return [
            'typeUlid' => $this->faker->uuid(),
            'statusUlid' => $this->faker->uuid(),
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'lead' => $this->faker->word(),
            'confirmed' => true,
        ];
    }

    private function configureTypeMock(
        \PHPUnit\Framework\MockObject\MockObject $type,
        string $ulid
    ): void {
        $type->method('getUlid')->willReturn($ulid);
    }

    private function configureStatusMock(
        \PHPUnit\Framework\MockObject\MockObject $status,
        string $ulid
    ): void {
        $status->method('getUlid')->willReturn($ulid);
    }

    /**
     * @param array<string, string|bool> $data
     */
    private function configureCustomerMock(
        \PHPUnit\Framework\MockObject\MockObject $customer,
        \PHPUnit\Framework\MockObject\MockObject $type,
        \PHPUnit\Framework\MockObject\MockObject $status,
        array $data
    ): void {
        $customer->method('getType')->willReturn($type);
        $customer->method('getStatus')->willReturn($status);
        $customer->method('getInitials')->willReturn($data['initials']);
        $customer->method('getEmail')->willReturn($data['email']);
        $customer->method('getPhone')->willReturn($data['phone']);
        $customer->method('getLeadSource')->willReturn($data['lead']);
        $customer->method('isConfirmed')->willReturn($data['confirmed']);
    }
}
