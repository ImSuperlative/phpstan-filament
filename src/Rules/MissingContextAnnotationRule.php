<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules;

use ImSuperlative\PhpstanFilament\Data\FilamentTagAnnotation;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentContext;
use ImSuperlative\PhpstanFilament\Rules\Fixers\AddFilamentPageAttributeFixer;
use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectIndex;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @template TNode of InClassNode
 *
 * @implements Rule<TNode>
 */
final class MissingContextAnnotationRule implements Rule
{
    public function __construct(
        protected bool $checkMissingContext,
        protected FilamentProjectIndex $projectIndex,
    ) {}

    /** @return class-string<TNode> */
    public function getNodeType(): string
    {
        /** @var class-string<TNode> */
        return InClassNode::class;
    }

    /**
     * @param  TNode  $node
     * @return list<FixableNodeRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->checkMissingContext) {
            return [];
        }

        $classReflection = $node->getClassReflection();
        $className = $classReflection->getName();

        // Get inferred pages from ComponentContext
        $componentNode = $this->projectIndex->get(ComponentContext::class)?->get($className);
        if ($componentNode === null || $componentNode->pageModels === []) {
            return [];
        }

        // Get explicit page annotations from ComponentAnnotations
        $explicit = $this->projectIndex->get(ComponentAnnotations::class)
            ?->get($className);
        $explicitPageTypes = [];
        if ($explicit !== null) {
            foreach ($explicit->pages as $annotation) {
                $explicitPageTypes[] = (string) $annotation->pageType();
            }
        }

        // Find inferred pages not covered by explicit annotations
        $missingPageModels = array_filter(
            $componentNode->pageModels,
            fn (?string $model, string $page) => ! in_array($page, $explicitPageTypes, true),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($missingPageModels === []) {
            return [];
        }

        // Build FilamentPageAnnotation objects for the fixer
        $missingAnnotations = [];
        foreach ($missingPageModels as $page => $model) {
            $type = $model !== null
                ? new GenericTypeNode(new IdentifierTypeNode($page), [new IdentifierTypeNode($model)])
                : new IdentifierTypeNode($page);

            $missingAnnotations[] = new FilamentTagAnnotation(type: $type)->toPageAnnotation();
        }

        $missingPages = array_keys($missingPageModels);

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Class %s is missing a @filament-page annotation. Inferred from: %s.',
                    $classReflection->getDisplayName(),
                    implode(', ', $missingPages),
                )
            )
                ->identifier('PhpstanFilament.missingContext')
                ->tip('Add a @filament-page annotation or #[FilamentPage] attribute to make the context explicit.')
                ->fixNode(
                    $node->getOriginalNode(),
                    fn (ClassLike $class) => AddFilamentPageAttributeFixer::fix($class, $missingAnnotations),
                )
                ->build(),
        ];
    }
}
