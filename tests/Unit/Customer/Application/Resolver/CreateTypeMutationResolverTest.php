<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Core\Customer\Application\MutationInput\CreateTypeMutationInput;
use App\Core\Customer\Application\Resolver\CreateTypeMutationResolver;
use App\Core\Customer\Application\Transformer\CreateTypeMutationInputTransformer;
use App\Core\Customer\Application\Transformer\TypeTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeMutationResolverTest extends UnitTestCase
{
    public function testInvokeCreatesType(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $value = $this->faker->word();
        $input = ['value' => $value];

        $this->setupTransformerAndValidator($dependencies, $input);
        $type = $this->setupTypeTransformer($dependencies['typeTransformer'], $value);
        $this->setupFactoryAndCommandBus($dependencies, $type);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($type, $result);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function setupDependencies(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(CreateTypeMutationInputTransformer::class),
            'factory' => $this->createMock(CreateTypeFactoryInterface::class),
            'typeTransformer' => $this->createMock(TypeTransformerInterface::class),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function createResolver(array $deps): CreateTypeMutationResolver
    {
        return new CreateTypeMutationResolver(
            $deps['commandBus'],
            $deps['validator'],
            $deps['transformer'],
            $deps['factory'],
            $deps['typeTransformer'],
        );
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps
     * @param array<string, string> $input
     */
    private function setupTransformerAndValidator(array $deps, array $input): void
    {
        $mutationInput = new CreateTypeMutationInput();
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

    private function setupTypeTransformer(
        \PHPUnit\Framework\MockObject\MockObject $typeTransformer,
        string $value
    ): \PHPUnit\Framework\MockObject\MockObject {
        $type = $this->createMock(CustomerType::class);
        $typeTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($type);

        return $type;
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps
     */
    private function setupFactoryAndCommandBus(
        array $deps,
        \PHPUnit\Framework\MockObject\MockObject $type
    ): void {
        $command = new CreateTypeCommand($type);

        $deps['factory']
            ->expects(self::once())
            ->method('create')
            ->with($type)
            ->willReturn($command);

        $deps['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);
    }
}
