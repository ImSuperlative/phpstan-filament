<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

enum SegmentTag
{
    case Relation;
    case Property;
    case Method;
    case TypedProperty;
    case CollectionItem;
}
