<?php

use ImSuperlative\PhpstanFilament\Parser\FieldFluentMethodVisitor;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

beforeEach(function () {
    $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    $this->finder = new NodeFinder;
});

function traverseCode(string $code, object $testContext): array
{
    $stmts = $testContext->parser->parse($code);
    $traverser = new NodeTraverser;
    $traverser->addVisitor(new NameResolver);
    $traverser->addVisitor(new FieldFluentMethodVisitor);
    $traverser->traverse($stmts);

    return $testContext->finder->findInstanceOf($stmts, StaticCall::class);
}

it('tags make() as virtual when chain contains ->state()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('name')->state(fn () => 'computed');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeTrue();
});

it('tags make() as virtual when chain contains ->getStateUsing()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('name')->getStateUsing(fn () => 'x');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeTrue();
});

it('tags make() as virtual when chain contains ->view()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('name')->view('custom-view');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeTrue();
});

it('does not tag make() without override methods', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('name')->label('Name');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeNull();
});

it('tags make() with aggregate for ->counts()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('comments_count')->counts('comments');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.aggregate'))->toBe(['comments', null]);
});

it('tags make() with aggregate for ->avg() with column', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('reviews_avg_rating')->avg('reviews', 'rating');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.aggregate'))->toBe(['reviews', 'rating']);
});

it('tags all make() nodes in method containing ->records()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table($table) {
            return $table
                ->records(collect())
                ->columns([
                    TextColumn::make('name'),
                    TextColumn::make('email'),
                ]);
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCalls = collect($calls)->filter(fn ($c) => $c->name->toString() === 'make');

    expect($makeCalls)->toHaveCount(2);
    $makeCalls->each(fn ($call) => expect($call->getAttribute('filament.scopeSkipped'))->toBeTrue());
});

it('tags placeholder as virtual only for infolist entries', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Infolists\Components\TextEntry;
    class Foo {
        public function infolist() {
            TextEntry::make('name')->placeholder('N/A');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeTrue();
});

it('tags make() with aggregate for ->exists()', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('has_comments')->exists('comments');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.aggregate'))->toBe(['comments', null]);
});

it('tags make() with aggregate for ->sum() with column', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Tables\Columns\TextColumn;
    class Foo {
        public function table() {
            TextColumn::make('orders_sum_total')->sum('orders', 'total');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.aggregate'))->toBe(['orders', 'total']);
});

it('does not tag placeholder as virtual for form fields', function () {
    $code = <<<'PHP'
    <?php
    use Filament\Forms\Components\TextInput;
    class Foo {
        public function form() {
            TextInput::make('name')->placeholder('Enter name');
        }
    }
    PHP;

    $calls = traverseCode($code, $this);
    $makeCall = collect($calls)->first(fn ($c) => $c->name->toString() === 'make');

    expect($makeCall->getAttribute('filament.virtual'))->toBeNull();
});
