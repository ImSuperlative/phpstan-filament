<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentModel;

class MethodLevelAnnotation
{
    #[FilamentModel('Fixtures\App\Models\Post')]
    public static function configure(): void {}
}
