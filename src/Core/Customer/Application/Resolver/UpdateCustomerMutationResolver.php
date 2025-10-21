<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class UpdateCustomerMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateCustomerMutationInputTransformer $transformer,
        private UpdateCustomerCommandFactoryInterface $updateCommandFactory,
        private IriConverterInterface $iriConverter,
        private CustomerRepositoryInterface $customerRepository,
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

        $customer = $this->customerRepository->find($input['id']);
        if (!$customer) {
            throw new CustomerNotFoundException();
        }

        $customerType = $this->iriConverter
            ->getResourceFromIri($input['type'] ?? '/api/customer_types/' . $customer->getType()->getUlid());
        $customerStatus = $this->iriConverter
            ->getResourceFromIri($input['status'] ?? '/api/customer_statuses/' . $customer->getStatus()->getUlid());

        $customerUpdate = new CustomerUpdate(
            $input['initials'] ?? $customer->getInitials(),
            $input['email'] ?? $customer->getEmail(),
            $input['phone'] ?? $customer->getPhone(),
            $input['leadSource'] ?? $customer->getLeadSource(),
            $customerType,
            $customerStatus,
            $input['confirmed'] ?? $customer->isConfirmed()
        );

        $command = $this->updateCommandFactory->create($customer, $customerUpdate);
        $this->commandBus->dispatch($command);

        return $customer;
    }
}
