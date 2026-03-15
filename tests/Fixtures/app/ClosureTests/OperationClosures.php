<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Feature #5: $operation should be typed as 'create'|'edit'|'view'.
 *
 * This means PHPStan can catch impossible comparisons like:
 * - $operation === 'update' (should be 'edit')
 * - $operation === 'editt' (typo)
 */
class OperationClosures
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Valid usage — should NOT error
            TextInput::make('title')
                ->disabled(fn ($operation): bool => $operation === 'view')
                ->required(fn ($operation): bool => $operation === 'create')
                ->visible(fn ($operation): bool => $operation !== 'view'),

            // Invalid usage — SHOULD error (impossible comparison)
            TextInput::make('slug')
                ->disabled(fn ($operation): bool => $operation === 'update')
                ->visible(fn ($operation): bool => $operation === 'editt'),

            // Using $context alias — same type as $operation
            TextInput::make('body')
                ->disabled(fn ($context): bool => $context === 'view'),
        ]);
    }
}
