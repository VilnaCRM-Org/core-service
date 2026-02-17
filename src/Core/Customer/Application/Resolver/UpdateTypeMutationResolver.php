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
use App\Shared\Application\Transformer\IriTransformerInterface;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class UpdateTypeMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidatorInterface $validator,
        private CustomerTf\UpdateTypeMutationInputTransformer $inputTransformer,
        private CustomerFactory\UpdateTypeCommandFactoryInterface $factory,
        private TypeRepositoryInterface $repository,
        private IriTransformerInterface $iriTransformer,
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
    #[Override]
    public function __invoke(?object $item, array $context): CustomerType
    {
        /** @var array{id: string, value: string} $input */
        $input = $context['args']['input'];
        $mutationInput = $this->inputTransformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerType = $item instanceof CustomerType
            ? $item
            : $this->repository->find($this->iriTransformer->transform($input['id']));

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
}
