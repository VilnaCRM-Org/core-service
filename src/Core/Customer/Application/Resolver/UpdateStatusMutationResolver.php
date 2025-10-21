<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository as CustomerRepository;
use App\Core\Customer\Domain\ValueObject as CustomerValueObject;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class UpdateStatusMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CustomerTf\UpdateStatusMutationInputTransformer $inputs,
        private CustomerFactory\UpdateStatusCommandFactoryInterface $factory,
        private CustomerRepository\StatusRepositoryInterface $statusRepository,
        private UlidFactory $ulids,
    ) {
    }

    /**
     * @param array{
     *     args: array{
     *         input: array{
     *             id: string,
     *             value: string
     *         }
     *     }
     * } $context
     */
    public function __invoke(?object $item, array $context): CustomerStatus
    {
        /** @var array{id: string, value: string} $input */
        $input = $context['args']['input'];
        $mutationInput = $this->inputs->transform($input);
        $this->validator->validate($mutationInput);

        $ulid = $this->ulids->create($input['id']);
        $customerStatus = $this->statusRepository->find($ulid)
            ?? throw new CustomerStatusNotFoundException();

        $command = $this->factory->create(
            $customerStatus,
            new CustomerValueObject\CustomerStatusUpdate($input['value'])
        );
        $this->commandBus->dispatch($command);

        return $customerStatus;
    }
}
