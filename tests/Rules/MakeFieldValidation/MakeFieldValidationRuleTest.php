<?php

use ImSuperlative\FilamentPhpstan\FieldValidationLevel;
use ImSuperlative\FilamentPhpstan\Parser\TypeStringParser;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\AttributeAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FieldPathResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation\AggregateFieldValidator;
use ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

// Helper to build rule with given level
function buildRule(FieldValidationLevel $level): MakeFieldValidationRule
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $typeStringParser = TypeStringParser::make();

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);
    $phpDocAnnotationParser = new PhpDocAnnotationParser($typeStringParser);
    $annotationReader = new AnnotationReader(
        new AttributeAnnotationParser($typeStringParser),
        $phpDocAnnotationParser,
    );
    $fieldPathResolver = new FieldPathResolver($modelReflectionHelper, $reflectionProvider);
    $resourceModelResolver = new ResourceModelResolver($reflectionProvider, $filamentClassHelper, $modelReflectionHelper);

    return new MakeFieldValidationRule(
        level: $level,
        modelReflectionHelper: $modelReflectionHelper,
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: new ComponentContextResolver(
            $filamentClassHelper,
            $resourceModelResolver,
            $annotationReader,
            $reflectionProvider,
            $modelReflectionHelper,
            new VirtualAnnotationProvider(
                enabled: false,
                filamentPath: '',
                currentWorkingDirectory: '',
                analysedPaths: [],
                analysedPathsFromConfig: [],
                resourceModelResolver: $resourceModelResolver,
            ),
        ),
        phpDocParser: $phpDocAnnotationParser,
        fieldPathResolver: $fieldPathResolver,
        aggregateFieldValidator: new AggregateFieldValidator($level, $modelReflectionHelper),
    );
}

beforeAll(function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));
});

// --- Level 1: Relations only ---

it('level 1: returns no errors for non-relation dot-notation segments', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));

    // 'writer' unknown, 'getFullTitle' is method but not relation — both return no errors
    // at levels 1-2 (could be cast, accessor, etc.)
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});

it('level 2: returns no errors for non-relation dot-notation segments', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    // Same as level 1 — non-relation segments return no errors at levels 1-2
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});

it('level 1: skips plain field names', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/ValidMakeFields.php'],
        []
    );
});

it('level 1: errors on unknown aggregate relation', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));

    // Aggregate format is specific — relation must exist at any level
    // Column not checked at levels 1-2
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
        ]
    );
});

it('level 2: errors on unknown aggregate relation, column not checked', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    // Same as level 1 — column validation is level 3 only
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
        ]
    );
});

it('level 3: validates aggregate column on related model', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
            ["'rating' does not exist on Fixtures\\App\\Models\\Comment in aggregate field 'comments_avg_rating'.", 20],
        ]
    );
});

// --- Level 0: Off ---

it('level 0: skips all validation', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_0));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});

// --- Level 2: Strict ---

it('level 2: reports plain field not on model', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/StrictValidationResource.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Post.", 18],
        ]
    );
});

// --- Form fields excluded ---

it('does not validate form fields at any level', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/FormFieldsExcluded.php'],
        []
    );
});

// --- Virtual columns ---
// Note: Virtual column skipping is now handled via filament.virtual node attributes
// set by FieldFluentMethodVisitor, not by VirtualFieldRegistry injection.

it('level 2: skips virtual columns with ->state()', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/VirtualColumnResource.php'],
        []
    );
});

// --- ManageRelatedRecords nested model ---

it('level 2: resolves related model for ManageRelatedRecords page', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/ManagePostComments.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Comment.", 26],
        ]
    );
});

// --- Level 3: Full path validation ---

it('level 3: validates full dot path including leaf and typed properties', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/FullPathValidation.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 36],
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'comments.post.author.nonexistent'.", 39],
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostOptions in dot-notation field 'options.nonexistent_field'.", 42],
            ["'fakething' is not a relationship or typed property on Fixtures\\App\\Models\\Post in dot-notation field 'fakething.name'.", 45],
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 48],
        ]
    );
});

// --- Records table skip ---

// Note: Scope skipping is now handled via filament.scopeSkipped node attributes
// set by FieldFluentMethodVisitor, not by VirtualFieldRegistry injection.

it('skips all validation when table uses ->records()', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/RecordsTablePage.php'],
        []
    );
});

// --- Custom component validation ---

it('level 2: validates fields inside custom component using collector-inferred model', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    // EmailDeliveryGroup has TextEntry::make('latestSubmissionEmail.nonexistent_field')
    // 'latestSubmissionEmail' is a valid relation on Post
    // 'nonexistent_field' → not checked at level 2 (leaf not validated)
    // CreatedAtEntry has TextEntry::make('created_at') → valid @property on Post
    $this->analyse(
        [
            __DIR__.'/../../Fixtures/App/CustomComponents/CreatedAtEntry.php',
            __DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php',
        ],
        []
    );
});

it('level 3: validates leaf fields inside custom component', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3));

    // At level 3, leaf columns are validated
    // 'nonexistent_field' does not exist on Email model
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\\App\\Models\\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 28],
        ]
    );
});

it('level 3: @filament-field overrides segment type resolution', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3));

    // AnnotatedHelper has @filament-field Email latestSubmissionEmail
    // So 'latestSubmissionEmail' resolves to Email, then leaf is validated against Email
    // sent_at exists on Email → passes, nonexistent_field doesn't → error
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/AnnotatedHelper.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\App\Models\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 21],
        ]
    );
});

// --- MorphTo relationships ---

it('level 2: does not error on morphTo dot-notation fields', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MorphToFields.php'],
        []
    );
});

// --- @property-read relation fallback ---

it('level 1: detects @property-read Model type as relation', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));

    // 'reviewer' has no method but @property-read Author|null → detected as relation
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/PropertyReadRelation.php'],
        []
    );
});

// --- Nested entries with typed properties ---

it('level 1: returns no errors for nested typed property entries', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_1));

    // options is a typed property, not a relation → return no errors at level 1
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/NestedEntryFields.php'],
        []
    );
});

it('level 3: validates nested typed property entries', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/NestedEntryFields.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 39],
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 46],
        ]
    );
});

it('skips validation for custom component with no model context', function () {
    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php'],
        []
    );
});
