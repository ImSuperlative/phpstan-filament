<?php

use ImSuperlative\PhpstanFilament\Resolvers\ResourceModelResolver;
use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);

    $this->provider = new VirtualAnnotationProvider(
        enabled: true,
        filamentPath: [],
        currentWorkingDirectory: '',
        analysedPaths: [],
        analysedPathsFromConfig: [],
        resourceModelResolver: new ResourceModelResolver(
            $reflectionProvider,
            new FilamentClassHelper($reflectionProvider),
            new ModelReflectionHelper($reflectionProvider),
        ),
    );
});

it('flattens a transitive caller map', function () {
    $callerMap = [
        'SharedHelper' => ['TableConfig'],
        'TableConfig' => ['PostResource', 'CommentResource'],
        'PostResource' => ['SomePanel'],
    ];

    $reflection = new ReflectionMethod($this->provider, 'flattenCallerMap');

    $result = $reflection->invoke($this->provider, $callerMap);

    // SharedHelper should now include transitive callers
    expect($result['SharedHelper'])->toContain('TableConfig', 'PostResource', 'CommentResource', 'SomePanel');
    // TableConfig should include its transitive callers
    expect($result['TableConfig'])->toContain('PostResource', 'CommentResource', 'SomePanel');
    // Leaf entries stay unchanged
    expect($result['PostResource'])->toBe(['SomePanel']);
});

it('handles circular references without infinite loop', function () {
    $callerMap = [
        'A' => ['B'],
        'B' => ['A'], // circular
    ];

    $reflection = new ReflectionMethod($this->provider, 'flattenCallerMap');

    $result = $reflection->invoke($this->provider, $callerMap);

    expect($result['A'])->toContain('B');
    expect($result['B'])->toContain('A');
});
