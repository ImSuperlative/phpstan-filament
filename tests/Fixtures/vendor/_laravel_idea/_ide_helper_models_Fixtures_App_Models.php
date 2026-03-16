<?php //d1eeb5c1d0bd42f668620d0b03c7f18a93101694a1f66305d6a04585f241a157
/** @noinspection all */

namespace Fixtures\App\Models {

    use Fixtures\App\Enums\PostStatus;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\HasOne;
    use Illuminate\Database\Eloquent\Relations\MorphTo;
    use Illuminate\Support\Carbon;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Activity_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Activity_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Author_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Author_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Category_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Category_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Comment_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Comment_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Email_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Email_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Employee_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Employee_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Post_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Post_QB;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Tag_C;
    use LaravelIdea\Helper\Fixtures\App\Models\_IH_Tag_QB;
    
    /**
     * @property string $id
     * @property string $event_type
     * @property string $description
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @method static _IH_Activity_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Activity_C|Activity[] all($columns = ['*'])
     * @mixin _IH_Activity_QB
     */
    class Activity extends Model {}
    
    /**
     * @property string $id
     * @property string $name
     * @property string $email
     * @property string|null $bio
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property _IH_Post_C|Post[] $posts
     * @property-read int $posts_count
     * @method HasMany|_IH_Post_QB posts()
     * @method static _IH_Author_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Author_C|Author[] all($columns = ['*'])
     * @foreignLinks id,\Fixtures\App\Models\Post,author_id|id,\Fixtures\App\Models\Comment,author_id
     * @mixin _IH_Author_QB
     */
    class Author extends Model {}
    
    /**
     * @property string $id
     * @property string $name
     * @property string|null $description
     * @property _IH_Post_C|Post[] $posts
     * @property-read int $posts_count
     * @method HasMany|_IH_Post_QB posts()
     * @method static _IH_Category_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Category_C|Category[] all($columns = ['*'])
     * @foreignLinks id,\Fixtures\App\Models\Post,category_id
     * @mixin _IH_Category_QB
     */
    class Category extends Model {}
    
    /**
     * @property string $id
     * @property string $post_id
     * @property string $author_id
     * @property int|null $commentable_id
     * @property string|null $commentable_type
     * @property string $body
     * @property bool $is_approved
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property Author $author
     * @method BelongsTo|_IH_Author_QB author()
     * @property Model $commentable
     * @method MorphTo commentable()
     * @property Post $post
     * @method BelongsTo|_IH_Post_QB post()
     * @method static _IH_Comment_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Comment_C|Comment[] all($columns = ['*'])
     * @ownLinks post_id,\Fixtures\App\Models\Post,id|author_id,\Fixtures\App\Models\Author,id
     * @mixin _IH_Comment_QB
     */
    class Comment extends Model {}
    
    /**
     * @property string $id
     * @property string $post_id
     * @property Carbon|null $sent_at
     * @property Carbon|null $delivered_at
     * @property string|null $delivery_status
     * @property string|null $delivery_reason
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @method static _IH_Email_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Email_C|Email[] all($columns = ['*'])
     * @ownLinks post_id,\Fixtures\App\Models\Post,id
     * @mixin _IH_Email_QB
     */
    class Email extends Model {}
    
    /**
     * @property int $id
     * @property int $team_id
     * @property int|null $manager_id
     * @property string $first_name
     * @property string $last_name
     * @property string $email
     * @property string|null $phone
     * @property string|null $job_title
     * @property Carbon|null $hired_at
     * @property string $status
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property _IH_Employee_C|Employee[] $directReports
     * @property-read int $direct_reports_count
     * @method HasMany|_IH_Employee_QB directReports()
     * @property Employee|null $manager
     * @method BelongsTo|_IH_Employee_QB manager()
     * @method static _IH_Employee_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Employee_C|Employee[] all($columns = ['*'])
     * @ownLinks manager_id,\Fixtures\App\Models\Employee,id
     * @foreignLinks id,\Fixtures\App\Models\Employee,manager_id
     * @mixin _IH_Employee_QB
     */
    class Employee extends Model {}
    
    /**
     * @property string $id
     * @property string $title
     * @property string|null $body
     * @property string|null $slug
     * @property PostStatus $status
     * @property bool $is_featured
     * @property int $views_count
     * @property float|null $rating
     * @property array|null $metadata
     * @property array|null $options
     * @property string $author_id
     * @property string|null $category_id
     * @property Carbon|null $published_at
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read string $display_name attribute
     * @property string $summary attribute
     * @property Author $author
     * @method BelongsTo|_IH_Author_QB author()
     * @property Category|null $category
     * @method BelongsTo|_IH_Category_QB category()
     * @property _IH_Comment_C|Comment[] $comments
     * @property-read int $comments_count
     * @method HasMany|_IH_Comment_QB comments()
     * @property Email $latestReminderEmail
     * @method HasOne|_IH_Email_QB latestReminderEmail()
     * @property Email $latestSubmissionEmail
     * @method HasOne|_IH_Email_QB latestSubmissionEmail()
     * @property _IH_Tag_C|Tag[] $tags
     * @property-read int $tags_count
     * @method BelongsToMany|_IH_Tag_QB tags()
     * @method static _IH_Post_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Post_C|Post[] all($columns = ['*'])
     * @ownLinks author_id,\Fixtures\App\Models\Author,id|category_id,\Fixtures\App\Models\Category,id
     * @foreignLinks id,\Fixtures\App\Models\Comment,post_id|id,\Fixtures\App\Models\Email,post_id
     * @mixin _IH_Post_QB
     */
    class Post extends Model {}
    
    /**
     * @property string $id
     * @property string $name
     * @property string $slug
     * @property _IH_Post_C|Post[] $posts
     * @property-read int $posts_count
     * @method BelongsToMany|_IH_Post_QB posts()
     * @method static _IH_Tag_QB with(\Closure[]|string|string[]|\string[][] $relations)
     * @method static _IH_Tag_C|Tag[] all($columns = ['*'])
     * @foreignLinks 
     * @mixin _IH_Tag_QB
     */
    class Tag extends Model {}
}