<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Application\MutationInput\UpdateStatusMutationInput;
use App\Core\Customer\Application\Resolver\UpdateStatusMutationResolver;
use App\Core\Customer\Application\Transformer\UpdateStatusMutationInputTransformer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class UpdateStatusMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesStatus(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateStatusMutationInputTransformer::class);
        $factory = $this->createMock(UpdateStatusCommandFactoryInterface::class);
        $repository = $this->createMock(StatusRepositoryInterface::class);
        $ulidFactory = new UlidFactory();

        $resolver = new UpdateStatusMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $repository,
            $ulidFactory,
        );

        $input = [
            'id' => $this->faker->uuid(),
            'value' => $this->faker->word(),
        ];

        $mutationInput = new UpdateStatusMutationInput();
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

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);

                return true;
            }))
            ->willReturn($status);

        $capturedUpdate = null;

        $factory
            ->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($status), $this->isInstanceOf(CustomerStatusUpdate::class))
            ->willReturnCallback(
                function (CustomerStatus $statusArg, CustomerStatusUpdate $update) use (&$capturedUpdate) {
                    $capturedUpdate = $update;

                    return new UpdateCustomerStatusCommand($statusArg, $update);
                }
            );

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerStatusCommand::class));

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($status, $result);
        self::assertInstanceOf(CustomerStatusUpdate::class, $capturedUpdate);
        self::assertSame($input['value'], $capturedUpdate->value);
    }

    public function testInvokeThrowsWhenStatusNotFound(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateStatusMutationInputTransformer::class);
        $factory = $this->createMock(UpdateStatusCommandFactoryInterface::class);
        $repository = $this->createMock(StatusRepositoryInterface::class);
        $ulidFactory = new UlidFactory();

        $resolver = new UpdateStatusMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $repository,
            $ulidFactory,
        );

        $input = [
            'id' => $this->faker->uuid(),
            'value' => $this->faker->word(),
        ];

        $mutationInput = new UpdateStatusMutationInput();

        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);

                return true;
            }))
            ->willReturn(null);

        $commandBus
            ->expects(self::never())
            ->method('dispatch');
        $factory
            ->expects(self::never())
            ->method('create');

        $this->expectException(CustomerStatusNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
