<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\Factory\CustomerUpdateFactoryInterface;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\MutationInput\UpdateCustomerMutationInput;
use App\Core\Customer\Application\Resolver\UpdateCustomerMutationResolver;
use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Core\Customer\Domain\Entity\Customer;
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
        $customer = $this->createMock(Customer::class);
        $customerUpdate = $this->createMock(CustomerUpdate::class);

        $this->setupTransformerAndValidatorMocks($dependencies, $input);
        $this->setupUpdateFactoryAndCommandMocks($dependencies, $customer, $input, $customerUpdate);

        $result = $dependencies['resolver']->__invoke($customer, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
    }

    public function testInvokeUsesExistingDataWhenOptionalFieldsMissing(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $customer = $this->createMock(Customer::class);
        $input = ['id' => $this->faker->uuid()];
        $customerUpdate = $this->createMock(CustomerUpdate::class);

        $this->setupTransformerAndValidatorMocks($dependencies, $input);
        $this->setupUpdateFactoryAndCommandMocks($dependencies, $customer, $input, $customerUpdate);

        $result = $dependencies['resolver']->__invoke($customer, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
    }

    public function testInvokeThrowsWhenCustomerNotFound(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $input = ['id' => $this->faker->uuid()];

        $this->setupTransformerAndValidatorMocks($dependencies, $input);

        $dependencies['updateFactory']->expects(self::never())->method('create');
        $dependencies['commandFactory']->expects(self::never())->method('create');
        $dependencies['commandBus']->expects(self::never())->method('dispatch');

        $this->expectException(CustomerNotFoundException::class);
        $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeThrowsWhenCustomerNotFoundWithIri(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $ulid = $this->faker->uuid();
        $input = ['id' => '/api/customers/' . $ulid];

        $this->setupTransformerAndValidatorMocks($dependencies, $input);

        $dependencies['repository']
            ->expects(self::once())
            ->method('find')
            ->with($ulid)
            ->willReturn(null);

        $dependencies['updateFactory']->expects(self::never())->method('create');
        $dependencies['commandFactory']->expects(self::never())->method('create');
        $dependencies['commandBus']->expects(self::never())->method('dispatch');

        $this->expectException(CustomerNotFoundException::class);
        $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeFindsCustomerFromRepositoryWhenItemIsNull(): void
    {
        $dependencies = $this->createResolverWithDependencies();
        $ulid = $this->faker->uuid();
        $input = ['id' => '/api/customers/' . $ulid];
        $customer = $this->createMock(Customer::class);
        $customerUpdate = $this->createMock(CustomerUpdate::class);

        $this->setupTransformerAndValidatorMocks($dependencies, $input);
        $this->setupRepositoryMock($dependencies, $ulid, $customer);
        $this->setupUpdateFactoryAndCommandMocks($dependencies, $customer, $input, $customerUpdate);

        $result = $dependencies['resolver']->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject|UpdateCustomerMutationResolver> $deps
     * @param Customer&\PHPUnit\Framework\MockObject\MockObject $customer
     */
    private function setupRepositoryMock(array $deps, string $ulid, Customer $customer): void
    {
        $deps['repository']
            ->expects(self::once())
            ->method('find')
            ->with($ulid)
            ->willReturn($customer);
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

    /**
     * @return array{
     *     resolver: UpdateCustomerMutationResolver,
     *     commandBus: CommandBusInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     validator: MutationInputValidator&\PHPUnit\Framework\MockObject\MockObject,
     *     transformer: UpdateCustomerMutationInputTransformer
     *         &\PHPUnit\Framework\MockObject\MockObject,
     *     commandFactory: UpdateCustomerCommandFactoryInterface&\PHPUnit\Framework\MockObject\MockObject,
     *     updateFactory: CustomerUpdateFactoryInterface&\PHPUnit\Framework\MockObject\MockObject,
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
            'commandFactory' => $this->createMock(UpdateCustomerCommandFactoryInterface::class),
            'updateFactory' => $this->createMock(CustomerUpdateFactoryInterface::class),
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
            $mocks['commandFactory'],
            $mocks['updateFactory'],
            $mocks['repository'],
        );
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject|UpdateCustomerMutationResolver> $dependencies
     * @param Customer&\PHPUnit\Framework\MockObject\MockObject $customer
     * @param array<string, string|bool> $input
     * @param CustomerUpdate&\PHPUnit\Framework\MockObject\MockObject $customerUpdate
     */
    private function setupUpdateFactoryAndCommandMocks(
        array $dependencies,
        Customer $customer,
        array $input,
        CustomerUpdate $customerUpdate
    ): void {
        $dependencies['updateFactory']
            ->expects(self::once())
            ->method('create')
            ->with($customer, $input)
            ->willReturn($customerUpdate);

        $command = new UpdateCustomerCommand($customer, $customerUpdate);
        $dependencies['commandFactory']
            ->expects(self::once())
            ->method('create')
            ->with($customer, $customerUpdate)
            ->willReturn($command);

        $dependencies['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);
    }
}
