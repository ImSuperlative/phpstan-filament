<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentModel;

/**
 * @filament-model Fixtures\App\Models\Comment
 */
#[FilamentModel('Fixtures\App\Models\Post')]
class MixedAnnotations {}
