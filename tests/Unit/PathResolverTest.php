<?php

use ImSuperlative\PhpstanFilament\Support\PathResolver;

describe('partition', function () {
    it('partitions paths into include and exclude', function () {
        $result = PathResolver::partition(
            ['app/filament', '!app/filament/shared'],
            '/project',
        );

        expect($result['include'])->toBe(['/project/app/filament'])
            ->and($result['exclude'])->toBe(['/project/app/filament/shared']);
    });

    it('returns empty exclude when no exclusions', function () {
        $result = PathResolver::partition(['app/filament'], '/project');

        expect($result['include'])->toBe(['/project/app/filament'])
            ->and($result['exclude'])->toBe([]);
    });

    it('handles multiple includes and excludes', function () {
        $result = PathResolver::partition(
            ['app/filament', 'app/livewire', '!app/filament/debug', '!app/livewire/temp'],
            '/project',
        );

        expect($result['include'])->toHaveCount(2)
            ->and($result['exclude'])->toHaveCount(2);
    });
});

describe('includePaths / excludePatterns', function () {
    it('includePaths returns only non-excluded paths', function () {
        $paths = PathResolver::includePaths(
            ['app/filament', '!app/filament/shared'],
            '/project',
        );

        expect($paths)->toBe(['/project/app/filament']);
    });

    it('excludePatterns strips the ! prefix', function () {
        $patterns = PathResolver::excludePatterns(
            ['app/filament', '!app/filament/shared'],
            '/project',
        );

        expect($patterns)->toBe(['/project/app/filament/shared']);
    });
});

describe('isExcluded', function () {
    it('excludes exact file match', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/debug.php',
            ['/project/app/filament/debug.php'],
        );

        expect($excluded)->toBeTrue();
    });

    it('excludes files within a directory', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/shared/Helper.php',
            ['/project/app/filament/shared'],
        );

        expect($excluded)->toBeTrue();
    });

    it('does not exclude files outside a directory', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/Resource.php',
            ['/project/app/filament/shared'],
        );

        expect($excluded)->toBeFalse();
    });

    it('excludes files matching a glob pattern', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/posts/resources/schema/FormSchema.php',
            ['/project/app/filament/*/resources/schema/*'],
        );

        expect($excluded)->toBeTrue();
    });

    it('does not exclude files not matching a glob pattern', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/posts/resources/PostResource.php',
            ['/project/app/filament/*/resources/schema/*'],
        );

        expect($excluded)->toBeFalse();
    });

    it('handles multiple exclusion patterns', function () {
        $patterns = [
            '/project/app/filament/debug.php',
            '/project/app/filament/*/schema/*',
        ];

        expect(PathResolver::isExcluded('/project/app/filament/debug.php', $patterns))->toBeTrue()
            ->and(PathResolver::isExcluded('/project/app/filament/posts/schema/Form.php', $patterns))->toBeTrue()
            ->and(PathResolver::isExcluded('/project/app/filament/PostResource.php', $patterns))->toBeFalse();
    });

    it('does not false-positive on directory prefix substring', function () {
        $excluded = PathResolver::isExcluded(
            '/project/app/filament/shared-utils/Helper.php',
            ['/project/app/filament/shared'],
        );

        expect($excluded)->toBeFalse();
    });
});

describe('globRecursive', function () {
    it('finds php files recursively in fixtures', function () {
        $fixtureDir = fixture_path('App/MakeFieldTests');

        $files = PathResolver::globRecursive($fixtureDir, '*.php');

        expect($files)->not->toBeEmpty()
            ->and($files)->each->toEndWith('.php');
    });

    it('only returns files matching the pattern', function () {
        $fixtureDir = fixture_path('');

        $files = PathResolver::globRecursive($fixtureDir, '*.neon');
        $phpFiles = PathResolver::globRecursive($fixtureDir, '*.php');

        expect($files)->each->toEndWith('.neon')
            ->and($phpFiles)->each->toEndWith('.php');
    });
});
