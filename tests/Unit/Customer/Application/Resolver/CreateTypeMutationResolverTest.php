<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Core\Customer\Application\Resolver\CreateTypeMutationResolver;
use App\Core\Customer\Application\Transformer\CreateTypeMutationInputTransformer;
use App\Core\Customer\Application\Transformer\TypeTransformerInterface;
use App\Core\Customer\Application\MutationInput\CreateTypeMutationInput;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeMutationResolverTest extends UnitTestCase
{
    public function testInvokeCreatesType(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(CreateTypeMutationInputTransformer::class);
        $factory = $this->createMock(CreateTypeFactoryInterface::class);
        $typeTransformer = $this->createMock(TypeTransformerInterface::class);

        $resolver = new CreateTypeMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $typeTransformer,
        );

        $value = $this->faker->word();
        $input = ['value' => $value];

        $mutationInput = new CreateTypeMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $type = $this->createMock(CustomerType::class);
        $typeTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($type);

        $command = new CreateTypeCommand($type);

        $factory
            ->expects(self::once())
            ->method('create')
            ->with($type)
            ->willReturn($command);

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($type, $result);
    }
}
