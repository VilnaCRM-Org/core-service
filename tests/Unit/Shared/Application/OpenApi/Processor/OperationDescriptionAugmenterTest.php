<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\OperationDescriptionAugmenter;
use App\Tests\Unit\UnitTestCase;

final class OperationDescriptionAugmenterTest extends UnitTestCase
{
    private OperationDescriptionAugmenter $augmenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->augmenter = new OperationDescriptionAugmenter();
    }

    public function testAugmentWithNoPaths(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $this->augmenter->augment($openApi);

        $this->assertCount(0, $openApi->getPaths()->getPaths());
    }

    public function testAugmentAddsDescriptionToKnownOperation(): void
    {
        $operation = new Operation('api_customers_get_collection');
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals('Retrieve customer collection', $updatedOperation->getSummary());
        $this->assertEquals(
            'Retrieves a paginated collection of customers with optional filtering and sorting capabilities.',
            $updatedOperation->getDescription()
        );
    }

    public function testAugmentDoesNotOverrideExistingSummary(): void
    {
        $existingSummary = 'My custom summary';
        $operation = (new Operation('api_customers_get_collection'))
            ->withSummary($existingSummary);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals($existingSummary, $updatedOperation->getSummary());
    }

    public function testAugmentDoesNotOverrideExistingDescription(): void
    {
        $existingDescription = 'My custom description';
        $operation = (new Operation('api_customers_get_collection'))
            ->withDescription($existingDescription);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals($existingDescription, $updatedOperation->getDescription());
    }

    public function testAugmentWithEmptyStringSummary(): void
    {
        $operation = (new Operation('api_customers_get_collection'))
            ->withSummary('');
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals('Retrieve customer collection', $updatedOperation->getSummary());
    }

    public function testAugmentWithEmptyStringDescription(): void
    {
        $operation = (new Operation('api_customers_get_collection'))
            ->withDescription('');
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals(
            'Retrieves a paginated collection of customers with optional filtering and sorting capabilities.',
            $updatedOperation->getDescription()
        );
    }

    public function testAugmentWithUnknownOperation(): void
    {
        $operation = new Operation('unknown_operation_id');
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/unknown', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/unknown');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertNull($updatedOperation->getSummary());
        $this->assertNull($updatedOperation->getDescription());
    }

    public function testAugmentWithMultipleOperations(): void
    {
        $getOperation = new Operation('api_customers_get_collection');
        $postOperation = new Operation('api_customers_post');

        $pathItem = (new PathItem())
            ->withGet($getOperation)
            ->withPost($postOperation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');

        $updatedGetOperation = $updatedPathItem->getGet();
        $this->assertEquals('Retrieve customer collection', $updatedGetOperation->getSummary());

        $updatedPostOperation = $updatedPathItem->getPost();
        $this->assertEquals('Create a new customer', $updatedPostOperation->getSummary());
    }

    public function testAugmentWithAllHttpMethods(): void
    {
        $getOp = new Operation('api_customers_get_collection');
        $postOp = new Operation('api_customers_post');
        $putOp = new Operation('api_customers_ulid_put');
        $patchOp = new Operation('api_customers_ulid_patch');
        $deleteOp = new Operation('api_customers_ulid_delete');

        $pathItem = (new PathItem())
            ->withGet($getOp)
            ->withPost($postOp)
            ->withPut($putOp)
            ->withPatch($patchOp)
            ->withDelete($deleteOp);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');

        $this->assertNotNull($updatedPathItem->getGet()->getSummary());
        $this->assertNotNull($updatedPathItem->getPost()->getSummary());
        $this->assertNotNull($updatedPathItem->getPut()->getSummary());
        $this->assertNotNull($updatedPathItem->getPatch()->getSummary());
        $this->assertNotNull($updatedPathItem->getDelete()->getSummary());
    }

    public function testAugmentWithNullOperation(): void
    {
        $pathItem = new PathItem();

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');

        $this->assertNull($updatedPathItem->getGet());
        $this->assertNull($updatedPathItem->getPost());
    }

    public function testAugmentWithOperationWithoutOperationId(): void
    {
        $operation = new Operation();
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/test');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertNull($updatedOperation->getSummary());
        $this->assertNull($updatedOperation->getDescription());
    }

    public function testAugmentAddsOnlyMissingSummaryWhenDescriptionExists(): void
    {
        $existingDescription = 'My custom description';
        $operation = (new Operation('api_customers_get_collection'))
            ->withDescription($existingDescription);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals('Retrieve customer collection', $updatedOperation->getSummary());
        $this->assertEquals($existingDescription, $updatedOperation->getDescription());
    }

    public function testAugmentAddsOnlyMissingDescriptionWhenSummaryExists(): void
    {
        $existingSummary = 'My custom summary';
        $operation = (new Operation('api_customers_get_collection'))
            ->withSummary($existingSummary);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/api/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $updatedPathItem = $openApi->getPaths()->getPath('/api/customers');
        $updatedOperation = $updatedPathItem->getGet();

        $this->assertEquals($existingSummary, $updatedOperation->getSummary());
        $this->assertEquals(
            'Retrieves a paginated collection of customers with optional filtering and sorting capabilities.',
            $updatedOperation->getDescription()
        );
    }
}
