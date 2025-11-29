<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use App\Shared\Application\OpenApi\Cleaner\EmptyArrayFilter;
use App\Tests\Unit\UnitTestCase;

final class EmptyArrayFilterTest extends UnitTestCase
{
    private EmptyArrayFilter $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new EmptyArrayFilter();
    }

    public function testShouldRemoveExtensionProperties(): void
    {
        $this->assertTrue($this->checker->shouldRemoveEmptyArray('extensionProperties'));
    }

    public function testShouldRemoveEmptyComponentSections(): void
    {
        $removableKeys = [
            'responses',
            'parameters',
            'examples',
            'requestBodies',
            'headers',
            'securitySchemes',
            'links',
            'callbacks',
            'pathItems',
        ];

        foreach ($removableKeys as $key) {
            $this->assertTrue(
                $this->checker->shouldRemoveEmptyArray($key),
                "Expected '{$key}' to be removable"
            );
        }
    }

    public function testShouldNotRemoveNonRemovableStringKeys(): void
    {
        $nonRemovableKeys = [
            'schemas',
            'customProperty',
            'info',
            'paths',
            'tags',
        ];

        foreach ($nonRemovableKeys as $key) {
            $this->assertFalse(
                $this->checker->shouldRemoveEmptyArray($key),
                "Expected '{$key}' to not be removable"
            );
        }
    }

    public function testShouldNotRemoveNumericKeys(): void
    {
        $this->assertFalse($this->checker->shouldRemoveEmptyArray(0));
        $this->assertFalse($this->checker->shouldRemoveEmptyArray(1));
        $this->assertFalse($this->checker->shouldRemoveEmptyArray(42));
    }

    public function testAllRemovableKeysAreHandled(): void
    {
        $allRemovableKeys = [
            'extensionProperties',
            'responses',
            'parameters',
            'examples',
            'requestBodies',
            'headers',
            'securitySchemes',
            'links',
            'callbacks',
            'pathItems',
        ];

        foreach ($allRemovableKeys as $key) {
            $this->assertTrue(
                $this->checker->shouldRemoveEmptyArray($key),
                "All removable keys should return true for '{$key}'"
            );
        }
    }
}
