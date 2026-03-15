<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

final class FilamentComponent
{
    // --- Filament Resource Infrastructure ---

    public const string RESOURCE = 'Filament\Resources\Resource';

    public const string EDIT_RECORD = 'Filament\Resources\Pages\EditRecord';

    public const string CREATE_RECORD = 'Filament\Resources\Pages\CreateRecord';

    public const string LIST_RECORDS = 'Filament\Resources\Pages\ListRecords';

    public const string VIEW_RECORD = 'Filament\Resources\Pages\ViewRecord';

    public const string MANAGE_RELATED_RECORDS = 'Filament\Resources\Pages\ManageRelatedRecords';

    public const string RESOURCE_PAGE = 'Filament\Resources\Pages\Page';

    public const string PAGE = 'Filament\Pages\Page';

    public const string RELATION_MANAGER = 'Filament\Resources\RelationManagers\RelationManager';

    // --- Filament Component Bases ---

    public const string FORM_FIELD = 'Filament\Forms\Components\Field';

    public const string TABLE_COLUMN = 'Filament\Tables\Columns\Column';

    public const string SCHEMA_COMPONENT = 'Filament\Schemas\Components\Component';

    public const string INFOLIST_ENTRY = 'Filament\Infolists\Components\Entry';

    public const string ACTION = 'Filament\Actions\Action';

    // --- Filament Traits/Concerns ---

    public const string MACROABLE = 'Filament\Support\Concerns\Macroable';

    public const string HAS_OPTIONS = 'Filament\Forms\Components\Concerns\HasOptions';

    public const string EVALUATES_CLOSURES = 'Filament\Support\Concerns\EvaluatesClosures';

    public const string INTERACTS_WITH_RELATIONSHIP_TABLE = 'Filament\Resources\Concerns\InteractsWithRelationshipTable';

    // --- Filament Component Namespaces ---

    public const string INFOLIST_COMPONENTS_NS = 'Filament\Infolists\Components\\';

    public const string FORM_COMPONENTS_NS = 'Filament\Forms\Components\\';

    public const string TABLE_COLUMNS_NS = 'Filament\Tables\Columns\\';

    public const string FILAMENT_NS = 'Filament\\';

    // --- Laravel / Illuminate ---

    public const string MODEL = 'Illuminate\Database\Eloquent\Model';

    public const string RELATION = 'Illuminate\Database\Eloquent\Relations\Relation';

    public const string ELOQUENT_COLLECTION = 'Illuminate\Database\Eloquent\Collection';

    public const string ILLUMINATE_NS = 'Illuminate\\';

    // --- Form Components ---

    public const string TEXT_INPUT = 'Filament\Forms\Components\TextInput';

    public const string TEXTAREA = 'Filament\Forms\Components\Textarea';

    public const string RICH_EDITOR = 'Filament\Forms\Components\RichEditor';

    public const string MARKDOWN_EDITOR = 'Filament\Forms\Components\MarkdownEditor';

    public const string SELECT = 'Filament\Forms\Components\Select';

    public const string TOGGLE = 'Filament\Forms\Components\Toggle';

    public const string CHECKBOX = 'Filament\Forms\Components\Checkbox';

    public const string CHECKBOX_LIST = 'Filament\Forms\Components\CheckboxList';

    public const string RADIO = 'Filament\Forms\Components\Radio';

    public const string TOGGLE_BUTTONS = 'Filament\Forms\Components\ToggleButtons';

    public const string DATE_PICKER = 'Filament\Forms\Components\DatePicker';

    public const string DATE_TIME_PICKER = 'Filament\Forms\Components\DateTimePicker';

    public const string TIME_PICKER = 'Filament\Forms\Components\TimePicker';

    public const string COLOR_PICKER = 'Filament\Forms\Components\ColorPicker';

    public const string FILE_UPLOAD = 'Filament\Forms\Components\FileUpload';

    public const string KEY_VALUE = 'Filament\Forms\Components\KeyValue';

    public const string REPEATER = 'Filament\Forms\Components\Repeater';

    public const string BUILDER = 'Filament\Forms\Components\Builder';

    public const string TAGS_INPUT = 'Filament\Forms\Components\TagsInput';

    public const string HIDDEN = 'Filament\Forms\Components\Hidden';

    // --- Layout Components ---

    public const string FORM_SECTION = 'Filament\Forms\Components\Section';

    public const string FORM_GROUP = 'Filament\Forms\Components\Group';

    public const string FORM_FIELDSET = 'Filament\Forms\Components\Fieldset';

    public const string FORM_TABS = 'Filament\Forms\Components\Tabs';

    public const string FORM_WIZARD = 'Filament\Forms\Components\Wizard';

    public const string INFOLIST_SECTION = 'Filament\Infolists\Components\Section';

    public const string INFOLIST_GROUP = 'Filament\Infolists\Components\Group';

    public const string INFOLIST_SPLIT = 'Filament\Infolists\Components\Split';

    public const string INFOLIST_TABS = 'Filament\Infolists\Components\Tabs';

    public const string SCHEMA_SECTION = 'Filament\Schemas\Components\Section';

    public const string SCHEMA_GROUP = 'Filament\Schemas\Components\Group';

    // --- Resource Page Groups ---

    public const array RESOURCE_PAGES = [
        self::EDIT_RECORD,
        self::CREATE_RECORD,
        self::LIST_RECORDS,
        self::VIEW_RECORD,
        self::MANAGE_RELATED_RECORDS,
    ];
}
