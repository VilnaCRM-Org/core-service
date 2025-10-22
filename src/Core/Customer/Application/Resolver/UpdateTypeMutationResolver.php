<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject as CustomerValueObject;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class UpdateTypeMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CustomerTf\UpdateTypeMutationInputTransformer $inputTransformer,
        private CustomerFactory\UpdateTypeCommandFactoryInterface $factory,
        private TypeRepositoryInterface $repository,
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
    public function __invoke(?object $item, array $context): CustomerType
    {
        /** @var array{id: string, value: string} $input */
        $input = $context['args']['input'];
        $mutationInput = $this->inputTransformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerType = $item instanceof CustomerType
            ? $item
            : $this->repository->find($this->extractUlid($input['id']));

        if (!$customerType instanceof CustomerType) {
            throw CustomerTypeNotFoundException::withIri($input['id']);
        }

        $command = $this->factory->create(
            $customerType,
            new CustomerValueObject\CustomerTypeUpdate($input['value'])
        );
        $this->commandBus->dispatch($command);

        return $customerType;
    }

    /**
     * Extract ULID from IRI or return the value as-is if already a ULID.
     */
    private function extractUlid(string $idOrIri): string
    {
        return str_starts_with($idOrIri, '/') ? basename($idOrIri) : $idOrIri;
    }
}
