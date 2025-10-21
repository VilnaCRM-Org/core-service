<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\Transformer\CreateStatusMutationInputTransformer;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateStatusMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CreateStatusMutationInputTransformer $transformer,
        private CreateStatusFactoryInterface $statusCommandFactory,
        private StatusTransformerInterface $statusTransformer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(?object $item, array $context): CustomerStatus
    {
        $input = $context['args']['input'];
        $mutationInput = $this->transformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerStatus = $this->statusTransformer->transform($input['value']);
        $command = $this->statusCommandFactory->create($customerStatus);
        $this->commandBus->dispatch($command);

        return $customerStatus;
    }
}
