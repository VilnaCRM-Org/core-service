<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Contact;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\License;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\SpecExtensionPropertyApplier;
use App\Shared\Application\OpenApi\Cleaner\SpecMetadataCleaner;
use App\Shared\Application\OpenApi\Processor\SpecCleanupProcessor;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use ReflectionMethod;
use ReflectionProperty;

final class SpecCleanupProcessorTest extends UnitTestCase
{
    public function testProcessNormalizesMetadataAndKeepsExtensionProperties(): void
    {
        $info = new Info(
            'VilnaCRM',
            '1.0.0',
            'Spec metadata',
            termsOfService: 'https://example.com/terms',
            contact: new Contact('Vilna Support', 'https://example.com', 'support@example.com'),
            license: new License('MIT', 'https://opensource.org/licenses/MIT')
        );

        $components = new Components(
            new ArrayObject(['Customer' => new Schema()]),
            responses: new ArrayObject(['default' => new Response('desc')])
        );

        $paths = new Paths();
        $openApi = (new OpenApi(
            $info,
            [new Server('https://localhost', 'Local', new ArrayObject(['env' => 'dev']))],
            $paths,
            $components
        ))->withExtensionProperty('x-build', 'ci');

        $processed = (new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier()))->process($openApi);

        $processedInfo = $processed->getInfo();
        self::assertNull($processedInfo->getTermsOfService());
        self::assertSame('support@example.com', $processedInfo->getContact()?->getEmail());
        self::assertSame('MIT', $processedInfo->getLicense()?->getName());

        $server = $processed->getServers()[0];
        self::assertSame('https://localhost', $server->getUrl());
        self::assertSame('Local', $server->getDescription());
        self::assertNull($server->getVariables());

        $processedComponents = $processed->getComponents();
        self::assertNotNull($processedComponents->getSchemas());
        self::assertNull($processedComponents->getResponses());

        self::assertSame('ci', $processed->getExtensionProperties()['x-build']);
    }

    public function testProcessSkipsExtensionApplicationWhenNoneExist(): void
    {
        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            new Paths()
        );

        $processed = (new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier()))->process($openApi);

        self::assertSame([], $processed->getExtensionProperties());
    }

    public function testCleanComponentsHandlesNullViaReflection(): void
    {
        $processor = new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier());
        $method = new ReflectionMethod(SpecCleanupProcessor::class, 'cleanComponents');
        $method->setAccessible(true);

        self::assertNull($method->invoke($processor, null));
    }

    public function testProcessPreservesExistingWebhookCollection(): void
    {
        $webhooks = new ArrayObject(['test' => 'value']);
        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            new Paths(),
            new Components(),
            webhooks: $webhooks
        );

        $processed = (new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier()))->process($openApi);

        self::assertSame($webhooks, $processed->getWebhooks());
    }

    public function testProcessCreatesEmptyWebhookCollectionWhenMissing(): void
    {
        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            new Paths(),
            new Components(),
            webhooks: null
        );

        $processed = (new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier()))->process($openApi);

        self::assertInstanceOf(ArrayObject::class, $processed->getWebhooks());
        self::assertCount(0, (array) $processed->getWebhooks());
    }

    public function testApplyExtensionPropertiesHandlesNullValue(): void
    {
        $processor = new SpecCleanupProcessor(new SpecMetadataCleaner(), new SpecExtensionPropertyApplier());
        $method = new ReflectionMethod(SpecCleanupProcessor::class, 'applyExtensionProperties');
        $method->setAccessible(true);

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            new Paths()
        );

        $result = $method->invoke($processor, null, $openApi);

        self::assertSame($openApi, $result);
    }

    public function testConstructorUsesProvidedCollaborators(): void
    {
        $metadataCleaner = new SpecMetadataCleaner();
        $extensionApplier = new SpecExtensionPropertyApplier();

        $processor = new SpecCleanupProcessor($metadataCleaner, $extensionApplier);

        $metadataProperty = new ReflectionProperty(SpecCleanupProcessor::class, 'metadataCleaner');
        $metadataProperty->setAccessible(true);

        $extensionProperty = new ReflectionProperty(SpecCleanupProcessor::class, 'extensionApplier');
        $extensionProperty->setAccessible(true);

        self::assertSame($metadataCleaner, $metadataProperty->getValue($processor));
        self::assertSame($extensionApplier, $extensionProperty->getValue($processor));
    }
}
