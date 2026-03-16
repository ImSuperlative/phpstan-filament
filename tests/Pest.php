<?php

use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use ImSuperlative\PhpstanFilament\Tests\TestCase;
use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;

pest()->extend(TypeInferenceTestCase::class)
    ->in('Unit');

// pest()->extend(ConfigurableRuleTestCase::class)->in('Rules');

pest()->extend(\ImSuperlative\PhpstanFilament\Tests\Traits\TracksMemory::class);
