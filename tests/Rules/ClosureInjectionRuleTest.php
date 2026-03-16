<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Rules;

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\ClosureInjectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClosureInjectionRule>
 */
class ClosureInjectionRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ClosureInjectionRule::class);
    }

    public function test_valid_closure_injections(): void
    {
        $this->analyse(
            [fixture_path('App/ClosureTests/InjectionValid.php')],
            [],
        );
    }

    public function test_invalid_closure_injections(): void
    {
        $componentParams = '$context, $operation, $get, $livewire, $model, $parentRepeaterItemIndex, $rawState, $record, $set, $state, $component';
        $columnParams = '$livewire, $record, $rowLoop, $state, $table, $column, $value';
        $columnNoValueParams = '$livewire, $record, $rowLoop, $state, $table, $column';
        $actionParams = '$arguments, $data, $livewire, $model, $mountedActions, $record, $selectedRecords, $records, $selectedRecordsQuery, $recordsQuery, $schema, $schemaComponent, $component, $schemaGet, $get, $schemaSet, $set, $schemaComponentState, $state, $schemaState, $table, $action';

        $this->analyse(
            [fixture_path('App/ClosureTests/InjectionInvalid.php')],
            [
                ["Closure parameter '\$old' is not a valid injection for this context. Valid parameters: {$componentParams}.", 21],
                ["Closure parameter '\$rowLoop' is not a valid injection for this context. Valid parameters: {$componentParams}.", 25],
                ["Closure parameter '\$table' is not a valid injection for this context. Valid parameters: {$componentParams}.", 29],
                ["Closure parameter '\$nonexistent' is not a valid injection for this context. Valid parameters: {$componentParams}.", 33],
                ["Closure parameter '\$data' is not a valid injection for this context. Valid parameters: {$componentParams}.", 37],
                ["Closure parameter '\$records' is not a valid injection for this context. Valid parameters: {$componentParams}.", 41],
                ["Closure parameter '\$get' is not a valid injection for this context. Valid parameters: {$columnParams}.", 50],
                ["Closure parameter '\$set' is not a valid injection for this context. Valid parameters: {$columnParams}.", 54],
                ["Closure parameter '\$operation' is not a valid injection for this context. Valid parameters: {$columnParams}.", 58],
                ["Closure parameter '\$doesnotexist' is not a valid injection for this context. Valid parameters: {$columnNoValueParams}.", 62],
                ["Closure parameter '\$err' is not a valid injection for this context. Valid parameters: {$actionParams}.", 71],
            ],
        );
    }

    public function test_valid_typed_closure_injections(): void
    {
        $this->analyse(
            [fixture_path('App/ClosureTests/TypedInjectionValid.php')],
            [],
        );
    }

    public function test_typed_closure_injections_with_wrong_types(): void
    {
        $this->analyse(
            [fixture_path('App/ClosureTests/TypedInjectionInvalid.php')],
            [
                ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 16],
                ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 25],
            ],
        );
    }

    public function test_typed_state_params_with_incompatible_types(): void
    {
        $this->analyse(
            [fixture_path('App/ClosureTests/TypedStateInjection.php')],
            [
                ["Closure parameter '\$state' is typed as 'array', expected 'string|null'.", 19],
                ["Closure parameter '\$state' is typed as 'array', expected 'string'.", 36],
            ],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            project_root('extension.neon'),
            project_root('tests/phpstan-test-services.neon'),
        ];
    }
}
