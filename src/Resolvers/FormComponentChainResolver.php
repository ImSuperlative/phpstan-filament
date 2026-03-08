<?php

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\ChainAnalysis;
use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;

final class FormComponentChainResolver
{
    public function resolve(Expr $expr, Scope $scope, bool $isInherentlyMultiple = false): ChainAnalysis
    {
        $componentClass = $scope->getType($expr)->getObjectClassNames()[0] ?? null;
        $enumClass = null;
        $literalOptionKeys = null;
        $isMultiple = $isInherentlyMultiple || $this->isInherentlyMultiple($componentClass);

        $walkResult = AstHelper::walkMethodChain($expr, function (string $methodName, MethodCall $call) use ($scope, &$enumClass, &$literalOptionKeys, &$isMultiple) {
            $isMultiple = $isMultiple || $methodName === 'multiple';

            if ($methodName === 'enum' || $methodName === 'options') {
                $arg = AstHelper::firstArgValue($call);

                if ($arg !== null) {
                    $enumClass ??= $this->extractEnumClass($arg, $scope);
                    $literalOptionKeys ??= $this->extractLiteralOptionKeys($arg);
                }
            }
        });

        return new ChainAnalysis(
            componentClass: $componentClass,
            methodCalls: $walkResult->methodNames,
            enumClass: $enumClass,
            literalOptionKeys: $literalOptionKeys,
            isMultiple: $isMultiple,
            fieldName: $walkResult->fieldName,
        );
    }

    protected function extractEnumClass(Expr $arg, Scope $scope): ?string
    {
        return $arg instanceof ClassConstFetch
            ? array_find_map(
                $scope->getType($arg)->getConstantStrings(),
                static fn ($constantString) => enum_exists($constantString->getValue())
                    ? $constantString->getValue()
                    : null,
            )
            : null;
    }

    protected function isInherentlyMultiple(?string $componentClass): bool
    {
        return $componentClass === 'Filament\Forms\Components\CheckboxList';
    }

    /**
     * @return list<string|int>|null
     */
    protected function extractLiteralOptionKeys(Expr $arg): ?array
    {
        if (! $arg instanceof Array_) {
            return null;
        }

        /** @var list<string|int> $keys */
        $keys = array_reduce($arg->items, static function (array $carry, $item) {
            if ($item->key instanceof String_ || $item->key instanceof Int_) {
                $carry[] = $item->key->value;
            }

            return $carry;
        }, []);

        return $keys !== [] ? $keys : null;
    }
}
