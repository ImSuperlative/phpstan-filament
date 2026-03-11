<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Support;

use ImSuperlative\PhpstanFilament\Rules\RelationshipValidationRule;

interface RelationshipValidationRuleFactory
{
    public function create(bool $relationship): RelationshipValidationRule;
}