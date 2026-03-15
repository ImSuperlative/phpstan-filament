<?php

namespace Fixtures\App\Models;

use Fixtures\App\Data\PostOptions;
use Fixtures\App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $title
 * @property string|null $body
 * @property string|null $slug
 * @property PostStatus $status
 * @property bool $is_featured
 * @property int $views_count
 * @property float|null $rating
 * @property array<string, mixed>|null $metadata
 * @property string $author_id
 * @property string|null $category_id
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property PostOptions|null $options
 * @property-read int $comments_count
 * @property-read Author|null $reviewer
 */
class Post extends Model
{
    /** @return BelongsTo<Author, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasOne<Email, $this> */
    public function latestSubmissionEmail(): HasOne
    {
        return $this->hasOne(Email::class);
    }

    /** @return HasOne<Email, $this> */
    public function latestReminderEmail(): HasOne
    {
        return $this->hasOne(Email::class);
    }

    /** Old-style accessor: accessed as 'display_name' */
    public function getDisplayNameAttribute(): string
    {
        return $this->title;
    }

    /** New-style accessor: accessed as 'summary' */
    public function summary(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->title,
        );
    }

    public function getFullTitle(): string
    {
        return $this->title;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'rating' => 'float',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
