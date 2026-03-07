<?php

use ImSuperlative\FilamentPhpstan\Collectors\VirtualFieldRegistry;

it('registers and checks virtual fields', function () {
    $registry = new VirtualFieldRegistry;
    $registry->registerVirtual('App\Resource::table', 'custom_field');

    expect($registry->isVirtual('App\Resource::table', 'custom_field'))->toBeTrue()
        ->and($registry->isVirtual('App\Resource::table', 'other_field'))->toBeFalse()
        ->and($registry->isVirtual('App\Other::table', 'custom_field'))->toBeFalse();
});

it('registers and checks skipped scopes', function () {
    $registry = new VirtualFieldRegistry;
    $registry->registerSkippedScope('App\Resource::table');

    expect($registry->isScopeSkipped('App\Resource::table'))->toBeTrue()
        ->and($registry->isScopeSkipped('App\Other::table'))->toBeFalse();
});
