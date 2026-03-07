<?php

use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

pest()->extend(TypeInferenceTestCase::class)
    ->in('Unit');

pest()->extend(ConfigurableRuleTestCase::class)
    ->in('Rules');
