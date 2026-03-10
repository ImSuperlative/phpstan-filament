<?php

it('loads the extension and analyses a fixture without errors', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('smoke-test');
