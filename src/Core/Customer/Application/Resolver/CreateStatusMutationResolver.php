<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateStatusMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidatorInterface $validator,
        private CustomerTf\CreateStatusMutationInputTransformer $inputs,
        private CustomerFactory\CreateStatusFactoryInterface $factory,
        private CustomerTf\StatusTransformerInterface $statusBuilder,
    ) {
    }

    /**
     * @param array{
     *     args: array{
     *         input: array{
     *             value: string
     *         }
     *     }
     * } $context
     */
    public function __invoke(?object $item, array $context): CustomerStatus
    {
        /** @var array{value: string} $input */
        $input = $context['args']['input'];
        $mutationInput = $this->inputs->transform($input);
        $this->validator->validate($mutationInput);

        $customerStatus = $this->statusBuilder->transform($input['value']);
        $command = $this->factory->create($customerStatus);
        $this->commandBus->dispatch($command);

        return $customerStatus;
    }
}
