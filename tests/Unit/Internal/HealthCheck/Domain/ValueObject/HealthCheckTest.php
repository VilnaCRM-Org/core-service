<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Domain\ValueObject;

use App\Internal\HealthCheck\Domain\ValueObject\HealthCheck;
use App\Tests\Unit\UnitTestCase;

final class HealthCheckTest extends UnitTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(HealthCheck::class, new HealthCheck());
    }
}
