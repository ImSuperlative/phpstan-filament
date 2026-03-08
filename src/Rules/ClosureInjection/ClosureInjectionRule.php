<?php

namespace ImSuperlative\FilamentPhpstan\Rules\ClosureInjection;

use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\StateTypeResolver;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType as PHPStanIntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType as PHPStanUnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

/**
 * @template TNode of MethodCall
 *
 * @implements Rule<TNode>
 */
final class ClosureInjectionRule implements Rule
{
    public const string IDENTIFIER_NAME = 'filamentPhpstan.closureInjection.name';

    public const string IDENTIFIER_TYPE = 'filamentPhpstan.closureInjection.type';

    public const string IDENTIFIER_RESERVED = 'filamentPhpstan.closureInjection.reserved';

    public function __construct(
        protected bool $closureInjection,
        protected bool $reservedClosureInjection,
        protected TypedInjectionMap $injectionMap,
        protected FilamentClassHelper $filamentClassHelper,
        protected ReflectionProvider $reflectionProvider,
        protected StateTypeResolver $stateTypeResolver,
        protected ComponentContextResolver $componentContextResolver,
    ) {}

    /** @return class-string<TNode> */
    public function getNodeType(): string
    {
        /** @var class-string<TNode> */
        return MethodCall::class;
    }

    /**
     * @param  TNode  $node
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->shouldProcess($node)) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        $resolvedClass = $this->resolveInjectionClass($callerType->getObjectClassNames());

        if ($resolvedClass === null) {
            return [];
        }

        $methodName = $node->name->name;
        $methodAdditions = $this->injectionMap->getMethodAdditions($methodName);

        $errors = [];

        foreach ($this->extractClosureParams($node) as $param) {
            $paramErrors = $this->validateParam($param, $resolvedClass, $methodAdditions, $scope, $node);
            foreach ($paramErrors as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /** @phpstan-assert-if-true Identifier $node->name */
    protected function shouldProcess(MethodCall $node): bool
    {
        return $this->closureInjection && $node->name instanceof Identifier;
    }

