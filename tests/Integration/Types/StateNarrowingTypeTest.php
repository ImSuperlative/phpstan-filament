<?php

it('narrows $state type from method chain calls', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('state-narrowing-types');
