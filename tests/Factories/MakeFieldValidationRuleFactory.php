<?php

namespace ImSuperlative\PhpstanFilament\Tests\Factories;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\AggregateFieldValidator;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\MakeFieldValidationRule;

interface MakeFieldValidationRuleFactory
{
    public function create(FieldValidationLevel $level, AggregateFieldValidator $aggregateFieldValidator): MakeFieldValidationRule;
}