    /**
     * @param  list<string>  $classNames
     */
    protected function resolveInjectionClass(array $classNames): ?string
    {
        foreach ($classNames as $className) {
            if (! $this->filamentClassHelper->isInjectionSupported($className)) {
                continue;
            }

            if ($this->injectionMap->resolveForClass($className) !== null) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Collect all Param nodes from closure/arrow-function arguments.
     *
     * @return list<Param>
     */
    protected function extractClosureParams(MethodCall $node): array
    {
        /** @var list<Param> */
        return array_reduce(
            $node->getArgs(),
            fn (array $carry, Arg $arg) => $arg->value instanceof Closure || $arg->value instanceof ArrowFunction
                ? [...$carry, ...$arg->value->params]
                : $carry,
            [],
        );
    }

    /**
     * Validate a single closure parameter against the injection map.
     *
     * @param  list<string>  $methodAdditions
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateParam(Param $param, string $className, array $methodAdditions, Scope $scope, MethodCall $node): array
    {
        if (! $param->var instanceof Variable || ! is_string($param->var->name)) {
            return [];
        }

        return $param->type === null
            ? $this->validateUntypedParam($param->var->name, $className, $methodAdditions)
            : $this->validateTypedParam($param, $param->var->name, $className, $methodAdditions, $scope, $node);
    }

    /**
     * Untyped params: validate name is in classMap + methodAdditions.
     *
     * @param  list<string>  $methodAdditions
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateUntypedParam(string $paramName, string $className, array $methodAdditions): array
    {
        return $this->isAllowedName($paramName, $className, $methodAdditions)
            ? []
            : [$this->buildNameError($paramName, $className, $methodAdditions)];
    }

    /**
     * Typed params: validate type compatibility when name is known.
     * If name is not known, apply ByType / self-reference / container fallback rules.
     *
     * @param  list<string>  $methodAdditions
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateTypedParam(Param $param, string $paramName, string $className, array $methodAdditions, Scope $scope, MethodCall $node): array
    {
        $knownParam = $this->injectionMap->findParameter($className, $paramName);

        // Name matched a known param — check type compatibility.
        if ($knownParam !== null) {
            return $this->validateKnownParamType($param, $paramName, $className, $knownParam, $scope, $node);
        }

        // Name is a method addition — always allow.
        if (in_array($paramName, $methodAdditions, true)) {
            return [];
        }

        return $this->validateUnknownTypedParam($param, $paramName, $className, $methodAdditions, $scope);
    }

    /**
     * Validate type compatibility for a param whose name is in the injection map.
     *
     * @return list<RuleError>
     */
    protected function validateKnownParamType(Param $param, string $paramName, string $className, InjectionParameter $knownParam, Scope $scope, MethodCall $node): array
    {
        $paramType = $this->resolveTypeFromNode($param->type, $scope);

        if ($paramType === null) {
            return [];
        }

        $expectedType = $knownParam->type;

        // For state-like params with MixedType, try to resolve the actual state type
        if ($expectedType instanceof MixedType && $this->isStateParam($paramName)) {
            $resolved = $this->resolveActualStateType($node, $scope);
            if ($resolved !== null) {
                $expectedType = $resolved;
            }
        }

        if ($expectedType instanceof MixedType || $this->isTypeCompatible($paramType, $expectedType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Closure parameter '\$%s' is typed as '%s', expected '%s'.",
                $paramName,
                $paramType->describe(VerbosityLevel::typeOnly()),
                $expectedType->describe(VerbosityLevel::typeOnly()),
            ))
                ->identifier(self::IDENTIFIER_TYPE)
                ->metadata(['param' => $paramName, 'class' => $className])
                ->build(),
        ];
    }

    /**
     * Validate a typed param whose name is not in the injection map.
     * Checks ByType entries, self-reference, reserved names, and container DI fallback.
     *
     * @param  list<string>  $methodAdditions
     * @return list<RuleError>
     */
    protected function validateUnknownTypedParam(Param $param, string $paramName, string $className, array $methodAdditions, Scope $scope): array
    {
        $paramType = $this->resolveTypeFromNode($param->type, $scope);
        // Non-model object types are resolved via app()->make() — allow them.
        if ($paramType !== null && $this->isAllowedType($paramType, $className)) {
            return [];
        }

        if ($this->reservedClosureInjection && $this->injectionMap->isReservedName($paramName)) {
            return [$this->buildReservedError($paramName, $className, $methodAdditions)];
        }

        // Unknown typed param that is not an object — skip rather than false-positive.
        return [];
    }

    protected function isStateParam(string $paramName): bool
    {
        return in_array($paramName, ['state', 'old', 'oldRaw'], true);
    }

    protected function resolveActualStateType(MethodCall $node, Scope $scope): ?Type
    {
        $modelClass = $this->resolveModelClass($scope);

        return $this->stateTypeResolver->resolve($node, $scope, $modelClass);
    }

    protected function resolveModelClass(Scope $scope): ?string
    {
        return $this->componentContextResolver->resolveModelClassFromScope($scope);
    }

    /**
     * Check if two types are compatible in either direction,
     * including subtype narrowing for object types.
     */
    protected function isTypeCompatible(Type $paramType, Type $expectedType): bool
    {
        return $expectedType->isSuperTypeOf($paramType)->yes()
            || $paramType->isSuperTypeOf($expectedType)->yes()
            // Allow subtype narrowing: e.g. TextInput $component when
            // the expected type is static(Component).
            || ($paramType->isObject()->yes() && $paramType->isSuperTypeOf($expectedType)->maybe());
    }

    /**
     * Check if a param name is allowed for this context.
     *
     * @param  list<string>  $methodAdditions
     */
    protected function isAllowedName(string $paramName, string $className, array $methodAdditions): bool
    {
        $knownParams = $this->injectionMap->resolveForClass($className);
        if ($knownParams === null) {
            return true;
        }

        $knownNames = array_map(fn (InjectionParameter $p) => $p->name, $knownParams);
        $allowed = [...$knownNames, ...$methodAdditions];

        return in_array($paramName, $allowed, true);
    }

    /**
     * Build the "not a valid injection" error.
     *
     * @param  list<string>  $methodAdditions
     */
    protected function buildNameError(string $paramName, string $className, array $methodAdditions): RuleError
    {
        $validList = $this->formatValidList($className, $methodAdditions);

        return RuleErrorBuilder::message(sprintf(
            "Closure parameter '\$%s' is not a valid injection for this context. Valid parameters: %s.",
            $paramName,
            $validList,
        ))
            ->identifier(self::IDENTIFIER_NAME)
            ->metadata(['param' => $paramName, 'class' => $className])
            ->build();
    }

    /**
     * Build the "reserved name" error.
     *
     * @param  list<string>  $methodAdditions
     */
    protected function buildReservedError(string $paramName, string $className, array $methodAdditions): RuleError
    {
        $validList = $this->formatValidList($className, $methodAdditions);

        return RuleErrorBuilder::message(sprintf(
            "Closure parameter '\$%s' is not a valid injection for this context (reserved name). Valid parameters: %s.",
            $paramName,
            $validList,
        ))
            ->identifier(self::IDENTIFIER_RESERVED)
            ->metadata(['param' => $paramName, 'class' => $className])
            ->build();
    }

    /**
     * Format the "Valid parameters: $x, $y" list for error messages.
     *
     * @param  list<string>  $methodAdditions
     */
    protected function formatValidList(string $className, array $methodAdditions): string
    {
        $knownParams = $this->injectionMap->resolveForClass($className);
        $knownNames = $knownParams !== null
            ? array_map(fn (InjectionParameter $p) => $p->name, $knownParams)
            : [];
        $allowed = [...$knownNames, ...$methodAdditions];

        return implode(', ', array_map(fn (string $p) => "\$$p", $allowed));
    }

    /**
     * Check if a type is allowed via ByType entries or self-reference.
     */
    protected function isAllowedType(Type $paramType, string $className): bool
    {
        // Any object type is valid — resolved via app()->make() (container DI).
        return $paramType->isObject()->yes()
            || $this->injectionMap->isTypeAllowed($className, $paramType);
    }

    /**
     * Check if $targetClass is $className or one of its parent classes.
     */
    protected function isSelfOrParent(string $className, string $targetClass): bool
    {
        if ($className === $targetClass) {
            return true;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParentClassesNames();

        return in_array($targetClass, $parents, true);
    }

    /**
     * Convert a PhpParser type node to a PHPStan Type.
     * Returns null when the type cannot be determined.
     *
     * @param  Node|null  $typeNode
     */
    protected function resolveTypeFromNode(mixed $typeNode, Scope $scope): ?Type
    {
        return match (true) {
            $typeNode instanceof NullableType => $this->resolveNullableType($typeNode, $scope),
            $typeNode instanceof UnionType => $this->resolveCompositeType($typeNode->types, $scope, PHPStanUnionType::class),
            $typeNode instanceof IntersectionType => $this->resolveCompositeType($typeNode->types, $scope, PHPStanIntersectionType::class),
            $typeNode instanceof Name => new ObjectType($scope->resolveName($typeNode)),
            $typeNode instanceof Identifier => $this->resolveBuiltinType($typeNode->name),
            default => null,
        };
    }

    protected function resolveNullableType(NullableType $typeNode, Scope $scope): ?Type
    {
        $inner = $this->resolveTypeFromNode($typeNode->type, $scope);

        return $inner !== null
            ? new PHPStanUnionType([$inner, new NullType])
            : null;
    }

    /**
     * Resolve a union or intersection type from its parts.
     *
     * @param  array<Node>  $types
     * @param  class-string<PHPStanUnionType|PHPStanIntersectionType>  $typeClass
     */
    protected function resolveCompositeType(array $types, Scope $scope, string $typeClass): ?Type
    {
        $resolved = [];

        foreach ($types as $t) {
            $type = $this->resolveTypeFromNode($t, $scope);
            if ($type === null) {
                return null;
            }
            $resolved[] = $type;
        }

        return new $typeClass($resolved);
    }

    protected function resolveBuiltinType(string $name): ?Type
    {
        return match ($name) {
            'string' => new StringType,
            'int', 'integer' => new IntegerType,
            'float', 'double' => new FloatType,
            'bool', 'boolean' => new BooleanType,
            'array' => new ArrayType(new MixedType, new MixedType),
            'object' => new ObjectWithoutClassType,
            'null' => new NullType,
            'mixed' => new MixedType,
            'never' => new NeverType,
            'void' => new VoidType,
            'callable' => new CallableType,
            'iterable' => new IterableType(new MixedType, new MixedType),
            default => null,
        };
    }
}
