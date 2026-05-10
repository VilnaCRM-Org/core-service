<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ConstraintViolationPayloadItemsUpdaterTest extends UnitTestCase
{
    public function testUpdateReturnsNullWhenViolationPropertiesAreMissing(): void
    {
        $this->assertNull((new ConstraintViolationPayloadItemsUpdater())->update([]));
    }

    public function testUpdateReturnsNullWhenViolationsNodeIsMissing(): void
    {
        $input = [
            'properties' => [],
        ];

        $this->assertNull((new ConstraintViolationPayloadItemsUpdater())->update($input));
    }

    public function testUpdateReturnsNullWhenItemsPropertiesNodeIsMissing(): void
    {
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [],
                ],
            ],
        ];

        $this->assertNull((new ConstraintViolationPayloadItemsUpdater())->update($input));
    }

    public function testUpdateReturnsNullWhenItemsNodeIsMissing(): void
    {
        $input = [
            'properties' => [
                'violations' => [],
            ],
        ];

        $this->assertNull((new ConstraintViolationPayloadItemsUpdater())->update($input));
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

        $this->assertNull((new ConstraintViolationPayloadItemsUpdater())->update($input));
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

        $updated = (new ConstraintViolationPayloadItemsUpdater())->update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }

    public function testUpdateCreatesPayloadWhenMissing(): void
    {
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [],
                    ],
                ],
            ],
        ];

        $updated = (new ConstraintViolationPayloadItemsUpdater())->update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            'array',
            $updated['properties']['violations']['items']['properties']['payload']['type']
        );
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }

    public function testUpdateRemovesNullItemsFromArrayPayload(): void
    {
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

        $updated = (new ConstraintViolationPayloadItemsUpdater())->update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }

    public function testUpdateTreatsNullableArrayPayloadAsArray(): void
    {
        $input = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => [
                                'type' => ['array', 'null'],
                                'items' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $updated = (new ConstraintViolationPayloadItemsUpdater())->update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }

    public function testUpdatePersistsPayloadWhenIntermediateNodesAreArrayObjects(): void
    {
        $input = [
            'properties' => new ArrayObject([
                'violations' => new ArrayObject([
                    'items' => new ArrayObject([
                        'properties' => [
                            'payload' => ['type' => 'array'],
                        ],
                    ]),
                ]),
            ]),
        ];

        $updated = (new ConstraintViolationPayloadItemsUpdater())->update($input);

        $this->assertNotNull($updated);
        $this->assertSame(
            ['type' => 'object'],
            $updated['properties']['violations']['items']['properties']['payload']['items']
        );
    }
}
