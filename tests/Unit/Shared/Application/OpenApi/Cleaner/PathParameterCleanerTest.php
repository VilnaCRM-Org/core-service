<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Tests\Unit\UnitTestCase;

final class PathParameterCleanerTest extends UnitTestCase
{
    private PathParameterCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleaner = new PathParameterCleaner();
    }

    public function testCleanWithNonParameterType(): void
    {
        $nonParameter = 'string value';

        $result = $this->cleaner->clean($nonParameter);

        $this->assertEquals($nonParameter, $result);
    }

    public function testCleanWithQueryParameter(): void
    {
        $parameter = new Parameter('page', 'query', 'Page number');

        $result = $this->cleaner->clean($parameter);

        // Query parameters are returned unchanged
        $this->assertSame($parameter, $result);
    }

    public function testCleanWithPathParameter(): void
    {
        $parameter = (new Parameter('id', 'path', 'User ID'))
            ->withRequired(true)
            ->withSchema(['type' => 'string']);

        $result = $this->cleaner->clean($parameter);

        // Path parameters are returned as-is (ParameterNormalizer handles cleanup during serialization)
        $this->assertSame($parameter, $result);
        $this->assertEquals('id', $result->getName());
        $this->assertEquals('path', $result->getIn());
        $this->assertEquals('User ID', $result->getDescription());
        $this->assertTrue($result->getRequired());
        $this->assertEquals(['type' => 'string'], $result->getSchema());
    }

    public function testCleanWithPathParameterWithoutOptionalFields(): void
    {
        $parameter = new Parameter('id', 'path');

        $result = $this->cleaner->clean($parameter);

        $this->assertEquals('id', $result->getName());
        $this->assertEquals('path', $result->getIn());
    }

    public function testCleanWithHeaderParameter(): void
    {
        $parameter = new Parameter('Authorization', 'header', 'Auth token');

        $result = $this->cleaner->clean($parameter);

        // Header parameters are returned unchanged
        $this->assertSame($parameter, $result);
    }

    public function testCleanWithCookieParameter(): void
    {
        $parameter = new Parameter('session', 'cookie', 'Session ID');

        $result = $this->cleaner->clean($parameter);

        // Cookie parameters are returned unchanged
        $this->assertSame($parameter, $result);
    }
}
