<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Data;

enum SegmentTag
{
    case Relation;
    case Property;
    case Method;
    case TypedProperty;
    case CollectionItem;
}
