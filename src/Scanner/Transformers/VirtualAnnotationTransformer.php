<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers;

use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\VirtualAnnotations;
use ImSuperlative\PhpstanFilament\Scanner\EnrichmentTransformer;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

final class VirtualAnnotationTransformer implements EnrichmentTransformer
{
    public function __construct(
        protected bool $enabled,
        protected bool $warnOnVirtual,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        if (! $this->enabled && ! $this->warnOnVirtual) {
            return $result;
        }

        $componentToResources = $result->get(ComponentToResources::class);
        $resourceModels = $result->get(ResourceModels::class);

        if ($componentToResources === null) {
            return $result;
        }

        $annotations = [];

        foreach ($componentToResources->all() as $componentClass => $resourceClasses) {
            foreach ($resourceClasses as $resourceClass) {
                $model = $resourceModels?->get($resourceClass);
                $pageTypeNode = new IdentifierTypeNode($resourceClass);

                $typeNode = $model !== null
                    ? new GenericTypeNode($pageTypeNode, [new IdentifierTypeNode($model)])
                    : $pageTypeNode;

                $annotations[$componentClass][] = new FilamentPageAnnotation(type: $typeNode);
            }
        }

        return $result->set(new VirtualAnnotations($annotations));
    }
}
