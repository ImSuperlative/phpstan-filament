<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules;

use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Rules\Fixers\AddFilamentPageAttributeFixer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
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
        protected VirtualAnnotationProvider $virtualAnnotationProvider,
        protected AnnotationReader $annotationReader,
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

        $virtualAnnotations = $this->virtualAnnotationProvider->getPageAnnotations($classReflection->getName());
        if ($virtualAnnotations === []) {
            return [];
        }

        $explicitPageTypes = array_map(
            fn ($annotation) => (string) $annotation->pageType(),
            $this->annotationReader->readPageAnnotations($classReflection),
        );

        $missingAnnotations = array_values(array_filter(
            $virtualAnnotations,
            fn ($annotation) => ! in_array((string) $annotation->pageType(), $explicitPageTypes, true),
        ));

        if ($missingAnnotations === []) {
            return [];
        }

        $callerNames = array_map(
            fn ($annotation) => (string) $annotation->pageType(),
            $missingAnnotations,
        );

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Class %s is missing a @filament-page annotation. Inferred from: %s.',
                    $classReflection->getDisplayName(),
                    implode(', ', $callerNames),
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
