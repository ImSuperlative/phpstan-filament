<?php

namespace ImSuperlative\PhpstanFilament\Tests\Factories;

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionMapFactory;

interface InjectionMapFactoryFactory
{
    /**
     * @param  array<string, list<string>>  $userMethodAdditions
     */
    public function create(array $userMethodAdditions): InjectionMapFactory;
}
