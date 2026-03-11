<?php

use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getProvider(): VirtualAnnotationProvider
{
    return PhpstanTestCase::getContainer()->getByType(VirtualAnnotationProvider::class);
}

it('flattens a transitive caller map', function () {
    $result = getProvider()->flattenCallerMap([
        'SharedHelper' => ['TableConfig'],
        'TableConfig' => ['PostResource', 'CommentResource'],
        'PostResource' => ['SomePanel'],
    ]);

    expect($result['SharedHelper'])->toContain('TableConfig', 'PostResource', 'CommentResource', 'SomePanel')
        ->and($result['TableConfig'])->toContain('PostResource', 'CommentResource', 'SomePanel')
        ->and($result['PostResource'])->toBe(['SomePanel']);
});

it('handles circular references without infinite loop', function () {
    $result = getProvider()->flattenCallerMap([
        'A' => ['B'],
        'B' => ['A'],
    ]);

    expect($result['A'])->toContain('B')
        ->and($result['B'])->toContain('A');
});
