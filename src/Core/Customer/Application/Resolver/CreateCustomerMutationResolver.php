<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Core\Customer\Application\Transformer\CreateCustomerMutationInputTransformer;
use App\Core\Customer\Application\Transformer\CustomerTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateCustomerMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CreateCustomerMutationInputTransformer $transformer,
        private CreateCustomerFactoryInterface $createCustomerFactory,
        private IriConverterInterface $iriConverter,
        private CustomerTransformerInterface $customerTransformer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(?object $item, array $context): Customer
    {
        $input = $context['args']['input'];
        $mutationInput = $this->transformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerStatusEntity = $this->iriConverter
            ->getResourceFromIri($input['status']);
        $customerTypeEntity = $this->iriConverter
            ->getResourceFromIri($input['type']);

        $customer = $this->customerTransformer->transform(
            $input['initials'],
            $input['email'],
            $input['phone'],
            $input['leadSource'],
            $customerTypeEntity,
            $customerStatusEntity,
            $input['confirmed']
        );

        $command = $this->createCustomerFactory->create($customer);
        $this->commandBus->dispatch($command);

        return $command->customer;
    }
}
