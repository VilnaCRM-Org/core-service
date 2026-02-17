<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface as MutationResolver;
use App\Core\Customer\Application\Factory as CustomerFactory;
use App\Core\Customer\Application\Transformer as CustomerTf;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateTypeMutationResolver implements MutationResolver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidatorInterface $validator,
        private CustomerTf\CreateTypeMutationInputTransformer $inputTransformer,
        private CustomerFactory\CreateTypeFactoryInterface $factory,
        private CustomerTf\TypeTransformerInterface $typeBuilder,
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
    #[Override]
    public function __invoke(?object $item, array $context): CustomerType
    {
        /** @var array{value: string} $input */
        $input = $context['args']['input'];
        $mutationInput = $this->inputTransformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerType = $this->typeBuilder->transform($input['value']);
        $command = $this->factory->create($customerType);
        $this->commandBus->dispatch($command);

        return $customerType;
    }
}
