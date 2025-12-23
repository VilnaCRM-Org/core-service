<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\EmfLogFormatter;
use App\Tests\Unit\UnitTestCase;

final class EmfLogFormatterTest extends UnitTestCase
{
    private EmfLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new EmfLogFormatter();
    }

    public function testFormatsContextAsJson(): void
    {
        $context = [
            '_aws' => [
                'Timestamp' => 1702425600000,
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => 'CCore/BusinessMetrics',
                        'Dimensions' => [['Endpoint', 'Operation']],
                        'Metrics' => [['Name' => 'CustomersCreated', 'Unit' => 'Count']],
                    ],
                ],
            ],
            'Endpoint' => 'Customer',
            'Operation' => 'create',
            'CustomersCreated' => 1,
        ];

        $formatted = $this->formatter->format($context);

        self::assertStringStartsWith('{', $formatted);
        self::assertStringEndsWith("}\n", $formatted);

        $decoded = json_decode(rtrim($formatted, "\n"), true);
        self::assertSame($context, $decoded);
    }

    public function testReturnsEmptyStringForEmptyContext(): void
    {
        $formatted = $this->formatter->format([]);

        self::assertSame('', $formatted);
    }

    public function testReturnsEmptyStringForInvalidJson(): void
    {
        $formatted = $this->formatter->format(['invalid' => "\xB1"]); // Invalid UTF-8

        self::assertSame('', $formatted);
    }
}
