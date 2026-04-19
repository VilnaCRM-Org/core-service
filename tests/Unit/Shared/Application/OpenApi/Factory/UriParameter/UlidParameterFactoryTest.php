<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\UlidParameterFactory;
use App\Tests\Unit\UnitTestCase;

final class UlidParameterFactoryTest extends UnitTestCase
{
    public function testGetParameterUsesDefaultAllowedUlidsImplementation(): void
    {
        $parameter = new Parameter('ulid', 'query', 'Test identifier', true);
        $builder = $this->createMock(UriParameterBuilder::class);
        $factory = new class($builder) extends UlidParameterFactory {
            protected function getDescription(): string
            {
                return 'Test identifier';
            }

            protected function getExampleUlid(): string
            {
                return '01TESTULIDEXAMPLE';
            }
        };

        $builder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'Test identifier',
                true,
                '01TESTULIDEXAMPLE',
                'string',
                ['01TESTULIDEXAMPLE']
            )
            ->willReturn($parameter);

        self::assertSame($parameter, $factory->getParameter());
    }

    public function testGetDeleteParameterUsesDefaultDeleteUlidImplementation(): void
    {
        $parameter = new Parameter('ulid', 'query', 'Test identifier', true);
        $builder = $this->createMock(UriParameterBuilder::class);
        $factory = new class($builder) extends UlidParameterFactory {
            protected function getDescription(): string
            {
                return 'Test identifier';
            }

            protected function getExampleUlid(): string
            {
                return '01TESTULIDEXAMPLE';
            }
        };

        $builder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'Test identifier',
                true,
                '01TESTULIDEXAMPLE',
                'string',
                ['01TESTULIDEXAMPLE']
            )
            ->willReturn($parameter);

        self::assertSame($parameter, $factory->getDeleteParameter());
    }
}
