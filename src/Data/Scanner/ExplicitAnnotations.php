<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final readonly class ExplicitAnnotations
{
    /**
     * @param  ?string  $model  Resolved model FQCN from @filament-model
     * @param  array<string, ?string>  $pageModels  page FQCN => model FQCN|null from @filament-page
     * @param  list<string>  $states  field names from @filament-state
     * @param  list<string>  $fields  field names from @filament-field
     */
    public function __construct(
        public ?string $model = null,
        public array $pageModels = [],
        public array $states = [],
        public array $fields = [],
    ) {}
}
