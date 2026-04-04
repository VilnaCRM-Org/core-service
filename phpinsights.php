<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'CLI/bats/php',
        'src/Core/Customer/Application/DTO',
        // These schema-fixer classes intentionally traverse nested OpenAPI arrays.
        'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor.php',
        'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsUpdater.php',
        'src/Shared/Application/OpenApi/Processor/OpenApiSchemaFixesProcessor.php',
        'src/Shared/Application/OpenApi/Processor/HydraAllOfUpdater.php',
        'src/Shared/Application/OpenApi/Processor/HydraAllOfItemUpdater.php',
        'src/Shared/Application/OpenApi/Processor/PayloadItemsRequirementChecker.php',
        'src/Shared/Application/OpenApi/Processor/CustomerUlidRefReplacer.php',
        'src/Shared/Application/OpenApi/Processor/ConstraintViolationPropertiesExtractor.php',
        'src/Shared/Application/OpenApi/Processor/OpenApiArrayContentSchemaUpdater.php',
        'src/Shared/Application/OpenApi/Processor/OpenApiResponseContentUpdater.php',
        'src/Shared/Application/OpenApi/Serializer/HydraSchemaNormalizer.php',
        'src/Shared/Application/OpenApi/Updater/HydraDirectViewExampleUpdater.php',
        'src/Shared/Application/OpenApi/Writer/ConstraintViolationPropertiesWriter.php',
    ],
    'add' => [],
    'remove' => [
        UnusedParameterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SpaceAfterNotSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        UseSpacingSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenPublicPropertySniff::class,
    ],

    'config' => [
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class => [
            'exclude' => [
                // Doctrine ODM requires mixed $id in find() method signature
                'src/Core/Customer/Domain/Repository/CustomerRepositoryInterface',
                'src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository',
            ],
        ],
        ParameterTypeHintSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/CallableFirstParameterExtractorTest',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
            ],
            'ignoreComments' => true,
            'lineLimit' => 100,
        ],
        ForbiddenNormalClasses::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
                'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
                'src/Core/Customer/Domain/Entity/Customer',
            ],
        ],
        CyclomaticComplexityIsHigh::class => [
            'exclude' => [
                // These OpenAPI fixers still need broader redesign; keep the PR scoped.
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsUpdater',
                'src/Shared/Application/OpenApi/Processor/OpenApiSchemaFixesProcessor',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfUpdater',
                'src/Shared/Application/OpenApi/Processor/PayloadItemsRequirementChecker',
            ],
        ],
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 93,
        'min-architecture' => 100,
        'min-style' => 100,
    ],
    'threads' => null,
];
