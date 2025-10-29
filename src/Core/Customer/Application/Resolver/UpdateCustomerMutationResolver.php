<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory\CustomerUpdateFactoryInterface;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Transformer\IriTransformerInterface;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class UpdateCustomerMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateCustomerMutationInputTransformer $inputTransformer,
        private UpdateCustomerCommandFactoryInterface $commandFactory,
        private CustomerUpdateFactoryInterface $updateFactory,
        private CustomerRepositoryInterface $repository,
        private IriTransformerInterface $iriTransformer,
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

        $customer = $this->findCustomer($item, $input['id']);
        $customerUpdate = $this->updateFactory->create($customer, $input);
        $command = $this->commandFactory->create($customer, $customerUpdate);

        $this->commandBus->dispatch($command);

        return $customer;
    }

    private function findCustomer(?object $item, string $id): Customer
    {
        if ($item instanceof Customer) {
            return $item;
        }

        $ulid = $this->iriTransformer->transform($id);
        $customer = $this->repository->find($ulid);

        if (!$customer instanceof Customer) {
            throw CustomerNotFoundException::withId($id);
        }

        return $customer;
    }
}
