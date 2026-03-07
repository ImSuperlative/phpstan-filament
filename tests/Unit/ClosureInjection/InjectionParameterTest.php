<?php

use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\InjectionParameter;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

it('stores name and type', function () {
    $param = new InjectionParameter('record', new ObjectType('Illuminate\Database\Eloquent\Model'));

    expect($param->name)->toBe('record')
        ->and($param->type)->toBeInstanceOf(ObjectType::class);
});

it('supports string types', function () {
    $param = new InjectionParameter('operation', new StringType);

    expect($param->name)->toBe('operation')
        ->and($param->type)->toBeInstanceOf(StringType::class);
});
