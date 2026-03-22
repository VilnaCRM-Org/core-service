<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\OpenApiFixer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class OpenApiFixerBehaviorTest extends UnitTestCase
{
    private string $tempDir;
    private string $specFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/openapi_fixer_behavior_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->specFile = $this->tempDir . '/spec.yaml';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->recursiveDelete($this->tempDir);
    }

    public function testWriteSpecUsesQuotedStatusCodesAndTwoSpaceIndentation(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'description' => 'Validation failed',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $content = file_get_contents($this->specFile);
        $this->assertSame(
            "paths:\n  /customers:\n    post:\n      responses:\n        '422':\n          description: 'Validation failed'\n",
            $content,
        );
    }

    public function testRunThrowsExceptionWithParseExceptionAsPrevious(): void
    {
        file_put_contents($this->specFile, 'invalid: yaml: content:');

        $fixer = new OpenApiFixer($this->specFile);

        try {
            $fixer->run();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(0, $exception->getCode());
            $this->assertInstanceOf(\Throwable::class, $exception->getPrevious());
        }
    }

    public function testWriteSpecPreservesDeeplyNestedStructures(): void
    {
        $spec = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'level6' => [
                                    'level7' => [
                                        'level8' => [
                                            'level9' => [
                                                'level10' => [
                                                    'level11' => [
                                                        'value' => 'kept',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $content = file_get_contents($this->specFile);
        $this->assertSame(
            "level1:\n  level2:\n    level3:\n      level4:\n        level5:\n          level6:\n            level7:\n              level8:\n                level9:\n                  level10: { level11: { value: kept } }\n",
            $content,
        );

        $result = $this->readSpecFile();
        $this->assertSame('kept', $result['level1']['level2']['level3']['level4']['level5']['level6']['level7']['level8']['level9']['level10']['level11']['value']);
    }

    public function testFix422ErrorTypeSkipsMethodWithoutResponsesAndContinues(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'summary' => 'List customers',
                    ],
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('List customers', $result['paths']['/test']['get']['summary']);
        $this->assertSame('/errors/422', $result['paths']['/test']['post']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix204ResponsesSkipsMethodWithoutResponsesAndContinues(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'summary' => 'List customers',
                    ],
                    'delete' => [
                        'responses' => [
                            '204' => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('List customers', $result['paths']['/test']['get']['summary']);
        $this->assertArrayNotHasKey('content', $result['paths']['/test']['delete']['responses']['204']);
    }

    public function testFix204ResponsesWithQuotedStringStatusKey(): void
    {
        $fixer = new OpenApiFixer($this->specFile);
        $response = [
            'description' => 'No Content',
            'content' => [
                'application/json' => [
                    'schema' => ['type' => 'object'],
                ],
            ],
        ];

        $method = new \ReflectionMethod($fixer, 'processResponseFor204');
        $method->setAccessible(true);
        $method->invokeArgs($fixer, ['204', &$response]);

        $this->assertArrayNotHasKey('content', $response);
    }

    private function recursiveDelete(string $path): void
    {
        if (is_dir($path)) {
            $files = glob($path . '/*') ?: [];
            array_map(fn ($file) => $this->recursiveDelete($file), $files);
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    private function writeSpecFile(array $spec): void
    {
        $yaml = Yaml::dump($spec, 10, 2, Yaml::DUMP_NUMERIC_KEY_AS_STRING);
        file_put_contents($this->specFile, $yaml);
    }

    private function readSpecFile(): array
    {
        return Yaml::parseFile($this->specFile);
    }
}
