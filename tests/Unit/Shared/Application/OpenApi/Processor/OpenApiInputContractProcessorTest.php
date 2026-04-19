<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\NullableSchemaTypeNormalizer;
use App\Shared\Application\OpenApi\Processor\OpenApiInputContractProcessor;
use App\Shared\Application\OpenApi\Processor\OpenApiInputSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodyContentSchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodyPathUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequiredSchemaPropertyUpdater;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiInputContractProcessorTest extends UnitTestCase
{
    public function testProcessAppliesInputFixesToSchemathesisSensitiveRequestBodies(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/api/customers',
            (new PathItem())->withPost(
                $this->operationWithRequestBody('application/ld+json', 'email')
            )
        );
        $paths->addPath(
            '/api/customers/{ulid}',
            (new PathItem())->withPatch(
                $this->operationWithRequestBody('application/merge-patch+json', 'status')
            )
        );
        $paths->addPath(
            '/api/customer_types',
            (new PathItem())->withPost(
                $this->operationWithRequestBody('application/ld+json', 'value')
            )
        );
        $paths->addPath(
            '/api/customer_types/{ulid}',
            (new PathItem())->withPut(
                $this->operationWithRequestBody('application/ld+json', 'value')
            )
        );
        $paths->addPath(
            '/api/customer_statuses',
            (new PathItem())->withPost(
                $this->operationWithRequestBody('application/ld+json', 'value')
            )
        );
        $paths->addPath(
            '/api/customer_statuses/{ulid}',
            (new PathItem())->withPatch(
                $this->operationWithRequestBody('application/merge-patch+json', 'value')
            )
        );

        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [new Server('https://localhost')],
            $paths,
            new Components(
                new ArrayObject([
                    'Customer.CustomerCreate' => [
                        'required' => ['confirmed'],
                        'properties' => [
                            'confirmed' => ['type' => ['boolean', 'null']],
                        ],
                    ],
                    'CustomerType.TypeCreate' => [
                        'required' => ['value'],
                        'properties' => [
                            'value' => ['type' => ['string', 'null']],
                        ],
                    ],
                ])
            )
        );

        $processed = $this->createProcessor()->process($openApi);
        $schemas = $processed->getComponents()->getSchemas();

        self::assertInstanceOf(ArrayObject::class, $schemas);
        self::assertSame(
            'boolean',
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['Customer.CustomerCreate'])['properties']
                )['confirmed']
            )['type']
        );
        self::assertSame(
            'string',
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['CustomerType.TypeCreate'])['properties']
                )['value']
            )['type']
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/Customer.CustomerCreate'],
            $this->requestSchema($processed, '/api/customers', 'Post', 'application/ld+json')
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/Customer.CustomerPatch.jsonMergePatch'],
            $this->requestSchema($processed, '/api/customers/{ulid}', 'Patch', 'application/merge-patch+json')
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $this->requestSchema($processed, '/api/customer_types', 'Post', 'application/ld+json')
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePut'],
            $this->requestSchema($processed, '/api/customer_types/{ulid}', 'Put', 'application/ld+json')
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerStatus.StatusCreate'],
            $this->requestSchema($processed, '/api/customer_statuses', 'Post', 'application/ld+json')
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerStatus.StatusPatch.jsonMergePatch'],
            $this->requestSchema($processed, '/api/customer_statuses/{ulid}', 'Patch', 'application/merge-patch+json')
        );
    }

    private function createProcessor(): OpenApiInputContractProcessor
    {
        return new OpenApiInputContractProcessor(
            new OpenApiInputSchemaUpdater(
                new RequiredSchemaPropertyUpdater(
                    new NullableSchemaTypeNormalizer()
                )
            ),
            new RequestBodyPathUpdater(
                new RequestBodySchemaRefUpdater(
                    new RequestBodyContentSchemaRefUpdater(
                        new RequestBodySchemaRefDefinitionUpdater()
                    )
                )
            )
        );
    }

    private function operationWithRequestBody(string $contentType, string $propertyName): Operation
    {
        return new Operation(
            requestBody: new RequestBody(
                content: new ArrayObject([
                    $contentType => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                $propertyName => ['type' => 'string'],
                            ],
                        ],
                    ],
                ])
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestSchema(
        OpenApi $openApi,
        string $path,
        string $operationName,
        string $contentType
    ): array {
        $operation = $openApi->getPaths()->getPath($path)?->{'get' . $operationName}();
        $content = $operation?->getRequestBody()?->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);

        return $content[$contentType]['schema'];
    }
}
