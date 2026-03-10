<?php

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;

beforeAll(function () {
    ConfigurableRuleTestCase::useRule(ConfigurableRuleTestCase::buildRule(FieldValidationLevel::Level_0));
});

it('skips all validation at level 0', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});
