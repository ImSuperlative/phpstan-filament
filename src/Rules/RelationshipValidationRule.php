<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Rules;

use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * @template TNode of MethodCall
 *
 * @implements Rule<TNode>
 */
final class RelationshipValidationRule implements Rule
{
    public function __construct(
        protected bool $relationship,
        protected ModelReflectionHelper $modelReflectionHelper,
        protected FilamentClassHelper $filamentClassHelper,
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
     * @return list<IdentifierRuleError>
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->shouldProcess($node) || ! $this->isFilamentReceiver($node, $scope)) {
            return [];
        }

        $relationshipName = AstHelper::firstArgValueAs($node, String_::class)?->value;
        $modelClass = $this->componentContextResolver->resolveModelClassFromScope($scope);

        if ($relationshipName === null || $modelClass === null) {
            return [];
        }

        return $this->validateRelationship($relationshipName, $modelClass, $scope);
    }

    /** @param TNode $node */
    protected function shouldProcess(MethodCall $node): bool
    {
        return $this->relationship
            && $node->name instanceof Identifier
            && $node->name->name === 'relationship';
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateRelationship(string $relationshipName, string $modelClass, Scope $scope): array
    {
        if ($this->modelReflectionHelper->isRelationship($modelClass, $relationshipName, $scope) === true) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf("'%s' is not a relationship on %s.", $relationshipName, $modelClass)
            )
                ->identifier('filamentPhpstan.relationship')
                ->build(),
        ];
    }

    /** @param TNode $node */
    protected function isFilamentReceiver(MethodCall $node, Scope $scope): bool
    {
        return array_any(
            $scope->getType($node->var)->getObjectClassNames(),
            fn (string $class) => $this->filamentClassHelper->isFormField($class)
                || $this->filamentClassHelper->isSchemaComponent($class),
        );
    }
}
