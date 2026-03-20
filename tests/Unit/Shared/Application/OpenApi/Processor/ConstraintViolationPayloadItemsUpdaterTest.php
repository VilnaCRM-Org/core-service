<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsUpdater;
use App\Tests\Unit\UnitTestCase;

final class ConstraintViolationPayloadItemsUpdaterTest extends UnitTestCase
{
    public function testUpdateReturnsNullWhenViolationPropertiesAreMissing(): void
    {
        $this->assertNull(ConstraintViolationPayloadItemsUpdater::update([]));
    }

    public function testUpdateReturnsNullWhenPayloadIsNotArrayWithoutItems(): void
    {
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertNull(ConstraintViolationPayloadItemsUpdater::update($input));
    }

    public function testUpdateAddsItemsToArrayPayload(): void
    {
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => ['type' => 'array'],
                        ],
                    ],
                ],
            ],
        ];

        $updated = ConstraintViolationPayloadItemsUpdater::update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }

    public function testUpdateRemovesNullItemsFromArrayPayload(): void
    {
        // Test case where items key exists but is null - should be unset before adding new items
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => ['type' => 'array', 'items' => null],
                        ],
                    ],
                ],
            ],
        ];

        $updated = ConstraintViolationPayloadItemsUpdater::update($input);

        $this->assertNotNull($updated);
        // The null items should be removed, then new items should be added
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }
}
