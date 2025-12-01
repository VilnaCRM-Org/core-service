<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\TagDescriptionProcessor;
use App\Tests\Unit\UnitTestCase;

final class TagDescriptionProcessorTest extends UnitTestCase
{
    private TagDescriptionProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new TagDescriptionProcessor();
    }

    public function testProcessAddsDescriptionToKnownTag(): void
    {
        $tag = new Tag('Customer');
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            [$tag]
        );

        $result = $this->processor->process($openApi);

        $resultTags = $result->getTags();
        $this->assertCount(1, $resultTags);
        $this->assertEquals('Operations related to customer management', $resultTags[0]->getDescription());
    }

    public function testProcessDoesNotOverrideExistingDescription(): void
    {
        $existingDescription = 'My custom description';
        $tag = (new Tag('Customer'))->withDescription($existingDescription);
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            [$tag]
        );

        $result = $this->processor->process($openApi);

        $resultTags = $result->getTags();
        $this->assertEquals($existingDescription, $resultTags[0]->getDescription());
    }

    public function testProcessWithUnknownTag(): void
    {
        $tag = new Tag('UnknownTag');
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            [$tag]
        );

        $result = $this->processor->process($openApi);

        $resultTags = $result->getTags();
        $description = $resultTags[0]->getDescription();
        $this->assertTrue($description === null || $description === '');
    }

    public function testProcessWithMultipleTags(): void
    {
        $tags = [
            new Tag('Customer'),
            new Tag('CustomerStatus'),
            new Tag('CustomerType'),
            new Tag('HealthCheck'),
        ];
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            $tags
        );

        $result = $this->processor->process($openApi);

        $resultTags = $result->getTags();
        $this->assertCount(4, $resultTags);
        $this->assertEquals('Operations related to customer management', $resultTags[0]->getDescription());
        $this->assertEquals('Operations related to customer status management', $resultTags[1]->getDescription());
        $this->assertEquals('Operations related to customer type management', $resultTags[2]->getDescription());
        $this->assertEquals('Health check endpoints for monitoring', $resultTags[3]->getDescription());
    }

    public function testProcessWithEmptyTags(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $result = $this->processor->process($openApi);

        $this->assertCount(0, $result->getTags());
    }

    public function testProcessWithMixedTags(): void
    {
        $tags = [
            new Tag('Customer'),
            (new Tag('CustomerStatus'))->withDescription('Already set'),
            new Tag('UnknownTag'),
        ];
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            $tags
        );

        $result = $this->processor->process($openApi);

        $resultTags = $result->getTags();
        $this->assertCount(3, $resultTags);
        $this->assertEquals('Operations related to customer management', $resultTags[0]->getDescription());
        $this->assertEquals('Already set', $resultTags[1]->getDescription());
        $description = $resultTags[2]->getDescription();
        $this->assertTrue($description === null || $description === '');
    }
}
