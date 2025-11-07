<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\TagDescriptionAugmenter;
use App\Tests\Unit\UnitTestCase;

final class TagDescriptionAugmenterTest extends UnitTestCase
{
    private TagDescriptionAugmenter $augmenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->augmenter = new TagDescriptionAugmenter();
    }

    public function testAugmentWithNoTags(): void
    {
        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $result = $this->augmenter->augment($openApi);

        $this->assertCount(0, $result->getTags());
    }

    public function testAugmentAddsDescriptionToKnownTag(): void
    {
        $tag = new Tag('Customer');

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags([$tag]);

        $result = $this->augmenter->augment($openApi);

        $tags = $result->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals(
            'Operations related to customer management',
            $tags[0]->getDescription()
        );
    }

    public function testAugmentDoesNotOverrideExistingDescription(): void
    {
        $existingDescription = 'My custom customer description';
        $tag = (new Tag('Customer'))->withDescription($existingDescription);

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags([$tag]);

        $result = $this->augmenter->augment($openApi);

        $tags = $result->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals($existingDescription, $tags[0]->getDescription());
    }

    public function testAugmentWithUnknownTag(): void
    {
        $tag = new Tag('UnknownTag');

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags([$tag]);

        $result = $this->augmenter->augment($openApi);

        $tags = $result->getTags();
        $this->assertCount(1, $tags);
        // Unknown tag should remain unchanged (null or empty description)
        $description = $tags[0]->getDescription();
        $this->assertTrue($description === null || $description === '');
    }

    public function testAugmentWithMultipleTags(): void
    {
        $tags = $this->createMultipleTags();
        $openApi = $this->createOpenApiWithTags($tags);

        $result = $this->augmenter->augment($openApi);
        $resultTags = $result->getTags();

        $this->assertCount(5, $resultTags);
        $this->assertTagDescription('Operations related to customer management', $resultTags[0]);
        $this->assertTagDescription(
            'Operations related to customer status management',
            $resultTags[1]
        );
        $this->assertTagDescription(
            'Operations related to customer type management',
            $resultTags[2]
        );
        $this->assertTagDescription('Health check endpoints for monitoring', $resultTags[3]);
        $this->assertEmptyTagDescription($resultTags[4]);
    }

    public function testAugmentWithEmptyStringDescription(): void
    {
        $tag = (new Tag('Customer'))->withDescription('');

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags([$tag]);

        $result = $this->augmenter->augment($openApi);

        $tags = $result->getTags();
        $this->assertCount(1, $tags);
        // Empty string should be treated as no description and be replaced
        $this->assertEquals(
            'Operations related to customer management',
            $tags[0]->getDescription()
        );
    }

    public function testAugmentReturnsNewOpenApiInstance(): void
    {
        $tag = new Tag('Customer');

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags([$tag]);

        $result = $this->augmenter->augment($openApi);

        $this->assertInstanceOf(OpenApi::class, $result);
    }

    public function testAugmentWithAllKnownTags(): void
    {
        $tags = [
            new Tag('Customer'),
            new Tag('CustomerStatus'),
            new Tag('CustomerType'),
            new Tag('HealthCheck'),
        ];

        $openApi = (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags($tags);

        $result = $this->augmenter->augment($openApi);

        $resultTags = $result->getTags();
        $this->assertCount(4, $resultTags);

        foreach ($resultTags as $tag) {
            $this->assertNotNull($tag->getDescription());
            $this->assertNotEmpty($tag->getDescription());
        }
    }

    public function testAugmentWithMixedTagsWithAndWithoutDescriptions(): void
    {
        $tags = $this->createMixedTags();
        $openApi = $this->createOpenApiWithTags($tags);

        $result = $this->augmenter->augment($openApi);
        $resultTags = $result->getTags();

        $this->assertCount(3, $resultTags);
        $this->assertTagDescription('Operations related to customer management', $resultTags[0]);
        $this->assertTagDescription('Custom status desc', $resultTags[1]);
        $this->assertTagDescription(
            'Operations related to customer type management',
            $resultTags[2]
        );
    }

    /**
     * @return array<Tag>
     */
    private function createMultipleTags(): array
    {
        return [
            new Tag('Customer'),
            new Tag('CustomerStatus'),
            new Tag('CustomerType'),
            new Tag('HealthCheck'),
            new Tag('UnknownTag'),
        ];
    }

    /**
     * @param array<Tag> $tags
     */
    private function createOpenApiWithTags(array $tags): OpenApi
    {
        return (new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            new Paths()
        ))->withTags($tags);
    }

    private function assertTagDescription(string $expected, Tag $tag): void
    {
        $this->assertEquals($expected, $tag->getDescription());
    }

    private function assertEmptyTagDescription(Tag $tag): void
    {
        $description = $tag->getDescription();
        $this->assertTrue($description === null || $description === '');
    }

    /**
     * @return array<Tag>
     */
    private function createMixedTags(): array
    {
        return [
            new Tag('Customer'),
            (new Tag('CustomerStatus'))->withDescription('Custom status desc'),
            (new Tag('CustomerType'))->withDescription(''),
        ];
    }
}
