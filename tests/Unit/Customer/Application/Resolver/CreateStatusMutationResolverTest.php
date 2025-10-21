<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\Resolver\CreateStatusMutationResolver;
use App\Core\Customer\Application\Transformer\CreateStatusMutationInputTransformer;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Application\MutationInput\CreateStatusMutationInput;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusMutationResolverTest extends UnitTestCase
{
    public function testInvokeCreatesStatus(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(CreateStatusMutationInputTransformer::class);
        $factory = $this->createMock(CreateStatusFactoryInterface::class);
        $statusTransformer = $this->createMock(StatusTransformerInterface::class);

        $resolver = new CreateStatusMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $statusTransformer,
        );

        $value = $this->faker->word();
        $input = ['value' => $value];

        $mutationInput = new CreateStatusMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $status = $this->createMock(CustomerStatus::class);
        $statusTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($status);

        $command = new CreateStatusCommand($status);

        $factory
            ->expects(self::once())
            ->method('create')
            ->with($status)
            ->willReturn($command);

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($status, $result);
    }
}
