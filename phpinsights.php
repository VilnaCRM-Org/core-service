<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
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
        'scripts/codespaces',
        'scripts/validate-openapi-spec.sh',
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
                // OpenAPI processors need mixed return types for schema normalization
                'src/Shared/Application/OpenApi/Processor/CustomerUlidRefReplacer',
                'src/Shared/Application/OpenApi/Processor/SchemaNormalizer',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
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
                'src/Core/Customer/Domain/Entity/CustomerStatus',
                'src/Core/Customer/Domain/Entity/CustomerType',
            ],
        ],
        CyclomaticComplexityIsHigh::class => [
            'exclude' => [
                // OpenAPI processors traverse nested schema trees and remain intentionally localized.
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsUpdater',
                'src/Shared/Application/OpenApi/Processor/HydraCollectionSchemaFixer',
                'src/Shared/Application/OpenApi/Processor/HydraDirectViewExampleUpdater',
                'src/Shared/Application/OpenApi/Processor/OpenApiSchemaFixesProcessor',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfUpdater',
                'src/Shared/Application/OpenApi/Processor/HydraViewExampleUpdater',
                'src/Shared/Application/OpenApi/Processor/PayloadItemsRequirementChecker',
            ],
        ],
        FunctionLengthSniff::class => [
            'exclude' => [
                // OpenAPI processors have longer functions due to schema processing requirements.
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
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
