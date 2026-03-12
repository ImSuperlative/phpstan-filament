<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\Fixers;

use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;
use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;

final class AddFilamentPageAttributeFixer
{
    /**
     * @param  list<FilamentPageAnnotation>  $annotations
     */
    public static function fix(ClassLike $class, array $annotations): ClassLike
    {
        foreach ($annotations as $annotation) {
            $args = [new Arg(new ClassConstFetch(new FullyQualified((string) $annotation->pageType()), new Identifier('class')))];

            $modelType = $annotation->modelType();
            if ($modelType !== null) {
                $args[] = new Arg(
                    value: new ClassConstFetch(new FullyQualified((string) $modelType), new Identifier('class')),
                    name: new Identifier('model')
                );
            }

            $class->attrGroups[] = new AttributeGroup([
                new Attribute(new FullyQualified(FilamentPage::class), $args),
            ]);
        }

        return $class;
    }
}
