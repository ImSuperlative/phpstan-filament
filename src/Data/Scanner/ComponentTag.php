<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

enum ComponentTag: string
{
    // Primary types (exactly one per component)
    case Resource = 'resource';
    case Page = 'page';
    case RelationManager = 'relationManager';
    case Component = 'component';

    // Page subtypes (in addition to Page primary tag)
    case EditPage = 'editPage';
    case CreatePage = 'createPage';
    case ListRecords = 'listRecords';
    case ViewRecord = 'viewRecord';
    case ManageRelatedRecords = 'manageRelatedRecords';

    // Modifiers (zero or more per component)
    case Nested = 'nested';
    case Clustered = 'clustered';
    case Shared = 'shared';
}
