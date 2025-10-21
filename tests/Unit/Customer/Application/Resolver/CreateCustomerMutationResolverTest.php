<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Core\Customer\Application\MutationInput\CreateCustomerMutationInput;
use App\Core\Customer\Application\Resolver\CreateCustomerMutationResolver;
use App\Core\Customer\Application\Transformer\CreateCustomerMutationInputTransformer;
use App\Core\Customer\Application\Transformer\CustomerTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateCustomerMutationResolverTest extends UnitTestCase
{
    public function testInvokeCreatesCustomer(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $input = $this->generateInput();

        $this->setupTransformerExpectations($dependencies['transformer'], $input);
        $this->setupValidatorExpectations($dependencies['validator']);

        $entities = $this->setupEntityMocks();
        $this->setupIriConverterExpectations($dependencies['iriConverter'], $input, $entities);
        $this->setupCustomerTransformerExpectations(
            $dependencies['customerTransformer'],
            $input,
            $entities,
            $entities['customer']
        );

        $this->setupCommandFactoryAndBus(
            $dependencies['factory'],
            $dependencies['commandBus'],
            $entities['customer']
        );

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($entities['customer'], $result);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function setupDependencies(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(CreateCustomerMutationInputTransformer::class),
            'factory' => $this->createMock(CreateCustomerFactoryInterface::class),
            'iriConverter' => $this->createMock(IriConverterInterface::class),
            'customerTransformer' => $this->createMock(CustomerTransformerInterface::class),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function createResolver(array $deps): CreateCustomerMutationResolver
    {
        return new CreateCustomerMutationResolver(
            $deps['commandBus'],
            $deps['validator'],
            $deps['transformer'],
            $deps['factory'],
            $deps['iriConverter'],
            $deps['customerTransformer'],
        );
    }

    /** @return array<string, string|bool> */
    private function generateInput(): array
    {
        return [
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => '/api/customer_types/' . $this->faker->uuid(),
            'status' => '/api/customer_statuses/' . $this->faker->uuid(),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /** @param array<string, string|bool> $input */
    private function setupTransformerExpectations(
        \PHPUnit\Framework\MockObject\MockObject $transformer,
        array $input
    ): void {
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn(new CreateCustomerMutationInput());
    }

    private function setupValidatorExpectations(
        \PHPUnit\Framework\MockObject\MockObject $validator
    ): void {
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($this->isInstanceOf(CreateCustomerMutationInput::class));
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function setupEntityMocks(): array
    {
        return [
            'customerStatus' => $this->createMock(CustomerStatus::class),
            'customerType' => $this->createMock(CustomerType::class),
            'customer' => $this->createMock(Customer::class),
        ];
    }

    /**
     * @param array<string, string|bool> $input
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $entities
     */
    private function setupIriConverterExpectations(
        \PHPUnit\Framework\MockObject\MockObject $iriConverter,
        array $input,
        array $entities
    ): void {
        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([$input['status']], [$input['type']])
            ->willReturnOnConsecutiveCalls($entities['customerStatus'], $entities['customerType']);
    }

    /**
     * @param array<string, string|bool> $input
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $entities
     */
    private function setupCustomerTransformerExpectations(
        \PHPUnit\Framework\MockObject\MockObject $customerTransformer,
        array $input,
        array $entities,
        \PHPUnit\Framework\MockObject\MockObject $customer
    ): void {
        $customerTransformer
            ->expects(self::once())
            ->method('transform')
            ->with(
                $input['initials'],
                $input['email'],
                $input['phone'],
                $input['leadSource'],
                $entities['customerType'],
                $entities['customerStatus'],
                $input['confirmed']
            )
            ->willReturn($customer);
    }

    private function setupCommandFactoryAndBus(
        \PHPUnit\Framework\MockObject\MockObject $factory,
        \PHPUnit\Framework\MockObject\MockObject $commandBus,
        \PHPUnit\Framework\MockObject\MockObject $customer
    ): void {
        $command = new CreateCustomerCommand($customer);

        $factory
            ->expects(self::once())
            ->method('create')
            ->with($customer)
            ->willReturn($command);

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);
    }
}
