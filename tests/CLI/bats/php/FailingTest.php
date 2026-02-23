<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class FailingTest extends TestCase
{
    public function testFailure(): void
    {
        $this->assertTrue(false);
    }
}
