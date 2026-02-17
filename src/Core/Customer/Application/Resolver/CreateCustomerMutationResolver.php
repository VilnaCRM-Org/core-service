<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateCustomerMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidatorInterface $validator,
        private CustomerTf\CreateCustomerMutationInputTransformer $inputMapper,
        private CustomerFactory\CreateCustomerFactoryInterface $factory,
        private IriConverterInterface $iriConverter,
        private CustomerTf\CustomerTransformerInterface $builder,
    ) {
    }

    /**
     * @param array{
     *     args: array{
     *         input: array{
     *             initials: string,
     *             email: string,
     *             phone: string,
     *             leadSource: string,
     *             type: string,
     *             status: string,
     *             confirmed: bool
     *         }
     *     }
     * } $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): Customer
    {
        /**
         * @var array{
         *     initials: string,
         *     email: string,
         *     phone: string,
         *     leadSource: string,
         *     type: string,
         *     status: string,
         *     confirmed: bool
         * } $input
         */
        $input = $context['args']['input'];
        $mutationInput = $this->inputMapper->transform($input);
        $this->validator->validate($mutationInput);

        $customerStatusEntity = $this->iriConverter
            ->getResourceFromIri($input['status']);
        $customerTypeEntity = $this->iriConverter
            ->getResourceFromIri($input['type']);

        $customer = $this->builder->transform(
            $input['initials'],
            $input['email'],
            $input['phone'],
            $input['leadSource'],
            $customerTypeEntity,
            $customerStatusEntity,
            $input['confirmed']
        );

        $command = $this->factory->create($customer);
        $this->commandBus->dispatch($command);

        return $command->customer;
    }
}
