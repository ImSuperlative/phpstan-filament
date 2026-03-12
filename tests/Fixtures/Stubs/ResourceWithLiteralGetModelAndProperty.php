<?php

namespace ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs;

use Filament\Resources\Resource;

class ResourceWithLiteralGetModelAndProperty extends Resource
{
    protected static ?string $model = TestModel::class;

    public static function getModel(): string
    {
        return self::$model;
    }
}
