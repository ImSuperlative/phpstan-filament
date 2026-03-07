<?php

namespace ImSuperlative\FilamentPhpstan\Rules;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @template TNode of MethodCall
 *
 * @implements Rule<TNode>
 */
final class RedundantEnumRule implements Rule
{
    public function __construct(
        protected bool $redundantEnum,
        protected FilamentClassHelper $filamentClassHelper,
    ) {}

    /** @return class-string<TNode> */
    public function getNodeType(): string
    {
        /** @var class-string<TNode> */
        return MethodCall::class;
    }

    /**
     * @param  TNode  $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->shouldProcess($node, $scope)) {
            return [];
        }

        if (! $this->chainHasBothEnumAndOptions($node, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Calling ->enum() is unnecessary when ->options() receives an enum class.'
            )
                ->identifier('filamentPhpstan.redundantEnum')
                ->tip('Remove the ->enum() call. When ->options() receives an enum class, it calls ->enum() automatically.')
                ->build(),
        ];
    }

    protected function shouldProcess(MethodCall $node, Scope $scope): bool
    {
        return $this->redundantEnum
            && $node->name instanceof Identifier
            && ($node->name->name === 'enum' || $node->name->name === 'options')
            && $this->hasOptionsReceiver($node, $scope);
    }

    protected function chainHasBothEnumAndOptions(MethodCall $node, Scope $scope): bool
    {
        /** @var Identifier $name */
        $name = $node->name;

        $hasEnum = $name->name === 'enum';
        $hasOptionsWithEnum = $name->name === 'options' && $this->isEnumArg($node, $scope);

        $current = $node->var;

        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier) {
                $innerName = $current->name->name;

                if (! $hasEnum && $innerName === 'enum') {
                    $hasEnum = true;
                } elseif (! $hasOptionsWithEnum && $innerName === 'options' && $this->isEnumArg($current, $scope)) {
                    $hasOptionsWithEnum = true;
                }

                if ($hasEnum && $hasOptionsWithEnum) {
                    return true;
                }
            }

            $current = $current->var;
        }

        return false;
    }

    protected function hasOptionsReceiver(MethodCall $node, Scope $scope): bool
    {
        return array_any(
            $scope->getType($node->var)->getObjectClassNames(),
            fn (string $class) => $this->filamentClassHelper->hasOptions($class),
        );
    }

    protected function isEnumArg(MethodCall $call, Scope $scope): bool
    {
        $arg = AstHelper::firstArgValue($call);

        if (! $arg instanceof ClassConstFetch) {
            return false;
        }

        return array_any(
            $scope->getType($arg)->getConstantStrings(),
            static fn ($constantString) => enum_exists($constantString->getValue()),
        );
    }
}
