<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\UpdateStatusMutationInputTransformer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class UpdateStatusMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateStatusMutationInputTransformer $transformer,
        private UpdateStatusCommandFactoryInterface $commandFactory,
        private StatusRepositoryInterface $repository,
        private UlidFactory $ulidFactory,
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

        $customerStatus = $this->repository->find(
            $this->ulidFactory->create($input['id'])
        ) ?? throw new CustomerStatusNotFoundException();

        $command = $this->commandFactory->create(
            $customerStatus,
            new CustomerStatusUpdate($input['value'])
        );
        $this->commandBus->dispatch($command);

        return $customerStatus;
    }
}
