<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Emf;

use App\Shared\Infrastructure\Observability\Emf\EmfPayloadFactory;
use App\Shared\Infrastructure\Observability\Emf\EmfPayloadFactoryInterface;
use App\Tests\Unit\UnitTestCase;

final class EmfPayloadFactoryInterfaceTest extends UnitTestCase
{
    public function testFactoryImplementsInterface(): void
    {
        $interfaces = class_implements(EmfPayloadFactory::class);

        self::assertContains(
            EmfPayloadFactoryInterface::class,
            $interfaces,
            'EmfPayloadFactory must implement EmfPayloadFactoryInterface'
        );
    }
}
