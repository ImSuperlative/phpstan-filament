<?php

namespace ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs;

use Filament\Resources\Resource;

class ResourceWithPhpDocGetModel extends Resource
{
    /** @return class-string<TestModel> */
    public static function getModel(): string
    {
        return 'dynamic';
    }
}
