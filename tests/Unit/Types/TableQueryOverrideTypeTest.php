<?php

it('resolves model from Table::query() override instead of resource', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('table-query-override-types');
