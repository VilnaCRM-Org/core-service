<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

use function assert;

final readonly class UpdateCustomerMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateCustomerMutationInputTransformer $inputTransformer,
        private UpdateCustomerCommandFactoryInterface $factory,
        private IriConverterInterface $iriConverter,
        private CustomerRepositoryInterface $customers,
    ) {
    }

    /**
     * @param array{
     *     args: array{
     *         input: array{
     *             id: string,
     *             initials?: string|null,
     *             email?: string|null,
     *             phone?: string|null,
     *             leadSource?: string|null,
     *             type?: string|null,
     *             status?: string|null,
     *             confirmed?: bool|null
     *         }
     *     }
     * } $context
     */
    public function __invoke(?object $item, array $context): Customer
    {
        /**
         * @var array{
         *     id: string,
         *     initials?: string|null,
         *     email?: string|null,
         *     phone?: string|null,
         *     leadSource?: string|null,
         *     type?: string|null,
         *     status?: string|null,
         *     confirmed?: bool|null
         * } $input
         */
        $input = $context['args']['input'];
        $mutationInput = $this->inputTransformer->transform($input);
        $this->validator->validate($mutationInput);

        $customer = $this->customers->find($input['id']);
        if ($customer === null) {
            throw new CustomerNotFoundException();
        }

        $customerUpdate = $this->createCustomerUpdate($customer, $input);

        $command = $this->factory->create($customer, $customerUpdate);
        $this->commandBus->dispatch($command);

        return $customer;
    }

    /**
     * @param array{
     *     id: string,
     *     initials?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     leadSource?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     confirmed?: bool|null
     * } $input
     */
    private function createCustomerUpdate(
        Customer $customer,
        array $input
    ): CustomerUpdate {
        $customerType = $this->resolveCustomerType($customer, $input);
        $customerStatus = $this->resolveCustomerStatus($customer, $input);

        return new CustomerUpdate(
            $input['initials'] ?? $customer->getInitials(),
            $input['email'] ?? $customer->getEmail(),
            $input['phone'] ?? $customer->getPhone(),
            $input['leadSource'] ?? $customer->getLeadSource(),
            $customerType,
            $customerStatus,
            $input['confirmed'] ?? $customer->isConfirmed()
        );
    }

    /**
     * @param array{type?: string|null} $input
     */
    private function resolveCustomerType(
        Customer $customer,
        array $input
    ): CustomerType {
        $typeIri = $input['type'] ?? $this->getDefaultTypeIri($customer);

        $resource = $this->iriConverter->getResourceFromIri($typeIri);
        assert($resource instanceof CustomerType);

        return $resource;
    }

    private function getDefaultTypeIri(Customer $customer): string
    {
        return sprintf(
            '/api/customer_types/%s',
            $customer->getType()->getUlid()
        );
    }

    /**
     * @param array{status?: string|null} $input
     */
    private function resolveCustomerStatus(
        Customer $customer,
        array $input
    ): CustomerStatus {
        $statusIri = $input['status']
            ?? $this->getDefaultStatusIri($customer);

        $resource = $this->iriConverter->getResourceFromIri($statusIri);
        assert($resource instanceof CustomerStatus);

        return $resource;
    }

    private function getDefaultStatusIri(Customer $customer): string
    {
        return sprintf(
            '/api/customer_statuses/%s',
            $customer->getStatus()->getUlid()
        );
    }
}
