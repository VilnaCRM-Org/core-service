<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\PayloadItemsRequirementChecker;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class PayloadItemsRequirementCheckerTest extends UnitTestCase
{
    public function testShouldAddItemsReturnsFalseWhenPayloadIsNotArray(): void
    {
        $this->assertFalse(PayloadItemsRequirementChecker::shouldAddItems(null));
    }

    public function testShouldAddItemsReturnsFalseWhenNotArrayType(): void
    {
        $payload = ['type' => 'string'];

        $this->assertFalse(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }

    public function testShouldAddItemsReturnsFalseWhenItemsPresent(): void
    {
        $payload = ['type' => 'array', 'items' => ['type' => 'string']];

        $this->assertFalse(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }

    public function testShouldAddItemsReturnsTrueWhenItemsMissing(): void
    {
        $payload = ['type' => 'array'];

        $this->assertTrue(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }

    public function testShouldAddItemsReturnsTrueWhenItemsIsNull(): void
    {
        $payload = ['type' => 'array', 'items' => null];

        $this->assertTrue(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }

    public function testShouldAddItemsReturnsTrueWhenTypeIsArrayObject(): void
    {
        $payload = ['type' => new ArrayObject(['array']), 'items' => null];

        $this->assertTrue(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }

    public function testShouldAddItemsReturnsFalseWhenTypeIsArrayObjectWithItems(): void
    {
        $payload = ['type' => new ArrayObject(['array']), 'items' => ['type' => 'string']];

        $this->assertFalse(PayloadItemsRequirementChecker::shouldAddItems($payload));
    }
}
