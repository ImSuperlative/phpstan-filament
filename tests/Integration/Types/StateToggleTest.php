<?php

it('leaves $state as mixed when stateClosure is disabled', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('state-toggle-types');
