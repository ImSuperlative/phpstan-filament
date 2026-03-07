<?php

use ImSuperlative\FilamentPhpstan\Collectors\AggregateFieldRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\VirtualFieldRegistry;
use ImSuperlative\FilamentPhpstan\FieldValidationLevel;
use ImSuperlative\FilamentPhpstan\Parser\StatePathPrefixVisitor;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FieldPathResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation\AggregateFieldValidator;
use ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\Parser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

// Helper to build rule with given level and optional registry
function buildRule(FieldValidationLevel $level, ?VirtualFieldRegistry $registry = null, ?CustomComponentRegistry $customRegistry = null): MakeFieldValidationRule
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);
    $annotationReader = new AnnotationReader($lexer, $typeParser, $phpDocParser);
    $fieldPathResolver = new FieldPathResolver($modelReflectionHelper, $reflectionProvider);

    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    return new MakeFieldValidationRule(
        level: $level,
        modelReflectionHelper: $modelReflectionHelper,
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: new ComponentContextResolver(
            $filamentClassHelper,
            new ResourceModelResolver($reflectionProvider, $filamentClassHelper),
            $annotationReader,
            new TableQueryRegistry,
            $reflectionProvider,
            $modelReflectionHelper,
            $customRegistry ?? new CustomComponentRegistry,
            new SchemaCallSiteRegistry,
        ),
        virtualFieldRegistry: $registry ?? new VirtualFieldRegistry,
        aggregateFieldRegistry: new AggregateFieldRegistry,
        annotationReader: $annotationReader,
        statePathPrefixVisitor: new StatePathPrefixVisitor($parser),
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

it('level 2: skips virtual columns with ->state()', function () {
    $registry = new VirtualFieldRegistry;
    $registry->registerVirtual(
        'Fixtures\App\MakeFieldTests\VirtualColumnResource::table',
        'custom_display'
    );
    $registry->registerVirtual(
        'Fixtures\App\MakeFieldTests\VirtualColumnResource::table',
        'computed_value'
    );

    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2, $registry));

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

it('skips all validation when table uses ->records()', function () {
    $registry = new VirtualFieldRegistry;
    $registry->registerSkippedScope('Fixtures\App\MakeFieldTests\RecordsTablePage::table');

    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2, $registry));

    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/RecordsTablePage.php'],
        []
    );
});

// --- Custom component validation ---

it('level 2: validates fields inside custom component using collector-inferred model', function () {
    $customRegistry = new CustomComponentRegistry;
    $customRegistry->register('Fixtures\App\CustomComponents\EmailDeliveryGroup', 'Fixtures\App\Models\Post');
    $customRegistry->register('Fixtures\App\CustomComponents\CreatedAtEntry', 'Fixtures\App\Models\Post');

    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_2, customRegistry: $customRegistry));

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
    $customRegistry = new CustomComponentRegistry;
    $customRegistry->register('Fixtures\App\CustomComponents\EmailDeliveryGroup', 'Fixtures\App\Models\Post');

    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3, customRegistry: $customRegistry));

    // At level 3, leaf columns are validated
    // 'nonexistent_field' does not exist on Email model
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\\App\\Models\\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 25],
        ]
    );
});

it('level 3: @filament-field overrides segment type resolution', function () {
    $customRegistry = new CustomComponentRegistry;
    $customRegistry->register('Fixtures\App\CustomComponents\AnnotatedHelper', 'Fixtures\App\Models\Post');

    ConfigurableRuleTestCase::useRule(buildRule(FieldValidationLevel::Level_3, customRegistry: $customRegistry));

    // AnnotatedHelper has @filament-field Email latestSubmissionEmail
    // So 'latestSubmissionEmail' resolves to Email, then leaf is validated against Email
    // sent_at exists on Email → passes, nonexistent_field doesn't → error
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/AnnotatedHelper.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\App\Models\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 19],
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
