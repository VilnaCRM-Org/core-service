<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Infrastructure\EventListener\SchemathesisSingleCustomerEmailExtractor;
use App\Tests\Unit\UnitTestCase;

final class SchemathesisSingleCustomerEmailExtractorTest extends UnitTestCase
{
    private SchemathesisSingleCustomerEmailExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new SchemathesisSingleCustomerEmailExtractor();
    }

    public function testExtractWithValidEmail(): void
    {
        $payload = ['email' => 'test@example.com'];

        $result = $this->extractor->extract($payload);

        $this->assertEquals(['test@example.com'], $result);
    }

    public function testExtractWithNullEmail(): void
    {
        $payload = ['email' => null];

        $result = $this->extractor->extract($payload);

        $this->assertEquals([], $result);
    }

    public function testExtractWithoutEmail(): void
    {
        $payload = [];

        $result = $this->extractor->extract($payload);

        $this->assertEquals([], $result);
    }

    public function testExtractWithNonStringEmail(): void
    {
        $payload = ['email' => 123];

        $result = $this->extractor->extract($payload);

        $this->assertEquals([], $result);
    }
}
