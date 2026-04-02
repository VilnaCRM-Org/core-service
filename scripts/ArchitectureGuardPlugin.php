<?php

declare(strict_types=1);

namespace App\Psalm;

use const DIRECTORY_SEPARATOR;

use PhpParser\Node;
use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Issue\ForbiddenCode;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;

use function in_array;
use function preg_match;
use function sprintf;
use function str_contains;

final class ArchitectureGuardPlugin implements
    AfterExpressionAnalysisInterface,
    AfterFunctionLikeAnalysisInterface
{
    private const SOURCE_DIRECTORY = DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    private const FACTORY_DIRECTORY = DIRECTORY_SEPARATOR . 'Factory' . DIRECTORY_SEPARATOR;
    private const NATIVE_ARRAY_MESSAGE =
        'Use Psalm array shapes/docblocks or typed collections instead of native array declarations in src/.';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $filePath = $event->getStatementsSource()->getFilePath();

        if (!self::isProductionSource($filePath) || self::isFactorySource($filePath)) {
            return null;
        }

        $expression = $event->getExpr();
        if (!$expression instanceof Expr\New_) {
            return null;
        }

        if ($expression->class instanceof Node\Stmt\Class_) {
            return null;
        }

        if (!$expression->class instanceof Node\Name) {
            return null;
        }

        $resolvedName = (string) $expression->class->getAttribute('resolvedName');
        $className = $resolvedName !== '' ? $resolvedName : $expression->class->toString();

        if (self::shouldAllowInstantiation($className, $event->getContext()->self)) {
            return null;
        }

        self::reportExpressionIssue(
            $event,
            $expression->class,
            sprintf(
                'Instantiate %s via a factory or dependency injection in production code.',
                $className
            )
        );

        return null;
    }

    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $filePath = $event->getStatementsSource()->getFilePath();

        if (!self::isProductionSource($filePath)) {
            return null;
        }

        $statement = $event->getStmt();

        foreach ($statement->getParams() as $parameter) {
            if (!self::containsNativeArrayType($parameter->type)) {
                continue;
            }

            self::reportFunctionLikeIssue(
                $event,
                $parameter,
                self::NATIVE_ARRAY_MESSAGE
            );
        }

        $returnType = $statement->getReturnType();
        if ($returnType !== null && self::containsNativeArrayType($returnType)) {
            self::reportFunctionLikeIssue(
                $event,
                $returnType,
                self::NATIVE_ARRAY_MESSAGE
            );
        }

        return null;
    }

    private static function containsNativeArrayType(
        Node\Identifier|Node\Name|Node\ComplexType|null $type
    ): bool {
        if ($type === null) {
            return false;
        }

        if ($type instanceof Node\Identifier) {
            return $type->toLowerString() === 'array';
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            foreach ($type->types as $innerType) {
                if (self::containsNativeArrayType($innerType)) {
                    return true;
                }
            }

            return false;
        }

        if ($type instanceof Node\NullableType) {
            return self::containsNativeArrayType($type->type);
        }

        return false;
    }

    private static function shouldAllowInstantiation(
        string $className,
        ?string $contextSelf
    ): bool {
        if ($className === '' || in_array($className, ['self', 'static', 'parent'], true)) {
            return true;
        }

        if ($contextSelf === $className || self::isFactoryContext($contextSelf)) {
            return true;
        }

        if (preg_match('/(?:^|\\\\)(?:Exception|Error)$/', $className) === 1) {
            return true;
        }

        return preg_match('/(?:^|\\\\)(?:ArrayIterator|ArrayObject)$/', $className) === 1;
    }

    private static function reportExpressionIssue(
        AfterExpressionAnalysisEvent $event,
        Node $node,
        string $message,
    ): void {
        IssueBuffer::maybeAdd(
            new ForbiddenCode(
                $message,
                new CodeLocation($event->getStatementsSource(), $node)
            ),
            $event->getStatementsSource()->getSuppressedIssues()
        );
    }

    private static function reportFunctionLikeIssue(
        AfterFunctionLikeAnalysisEvent $event,
        Node $node,
        string $message,
    ): void {
        IssueBuffer::maybeAdd(
            new ForbiddenCode(
                $message,
                new CodeLocation($event->getStatementsSource(), $node)
            ),
            $event->getFunctionlikeStorage()->suppressed_issues
        );
    }

    private static function isFactoryContext(?string $className): bool
    {
        return $className !== null && str_contains($className, '\\Factory\\');
    }

    private static function isFactorySource(string $filePath): bool
    {
        return str_contains($filePath, self::FACTORY_DIRECTORY);
    }

    private static function isProductionSource(string $filePath): bool
    {
        return str_contains($filePath, self::SOURCE_DIRECTORY);
    }
}
