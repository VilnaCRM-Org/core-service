<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use App\Shared\Application\OpenApi\Cleaner\EmptyArrayCleaner;
use App\Shared\Application\OpenApi\Cleaner\ValueCleaner;
use App\Tests\Unit\UnitTestCase;

final class ValueCleanerTest extends UnitTestCase
{
    private ValueCleaner $filter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $emptyValueChecker = new EmptyArrayCleaner();
        $this->filter = new ValueCleaner($emptyValueChecker);
    }

    public function testShouldRemoveNullValues(): void
    {
        $this->assertTrue($this->filter->shouldRemove('key', null));
    }

    public function testShouldNotRemoveNonNullScalarValues(): void
    {
        $this->assertFalse($this->filter->shouldRemove('key', 'string'));
        $this->assertFalse($this->filter->shouldRemove('key', 42));
        $this->assertFalse($this->filter->shouldRemove('key', 3.14));
        $this->assertFalse($this->filter->shouldRemove('key', true));
        $this->assertFalse($this->filter->shouldRemove('key', false));
    }

    public function testShouldRemoveEmptyArrayWithRemovableKey(): void
    {
        $this->assertTrue($this->filter->shouldRemove('extensionProperties', []));
        $this->assertTrue($this->filter->shouldRemove('responses', []));
        $this->assertTrue($this->filter->shouldRemove('parameters', []));
    }

    public function testShouldNotRemoveEmptyArrayWithNonRemovableKey(): void
    {
        $this->assertFalse($this->filter->shouldRemove('customProperty', []));
        $this->assertFalse($this->filter->shouldRemove('schemas', []));
    }

    public function testShouldNotRemoveEmptyArrayWithNumericKey(): void
    {
        $this->assertFalse($this->filter->shouldRemove(0, []));
        $this->assertFalse($this->filter->shouldRemove(1, []));
    }

    public function testShouldNotRemoveNonEmptyArray(): void
    {
        $this->assertFalse($this->filter->shouldRemove('extensionProperties', ['value']));
        $this->assertFalse($this->filter->shouldRemove('responses', ['200' => 'OK']));
    }
}
