<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentChainResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentStateMap;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentTypeNarrower;
use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ActionDataHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $actionData,
        protected readonly FormComponentStateMap $componentStateMap,
        protected readonly FormComponentTypeNarrower $typeNarrower,
        protected readonly FormComponentChainResolver $chainResolver,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName)) {
            return null;
        }

        $fields = $this->extractSchemaComponents($context->methodCall->var, $context->scope);

        return $fields !== null
            ? $this->buildArrayShapeType($fields)
            : null;
    }

    protected function shouldResolveType(string $paramName): bool
    {
        return $this->actionData && $paramName === 'data';
    }

    /**
     * @return list<array{string, Type}>|null
     */
    protected function extractSchemaComponents(Expr $expr, Scope $scope): ?array
    {
        $array = AstHelper::findInMethodChain(
            $expr,
            static fn (MethodCall $call) => $call->name instanceof Identifier && in_array($call->name->toString(), ['form', 'schema'], true)
                ? AstHelper::firstArgValueAs($call, Array_::class)
                : null,
        );

        return $array !== null ? $this->extractFieldsFromArray($array, $scope) : null;
    }

    /**
     * @return list<array{string, Type}>
     */
    protected function extractFieldsFromArray(Array_ $array, Scope $scope): array
    {
        $fields = [];

        foreach ($array->items as $item) {
            $field = $this->extractField($item->value, $scope);

            if ($field !== null) {
                $fields[] = $field;

                continue;
            }

            $nested = $this->extractNestedSchemaFields($item->value, $scope);

            if ($nested !== null) {
                array_push($fields, ...$nested);
            }
        }

        return $fields;
    }

    /**
     * @return list<array{string, Type}>|null
     */
    protected function extractNestedSchemaFields(Expr $expr, Scope $scope): ?array
    {
        $nestedArray = null;
        $statePath = null;

        AstHelper::findInMethodChain(
            $expr,
            static function (MethodCall $call) use (&$nestedArray, &$statePath) {
                if (! $call->name instanceof Identifier) {
                    return null;
                }

                $name = $call->name->toString();

                if ($name === 'schema' && $nestedArray === null) {
                    $nestedArray = AstHelper::firstArgValueAs($call, Array_::class);
                }

                if ($name === 'statePath' && $statePath === null) {
                    $statePath = AstHelper::firstArgValueAs($call, String_::class)?->value;
                }

                return null; // always continue walking
            },
        );

        if ($nestedArray === null) {
            return null;
        }

        $fields = $this->extractFieldsFromArray($nestedArray, $scope);

        if ($statePath !== null && $fields !== []) {
            return [[$statePath, $this->buildArrayShapeType($fields)]];
        }

        return $fields;
    }

    /**
     * @return array{string, Type}|null
     */
    protected function extractField(Expr $expr, Scope $scope): ?array
    {
        $analysis = $this->chainResolver->resolve($expr, $scope);

        if ($analysis->fieldName === null || $analysis->componentClass === null) {
            return null;
        }

        $baseType = $this->componentStateMap->resolveForClass($analysis->componentClass);

        if ($baseType === null) {
            return null;
        }

        $stateType = $this->typeNarrower->narrowWithOptions($analysis, $baseType);

        if (in_array('required', $analysis->methodCalls, true)) {
            $stateType = TypeCombinator::removeNull($stateType);
        }

        return [$analysis->fieldName, $stateType];
    }

    /**
     * @param  list<array{string, Type}>  $fields
     */
    protected function buildArrayShapeType(array $fields): Type
    {
        return new ConstantArrayType(
            array_map(fn (array $field) => new ConstantStringType($field[0]), $fields),
            array_map(fn (array $field) => $field[1], $fields),
        );
    }
}
