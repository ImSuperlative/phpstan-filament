<?php

it('resolves dot-notation state types at makeFieldValidation level 3', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('dot-notation-types');
