<?php
/** @noinspection all */

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property string $event_type
 * @property string $description
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 */
	class Activity extends \Eloquent {}
}

namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $bio
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Fixtures\App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Author newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Author newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Author query()
 */
	class Author extends \Eloquent {}
}

namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Fixtures\App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 */
	class Category extends \Eloquent {}
}

namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property string $post_id
 * @property string $author_id
 * @property string|null $commentable_type
 * @property string|null $commentable_id
 * @property string $body
 * @property bool $is_approved
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Fixtures\App\Models\Author|null $author
 * @property-read \Illuminate\Database\Eloquent\Model $commentable
 * @property-read \Fixtures\App\Models\Post|null $post
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment query()
 */
	class Comment extends \Eloquent {}
}

namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property string|null $delivery_status
 * @property string|null $delivery_reason
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email query()
 */
	class Email extends \Eloquent {}
}

namespace Fixtures\App\Models{
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
 * @property-read \Fixtures\App\Models\Author|null $author
 * @property-read \Fixtures\App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Fixtures\App\Models\Comment> $comments
 * @property-read string $display_name
 * @property-read \Fixtures\App\Models\Email|null $latestReminderEmail
 * @property-read \Fixtures\App\Models\Email|null $latestSubmissionEmail
 * @property-read mixed $summary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Fixtures\App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 */
	class Post extends \Eloquent {}
}

namespace Fixtures\App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Fixtures\App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 */
	class Tag extends \Eloquent {}
}

