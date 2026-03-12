<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

use ImSuperlative\PhpstanFilament\Data\ChainWalkResult;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;

final class AstHelper
{
    public static function buildScopeKey(Scope $scope): ?string
    {
        $classReflection = $scope->getClassReflection();
        $methodName = $scope->getFunctionName();

        return $classReflection !== null && $methodName !== null
            ? $classReflection->getName().'::'.$methodName
            : null;
    }

    /**
     * Extract the first string argument from a ::make('name') static call.
     */
    public static function extractMakeName(Expr $expr): ?string
    {
        if (! self::isStaticMakeCall($expr)) {
            return null;
        }

        /** @var StaticCall $expr */
        return self::firstArgValueAs($expr, String_::class)?->value;
    }

    /**
     * Walk a method chain and return the first non-null result from the callback.
     *
     * @template R
     *
     * @param  callable(MethodCall): ?R  $callback
     * @return ?R
     */
    public static function findInMethodChain(Expr $expr, callable $callback): mixed
    {
        $current = $expr;

        while ($current instanceof MethodCall) {
            $result = $callback($current);

            if ($result !== null) {
                return $result;
            }

            $current = $current->var;
        }

        return null;
    }

    /**
     * Walk a method chain to its root (the non-MethodCall receiver).
     */
    public static function methodChainRoot(Expr $expr): Expr
    {
        $current = $expr;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        return $current;
    }

    /**
     * Extract the first argument's value from a call node, or null.
     */
    public static function firstArgValue(StaticCall|MethodCall $call): ?Expr
    {
        return ($call->getArgs()[0] ?? null)?->value;
    }

    /**
     * Extract the first argument's value if it's an instance of the given class.
     *
     * @template T of Expr
     *
     * @param  class-string<T>  $class
     * @return ?T
     */
    public static function firstArgValueAs(StaticCall|MethodCall $call, string $class): ?Expr
    {
        $value = self::firstArgValue($call);

        return $value instanceof $class ? $value : null;
    }

    /**
     * Walk a method chain from the outermost call inward.
     * Calls $visitor for each MethodCall with an Identifier name.
     * Returns collected method names and the ::make('name') field name if the root is a static make call.
     *
     * @param  callable(string $methodName, MethodCall $call): void  $visitor
     */
    public static function walkMethodChain(Expr $expr, callable $visitor): ChainWalkResult
    {
        $methodNames = [];
        $current = $expr;

        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier) {
                $methodName = $current->name->toString();
                $methodNames[] = $methodName;
                $visitor($methodName, $current);
            }

            $current = $current->var;
        }

        return new ChainWalkResult(
            methodNames: $methodNames,
            fieldName: self::extractMakeName($current),
        );
    }

    /**
     * Resolve the array from a ->schema() or ->components() call argument,
     * handling direct arrays, arrow functions, and single-return closures.
     */
    public static function resolveSchemaArray(StaticCall|MethodCall $call): ?Array_
    {
        $arg = self::firstArgValue($call);

        if ($arg instanceof Array_) {
            return $arg;
        }

        if ($arg instanceof ArrowFunction && $arg->expr instanceof Array_) {
            return $arg->expr;
        }

        if ($arg instanceof Closure && count($arg->stmts) === 1) {
            $stmt = $arg->stmts[0];

            if ($stmt instanceof Return_ && $stmt->expr instanceof Array_) {
                return $stmt->expr;
            }
        }

        return null;
    }

    /** @phpstan-assert-if-true StaticCall $expr */
    protected static function isStaticMakeCall(Expr $expr): bool
    {
        return $expr instanceof StaticCall
            && $expr->name instanceof Identifier
            && $expr->name->toString() === 'make';
    }
}
