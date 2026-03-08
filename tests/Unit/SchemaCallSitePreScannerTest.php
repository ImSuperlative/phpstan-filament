<?php

use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSitePreScanner;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;

it('discovers configure() call sites from fixture files', function () {
    $scanner = new SchemaCallSitePreScanner(
        enabled: true,
        filamentPath: '',
        currentWorkingDirectory: '',
        analysedPaths: [__DIR__.'/../Fixtures'],
    );

    $callerMap = $scanner->getCallerMap();

    // SchemaCallSite.php has: TestResourceUsingSchema calls TestSchemaClass::configure()
    expect($callerMap)->toHaveKey('ImSuperlative\FilamentPhpstan\Tests\Fixtures\TestSchemaClass');
    expect($callerMap['ImSuperlative\FilamentPhpstan\Tests\Fixtures\TestSchemaClass'])
        ->toContain('ImSuperlative\FilamentPhpstan\Tests\Fixtures\TestResourceUsingSchema');
});

it('returns empty map when disabled', function () {
    $scanner = new SchemaCallSitePreScanner(
        enabled: false,
        filamentPath: '',
        currentWorkingDirectory: '',
        analysedPaths: [__DIR__.'/../Fixtures'],
    );

    expect($scanner->getCallerMap())->toBe([]);
});

it('caches results across multiple calls', function () {
    $scanner = new SchemaCallSitePreScanner(
        enabled: true,
        filamentPath: '',
        currentWorkingDirectory: '',
        analysedPaths: [__DIR__.'/../Fixtures/SchemaCallSite.php'],
    );

    $first = $scanner->getCallerMap();
    $second = $scanner->getCallerMap();

    expect($first)->toBe($second);
});

it('populates registry via ensurePreScanned', function () {
    $scanner = new SchemaCallSitePreScanner(
        enabled: true,
        filamentPath: '',
        currentWorkingDirectory: '',
        analysedPaths: [__DIR__.'/../Fixtures/SchemaCallSite.php'],
    );

    $registry = new SchemaCallSiteRegistry($scanner);

    $callers = $registry->getCallersForClass('ImSuperlative\FilamentPhpstan\Tests\Fixtures\TestSchemaClass');

    expect($callers)->toContain('ImSuperlative\FilamentPhpstan\Tests\Fixtures\TestResourceUsingSchema');
});

it('handles empty analysedPaths gracefully', function () {
    $scanner = new SchemaCallSitePreScanner(enabled: true, filamentPath: '', currentWorkingDirectory: '', analysedPaths: []);

    expect($scanner->getCallerMap())->toBe([]);
});

it('handles nonexistent paths gracefully', function () {
    $scanner = new SchemaCallSitePreScanner(enabled: true, filamentPath: '', currentWorkingDirectory: '', analysedPaths: ['/nonexistent/path']);

    expect($scanner->getCallerMap())->toBe([]);
});
