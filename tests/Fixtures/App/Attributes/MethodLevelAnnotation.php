<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\FilamentPhpstan\Attributes\FilamentModel;

class MethodLevelAnnotation
{
    #[FilamentModel('Fixtures\App\Models\Post')]
    public static function configure(): void {}
}
