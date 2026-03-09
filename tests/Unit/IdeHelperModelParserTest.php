<?php

use ImSuperlative\FilamentPhpstan\Parser\TypeStringParser;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperModelParser;
use PHPStan\Parser\Parser;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    $typeStringParser = TypeStringParser::make();

    $this->parser = new IdeHelperModelParser($parser, $typeStringParser->getLexer(), $typeStringParser->getPhpDocParser());
});

function describeType(Type $type): string
{
    return $type->describe(VerbosityLevel::precise());
}

it('parses all 7 models from barryvdh file', function () {
    $result = $this->parser->parseFile(__DIR__.'/../Fixtures/App/Models/_ide_helper_models.php');

    expect($result)->toHaveCount(7)
        ->and(array_keys($result))->toBe([
            'Fixtures\App\Models\Activity',
            'Fixtures\App\Models\Author',
            'Fixtures\App\Models\Category',
            'Fixtures\App\Models\Comment',
            'Fixtures\App\Models\Email',
            'Fixtures\App\Models\Post',
            'Fixtures\App\Models\Tag',
        ]);
});

it('extracts @property with correct types from barryvdh file', function () {
    $result = $this->parser->parseFile(__DIR__.'/../Fixtures/App/Models/_ide_helper_models.php');

    $post = $result['Fixtures\App\Models\Post'];

    expect(describeType($post->properties['id']->type))->toBe('string')
        ->and($post->properties['id']->readOnly)->toBeFalse()
        ->and(describeType($post->properties['is_featured']->type))->toBe('bool')
        ->and(describeType($post->properties['rating']->type))->toBe('float|null');
});

it('extracts @property-read as readOnly from barryvdh file', function () {
    $result = $this->parser->parseFile(__DIR__.'/../Fixtures/App/Models/_ide_helper_models.php');

    $post = $result['Fixtures\App\Models\Post'];

    expect($post->properties['author']->readOnly)->toBeTrue()
        ->and($post->properties['comments_count']->readOnly)->toBeTrue()
        ->and($post->properties['display_name']->readOnly)->toBeTrue();

    // author is Author|null
    $authorType = $post->properties['author']->type;
    expect(describeType($authorType))->toBe('Fixtures\App\Models\Author|null');
});

it('extracts @method with static flag and return type from barryvdh file', function () {
    $result = $this->parser->parseFile(__DIR__.'/../Fixtures/App/Models/_ide_helper_models.php');

    $post = $result['Fixtures\App\Models\Post'];

    expect($post->methods)->toHaveKey('newModelQuery')
        ->and($post->methods['newModelQuery']->isStatic)->toBeTrue();

    // Return type is Builder|Post (union)
    $returnType = describeType($post->methods['newModelQuery']->returnType);
    expect($returnType)->toContain('Illuminate\Database\Eloquent\Builder')
        ->and($returnType)->toContain('Fixtures\App\Models\Post');
});

it('parses Laravel Idea format correctly', function () {
    $result = $this->parser->parseFile(
        dirname(__DIR__, 2).'/vendor/_laravel_idea/_ide_helper_models_Fixtures_App_Models.php'
    );

    expect($result)->toHaveCount(7);

    $comment = $result['Fixtures\App\Models\Comment'];
    expect(describeType($comment->properties['body']->type))->toBe('string')
        ->and($comment->properties['body']->readOnly)->toBeFalse();

    $post = $result['Fixtures\App\Models\Post'];
    expect($post->properties['display_name']->readOnly)->toBeTrue()
        ->and(describeType($post->properties['display_name']->type))->toBe('string');
});

it('returns empty array for non-existent file', function () {
    $result = $this->parser->parseFile('/non/existent/file.php');

    expect($result)->toBe([]);
});

it('resolves short type names using use imports', function () {
    $result = $this->parser->parseFile(
        dirname(__DIR__, 2).'/vendor/_laravel_idea/_ide_helper_models_Fixtures_App_Models.php'
    );

    $author = $result['Fixtures\App\Models\Author'];

    // Carbon is imported via use statement — should resolve to ObjectType
    $createdAtDesc = describeType($author->properties['created_at']->type);
    expect($createdAtDesc)->toContain('Illuminate\Support\Carbon');
});

it('extracts non-static methods from Laravel Idea format', function () {
    $result = $this->parser->parseFile(
        dirname(__DIR__, 2).'/vendor/_laravel_idea/_ide_helper_models_Fixtures_App_Models.php'
    );

    $post = $result['Fixtures\App\Models\Post'];

    expect($post->methods)->toHaveKey('author')
        ->and($post->methods['author']->isStatic)->toBeFalse();

    $returnDesc = describeType($post->methods['author']->returnType);
    expect($returnDesc)->toContain('BelongsTo');
});
