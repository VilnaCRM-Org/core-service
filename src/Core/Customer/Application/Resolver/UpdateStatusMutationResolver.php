<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject as CustomerValueObject;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class UpdateStatusMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CustomerTf\UpdateStatusMutationInputTransformer $inputTransformer,
        private CustomerFactory\UpdateStatusCommandFactoryInterface $factory,
        private StatusRepositoryInterface $repository,
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
        $mutationInput = $this->inputTransformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerStatus = $item instanceof CustomerStatus
            ? $item
            : $this->repository->find($this->extractUlid($input['id']));

        if (!$customerStatus instanceof CustomerStatus) {
            throw CustomerStatusNotFoundException::withIri($input['id']);
        }

        $command = $this->factory->create(
            $customerStatus,
            new CustomerValueObject\CustomerStatusUpdate($input['value'])
        );
        $this->commandBus->dispatch($command);

        return $customerStatus;
    }

    /**
     * Extract ULID from IRI or return the value as-is if already a ULID.
     */
    private function extractUlid(string $idOrIri): string
    {
        return str_starts_with($idOrIri, '/') ? basename($idOrIri) : $idOrIri;
    }
}
