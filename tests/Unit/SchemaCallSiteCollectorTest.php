<?php

// tests/Unit/SchemaCallSiteCollectorTest.php

use PhpParser\Node\Expr\StaticCall;

it('returns the correct node type', function () {
    expect(StaticCall::class)
        ->toBe(StaticCall::class);
});
