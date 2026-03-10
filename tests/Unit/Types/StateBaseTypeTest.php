<?php

it('types $state based on component class from ComponentStateMap', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('state-base-types');
