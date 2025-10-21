<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\UpdateTypeMutationInputTransformer;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class UpdateTypeMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private UpdateTypeMutationInputTransformer $transformer,
        private UpdateTypeCommandFactoryInterface $commandFactory,
        private TypeRepositoryInterface $repository,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(?object $item, array $context): CustomerType
    {
        $input = $context['args']['input'];
        $mutationInput = $this->transformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerType = $this->repository->find(
            $this->ulidTransformer->create($input['id'])
        ) ?? throw new CustomerTypeNotFoundException();

        $command = $this->commandFactory->create(
            $customerType,
            new CustomerTypeUpdate($input['value'])
        );
        $this->commandBus->dispatch($command);

        return $customerType;
    }
}
