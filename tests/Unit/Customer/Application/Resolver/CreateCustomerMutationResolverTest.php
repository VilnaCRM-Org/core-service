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
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(CreateCustomerMutationInputTransformer::class);
        $factory = $this->createMock(CreateCustomerFactoryInterface::class);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $customerTransformer = $this->createMock(CustomerTransformerInterface::class);

        $resolver = new CreateCustomerMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $iriConverter,
            $customerTransformer,
        );

        $input = [
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => '/api/customer_types/' . $this->faker->uuid(),
            'status' => '/api/customer_statuses/' . $this->faker->uuid(),
            'confirmed' => $this->faker->boolean(),
        ];

        $mutationInput = new CreateCustomerMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $customerStatus = $this->createMock(CustomerStatus::class);
        $customerType = $this->createMock(CustomerType::class);

        $iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->withConsecutive([$input['status']], [$input['type']])
            ->willReturnOnConsecutiveCalls($customerStatus, $customerType);

        $customer = $this->createMock(Customer::class);

        $customerTransformer
            ->expects(self::once())
            ->method('transform')
            ->with(
                $input['initials'],
                $input['email'],
                $input['phone'],
                $input['leadSource'],
                $customerType,
                $customerStatus,
                $input['confirmed']
            )
            ->willReturn($customer);

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

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($customer, $result);
    }
}
