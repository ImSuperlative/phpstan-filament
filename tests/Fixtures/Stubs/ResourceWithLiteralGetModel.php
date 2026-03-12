<?php

namespace ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs;

use Filament\Resources\Resource;

class ResourceWithLiteralGetModel extends Resource
{
    public static function getModel(): string
    {
        return TestModel::class;
    }
}
