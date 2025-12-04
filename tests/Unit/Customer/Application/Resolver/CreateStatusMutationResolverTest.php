<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\MutationInput\CreateStatusMutationInput;
use App\Core\Customer\Application\Resolver\CreateStatusMutationResolver;
use App\Core\Customer\Application\Transformer\CreateStatusMutationInputTransformer;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusMutationResolverTest extends UnitTestCase
{
    public function testInvokeCreatesStatus(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $value = $this->faker->word();
        $input = ['value' => $value];

        $this->setupTransformerAndValidator($dependencies, $input);
        $status = $this->setupStatusTransformer($dependencies['statusTransformer'], $value);
        $this->setupFactoryAndCommandBus($dependencies, $status);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($status, $result);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function setupDependencies(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(CreateStatusMutationInputTransformer::class),
            'factory' => $this->createMock(CreateStatusFactoryInterface::class),
            'statusTransformer' => $this->createMock(StatusTransformerInterface::class),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function createResolver(array $deps): CreateStatusMutationResolver
    {
        return new CreateStatusMutationResolver(
            $deps['commandBus'],
            $deps['validator'],
            $deps['transformer'],
            $deps['factory'],
            $deps['statusTransformer'],
        );
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps
     * @param array<string, string> $input
     */
    private function setupTransformerAndValidator(array $deps, array $input): void
    {
        $mutationInput = new CreateStatusMutationInput();
        $deps['transformer']
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $deps['validator']
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);
    }

    private function setupStatusTransformer(
        \PHPUnit\Framework\MockObject\MockObject $statusTransformer,
        string $value
    ): \PHPUnit\Framework\MockObject\MockObject {
        $status = $this->createMock(CustomerStatus::class);
        $statusTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($status);

        return $status;
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps
     */
    private function setupFactoryAndCommandBus(
        array $deps,
        \PHPUnit\Framework\MockObject\MockObject $status
    ): void {
        $command = new CreateStatusCommand($status);

        $deps['factory']
            ->expects(self::once())
            ->method('create')
            ->with($status)
            ->willReturn($command);

        $deps['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);
    }
}
