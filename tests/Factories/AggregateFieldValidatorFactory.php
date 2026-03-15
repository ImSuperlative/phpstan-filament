<?php

namespace ImSuperlative\PhpstanFilament\Tests\Factories;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\AggregateFieldValidator;

interface AggregateFieldValidatorFactory
{
    public function create(FieldValidationLevel $level): AggregateFieldValidator;
}
