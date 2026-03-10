<?php

use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;

pest()->extend(TypeInferenceTestCase::class)
    ->in('Unit');

pest()->extend(ConfigurableRuleTestCase::class)
    ->in('Rules');
